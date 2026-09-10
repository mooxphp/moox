<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\MailTemplate\Database\Factories\MailLayoutFactory;
use Moox\MailTemplate\Models\Concerns\HasMailLogo;

class MailLayout extends BaseDraftModel
{
    use HasFactory;
    use HasMailLogo;

    protected $table = 'mail_layouts';

    /**
     * @return list<string>
     */
    protected function getCustomTranslatedAttributes(): array
    {
        return [
            'title',
            'footer',
        ];
    }

    protected $fillable = [
        'slug',
        'status',
        'logo',
        'background_color',
        'button_color',
        'text_color',
        'uuid',
        'ulid',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(MailTemplate::class);
    }

    public function isReferencedByTemplates(): bool
    {
        return $this->templates()->withTrashed()->exists();
    }

    protected static function booted(): void
    {
        static::deleting(function (MailLayout $layout): bool {
            return ! $layout->isReferencedByTemplates();
        });
    }

    protected static function newFactory(): MailLayoutFactory
    {
        return MailLayoutFactory::new();
    }
}
