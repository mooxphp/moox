<?php

namespace Moox\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Moox\Press\Models\WpUser;

class TrainingDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_invitation_id',
        'begin',
        'end',
        'type',
        'link',
        'location',
        'min_participants',
        'max_participants',
        'sent_at',
    ];

    protected $searchableFields = ['*'];

    protected $table = 'training_dates';

    protected $casts = [
        'begin' => 'datetime',
        'end' => 'datetime',
    ];

    public function trainingInvitation()
    {
        return $this->belongsTo(TrainingInvitation::class);
    }

    public function users()
    {
        return $this->morphedByMany(WpUser::class, 'training_dateable');
    }
}
