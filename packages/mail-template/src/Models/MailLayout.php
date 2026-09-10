<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\MailTemplate\Database\Factories\MailLayoutFactory;

class MailLayout extends BaseDraftModel
{
    use HasFactory;

    protected $table = 'mail_layouts';

    /**
     * @return list<string>
     */
    protected function getCustomTranslatedAttributes(): array
    {
        return [
            'title',
            'logo',
            'footer',
        ];
    }

    protected $fillable = [
        'slug',
        'status',
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

    protected static function newFactory(): MailLayoutFactory
    {
        return MailLayoutFactory::new();
    }
}
