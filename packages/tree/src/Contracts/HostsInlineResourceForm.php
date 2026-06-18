<?php

declare(strict_types=1);

namespace Moox\Tree\Contracts;

/**
 * Livewire host that renders a Filament resource form inline (e.g. Moox tree inspector)
 * instead of on a dedicated CreateRecord / EditRecord page.
 */
interface HostsInlineResourceForm
{
    public function isCreatingInlineResourceRecord(): bool;

    public function cancelInlineResourceForm(): void;

    public function completeInlineResourceDeletion(): void;
}
