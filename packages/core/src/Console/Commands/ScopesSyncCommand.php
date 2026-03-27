<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
use Moox\Core\Models\Scope;
use Moox\Core\Support\Scopes\ScopeValue;
use Throwable;

class ScopesSyncCommand extends Command
{
    protected $signature = 'scopes:sync {--dry-run : Show what would be synced without writing} {--disable-missing : Disable DB scopes not present in config}';

    protected $description = 'Sync scope definitions from package configs into scopes table';

    public function handle(): int
    {
        $rows = $this->collectScopeRows();
        $scopes = array_keys($rows);
        $isDryRun = (bool) $this->option('dry-run');
        $disableMissing = (bool) $this->option('disable-missing');

        if ($rows === []) {
            $this->warn('No scope definitions found in config resources.*.scopes.');
        }

        if ($isDryRun) {
            $this->line('Dry-run: the following scope rows would be upserted:');
            $this->table(
                ['scope', 'label', 'origin', 'source', 'context', 'boundary', 'is_active'],
                array_values($rows),
            );
        } else {
            $created = 0;
            $updated = 0;

            foreach ($rows as $row) {
                $record = Scope::query()->updateOrCreate(
                    ['scope' => $row['scope']],
                    [
                        'label' => $row['label'],
                        'origin' => $row['origin'],
                        'source' => $row['source'],
                        'context' => $row['context'],
                        'boundary' => $row['boundary'],
                        'is_active' => true,
                    ],
                );

                if ($record->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            $this->info("Scopes synced. Created: {$created}, Updated: {$updated}");
        }

        if ($disableMissing) {
            if ($isDryRun) {
                $count = Scope::query()
                    ->when($scopes !== [], fn ($query) => $query->whereNotIn('scope', $scopes))
                    ->where('is_active', true)
                    ->count();

                $this->warn("Dry-run: {$count} scope rows would be disabled.");
            } else {
                $disabled = Scope::query()
                    ->when($scopes !== [], fn ($query) => $query->whereNotIn('scope', $scopes))
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                $this->warn("Disabled {$disabled} scope rows not present in current config.");
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, array{scope: string, label: string|null, origin: string, source: string, context: string, boundary: string, is_active: bool}>
     */
    protected function collectScopeRows(): array
    {
        $rows = [];
        $configs = config()->all();

        foreach ($configs as $configValue) {
            if (! is_array($configValue)) {
                continue;
            }

            $resources = $configValue['resources'] ?? null;

            if (! is_array($resources)) {
                continue;
            }

            foreach ($resources as $resourceKey => $resourceDefinition) {
                if (! is_array($resourceDefinition)) {
                    continue;
                }

                $scopes = $resourceDefinition['scopes'] ?? $resourceDefinition['children'] ?? null;

                if (! is_array($scopes)) {
                    continue;
                }

                $baseScope = value($resourceDefinition['scope'] ?? null)
                    ?? ScopeValue::forKeyString(
                        (string) $resourceKey,
                        boundary: value($resourceDefinition['boundary'] ?? $resourceDefinition['mode'] ?? null),
                        source: value($resourceDefinition['source'] ?? $resourceDefinition['target'] ?? null),
                        context: value($resourceDefinition['context'] ?? null),
                    );

                foreach ($scopes as $originKey => $scopeDefinition) {
                    if (! is_array($scopeDefinition)) {
                        continue;
                    }

                    if (! (bool) value($scopeDefinition['enabled'] ?? true)) {
                        continue;
                    }

                    $origin = value($scopeDefinition['origin'] ?? null) ?: (is_string($originKey) ? $originKey : null);

                    if (! is_string($origin) || $origin === '') {
                        $this->warn('Skipping scope without valid origin key.');

                        continue;
                    }

                    $scopeString = value($scopeDefinition['scope'] ?? null);

                    if (blank($scopeString)) {
                        $scopeString = ScopeValue::deriveChildString(
                            $baseScope,
                            $origin,
                            context: is_string(value($scopeDefinition['context'] ?? null)) ? value($scopeDefinition['context']) : null,
                            boundary: is_string(value($scopeDefinition['boundary'] ?? $scopeDefinition['mode'] ?? null)) ? value($scopeDefinition['boundary'] ?? $scopeDefinition['mode']) : null,
                            source: is_string(value($scopeDefinition['source'] ?? $scopeDefinition['target'] ?? null)) ? value($scopeDefinition['source'] ?? $scopeDefinition['target']) : null,
                        );
                    }

                    try {
                        $parsed = ScopeValue::parse(is_string($scopeString) ? $scopeString : null);
                    } catch (Throwable $throwable) {
                        $this->warn('Skipping invalid scope: '.(string) $scopeString.' ('.$throwable->getMessage().')');

                        continue;
                    }

                    if (! $parsed) {
                        continue;
                    }

                    $scope = (string) $parsed;
                    $label = value($scopeDefinition['label'] ?? null) ?? value($scopeDefinition['navigation_label'] ?? null);

                    $rows[$scope] = [
                        'scope' => $scope,
                        'label' => is_string($label) && $label !== '' ? $label : null,
                        'origin' => $parsed->origin(),
                        'source' => $parsed->source(),
                        'context' => $parsed->context(),
                        'boundary' => $parsed->boundary(),
                        'is_active' => true,
                    ];
                }
            }
        }

        ksort($rows);

        return $rows;
    }
}
