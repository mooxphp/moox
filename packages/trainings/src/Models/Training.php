<?php

namespace Moox\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Moox\Press\Models\WpUser;

class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'duration',
        'link',
        'due_at',
        'cycle',
        'source_id',
        'training_type_id',
        'trainingable_id',
        'trainingable_type',
    ];

    protected $searchableFields = ['*'];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public function trainingInvitations()
    {
        return $this->hasMany(TrainingInvitation::class);
    }

    public function trainingType()
    {
        return $this->belongsTo(TrainingType::class);
    }

    public function users()
    {
        return $this->morphedByMany(WpUser::class, 'trainingable');
    }
}
