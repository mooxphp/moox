<?php

declare(strict_types=1);

use Moox\Tree\Support\TreeResourcePageExecutor;

it('invokes protected mutateFormDataBeforeCreate on resource pages', function (): void {
    $page = new class
    {
        public bool $mutated = false;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $this->mutated = true;

            return [...$data, 'mutated' => true];
        }
    };

    $executor = app(TreeResourcePageExecutor::class);

    $result = $executor->mutateFormDataBeforeCreate($page, ['title' => 'Test']);

    expect($page->mutated)->toBeTrue()
        ->and($result)->toBe(['title' => 'Test', 'mutated' => true]);
});
