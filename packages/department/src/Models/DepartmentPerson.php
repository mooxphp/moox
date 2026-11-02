<?php

declare(strict_types=1);

namespace Moox\Department\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Relations\HasRelations;

class DepartmentPerson extends Model
{
    use HasRelations;

    public $timestamps = false;

    protected $table = 'department_persons';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'department_id',
        'person_id',
        'position',
    ];

    public static function getResourceName(): string
    {
        return 'department-person';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'department_id' => 'integer',
            'person_id' => 'integer',
        ];
    }
}
