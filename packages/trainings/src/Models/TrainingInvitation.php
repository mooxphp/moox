<?php

namespace Moox\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Moox\Press\Models\WpUser;

class TrainingInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id',
        'title',
        'slug',
        'content',
        'status',
    ];

    protected $searchableFields = ['*'];

    protected $table = 'training_invitations';

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function trainingDates()
    {
        return $this->hasMany(TrainingDate::class);
    }

    public function users()
    {
        return $this->morphedByMany(WpUser::class, 'training_invitationable');
    }
}
