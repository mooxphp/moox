<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\Tests\TestCase;
use Moox\Core\Enums\TranslationStatus;

uses(TestCase::class);

it('resolves record status from the current locale translation on draft-like models', function (): void {
    $record = new class extends Model
    {
        public function translate(?string $locale = null, bool $withFallback = false): ?object
        {
            return (object) [
                'translation_status' => $locale === 'de_DE'
                    ? TranslationStatus::PUBLISHED
                    : TranslationStatus::DRAFT,
            ];
        }
    };

    $record->setRawAttributes(['status' => 'draft']);

    app()->instance('request', Request::create('/', 'GET', ['lang' => 'de_DE']));

    $context = LocationContext::forEntity('page', $record);

    expect($context->get('record_status'))->toBe('published');
});

it('falls back to the main-table status when no translation status is available', function (): void {
    $record = new class extends Model {};

    $record->setRawAttributes(['status' => 'archived']);

    $context = LocationContext::forEntity('item', $record);

    expect($context->get('record_status'))->toBe('archived');
});
