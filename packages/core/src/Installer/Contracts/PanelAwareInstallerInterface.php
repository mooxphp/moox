<?php

namespace Moox\Core\Installer\Contracts;

/**
 * Contract for installers that need panel awareness.
 *
 * Implement this interface for installers that need to interact
 * with Filament panels (e.g., plugin installers).
 */
interface PanelAwareInstallerInterface extends AssetInstallerInterface
{
    /**
     * Set the panel path for installation.
     */
    public function setPanelPath(?string $panelPath): void;

    /**
     * Get the current panel path.
     */
    public function getPanelPath(): ?string;

    /**
     * Check if panel selection is required before installation.
     */
    public function requiresPanelSelection(): bool;
}
