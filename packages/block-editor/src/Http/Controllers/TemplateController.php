<?php

namespace Moox\BlockEditor\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Moox\BlockEditor\Http\Requests\IndexTemplateRequest;
use Moox\BlockEditor\Http\Requests\StoreTemplateRequest;
use Moox\BlockEditor\Http\Requests\UpdateTemplateRequest;
use Moox\BlockEditor\Models\Template;
use Moox\BlockEditor\Support\ApiAuthorization;

class TemplateController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        if (ApiAuthorization::isEnabled()) {
            $this->authorizeResource(Template::class, 'template');
        }
    }

    public function index(IndexTemplateRequest $request): JsonResponse
    {
        /** @var array{per_page?: int, search?: string, sort?: string, direction?: string} $validated */
        $validated = $request->validated();

        $perPage = (int) ($validated['per_page'] ?? 50);
        $search = $validated['search'] ?? null;
        $sort = $validated['sort'] ?? 'id';
        $direction = $validated['direction'] ?? 'desc';

        $templates = Template::query()
            ->when(
                is_string($search) && trim($search) !== '',
                function ($query) use ($search): void {
                    $term = '%'.trim($search).'%';

                    $query->where(function ($subQuery) use ($term): void {
                        $subQuery->where('name', 'like', $term)
                            ->orWhere('slug', 'like', $term);
                    });
                }
            )
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json($templates);
    }

    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $template = Template::query()->create($request->validated());

        return response()->json($template, 201);
    }

    public function show(Template $template): JsonResponse
    {
        return response()->json($template);
    }

    public function update(UpdateTemplateRequest $request, Template $template): JsonResponse
    {
        $template->update($request->validated());

        return response()->json($template->fresh());
    }

    public function destroy(Template $template): Response
    {
        $template->delete();

        return response()->noContent();
    }
}
