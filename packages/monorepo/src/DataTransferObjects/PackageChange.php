<?php

namespace Moox\Monorepo\DataTransferObjects;

use Illuminate\Support\Collection;

class PackageChange
{
    public function __construct(
        public readonly string $packageName,
        public readonly Collection $changes,
        public readonly string $releaseMessage,
        public readonly string $changeType = 'compatibility', // 'initial', 'compatibility', 'feature'
        public readonly string $packageType = 'public', // 'public', 'private'
        public readonly array $metadata = []
    ) {}

    /**
     * Create for initial release
     */
    public static function initial(string $packageName, string $packageType = 'public'): self
    {
        return new self(
            packageName: $packageName,
            changes: collect(['Initial release']),
            releaseMessage: 'Initial release',
            changeType: 'initial',
            packageType: $packageType
        );
    }

    /**
     * Create for compatibility release
     */
    public static function compatibility(string $packageName, string $packageType = 'public'): self
    {
        return new self(
            packageName: $packageName,
            changes: collect(['Compatibility release']),
            releaseMessage: 'Compatibility release',
            changeType: 'compatibility',
            packageType: $packageType
        );
    }

    /**
     * Create with specific changes
     */
    public static function withChanges(string $packageName, array $changes, string $packageType = 'public'): self
    {
        $changeCollection = collect($changes);
        $releaseMessage = $changeCollection->count() === 1 
            ? $changeCollection->first() 
            : $changeCollection->implode('; ');

        return new self(
            packageName: $packageName,
            changes: $changeCollection,
            releaseMessage: $releaseMessage,
            changeType: 'feature',
            packageType: $packageType
        );
    }

    /**
     * Check if this is an initial release
     */
    public function isInitialRelease(): bool
    {
        return $this->changeType === 'initial';
    }

    /**
     * Check if this is a compatibility release
     */
    public function isCompatibilityRelease(): bool
    {
        return $this->changeType === 'compatibility';
    }

    /**
     * Get sanitized release message for workflows
     */
    public function getSanitizedReleaseMessage(): string
    {
        // Remove problematic characters for bash/workflow usage
        $message = str_replace([
            '(', ')', '`', '$', '"', "'", "\n", "\r", "\t"
        ], [
            '[', ']', '', '', '', '', ' ', ' ', ' '
        ], $this->releaseMessage);

        return trim(substr($message, 0, 200));
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'packageName' => $this->packageName,
            'changes' => $this->changes->toArray(),
            'releaseMessage' => $this->releaseMessage,
            'changeType' => $this->changeType,
            'packageType' => $this->packageType,
            'metadata' => $this->metadata,
        ];
    }
} 