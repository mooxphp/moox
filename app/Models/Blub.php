<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blub extends Model
{
    protected $table = 'blubs';

    protected $fillable = [
        'title', 'slug'
    ];
}
