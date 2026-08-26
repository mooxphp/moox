<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Moox\MailOutbox\Database\Factories\MailTemplateFactory;

class MailTemplate extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'mail_templates';

    protected $fillable = [
        'key',
        'locale',
        'view',
        'brand_name',
        'subject',
        'logo_path',
        'mail_content',
        'footer',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (! filled($this->logo_path)) {
            return null;
        }

        $path = (string) $this->logo_path;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    protected static function newFactory(): MailTemplateFactory
    {
        return MailTemplateFactory::new();
    }
}
