<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Moox\BlockEditor\Http\Requests\IndexTemplateRequest;
use Moox\BlockEditor\Http\Requests\StoreTemplateRequest;
use Moox\BlockEditor\Http\Requests\UpdateTemplateRequest;
use Moox\BlockEditor\Models\Template;
use Moox\BlockEditor\Repositories\TemplateRepository;
use Moox\BlockEditor\Support\ApiAuthorization;

class TemplateController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TemplateRepository $templates,
    ) {
        if (ApiAuthorization::isEnabled()) {
            $this->authorizeResource(Template::class, 'template');
        }
    }

    public function index(IndexTemplateRequest $request): JsonResponse
    {
        /** @var array{per_page?: int, search?: string, sort?: string, direction?: string} $validated */
        $validated = $request->validated();

        return response()->json($this->templates->paginate($validated, $request));
    }

    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $template = $this->templates->create($request->validated());

        return response()->json($template, 201);
    }

    public function update(UpdateTemplateRequest $request, Template $template): JsonResponse
    {
        $template = $this->templates->update($template, $request->validated());

        return response()->json($template);
    }

    public function destroy(Template $template): Response
    {
        $this->templates->delete($template);

        return response()->noContent();
    }
}
