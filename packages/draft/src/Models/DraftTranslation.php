<?php

namespace Moox\Draft\Models;

use Illuminate\Database\Eloquent\Model;

class DraftTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['title', 'slug', 'description', 'content'];
}
