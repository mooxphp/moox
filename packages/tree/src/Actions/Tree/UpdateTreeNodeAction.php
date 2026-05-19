<?php

declare(strict_types=1);

namespace Heco\FilamentTreeIndex\Actions\Tree;

use Heco\FilamentTreeIndex\Config\TreeIndexConfiguration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class UpdateTreeNodeAction
{
    public function __construct(private readonly TreeIndexConfiguration $configuration) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Model $record, array $attributes): Model
    {
        return DB::transaction(function () use ($record, $attributes): Model {
            $record->update($attributes);

            return $record->refresh();
        });
    }
}
