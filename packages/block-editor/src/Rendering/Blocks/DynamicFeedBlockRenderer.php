<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Rendering\Blocks;

use Illuminate\Support\Facades\Log;
use Moox\BlockEditor\EntityQuery\EntityQueryDefinition;
use Moox\BlockEditor\EntityQuery\EntityQuerySourceRegistry;
use Moox\BlockEditor\Rendering\Contracts\BlockRenderer;
use Moox\BlockEditor\Rendering\RenderContext;
use Throwable;

final class DynamicFeedBlockRenderer implements BlockRenderer
{
    public function supports(string $type): bool
    {
        return $type === 'dynamicFeed';
    }

    public function render(array $block, RenderContext $context): string
    {
        $sourceKey = (string) ($block['sourceKey'] ?? '');

        if ($sourceKey === '' || ! EntityQuerySourceRegistry::has($sourceKey)) {
            Log::warning('Dynamic feed block references unknown source.', [
                'sourceKey' => $sourceKey,
                'blockId' => $block['id'] ?? null,
            ]);

            return '';
        }

        try {
            $source = EntityQuerySourceRegistry::resolve($sourceKey);
        } catch (Throwable $exception) {
            Log::warning('Dynamic feed source could not be resolved.', [
                'sourceKey' => $sourceKey,
                'message' => $exception->getMessage(),
            ]);

            return '';
        }

        $viewKey = (string) ($block['view'] ?? $source->defaultView());
        $views = $source->views();
        $viewName = $views[$viewKey]['view'] ?? null;

        if (! is_string($viewName) || $viewName === '') {
            Log::warning('Dynamic feed block references unknown view.', [
                'sourceKey' => $sourceKey,
                'view' => $viewKey,
            ]);

            return '';
        }

        $definition = EntityQueryDefinition::fromBlock($block, $context->locale);
        $items = $source->query($definition);

        return view($viewName, [
            'items' => $items,
            'block' => $block,
            'locale' => $context->locale,
        ])->render();
    }
}
