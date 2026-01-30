<?php

namespace Moox\Prompts\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property object|null $createdBy Relation (morphTo), bei Zugriff geladenes Model oder null
 */
class CommandExecution extends Model
{
    protected $fillable = [
        'flow_id',
        'command_name',
        'command_description',
        'status',
        'started_at',
        'completed_at',
        'failed_at',
        'failed_at_step',
        'cancelled_at',
        'cancelled_at_step',
        'error_message',
        'steps',
        'step_outputs',
        'context',
        'created_by_type',
        'created_by_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'steps' => 'array',
        'step_outputs' => 'array',
    ];

    public function createdBy()
    {
        return $this->morphTo();
    }
}
