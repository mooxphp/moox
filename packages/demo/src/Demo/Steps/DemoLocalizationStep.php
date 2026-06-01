<?php

declare(strict_types=1);

namespace Moox\Demo\Demo\Steps;

use Illuminate\Support\Facades\DB;
use Moox\Demo\Console\DemoConsole;
use Moox\Demo\Demo\DemoContext;
use Moox\Demo\Seeding\MooxSeederLocales;
use Moox\Localization\Models\Localization;

final class DemoLocalizationStep
{
    public function __construct(
        private readonly DemoConsole $console,
    ) {}

    public function run(DemoContext $context): void
    {
        if (! class_exists(Localization::class)) {
            $this->console->skip('Localizations', 'moox/localization not installed');

            return;
        }

        if (! $this->tableExists('static_languages')) {
            $this->console->failTask('Localizations', 'static_languages table missing — run moox/data first');

            return;
        }

        $locales = MooxSeederLocales::mergeForDemoRun($context->locales);

        if ($locales === []) {
            $this->console->skip('Localizations', 'no locales configured');

            return;
        }

        $languages = DB::table('static_languages')->pluck('id', 'alpha2');

        if ($languages->isEmpty()) {
            $this->console->failTask('Localizations', 'no rows in static_languages — seed moox/data first');

            return;
        }

        $modelClass = Localization::class;

        $this->console->beginNestedOutput('Localizations');

        foreach ($locales as $index => $localeVariant) {
            $alpha2 = strtolower(substr($localeVariant, 0, 2));
            $languageId = $languages->get($alpha2) ?? $languages->first();

            $slug = strtolower(str_replace('_', '-', $localeVariant));
            $title = str_replace('_', ' ', $localeVariant);

            $modelClass::query()->updateOrCreate(
                ['locale_variant' => $localeVariant],
                [
                    'title' => $title,
                    'slug' => $slug,
                    'language_id' => $languageId,
                    'fallback_language_id' => null,
                    'is_active_admin' => true,
                    'is_active_frontend' => true,
                    'is_default' => $index === 0,
                    'fallback_behaviour' => 'default',
                    'language_routing' => 'path',
                    'routing_path' => $slug,
                    'routing_subdomain' => null,
                    'routing_domain' => null,
                    'translation_status' => 100,
                    'language_settings' => json_encode(['locale' => $localeVariant]),
                ]
            );

            $this->console->created("Localization {$localeVariant}");
        }

        $this->console->finishTask('Localizations', count($locales).' locale(s)');
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
