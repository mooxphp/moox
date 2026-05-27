<?php

namespace Moox\Category\Database\Seeders;

use Moox\User\Models\User;
use Moox\Category\Database\Seeders\Support\AttachExistingMedia;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Moox\Category\Models\Category;
use Moox\Category\Models\CategoryTranslation;
use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;
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
    public const SEED_BATCH = 'category_seeder_v1';

    /** @var list<string> */
    public const LOCALES = ['cs_CZ', 'en_US', 'de_DE', 'pl_PL'];

    private const MAX_TREE_DEPTH = 4;

    private const MEDIA_ATTACH_PROBABILITY = 0.85;

    /** @var list<string> */
    private const ROOT_LABELS_EN = [
        'Pumps & Pumping Systems',
        'Building Services',
        'Water Treatment',
        'HVAC & Climate',
        'Industrial Valves',
        'Measurement & Control',
        'Drainage & Wastewater',
        'Fire Protection',
        'Solar & Renewables',
        'Installation Technology',
        'Spare Parts',
        'Service & Maintenance',
    ];

    /**
     * Localized subcategory titles per root (index 0 = first sub, etc.).
     *
     * @return array<string, array<string, list<string>>>
     */
    private static function subcategoryCatalog(): array
    {
        return [
            'Pumps & Pumping Systems' => [
                'en_US' => ['Circulator Pumps', 'Submersible Pumps', 'Booster Sets', 'Drainage Pumps', 'Well Pumps'],
                'de_DE' => ['Heizungsumwälzpumpen', 'Tauchmotorpumpen', 'Druckerhöhungsanlagen', 'Entwässerungspumpen', 'Brunnenpumpen'],
                'cs_CZ' => ['Oběhová čerpadla', 'Ponorná čerpadla', 'Posilovací systémy', 'Drenážní čerpadla', 'Studniční čerpadla'],
                'pl_PL' => ['Pompy obiegowe', 'Pompy zanurzeniowe', 'Zestawy podciśnieniowe', 'Pompy drenażowe', 'Pompy studzienne'],
            ],
            'Building Services' => [
                'en_US' => ['Heating Technology', 'Sanitary Systems', 'Building Automation', 'Smart Home', 'Pipe Systems'],
                'de_DE' => ['Heiztechnik', 'Sanitärtechnik', 'Gebäudeautomation', 'Smart Home', 'Rohrsysteme'],
                'cs_CZ' => ['Tepelná technika', 'Sanitární technika', 'Automatizace budov', 'Chytrá domácnost', 'Potrubní systémy'],
                'pl_PL' => ['Technika grzewcza', 'Instalacje sanitarne', 'Automatyka budynkowa', 'Smart home', 'Systemy rurowe'],
            ],
            'Water Treatment' => [
                'en_US' => ['Filtration', 'Softening Systems', 'UV Disinfection', 'Reverse Osmosis', 'Dosing Technology'],
                'de_DE' => ['Filtration', 'Enthärtungsanlagen', 'UV-Desinfektion', 'Umkehrosmose', 'Dosiertechnik'],
                'cs_CZ' => ['Filtrace', 'Změkčovací systémy', 'UV dezinfekce', 'Reverzní osmóza', 'Dávkovací technika'],
                'pl_PL' => ['Filtracja', 'Systemy zmiękczania', 'Dezynfekcja UV', 'Osmoza odwrotna', 'Technika dozowania'],
            ],
            'HVAC & Climate' => [
                'en_US' => ['Ventilation', 'Air Conditioning', 'Heat Recovery', 'Duct Systems', 'Climate Controls'],
                'de_DE' => ['Lüftung', 'Klimatisierung', 'Wärmerückgewinnung', 'Kanalsysteme', 'Klimaregelung'],
                'cs_CZ' => ['Větrání', 'Klimatizace', 'Rekuperace', 'Potrubní systémy VZT', 'Regulace klimatu'],
                'pl_PL' => ['Wentylacja', 'Klimatyzacja', 'Rekuperacja', 'Systemy kanałowe', 'Sterowanie klimatem'],
            ],
            'Industrial Valves' => [
                'en_US' => ['Gate Valves', 'Ball Valves', 'Butterfly Valves', 'Check Valves', 'Control Valves'],
                'de_DE' => ['Schieber', 'Kugelhähne', 'Klappen', 'Rückflussverhinderer', 'Regelventile'],
                'cs_CZ' => ['Šoupátka', 'Kulové kohouty', 'Klapky', 'Zpětné klapky', 'Regulační ventily'],
                'pl_PL' => ['Zasuwy', 'Zawory kulowe', 'Zawory zwrotne', 'Zawory zwrotne odcinające', 'Zawory regulacyjne'],
            ],
            'Measurement & Control' => [
                'en_US' => ['Pressure Sensors', 'Flow Meters', 'Temperature Sensors', 'Controllers', 'Data Loggers'],
                'de_DE' => ['Drucksensoren', 'Durchflussmessgeräte', 'Temperatursensoren', 'Regler', 'Datenlogger'],
                'cs_CZ' => ['Senzory tlaku', 'Průtokoměry', 'Teplotní senzory', 'Regulátory', 'Záznamníky dat'],
                'pl_PL' => ['Czujniki ciśnienia', 'Przepływomierze', 'Czujniki temperatury', 'Regulatory', 'Rejestratory danych'],
            ],
            'Drainage & Wastewater' => [
                'en_US' => ['House Drainage', 'Lifting Stations', 'Wastewater Pumps', 'Grease Separators', 'Rainwater Systems'],
                'de_DE' => ['Gebäudeentwässerung', 'Hebeanlagen', 'Abwasserpumpen', 'Fettabscheider', 'Regenwassersysteme'],
                'cs_CZ' => ['Domovní odvodnění', 'Přečerpávací stanice', 'Čerpadla odpadních vod', 'Lapáky tuku', 'Systémy dešťové vody'],
                'pl_PL' => ['Odprowadzenie budynkowe', 'Przepompownie', 'Pompy ściekowe', 'Osadniki tłuszczu', 'Systemy wód deszczowych'],
            ],
            'Fire Protection' => [
                'en_US' => ['Sprinkler Systems', 'Fire Pumps', 'Hydrants', 'Alarm Technology', 'Smoke Extraction'],
                'de_DE' => ['Sprinkleranlagen', 'Löschwasserpumpen', 'Hydranten', 'Alarmtechnik', 'Rauchabzug'],
                'cs_CZ' => ['Sprinklerové systémy', 'Požární čerpadla', 'Hydranty', 'Požární signalizace', 'Odtah kouře'],
                'pl_PL' => ['Systemy tryskaczowe', 'Pompo pożarowe', 'Hydranty', 'Technika alarmowa', 'Oddymianie'],
            ],
            'Solar & Renewables' => [
                'en_US' => ['Photovoltaic', 'Solar Thermal', 'Heat Pumps', 'Storage Systems', 'Inverters'],
                'de_DE' => ['Photovoltaik', 'Solarthermie', 'Wärmepumpen', 'Speichersysteme', 'Wechselrichter'],
                'cs_CZ' => ['Fotovoltaika', 'Solární termika', 'Tepelná čerpadla', 'Akumulační systémy', 'Střídače'],
                'pl_PL' => ['Fotowoltaika', 'Kolektory słoneczne', 'Pompy ciepła', 'Systemy magazynowania', 'Falowniki'],
            ],
            'Installation Technology' => [
                'en_US' => ['Mounting Systems', 'Tools & Consumables', 'Sealing Technology', 'Fasteners', 'Chemical Anchors'],
                'de_DE' => ['Befestigungssysteme', 'Werkzeuge & Verbrauchsmaterial', 'Dichttechnik', 'Befestigungselemente', 'Chemische Dübel'],
                'cs_CZ' => ['Montážní systémy', 'Nářadí a spotřební materiál', 'Těsnicí technika', 'Spojovací prvky', 'Chemické kotvy'],
                'pl_PL' => ['Systemy mocowań', 'Narzędzia i materiały eksploatacyjne', 'Technika uszczelniania', 'Elementy złączne', 'Kotwy chemiczne'],
            ],
            'Spare Parts' => [
                'en_US' => ['Pump Spare Parts', 'Seal Kits', 'Impellers', 'Motors', 'Wear Parts'],
                'de_DE' => ['Pumpenersatzteile', 'Dichtungssätze', 'Laufräder', 'Motoren', 'Verschleißteile'],
                'cs_CZ' => ['Náhradní díly čerpadel', 'Sady těsnění', 'Oběžná kola', 'Motory', 'Opotřebitelné díly'],
                'pl_PL' => ['Części zamienne pomp', 'Zestawy uszczelek', 'Łopatki wirnika', 'Silniki', 'Części eksploatacyjne'],
            ],
            'Service & Maintenance' => [
                'en_US' => ['Commissioning', 'Maintenance Contracts', 'Repair Service', 'Training', 'Technical Support'],
                'de_DE' => ['Inbetriebnahme', 'Wartungsverträge', 'Reparaturservice', 'Schulungen', 'Technischer Support'],
                'cs_CZ' => ['Uvedení do provozu', 'Servisní smlouvy', 'Opravárenský servis', 'Školení', 'Technická podpora'],
                'pl_PL' => ['Uruchomienie', 'Umowy serwisowe', 'Serwis naprawczy', 'Szkolenia', 'Wsparcie techniczne'],
            ],
        ];
    }

    public function __construct(
        private readonly ?int $count = null,
    ) {}

    public function run(): void
    {
        $this->seed();

        if (class_exists(\Moox\Demo\Seeding\RunsMooxDemoAssets::class)) {
            \Moox\Demo\Seeding\RunsMooxDemoAssets::invoke($this);
        }
    }

    protected function seed(): void
    {
        $total = $this->resolvedCount();

        $user = User::query()->first();
        if ($user === null) {
            $this->command?->error('No user found. Create at least one user (e.g. UserSeeder) before seeding mock categories.');

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

        DB::transaction(function () use ($baseUrl, $total, $parentMap, $mediaPool, $user, &$idByIndex): void {
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
                    'seed_label_en' => self::labelForIndex($i),
                ];

                if ($parentId !== null) {
                    $category->parent_id = $parentId;
                }

                foreach (self::LOCALES as $locale) {
                    $title = $this->titleForLocale($locale, $i);
                    $slug = $this->slugForTitle($title, $i, $locale);

                    $translation = $category->translateOrNew($locale);
                    $translation->title = $title;
                    $translation->slug = $slug;
                    $translation->permalink = $baseUrl.'/'.Str::lower(str_replace('_', '-', $locale)).'/categories/'.$slug;
                    $translation->description = $this->descriptionForLocale($title, $locale);
                    $translation->content = $this->markdownContentForLocale($title, $locale);
                    $this->applyTranslationStatus($translation, $translationStatuses[$locale]);
                    $translation->author_id = $user->getKey();
                    $translation->author_type = $user->getMorphClass();
                }

                $category->save();
                $category->refresh();
                $category->load('translations');

                $resolvedStatus = self::resolveCategoryStatusFromTranslationStatuses(
                    $category->translations->pluck('translation_status')->all()
                );

                if ($category->status !== $resolvedStatus) {
                    $category->status = $resolvedStatus;
                    $category->saveQuietly();
                }

                $idByIndex[$i] = (int) $category->getKey();

                if ($mediaPool->isNotEmpty() && $this->randomChance((int) (self::MEDIA_ATTACH_PROBABILITY * 100))) {
                    $media = $mediaPool->random();
                    AttachExistingMedia::attach($category, $media, 'image', 'en_US');
                }
            }

            Category::fixTree();

            Category::query()
                ->whereIn('basedata->seed_batch', [self::SEED_BATCH])
                ->each(function (Category $category): void {
                    $category->count = $category->children()->count();
                    $category->saveQuietly();
                });
        });

        Auth::logout();

        $withMedia = DB::table('media_usables')
            ->where('media_usable_type', Category::class)
            ->whereIn('media_usable_id', Category::query()
                ->where('basedata->seed_batch', self::SEED_BATCH)
                ->pluck('id'))
            ->count();

        $this->command?->info(sprintf(
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

        $rootCount = min(count(self::ROOT_LABELS_EN), max(5, (int) round(sqrt($total) * 1.2)));
        $rootCount = min($rootCount, $total);

        $map = [];
        for ($i = 1; $i <= $rootCount; $i++) {
            $map[$i] = null;
        }

        for ($i = $rootCount + 1; $i <= $total; $i++) {
            $candidates = self::parentCandidatesForChild($map, $i);
            $targetRoot = (($i - 1) % count(self::ROOT_LABELS_EN)) + 1;
            if ($targetRoot > $rootCount) {
                $targetRoot = (($targetRoot - 1) % $rootCount) + 1;
            }

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

    /**
     * @return Collection<int, Media>
     */
    private function loadImageMediaPool(): Collection
    {
        $query = Media::query()
            ->where(function ($builder): void {
                $builder
                    ->where('mime_type', 'like', 'image/%')
                    ->orWhereIn('mime_type', [
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                        'image/gif',
                        'image/svg+xml',
                    ]);
            });

        $ids = $query->pluck('id');

        if ($ids->isEmpty()) {
            $ids = Media::query()->limit(500)->pluck('id');
        }

        return Media::query()->whereIn('id', $ids)->get();
    }

    private static function rootAncestorIndex(array $map, int $index): int
    {
        $current = $index;

        while (($map[$current] ?? null) !== null) {
            $current = $map[$current];
        }

        return $current;
    }

    private static function labelForIndex(int $index): string
    {
        return self::titleForLocaleStatic('en_US', $index);
    }

    private function titleForLocale(string $locale, int $index): string
    {
        return self::titleForLocaleStatic($locale, $index);
    }

    private static function titleForLocaleStatic(string $locale, int $index): string
    {
        $roots = self::ROOT_LABELS_EN;
        $rootKey = $roots[($index - 1) % count($roots)];
        $tier = intdiv($index - 1, count($roots));

        if ($tier === 0 && $index <= count($roots)) {
            return self::rootTitle($rootKey, $locale);
        }

        $catalog = self::subcategoryCatalog()[$rootKey][$locale]
            ?? self::subcategoryCatalog()[$rootKey]['en_US']
            ?? [self::rootTitle($rootKey, $locale)];

        $subIndex = $tier - 1;

        return $catalog[$subIndex % count($catalog)];
    }

    private function slugForTitle(string $title, int $index, string $locale): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'category';
        }

        return Str::limit($base, 72, '').'-'.sprintf('%03d', $index);
    }

    /**
     * @return array<string, string>
     */
    private static function germanTitleMap(): array
    {
        return [
            'Pumps & Pumping Systems' => 'Pumpen & Pumpensysteme',
            'Building Services' => 'Gebäudetechnik',
            'Water Treatment' => 'Wasseraufbereitung',
            'HVAC & Climate' => 'HLK & Klimatechnik',
            'Industrial Valves' => 'Industrieventile',
            'Measurement & Control' => 'Mess- & Regeltechnik',
            'Drainage & Wastewater' => 'Entwässerung & Abwasser',
            'Fire Protection' => 'Brandschutz',
            'Solar & Renewables' => 'Solar & Erneuerbare',
            'Installation Technology' => 'Installationstechnik',
            'Spare Parts' => 'Ersatzteile',
            'Service & Maintenance' => 'Service & Wartung',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function czechTitleMap(): array
    {
        return [
            'Pumps & Pumping Systems' => 'Čerpadla a čerpací systémy',
            'Building Services' => 'Technika budov',
            'Water Treatment' => 'Úprava vody',
            'HVAC & Climate' => 'VZT a klimatizace',
            'Industrial Valves' => 'Průmyslové ventily',
            'Measurement & Control' => 'Měření a regulace',
            'Drainage & Wastewater' => 'Odvodnění a odpadní vody',
            'Fire Protection' => 'Požární ochrana',
            'Solar & Renewables' => 'Solární a obnovitelné zdroje',
            'Installation Technology' => 'Instalační technika',
            'Spare Parts' => 'Náhradní díly',
            'Service & Maintenance' => 'Servis a údržba',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function polishTitleMap(): array
    {
        return [
            'Pumps & Pumping Systems' => 'Pompy i systemy pompowania',
            'Building Services' => 'Technika budowlana',
            'Water Treatment' => 'Uzdatnianie wody',
            'HVAC & Climate' => 'HVAC i klimatyzacja',
            'Industrial Valves' => 'Zawory przemysłowe',
            'Measurement & Control' => 'Pomiar i sterowanie',
            'Drainage & Wastewater' => 'Odprowadzanie i ścieki',
            'Fire Protection' => 'Ochrona przeciwpożarowa',
            'Solar & Renewables' => 'Energia słoneczna i OZE',
            'Installation Technology' => 'Technika instalacyjna',
            'Spare Parts' => 'Części zamienne',
            'Service & Maintenance' => 'Serwis i konserwacja',
        ];
    }

    private static function rootTitle(string $rootKey, string $locale): string
    {
        $map = match ($locale) {
            'de_DE' => self::germanTitleMap(),
            'cs_CZ' => self::czechTitleMap(),
            'pl_PL' => self::polishTitleMap(),
            default => [],
        };

        return $map[$rootKey] ?? $rootKey;
    }

    private function descriptionForLocale(string $title, string $locale): string
    {
        $intro = match ($locale) {
            'de_DE' => "Entdecken Sie Produkte, Systeme und Services für „{$title}“. Unsere Lösungen sind auf Zuverlässigkeit, Effizienz und einfache Integration ausgelegt.",
            'cs_CZ' => "V této kategorii najdete produkty, systémy a služby pro oblast „{$title}“. Řešení klademe důraz na spolehlivost, efektivitu a snadnou integraci.",
            'pl_PL' => "W tej kategorii znajdziesz produkty, systemy i usługi dla „{$title}“. Stawiamy na niezawodność, efektywność i łatwą integrację.",
            default => "Explore products, systems, and services for “{$title}”. Our solutions focus on reliability, efficiency, and straightforward integration.",
        };

        $tail = $this->randomElement($this->descriptionTails($locale));

        return $intro.' '.$tail;
    }

    /**
     * @return list<string>
     */
    private function descriptionTails(string $locale): array
    {
        return match ($locale) {
            'de_DE' => [
                'Technische Beratung und Planungssupport sind auf Anfrage verfügbar.',
                'Typische Einsatzgebiete: Gewerbe, Industrie und moderne Wohngebäude.',
                'Dokumentation, Ersatzteile und Serviceleistungen runden das Portfolio ab.',
            ],
            'cs_CZ' => [
                'Technické poradenství a podpora projektování jsou k dispozici na vyžádání.',
                'Typické aplikace: komerční budovy, průmysl a moderní bydlení.',
                'Dokumentace, náhradní díly a servis doplňují nabídku.',
            ],
            'pl_PL' => [
                'Doradztwo techniczne i wsparcie projektowe dostępne są na życzenie.',
                'Typowe zastosowania: budynki komercyjne, przemysł i nowoczesne domy.',
                'Dokumentacja, części zamienne i serwis uzupełniają ofertę.',
            ],
            default => [
                'Technical consulting and planning support are available on request.',
                'Typical applications include commercial buildings, industry, and modern residential projects.',
                'Documentation, spare parts, and service offerings complete the portfolio.',
            ],
        };
    }

    private function markdownContentForLocale(string $title, string $locale): string
    {
        $overview = match ($locale) {
            'de_DE' => "Die Kategorie **{$title}** bündelt bewährte Komponenten und Komplettlösungen für Planer, Installateure und Betreiber.",
            'cs_CZ' => "Kategorie **{$title}** sdružuje ověřené komponenty a kompletní řešení pro projektanty, montéry i provozovatele.",
            'pl_PL' => "Kategoria **{$title}** łączy sprawdzone komponenty i kompletne rozwiązania dla projektantów, instalatorów i operatorów.",
            default => "The **{$title}** category brings together proven components and complete solutions for planners, installers, and operators.",
        };

        $bullets = collect($this->contentBullets($locale))
            ->map(fn (string $line): string => '- '.$line)
            ->implode("\n");

        $closing = match ($locale) {
            'de_DE' => 'Kontaktieren Sie unser Team für Auslegung, Lieferumfang und passende Zubehörteile.',
            'cs_CZ' => 'Obraťte se na náš tým ohledně dimenzování, rozsahu dodávky a vhodného příslušenství.',
            'pl_PL' => 'Skontaktuj się z naszym zespołem w sprawie doboru, zakresu dostawy i odpowiednich akcesoriów.',
            default => 'Contact our team for sizing, scope of supply, and suitable accessories.',
        };

        return <<<MD
        # {$title}

        {$overview}

        ## Highlights

        {$bullets}

        ## Details

        {$closing}
        MD;
    }

    /**
     * @return list<string>
     */
    private function contentBullets(string $locale): array
    {
        return match ($locale) {
            'de_DE' => [
                'Auswahl an Standard- und Sonderausführungen',
                'Kompatibel mit gängigen Schnittstellen und Normen',
                'Schnelle Verfügbarkeit und übersichtliche Dokumentation',
                'Service- und Wartungsoptionen aus einer Hand',
            ],
            'cs_CZ' => [
                'Široká nabídka standardních i speciálních provedení',
                'Kompatibilita s běžnými rozhraními a normami',
                'Rychlá dostupnost a přehledná dokumentace',
                'Servis a údržba z jednoho zdroje',
            ],
            'pl_PL' => [
                'Wybór wersji standardowych i specjalnych',
                'Zgodność z popularnymi interfejsami i normami',
                'Szybka dostępność i przejrzysta dokumentacja',
                'Serwis i konserwacja z jednego źródła',
            ],
            default => [
                'Range of standard and special configurations',
                'Compatible with common interfaces and standards',
                'Fast availability and clear documentation',
                'Service and maintenance options from a single source',
            ],
        };
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
