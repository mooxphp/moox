<?php

declare(strict_types=1);

namespace Moox\MailTesting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $mail_testing_run_id
 * @property int $position
 * @property string $html_hash
 * @property int $byte_length
 * @property int $compose_ms
 * @property int $convert_ms
 * @property int $persist_ms
 * @property string|null $storage_path
 * @property string|null $html
 */
class MailTestingMessage extends Model
{
    protected $table = 'mail_testing_messages';

    protected $fillable = [
        'mail_testing_run_id',
        'position',
        'html_hash',
        'byte_length',
        'compose_ms',
        'convert_ms',
        'persist_ms',
        'storage_path',
        'html',
    ];

    protected static function booted(): void
    {
        static::deleting(function (MailTestingMessage $message): void {
            $message->deleteStoredHtml();
        });
    }

    /**
     * @return BelongsTo<MailTestingRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(MailTestingRun::class, 'mail_testing_run_id');
    }

    public function deleteStoredHtml(): void
    {
        if (! is_string($this->storage_path) || $this->storage_path === '') {
            return;
        }

        $disk = Storage::disk((string) config('mail-testing.disk', 'local'));

        if ($disk->exists($this->storage_path)) {
            $disk->delete($this->storage_path);
        }
    }

    public function resolvedHtml(): ?string
    {
        if (is_string($this->html) && $this->html !== '') {
            return $this->html;
        }

        if (! is_string($this->storage_path) || $this->storage_path === '') {
            return null;
        }

        $disk = (string) config('mail-testing.disk', 'local');

        if (! Storage::disk($disk)->exists($this->storage_path)) {
            return null;
        }

        $contents = Storage::disk($disk)->get($this->storage_path);

        return is_string($contents) ? $contents : null;
    }
}
