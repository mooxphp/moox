<?php

namespace Moox\Page\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class Page extends Model implements TranslatableContract
{
    use Translatable;

    protected $table = 'pages';

    protected $fillable = [
        'started_at',
        'finished_at',
        'failed',
    ];

    public $translatedAttributes = ['name'];

    protected $casts = [
        'failed' => 'bool',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
