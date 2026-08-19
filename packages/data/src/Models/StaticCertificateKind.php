<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Moox\Core\Entities\Items\Static\BaseStaticModel;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticCertificateKind extends BaseStaticModel
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_certificate_kinds';

    protected $fillable = [
        'code',
        'is_normative',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_normative' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_normative' => 'boolean',
        ];
    }
}
