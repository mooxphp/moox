<?php

declare(strict_types=1);

namespace Moox\Restore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Moox\Restore\Database\Factories\RestoreDestinationFactory;
use Spatie\BackupServer\Models\Source;

/**
 * @property int $id
 * @property string $host
 * @property int $source_id
 * @property array<string, mixed>|null $env_data
 * @property-read Source|null $source
 */
class RestoreDestination extends Model
{
    /** @use HasFactory<RestoreDestinationFactory> */
    use HasFactory;

    protected $table = 'restore_destinations';

    protected $fillable = [
        'host',
        'source_id',
        'env_data',
    ];

    protected $casts = [
        'env_data' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (RestoreDestination $model): void {
            if (isset($model->env_data['DB_PASSWORD']) && is_string($model->env_data['DB_PASSWORD'])) {
                $envData = $model->env_data;
                $envData['DB_PASSWORD'] = Crypt::encryptString($envData['DB_PASSWORD']);
                $model->env_data = $envData;
            }
        });

        static::retrieved(function (RestoreDestination $model): void {
            if (isset($model->env_data['DB_PASSWORD']) && is_string($model->env_data['DB_PASSWORD'])) {
                $envData = $model->env_data;
                $envData['DB_PASSWORD'] = Crypt::decryptString($envData['DB_PASSWORD']);
                $model->env_data = $envData;
            }
        });
    }

    /**
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    protected static function newFactory(): RestoreDestinationFactory
    {
        return RestoreDestinationFactory::new();
    }
}
