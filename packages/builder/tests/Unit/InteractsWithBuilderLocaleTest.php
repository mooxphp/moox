<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Moox\Builder\Filament\Resources\Pages\Concerns\InteractsWithBuilderLocale;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('resets an invalid livewire lang to the admin default before save', function (): void {
    config()->set('translatable.locales', ['en_US', 'de_CH']);
    config()->set('builder.default_locale', 'en_US');

    $page = new class
    {
        use InteractsWithBuilderLocale;

        public function prepareForSave(string $lang): string
        {
            $this->lang = $lang;
            $this->ensureAllowedBuilderAdminLocale();

            return $this->lang;
        }
    };

    expect($page->prepareForSave('de_CH'))->toBe('de_CH')
        ->and($page->prepareForSave('banana_XY'))->toBe(
            app(BuilderLocaleResolver::class)->adminDefaultLocale(),
        );
});
