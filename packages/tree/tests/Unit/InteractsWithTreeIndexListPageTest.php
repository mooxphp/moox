<?php

declare(strict_types=1);

use Moox\Tree\Filament\Concerns\InteractsWithTreeIndexListPage;
use Moox\Tree\Tests\TestCase;

uses(TestCase::class);

it('syncs tab to request when active tab is updated', function (): void {
    $pageClass = new class
    {
        use InteractsWithTreeIndexListPage;

        public string $activeTab = 'deleted';

        public function usesListPageTabs(): bool
        {
            return true;
        }
    };

    $page = new $pageClass;
    $page->updated('activeTab');

    expect(request()->input('tab'))->toBe('deleted');
});
