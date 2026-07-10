<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Moox\BlockEditor\Models\Template;

final class TemplateRepository
{
    /**
     * @param  array{per_page?: int, search?: string, sort?: string, direction?: string}  $validated
     */
    public function paginate(array $validated, Request $request): LengthAwarePaginator
    {
        $perPage = (int) ($validated['per_page'] ?? 50);
        $search = $validated['search'] ?? null;
        $sort = $validated['sort'] ?? 'id';
        $direction = $validated['direction'] ?? 'desc';

        return Template::query()
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
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Template
    {
        return Template::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Template $template, array $attributes): Template
    {
        $template->update($attributes);

        $fresh = $template->fresh();

        return $fresh instanceof Template ? $fresh : $template;
    }

    public function delete(Template $template): void
    {
        $template->delete();
    }
}
