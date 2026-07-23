<?php

declare(strict_types=1);

namespace Moox\Tree\Actions\Tree;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Support\TreeGraphValidator;

final class UpdateTreeNodeAction
{
    public function __construct(private readonly TreeIndexConfiguration $configuration)
    {
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Model $record, array $attributes): Model
    {
        $parentColumn = $this->configuration->getParentColumn();

        if (array_key_exists($parentColumn, $attributes)) {
            app(TreeGraphValidator::class, ['configuration' => $this->configuration])
                ->validateParentAssignment($record, $attributes[$parentColumn]);
        }

        return DB::transaction(function () use ($record, $attributes): Model {
            $record->update($attributes);

            return $record->refresh();
        });
    }
}
