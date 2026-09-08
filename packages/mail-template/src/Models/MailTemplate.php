<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\MailTemplate\Database\Factories\MailTemplateFactory;

class MailTemplate extends BaseDraftModel
{
    use HasFactory;

    protected $table = 'mail_templates';

    /**
     * @return list<string>
     */
    protected function getCustomTranslatedAttributes(): array
    {
        return [
            'title',
            'mail_content',
            'footer',
        ];
    }

    protected $fillable = [
        'slug',
        'layout',
        'logo_path',
        'status',
        'uuid',
        'ulid',
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
