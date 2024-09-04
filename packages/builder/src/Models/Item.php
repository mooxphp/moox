<?php

namespace Moox\Builder\Models;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $table = 'items';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public static function getStatusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ];
    }

    public static function getTypeOptions(): array
    {
        return [
            'post' => 'Post',
            'page' => 'Page',
            'product' => 'Product',
        ];
    }

    public static function getStatusFormField(): Select
    {
        return Select::make('status')
            ->options(self::getStatusOptions())
            ->required();
    }

    public static function getTypeFormField(): Select
    {
        return Select::make('type')
            ->options(self::getTypeOptions())
            ->required();
    }
}
