<?php

namespace Moox\Media\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInModel;
use Moox\Press\QueryBuilder\UserQueryBuilder;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [

    ];

    protected $searchableFields = ['*'];

    protected $casts = [

    ];


}
