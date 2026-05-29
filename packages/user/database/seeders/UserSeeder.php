<?php

declare(strict_types=1);

namespace Moox\User\Database\Seeders;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Moox\Demo\Seeding\ImportDemoMediaToMediathek;
use Moox\Demo\Seeding\ReportsMooxSeederProgress;
use Moox\Demo\Seeding\RunsMooxDemoAssets;
use Moox\Demo\Seeding\SeedingConfig;
use Moox\Demo\Seeding\SeedOutput;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;
use Moox\User\Models\User;

class UserSeeder extends Seeder
{
    use ReportsMooxSeederProgress;

    public const DEMO_EMAIL_DOMAIN = 'moox.org';

    public const DEMO_EXTRA_PASSWORD = 'password';

    /**
     * Fixed demo login accounts (name, email, password per entry).
     *
     * @var list<array{name: string, email: string, password: string}>
     */
    public const DEFAULT_USERS = [
        [
            'name' => 'Reinhold Jesse',
            'email' => 'reinhold.jesse@heco.de',
            'password' => '123456789',
        ],
        [
            'name' => 'Moox Admin',
            'email' => 'admin@moox.org',
            'password' => 'password',
        ],
        [
            'name' => 'Moox Editor',
            'email' => 'editor@moox.org',
            'password' => 'password',
        ],
    ];

    /** @var list<string> */
    public const LOCALES = ['cs_CZ', 'en_US', 'de_DE', 'pl_PL'];

    /** @var array<string, string> */
    private const FAKER_LOCALE_MAP = [
        'cs_CZ' => 'cs_CZ',
        'en_US' => 'en_US',
        'de_DE' => 'de_DE',
        'pl_PL' => 'pl_PL',
    ];

    public function run(): void
    {
        $this->seed();

        if (class_exists(RunsMooxDemoAssets::class)) {
            RunsMooxDemoAssets::invoke($this);
        }
    }

    protected function seed(): void
    {
        $extraCount = $this->resolveExtraUserCount();

        $this->purgeDemoUsers();

        $seededFixed = 0;

        foreach (self::DEFAULT_USERS as $user) {
            User::query()->create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make($user['password']),
                'email_verified_at' => now(),
            ]);

            $seededFixed++;
            $this->reportCreated("User {$user['email']}");
        }

        if ($extraCount > 0) {
            $this->seedExtraUsers($extraCount);
        }

        $total = $seededFixed + $extraCount;

        $this->reportDetail(sprintf(
            '%d user(s) total (%d default account(s) + %d from dataset)',
            $total,
            $seededFixed,
            $extraCount
        ));
    }

    private function seedExtraUsers(int $extraCount): void
    {
        if ($this->hasSeedOutput()) {
            $progress = SeedOutput::progressBar($extraCount, 'Demo users');

            for ($i = 1; $i <= $extraCount; $i++) {
                $this->createExtraUser($i);
                $progress->advance();
            }

            $progress->finish("{$extraCount} demo user(s)");

            return;
        }

        for ($i = 1; $i <= $extraCount; $i++) {
            $this->createExtraUser($i);
        }

        $this->command?->info(sprintf('Seeded %d extra demo user(s).', $extraCount));
    }

    private function createExtraUser(int $index): void
    {
        User::query()->create([
            'name' => $this->displayNameForDemoAuthor(),
            'email' => sprintf('demo-user-%03d@%s', $index, self::DEMO_EMAIL_DOMAIN),
            'password' => Hash::make(self::DEMO_EXTRA_PASSWORD),
            'email_verified_at' => now(),
        ]);
    }

    /**
     * User has no translations — names use de_DE Faker so author labels stay German in ?lang=de_DE.
     */
    private function displayNameForDemoAuthor(): string
    {
        $faker = $this->fakerForLocale('de_DE');

        return trim($faker->firstName().' '.$faker->lastName());
    }

    /**
     * @return list<string>
     */
    private function defaultUserEmails(): array
    {
        return array_column(self::DEFAULT_USERS, 'email');
    }

    protected function seedDemoAssets(): void
    {
        if (! class_exists(ImportDemoMediaToMediathek::class)) {
            return;
        }

        if (! class_exists(Media::class)) {
            return;
        }

        $users = $this->seededDemoUsers();

        $sourceDir = config('demo.media.users_path');

        if (! is_string($sourceDir) || $sourceDir === '') {
            return;
        }

        $imagePaths = ImportDemoMediaToMediathek::listImagePaths($sourceDir, count($users));

        if ($imagePaths === []) {
            if ($this->command !== null) {
                $this->command->warn('  No demo user images found in '.$sourceDir);
            }

            return;
        }

        $withAvatar = 0;

        foreach ($users as $index => $user) {
            $imagePath = $imagePaths[$index] ?? null;

            if ($imagePath === null) {
                continue;
            }

            $media = ImportDemoMediaToMediathek::importFromPath($imagePath, null);

            if (! $media instanceof Media) {
                continue;
            }

            MediaUsable::query()->firstOrCreate([
                'media_id' => $media->getKey(),
                'media_usable_id' => $user->getKey(),
                'media_usable_type' => User::class,
            ]);

            $user->forceFill([
                'avatar_url' => ImportDemoMediaToMediathek::avatarUrlFromMedia($media),
            ])->saveQuietly();

            $withAvatar++;

            if ($this->hasSeedOutput()) {
                SeedOutput::created("Avatar for {$user->email}");
            } elseif ($this->command?->getOutput()->isVerbose()) {
                $this->command->line("  User {$user->email}: mediathek media #{$media->getKey()} ({$media->file_name})");
            }
        }

        $this->reportDetail(sprintf('Attached avatars for %d user(s).', $withAvatar));
    }

    private function resolveExtraUserCount(): int
    {
        $smallDefault = (int) (config('demo.dataset_sizes.small') ?? 100);

        if (class_exists(SeedingConfig::class)) {
            return SeedingConfig::resolveCount('user', $smallDefault);
        }

        return $smallDefault;
    }

    /**
     * @return Collection<int, User>
     */
    private function seededDemoUsers(): Collection
    {
        return User::query()
            ->whereIn('email', $this->defaultUserEmails())
            ->orWhere('email', 'like', 'demo-user-%@'.self::DEMO_EMAIL_DOMAIN)
            ->orderBy('id')
            ->get()
            ->values();
    }

    private function purgeDemoUsers(): void
    {
        User::query()
            ->whereIn('email', $this->defaultUserEmails())
            ->orWhere('email', 'like', 'demo-user-%@'.self::DEMO_EMAIL_DOMAIN)
            ->forceDelete();
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
}
