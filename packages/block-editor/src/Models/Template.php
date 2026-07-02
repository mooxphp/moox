<?php

namespace Moox\BlockEditor\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $table = 'editor_templates';

    protected $fillable = [
        'name',
        'slug',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }
}
