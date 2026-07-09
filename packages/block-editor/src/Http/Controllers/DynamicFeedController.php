<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Moox\BlockEditor\EntityQuery\EntityQueryDefinition;
use Moox\BlockEditor\EntityQuery\EntityQuerySourceRegistry;
use Moox\BlockEditor\Http\Requests\PreviewDynamicFeedRequest;
use Moox\BlockEditor\Rendering\Blocks\DynamicFeedBlockRenderer;
use Moox\BlockEditor\Rendering\RenderContext;
use Moox\BlockEditor\Support\BlockEditorLocale;

final class DynamicFeedController extends Controller
{
    use AuthorizesRequests;

    public function sources(): JsonResponse
    {
        $sources = EntityQuerySourceRegistry::sources()->map(fn ($source): array => [
            'key' => $source->key(),
            'label' => $source->label(),
            'filterSchema' => $source->filterSchema(),
            'views' => collect($source->views())->map(fn (array $view, string $key): array => [
                'key' => $key,
                'label' => $view['label'] ?? $key,
            ])->values(),
            'defaultView' => $source->defaultView(),
        ]);

        return response()->json(['data' => $sources]);
    }

    public function views(string $sourceKey): JsonResponse
    {
        try {
            $source = EntityQuerySourceRegistry::resolve($sourceKey);
        } catch (InvalidArgumentException) {
            return response()->json(['message' => 'Unknown source.'], 404);
        }

        return response()->json([
            'data' => collect($source->views())->map(fn (array $view, string $key): array => [
                'key' => $key,
                'label' => $view['label'] ?? $key,
            ])->values(),
        ]);
    }

    public function filterOptions(Request $request, string $sourceKey, string $filter): JsonResponse
    {
        try {
            $source = EntityQuerySourceRegistry::resolve($sourceKey);
        } catch (InvalidArgumentException) {
            return response()->json(['message' => 'Unknown source.'], 404);
        }

        $locale = BlockEditorLocale::resolveActive($request);

        return response()->json([
            'data' => $source->filterOptions($filter, $locale),
        ]);
    }

    public function preview(
        PreviewDynamicFeedRequest $request,
        DynamicFeedBlockRenderer $renderer,
    ): JsonResponse
    {
        $validated = $request->validated();
        $locale = BlockEditorLocale::resolveActive($request);

        try {
            $source = EntityQuerySourceRegistry::resolve($validated['sourceKey']);
        } catch (InvalidArgumentException) {
            return response()->json(['message' => 'Unknown source.'], 404);
        }

        $definition = EntityQueryDefinition::fromArray([
            'sourceKey' => $validated['sourceKey'],
            'limit' => $validated['limit'] ?? null,
            'orderBy' => $validated['orderBy'] ?? null,
            'orderDirection' => $validated['orderDirection'] ?? null,
            'filters' => $validated['filters'] ?? [],
        ], $locale);

        $items = $source->query($definition);
        $view = (string) ($validated['view'] ?? $source->defaultView());

        $block = [
            'type' => 'dynamicFeed',
            'sourceKey' => $validated['sourceKey'],
            'limit' => $validated['limit'] ?? null,
            'orderBy' => $validated['orderBy'] ?? null,
            'orderDirection' => $validated['orderDirection'] ?? null,
            'filters' => $validated['filters'] ?? [],
            'view' => $view,
            'emptyMessage' => '',
            'isEditorPreview' => true,
        ];

        return response()->json([
            'locale' => $locale,
            'count' => $items->count(),
            'items' => $items->values(),
            'html' => $renderer->render($block, new RenderContext($locale)),
        ]);
    }
}
