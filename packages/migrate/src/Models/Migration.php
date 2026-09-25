<?php

declare(strict_types=1);

namespace Moox\Migrate\Models;

use Moox\Core\Entities\Items\Item\BaseItemModel;

class Migration extends BaseItemModel
{
    protected $table = 'migrations';

    public $timestamps = false;

    protected $fillable = [
        'migration',
        'batch',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'batch' => 'integer',
        ];
    }

    public static function getResourceName(): string
    {
        return 'migration';
    }
}
