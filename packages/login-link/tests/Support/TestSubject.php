<?php

declare(strict_types=1);

namespace Moox\LoginLink\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class TestSubject extends Model
{
    protected $table = 'test_subjects';

    protected $fillable = [
        'name',
        'email',
    ];
}
