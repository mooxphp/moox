<?php

class ChangelogParser
{
    private string $version;
    private array $globalChanges = [];
    private array $packageChanges = [];
    private array $monorepoNotes = [];

    public function parseLatestVersion(string $content): void
    {
        $sections = [];
        $currentSection = null;
        $currentLevel = 0;

        foreach (explode("\n", $content) as $line) {
            // Get first version number
            if (str_starts_with($line, '## ') && !$this->version) {
                $this->version = trim(str_replace('## ', '', $line));
                continue;
            }

            // Parse sections
            if (str_starts_with($line, '### ')) {
                $currentSection = trim(str_replace('### ', '', $line));
                $currentLevel = 3;
                continue;
            }

            // Store content based on section
            $trimmedLine = trim($line);
            if ($trimmedLine && $currentSection) {
                if ($currentSection === 'All') {
                    $this->globalChanges[] = $trimmedLine;
                } elseif ($currentLevel === 3) {
                    $this->packageChanges[$currentSection][] = $trimmedLine;
                }
            }
        }
    }

    public function isPackageAffected(string $package): bool
    {
        return isset($this->packageChanges[$package]) || $this->isMajorVersion();
    }

    public function isMajorVersion(): bool
    {
        $parts = explode('.', $this->version);
        return isset($parts[1]) && $parts[1] === '0' && $parts[2] === '0';
    }

    // Getters
    public function getVersion(): string
    {
        return $this->version;
    }

    public function getGlobalChanges(): array
    {
        return $this->globalChanges;
    }

    public function getPackageChanges(string $package): array
    {
        return $this->packageChanges[$package] ?? [];
    }
}
