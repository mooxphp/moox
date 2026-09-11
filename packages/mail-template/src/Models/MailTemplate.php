<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\MailTemplate\Database\Factories\MailTemplateFactory;
use Moox\MailTemplate\Models\Concerns\HasMailLogo;

/**
 * @property string $slug
 * @property int $mail_layout_id
 * @property-read MailLayout|null $mailLayout
 * @property-read Collection<int, MailTemplateTranslation> $translations
 */
class MailTemplate extends BaseDraftModel
{
    use HasFactory;
    use HasMailLogo;

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
        'mail_layout_id',
        'logo',
        'status',
        'uuid',
        'ulid',
    ];

    /**
     * @return BelongsTo<MailLayout, $this>
     */
    public function mailLayout(): BelongsTo
    {
        return $this->belongsTo(MailLayout::class)->withTrashed();
    }

    protected static function newFactory(): MailTemplateFactory
    {
        return MailTemplateFactory::new();
    }
}
