<?php

declare(strict_types=1);

namespace Moox\Static\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Moox\Core\Entities\Items\Static\BaseStaticModel;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Static\Database\Factories\StaticEntryFactory;

class StaticEntry extends BaseStaticModel
{
    use BaseInModel, HasFactory;

    protected $fillable = [
        'code',
    ];

    public static function getResourceName(): string
    {
        return 'static_entry';
    }

    protected static function newFactory(): StaticEntryFactory
    {
        return StaticEntryFactory::new();
    }
}
