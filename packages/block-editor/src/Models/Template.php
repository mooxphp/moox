<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $slug
 * @property array<string, mixed>|null $content
 */
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
