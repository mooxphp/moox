<?php

namespace Moox\Core\Entities\Items\Item\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

abstract class MooxCreatePage extends CreateRecord
{
    protected function resolveRecord($key): Model
    {
        $model = static::getModel();

        $query = $model::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query->find($key) ?? $model::make();
    }
}
