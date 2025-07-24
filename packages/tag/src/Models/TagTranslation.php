<?php

namespace Moox\Tag\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TagTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['locale', 'tag_id', 'title', 'slug', 'content'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'title' => 'string',
        'slug' => 'string',
        'content' => 'string',
    ];


}
