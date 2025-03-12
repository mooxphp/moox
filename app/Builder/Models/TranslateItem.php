<?php

declare(strict_types=1);

namespace App\Builder\Models;

use Illuminate\Database\Eloquent\Model;

class TranslateItem extends Model
{
    protected $table = 'preview_translate_items';

    protected $fillable = [
        'light',
        'title',
        'slug',
        'content',
        'street',
        'city',
        'postal_code',
        'country',
        'translatable',
        'status',
        'type',
    ];

    protected $casts = [
        'slug' => 'string',
        'title' => 'string',
    ];
}
