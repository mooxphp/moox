<?php

namespace Moox\Page\Models;

use Illuminate\Database\Eloquent\Model;

class PageTranslation extends Model
{
    public $timestamps = true;

    protected $fillable = ['name'];
}
