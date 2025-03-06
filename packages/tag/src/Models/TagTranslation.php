<?php

namespace Moox\Tag\Models;

use Illuminate\Database\Eloquent\Model;

class TagTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['title', 'slug', 'content'];
}
