<?php

declare(strict_types=1);

namespace Moox\Restore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Moox\Restore\Database\Factories\RestoreDestinationFactory;
use Spatie\BackupServer\Models\Source;

class RestoreDestination extends Model
{
    use HasFactory;

    // The table name, in case it's not the default plural of the model name
    protected $table = 'restore_destinations';

    protected $fillable = [
        'host',
        'source_id',
        'env_data',
    ];

    protected $casts = [
        'env_data' => 'array',
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            if (isset($model->env_data['DB_PASSWORD'])) {
                $envData = $model->env_data;
                $envData['DB_PASSWORD'] = Crypt::encryptString($envData['DB_PASSWORD']);
                $model->env_data = $envData;
            }
        });

        static::retrieved(function ($model) {
            if (isset($model->env_data['DB_PASSWORD'])) {
                $envData = $model->env_data;
                $envData['DB_PASSWORD'] = Crypt::decryptString($envData['DB_PASSWORD']);
                $model->env_data = $envData;
            }
        });
    }

    /**
     * A deploy destination can have many backups deployed to it.
     */
    public function source()
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    protected static function newFactory()
    {
        return RestoreDestinationFactory::new();
    }
}
