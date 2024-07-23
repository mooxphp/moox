<?php

namespace Moox\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingType extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'description'];

    protected $searchableFields = ['*'];

    protected $table = 'training_types';

    public function trainings()
    {
        return $this->hasMany(Training::class);
    }
}
