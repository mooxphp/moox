<?php

namespace Moox\Media\Models;

use Illuminate\Database\Eloquent\Model;

class MediaTranslation extends Model
{
    protected $fillable = ['name', 'title', 'alt', 'description', 'internal_note'];
}
