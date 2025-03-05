<?php

class PackageChangelogUpdater
{
    private ChangelogParser $parser;
    private string $packagesDir;

    public function __construct(string $changelogContent, string $packagesDir)
    {
        $this->parser = new ChangelogParser();
        $this->parser->parseLatestVersion($changelogContent);
        $this->packagesDir = $packagesDir;
    }

    public function updatePackage(string $package): void
    {
        $changelogPath = "{$this->packagesDir}/{$package}/CHANGELOG.md";

        $changes = [];

        // Add version header
        $changes[] = "## {$this->parser->getVersion()}";
        $changes[] = "";

        // Add global changes if any
        if ($globalChanges = $this->parser->getGlobalChanges()) {
            foreach ($globalChanges as $change) {
                $changes[] = $change;
            }
            $changes[] = "";
        }

        // Add package-specific changes
        if ($packageChanges = $this->parser->getPackageChanges($package)) {
            foreach ($packageChanges as $change) {
                $changes[] = $change;
            }
        } elseif ($this->parser->isMajorVersion()) {
            $changes[] = "Compatibility release";
        }

        // Prepend to existing changelog
        $this->prependToChangelog($changelogPath, $changes);
    }

    private function prependToChangelog(string $path, array $changes): void
    {
        $content = file_exists($path) ? file_get_contents($path) : "# Changelog\n\n";
        $newContent = implode("\n", $changes) . "\n\n" . $content;
        file_put_contents($path, $newContent);
    }
}
