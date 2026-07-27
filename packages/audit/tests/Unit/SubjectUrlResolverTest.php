<?php

declare(strict_types=1);

use Moox\Audit\Models\Activity;
use Moox\Audit\Support\AuditFilamentRegistry;
use Moox\Audit\Support\SubjectUrlResolver;
use Moox\Audit\Tests\Support\TestAuditableItem;
use Moox\Audit\Tests\Support\TestAuditableItemResource;
use Moox\Audit\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    AuditFilamentRegistry::clear();
});

it('resolves a subject url from the audit filament registry for an owner model', function (): void {
    $item = TestAuditableItem::query()->create([
        'title' => 'Linked',
        'status' => 'draft',
    ]);

    AuditFilamentRegistry::register(TestAuditableItemResource::class, [
        'owner_model' => TestAuditableItem::class,
    ]);

    $activity = new Activity;
    $activity->setRelation('subject', $item);

    expect(SubjectUrlResolver::forActivity($activity))->toBe('/test-items/'.$item->getKey().'/edit');
});

it('returns null when no subject resource can be resolved', function (): void {
    $item = new TestAuditableItem;
    $item->setRawAttributes(['id' => 99, 'title' => 'Orphan'], true);

    $activity = new Activity;
    $activity->setRelation('subject', $item);

    expect(SubjectUrlResolver::forActivity($activity))->toBeNull();
});
