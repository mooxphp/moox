<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ContextFactory
{
    public static function create(
        string $contextType,
        string $entityName,
        array $config = [],
        ?string $preset = null
    ): BuildContext {
        $contexts = config('builder.contexts', []);

        // Debug logging
        Log::info('ContextFactory creating context', [
            'contextType' => $contextType,
            'config' => $config,
            'available_contexts' => array_keys($contexts),
            'package_context_exists' => isset($contexts['package']),
            'package_context_config' => $contexts['package'] ?? null,
        ]);

        if ($contextType === 'package') {
            if (empty($config['package']['name'])) {
                throw new InvalidArgumentException('Package name is required for package context');
            }

            $packagePath = str_replace('.', '/', $config['package']['name']);
            $packageNamespace = str_replace('.', '\\', $config['package']['name']);

            $config['package']['namespace'] = $packageNamespace;

            $contextConfig = array_merge(
                $contexts[$contextType] ?? [],
                [
                    'base_path' => base_path("packages/{$packagePath}"),
                    'base_namespace' => $packageNamespace,
                    'package' => $config['package'],
                ],
                $config
            );

            Log::info('Package context configuration', [
                'final_context_config' => $contextConfig,
            ]);

            return new BuildContext($contextType, $contextConfig, [], $entityName, null, $preset);
        }

        if (! isset($contexts[$contextType])) {
            throw new InvalidArgumentException("Invalid context type: {$contextType}");
        }

        return new BuildContext(
            $contextType,
            array_merge($contexts[$contextType], $config),
            [],
            $entityName,
            null,
            $preset
        );
    }
}
