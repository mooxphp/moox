<?php

declare(strict_types=1);

namespace Moox\MailTesting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Moox\MailTesting\Enums\Engine;
use Moox\MailTesting\Enums\PersistBackend;
use Moox\MailTesting\Enums\RunStatus;

/**
 * @property int $id
 * @property RunStatus $status
 * @property Engine $engine
 * @property PersistBackend $persist_backend
 * @property int $count
 * @property int $processed
 * @property array<string, mixed> $options
 * @property string $options_fingerprint
 * @property int $compose_ms
 * @property int $convert_ms
 * @property int $persist_ms
 * @property int $generation_ms
 * @property int $total_ms
 * @property string|null $error
 */
class MailTestingRun extends Model
{
    protected $table = 'mail_testing_runs';

    protected $fillable = [
        'status',
        'engine',
        'persist_backend',
        'count',
        'processed',
        'options',
        'options_fingerprint',
        'compose_ms',
        'convert_ms',
        'persist_ms',
        'generation_ms',
        'total_ms',
        'error',
        'started_at',
        'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RunStatus::class,
            'engine' => Engine::class,
            'persist_backend' => PersistBackend::class,
            'options' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (MailTestingRun $run): void {
            $run->deleteStoredHtml();
        });
    }

    /**
     * @return HasMany<MailTestingMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(MailTestingMessage::class);
    }

    public function deleteStoredHtml(): void
    {
        if (! $this->exists) {
            return;
        }

        $disk = Storage::disk((string) config('mail-testing.disk', 'local'));
        $disk->deleteDirectory(self::htmlRelativeDirectory($this->getKey()));
    }

    public function purge(): void
    {
        $this->deleteStoredHtml();
        $this->messages()->delete();
        $this->delete();
    }

    public static function purgeAll(): void
    {
        Storage::disk((string) config('mail-testing.disk', 'local'))
            ->deleteDirectory('mail-testing');

        MailTestingMessage::query()->delete();
        self::query()->delete();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public static function fingerprint(int $count, PersistBackend $persist, array $options): string
    {
        ksort($options);

        return hash('sha1', json_encode([
            'count' => $count,
            'persist' => $persist->value,
            'options' => $options,
        ], JSON_THROW_ON_ERROR));
    }

    public static function htmlRelativeDirectory(int|string $runId): string
    {
        return 'mail-testing/'.$runId;
    }

    public static function htmlRelativePath(int|string $runId, int $position): string
    {
        return self::htmlRelativeDirectory($runId).'/'.$position.'.html';
    }

    public function htmlDirectoryPath(): ?string
    {
        if ($this->persist_backend !== PersistBackend::Storage || ! $this->exists) {
            return null;
        }

        return Storage::disk((string) config('mail-testing.disk', 'local'))
            ->path(self::htmlRelativeDirectory($this->getKey()));
    }
}
