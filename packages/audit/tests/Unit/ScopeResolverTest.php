<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Audit\Support\ScopeResolver;
use Moox\Audit\Tests\Support\TestDraftMain;
use Moox\Audit\Tests\Support\TestDraftMainTranslation;
use Moox\Audit\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Schema::create('test_draft_mains', function (Blueprint $table): void {
        $table->id();
        $table->string('status')->default('draft');
        $table->string('scope')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

it('resolves scope from the parent draft model for translation models', function (): void {
    $mainId = DB::table('test_draft_mains')->insertGetId([
        'status' => 'draft',
        'scope' => 'category:draft:default:private',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $translation = new TestDraftMainTranslation;
    $translation->setRawAttributes([
        'id' => 1,
        'test_draft_main_id' => $mainId,
        'locale' => 'en',
        'title' => 'Example',
    ], true);

    expect(ScopeResolver::forModel($translation))->toBe('category:draft:default:private');
});

it('returns null when a draft translation has no parent', function (): void {
    $translation = new TestDraftMainTranslation;
    $translation->setRawAttributes([
        'id' => 1,
        'locale' => 'en',
        'title' => 'Orphan',
    ], true);

    expect(ScopeResolver::forModel($translation))->toBeNull();
});

it('resolves scope directly from models with a scope column', function (): void {
    $main = new TestDraftMain;
    $main->setRawAttributes([
        'id' => 1,
        'status' => 'draft',
        'scope' => 'category:draft:default:public',
    ], true);

    expect(ScopeResolver::forModel($main))->toBe('category:draft:default:public');
});
