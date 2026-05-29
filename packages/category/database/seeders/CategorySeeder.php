<?php

declare(strict_types=1);

namespace Moox\Category\Database\Seeders;

use DateTimeImmutable;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Moox\Category\Database\Seeders\Support\AttachExistingMedia;
use Moox\Category\Models\Category;
use Moox\Category\Models\CategoryTranslation;
use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;
use Moox\Demo\Seeding\FormatsFakerLocaleText;
use Moox\Demo\Seeding\LoadsImageMediaPool;
use Moox\Demo\Seeding\ReportsMooxSeederProgress;
use Moox\Demo\Seeding\RunsMooxDemoAssets;
use Moox\Demo\Seeding\SeedingConfig;
use Moox\Demo\Seeding\SeedOutput;
use Moox\Localization\Models\Localization;
use Moox\Media\Models\Media;

/**
 * Seeds categories with nested tree, four locales, and existing mediathek via media_usables.
 *
 * Run once after users, localizations, and media library exist:
 *
 *     php artisan db:seed --class=CategorySeeder --force
 */
class CategorySeeder extends Seeder
{
    use FormatsFakerLocaleText;
    use LoadsImageMediaPool;
    use ReportsMooxSeederProgress;

    public const SEED_BATCH = 'category_seeder_v1';

    /** @var list<string> */
    public const LOCALES = ['cs_CZ', 'en_US', 'de_DE', 'pl_PL'];

    /** @var array<string, string> */
    private const FAKER_LOCALE_MAP = [
        'cs_CZ' => 'cs_CZ',
        'en_US' => 'en_US',
        'de_DE' => 'de_DE',
        'pl_PL' => 'pl_PL',
    ];

    private const MAX_TREE_DEPTH = 4;

    private const MEDIA_ATTACH_PROBABILITY = 0.85;

    private const PROGRESS_LOG_EVERY = 100;

    public function __construct(
        private readonly ?int $count = null,
    ) {}

    public function run(): void
    {
        $this->seed();

        if (class_exists(RunsMooxDemoAssets::class)) {
            RunsMooxDemoAssets::invoke($this);
        }
    }

    protected function seed(): void
    {
        $total = $this->resolvedCount();

        $this->purgeSeededCategories();

        $user = $this->requireDemoAuthor();
        if ($user === null) {
            return;
        }

        $missingLocales = collect(self::LOCALES)
            ->filter(fn (string $locale): bool => ! Localization::query()->where('locale_variant', $locale)->exists());

        if ($missingLocales->isNotEmpty()) {
            $this->command?->error(
                'Missing `localizations` rows for: '.$missingLocales->implode(', ').
                '. Add those locale_variant values before running this seeder.'
            );

            return;
        }

        $mediaPool = $this->loadImageMediaPool();
        if ($mediaPool->isEmpty()) {
            $this->command?->warn('No images in `media` table — categories will be seeded without images / media_usables.');
        }

        Auth::login($user);

        $baseUrl = rtrim((string) config('app.url'), '/');
        $parentMap = self::buildParentIndexMap($total);
        /** @var array<int, int> $idByIndex */
        $idByIndex = [];

        $progress = $this->hasSeedOutput()
            ? SeedOutput::progressBar($total, 'Demo categories')
            : null;

        DB::transaction(function () use ($baseUrl, $total, $parentMap, $mediaPool, $user, $progress, &$idByIndex): void {
            for ($i = 1; $i <= $total; $i++) {
                $parentIndex = $parentMap[$i] ?? null;
                $parentId = $parentIndex !== null ? ($idByIndex[$parentIndex] ?? null) : null;

                $translationStatuses = $this->translationStatusesForCategory();

                $category = new Category;
                $category->is_active = $this->randomChance(92);
                $category->status = self::resolveCategoryStatusFromTranslationStatuses($translationStatuses);
                $category->weight = $i;
                $category->color = $this->randomHexColor();
                $category->due_at = $this->randomChance(35)
                    ? $this->randomDateTimeBetween('-3 months', '+6 months')
                    : null;
                $category->custom_properties = [
                    'seed_batch' => self::SEED_BATCH,
                    'featured' => $this->randomChance(18),
                    'sort_hint' => random_int(1, 100),
                ];
                $category->basedata = [
                    'seed_batch' => self::SEED_BATCH,
                    'seed_index' => $i,
                ];

                if ($parentId !== null) {
                    $category->parent_id = $parentId;
                }

                $attachMedia = $mediaPool->isNotEmpty()
                    && $this->randomChance((int) (self::MEDIA_ATTACH_PROBABILITY * 100));

                foreach (self::LOCALES as $locale) {
                    $localeFaker = $this->fakerForLocale($locale);
                    $title = $this->fakerLocaleTitle($locale, $localeFaker, 'title');
                    $slug = $this->slugForTitle($title, $i, $locale);

                    $translation = $category->translateOrNew($locale);
                    $translation->title = $title;
                    $translation->slug = $slug;
                    $translation->permalink = $baseUrl.'/'.Str::lower(str_replace('_', '-', $locale)).'/categories/'.$slug;
                    $translation->description = $this->fakerLocaleText($locale, $localeFaker, preset: 'description');
                    $translation->content = $this->markdownContentFromLocale($locale, $localeFaker);
                    $this->applyTranslationStatus($translation, $translationStatuses[$locale]);
                    $this->assignTranslationAuthor($translation, $user);

                    if ($attachMedia && $locale === $this->primaryMediaLocale()) {
                        AttachExistingMedia::attach($category, $mediaPool->random(), 'image', $locale);
                        $attachMedia = false;
                    }
                }

                $category->save();

                $idByIndex[$i] = (int) $category->getKey();

                if ($progress !== null) {
                    $progress->advance();
                } elseif ($total > 50 && ($i % self::PROGRESS_LOG_EVERY === 0 || $i === $total)) {
                    $this->reportCreated("Category {$category->getKey()}");
                }
            }

            Category::fixTree();

            $seededIds = Category::query()
                ->where('basedata->seed_batch', self::SEED_BATCH)
                ->pluck('id');

            if ($seededIds->isNotEmpty()) {
                $childrenCountByParent = Category::query()
                    ->whereIn('parent_id', $seededIds)
                    ->selectRaw('parent_id, COUNT(*) as aggregate')
                    ->groupBy('parent_id')
                    ->pluck('aggregate', 'parent_id');

                foreach ($seededIds as $seededId) {
                    Category::query()
                        ->whereKey((int) $seededId)
                        ->update([
                            'count' => (int) ($childrenCountByParent[$seededId] ?? 0),
                        ]);
                }
            }
        });

        Auth::logout();

        $progress?->finish("{$total} demo categories");

        $withMedia = DB::table('media_usables')
            ->where('media_usable_type', Category::class)
            ->whereIn('media_usable_id', Category::query()
                ->where('basedata->seed_batch', self::SEED_BATCH)
                ->pluck('id'))
            ->count();

        $this->reportDetail(sprintf(
            'Seeded %d categories (%d locales each), %d media_usables links, tree depth up to %d.',
            $total,
            count(self::LOCALES),
            $withMedia,
            self::MAX_TREE_DEPTH
        ));
    }

    /**
     * @return array<int, int|null> 1-based child index => 1-based parent index or null for root
     */
    public static function buildParentIndexMap(int $total): array
    {
        if ($total < 1) {
            return [];
        }

        $rootCount = max(5, (int) round(sqrt($total) * 1.2));
        $rootCount = min($rootCount, $total);

        $map = [];
        for ($i = 1; $i <= $rootCount; $i++) {
            $map[$i] = null;
        }

        for ($i = $rootCount + 1; $i <= $total; $i++) {
            $candidates = self::parentCandidatesForChild($map, $i);
            $targetRoot = (($i - 1) % $rootCount) + 1;

            $sameBranch = array_values(array_filter(
                $candidates,
                fn (int $candidate): bool => self::rootAncestorIndex($map, $candidate) === $targetRoot
            ));

            if ($sameBranch !== []) {
                $candidates = $sameBranch;
            }

            $map[$i] = $candidates[array_rand($candidates)];
        }

        return $map;
    }

    /**
     * @param  array<int, int|null>  $map
     * @return list<int>
     */
    private static function parentCandidatesForChild(array $map, int $childIndex): array
    {
        $depthByIndex = self::depthsFromParentMap($map, $childIndex - 1);
        $candidates = [];

        for ($candidate = 1; $candidate < $childIndex; $candidate++) {
            $depth = $depthByIndex[$candidate] ?? 1;
            if ($depth < self::MAX_TREE_DEPTH) {
                $candidates[] = $candidate;
            }
        }

        if ($candidates === []) {
            $candidates[] = max(1, $childIndex - 1);
        }

        return $candidates;
    }

    /**
     * @param  array<int, int|null>  $map
     * @return array<int, int>
     */
    private static function depthsFromParentMap(array $map, int $maxIndex): array
    {
        $depths = [];

        for ($i = 1; $i <= $maxIndex; $i++) {
            $parent = $map[$i] ?? null;
            $depths[$i] = $parent === null ? 1 : (($depths[$parent] ?? 1) + 1);
        }

        return $depths;
    }

    private static function rootAncestorIndex(array $map, int $index): int
    {
        $current = $index;

        while (($map[$current] ?? null) !== null) {
            $current = $map[$current];
        }

        return $current;
    }

    private function slugForTitle(string $title, int $index, string $locale): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'item-'.sprintf('%03d', $index);
        }

        return Str::limit($base, 72, '').'-'.sprintf('%03d', $index);
    }

    /**
     * Remove prior demo categories so re-runs replace Latin/legacy rows (repeatable moox:demo).
     */
    private function purgeSeededCategories(): void
    {
        $ids = Category::query()
            ->where('basedata->seed_batch', self::SEED_BATCH)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        CategoryTranslation::query()
            ->whereIn('category_id', $ids)
            ->forceDelete();

        Category::query()
            ->whereIn('id', $ids)
            ->orderByDesc('_lft')
            ->each(static fn (Category $category): bool => (bool) $category->forceDelete());

        $this->reportDetail(sprintf('Purged %d prior demo categor(ies) (seed_batch %s).', $ids->count(), self::SEED_BATCH));
    }

    private function fakerForLocale(string $locale): Generator
    {
        static $cache = [];
        $resolvedLocale = self::FAKER_LOCALE_MAP[$locale] ?? 'en_US';

        if (! isset($cache[$resolvedLocale])) {
            $cache[$resolvedLocale] = FakerFactory::create($resolvedLocale);
        }

        return $cache[$resolvedLocale];
    }

    /**
     * Mirrors {@see BaseDraftTranslationModel::checkAndUpdateMainEntryStatus()}
     * for multi-locale categories after translations are saved.
     *
     * @param  list<string>  $translationStatuses
     */
    public static function resolveCategoryStatusFromTranslationStatuses(array $translationStatuses): string
    {
        $translationStatuses = array_values(array_filter(
            $translationStatuses,
            static fn (mixed $status): bool => is_string($status) && $status !== ''
        ));

        $count = count($translationStatuses);

        if ($count === 0) {
            return 'draft';
        }

        if ($count === 1) {
            return $translationStatuses[0];
        }

        $publishedCount = count(array_filter(
            $translationStatuses,
            static fn (string $status): bool => $status === 'published'
        ));

        if ($publishedCount === $count) {
            return 'published';
        }

        if ($publishedCount === 0) {
            return self::mostCommonStatus($translationStatuses) ?? 'draft';
        }

        $unpublished = array_values(array_filter(
            $translationStatuses,
            static fn (string $status): bool => $status !== 'published'
        ));

        return self::mostCommonStatus($unpublished) ?? 'draft';
    }

    /**
     * @return array<string, string> locale_variant => translation_status
     */
    private function translationStatusesForCategory(): array
    {
        $roll = random_int(1, 100);

        if ($roll <= 28) {
            return array_fill_keys(self::LOCALES, 'published');
        }

        if ($roll <= 48) {
            return array_fill_keys(self::LOCALES, 'draft');
        }

        if ($roll <= 58) {
            return array_fill_keys(self::LOCALES, 'waiting');
        }

        if ($roll <= 78) {
            return $this->mixedTranslationStatuses();
        }

        if ($roll <= 92) {
            return $this->mostlyPublishedTranslationStatuses();
        }

        return $this->oneScheduledTranslationStatuses();
    }

    /**
     * @return array<string, string>
     */
    private function mixedTranslationStatuses(): array
    {
        $statuses = [];

        foreach (self::LOCALES as $locale) {
            $statuses[$locale] = $this->weightedTranslationStatus();
        }

        if (count(array_unique($statuses)) < 2) {
            $statuses[self::LOCALES[1]] = $statuses[self::LOCALES[0]] === 'published' ? 'draft' : 'published';
        }

        return $statuses;
    }

    /**
     * @return array<string, string>
     */
    private function mostlyPublishedTranslationStatuses(): array
    {
        $statuses = array_fill_keys(self::LOCALES, 'published');
        $outlierLocale = self::LOCALES[array_rand(self::LOCALES)];
        $statuses[$outlierLocale] = $this->randomElement(['draft', 'waiting', 'scheduled', 'privat']);

        return $statuses;
    }

    /**
     * @return array<string, string>
     */
    private function oneScheduledTranslationStatuses(): array
    {
        $statuses = array_fill_keys(self::LOCALES, 'published');
        $statuses[self::LOCALES[array_rand(self::LOCALES)]] = 'scheduled';

        if (count(array_filter($statuses, static fn (string $s): bool => $s === 'published')) === count(self::LOCALES)) {
            $statuses[self::LOCALES[0]] = 'draft';
        }

        return $statuses;
    }

    private function weightedTranslationStatus(): string
    {
        $roll = random_int(1, 100);

        if ($roll <= 38) {
            return 'published';
        }

        if ($roll <= 73) {
            return 'draft';
        }

        if ($roll <= 88) {
            return 'waiting';
        }

        if ($roll <= 96) {
            return 'scheduled';
        }

        return 'privat';
    }

    private function applyTranslationStatus(
        CategoryTranslation $translation,
        string $status,
    ): void {
        $translation->translation_status = $status;

        if ($status === 'scheduled') {
            $translation->to_publish_at = $this->randomDateTimeBetween('+2 days', '+60 days');
        }
    }

    /**
     * @param  list<string>  $statuses
     */
    private static function mostCommonStatus(array $statuses): ?string
    {
        if ($statuses === []) {
            return null;
        }

        $counts = array_count_values($statuses);
        arsort($counts);

        return array_key_first($counts);
    }

    private function resolvedCount(): int
    {
        if ($this->count !== null) {
            return max(1, min(5000, $this->count));
        }

        if (class_exists(SeedingConfig::class)) {
            return SeedingConfig::resolveCount('category', 100);
        }

        $fromEnv = env('CATEGORY_MOCK_COUNT');
        if ($fromEnv !== null && $fromEnv !== '') {
            return max(1, min(5000, (int) $fromEnv));
        }

        return 100;
    }

    private function randomChance(int $percent): bool
    {
        return random_int(1, 100) <= $percent;
    }

    /**
     * @template T
     *
     * @param  list<T>  $items
     * @return T
     */
    private function randomElement(array $items): mixed
    {
        return $items[array_rand($items)];
    }

    private function randomHexColor(): string
    {
        return sprintf('#%06x', random_int(0, 0xFFFFFF));
    }

    private function randomDateTimeBetween(string $from, string $to): DateTimeImmutable
    {
        $min = strtotime($from);
        $max = strtotime($to);

        return (new DateTimeImmutable)->setTimestamp(random_int($min, $max));
    }
}
