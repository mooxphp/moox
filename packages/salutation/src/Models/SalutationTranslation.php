<?php

declare(strict_types=1);

namespace Moox\Salutation\Models;

use Illuminate\Database\Eloquent\Model;

class SalutationTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'salutation_translations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'locale',
        'title',
        'slug',
        'salutation',
        'formal_salutation',
    ];
}
