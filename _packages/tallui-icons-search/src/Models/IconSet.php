<?php

declare(strict_types=1);

namespace Usetall\TalluiIconsSearch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Sushi\Sushi;

/**
 * @property string $name
 * @property string $ignore_rule
 * @property string $outline_rule
 */
class IconSet extends Model
{
    use Sushi;

    public $timestamps = false;

    protected $guarded = [];

    /** @var array<mixed> */
    protected array $rows = [
        [
            'id' => 1,
            'name' => 'tallui-flags-circle',
            'repository' => 'https://github.com/usetall/tallui-flags-circle',
            'composer' => 'usetall/tallui-flags-circle',
            'ignore_rule' => '/^(?:o|s)-/',
            'outline_rule' => '/^o-/',
        ],
        [
            'id' => 2,
            'name' => 'tallui-flags-origin',
            'repository' => 'https://github.com/usetall/tallui-flags-origin',
            'composer' => 'usetall/tallui-flags-origin',
            'ignore_rule' => '/^(?:o|s)-/',
            'outline_rule' => '/^o-/',
        ],
        [
            'id' => 3,
            'name' => 'tallui-flags-rect',
            'repository' => 'https://github.com/usetall/tallui-flags-rect',
            'composer' => 'usetall/tallui-flags-rect',
            'ignore_rule' => '/^(?:o|s)-/',
            'outline_rule' => '/^o-/',
        ],
        [
            'id' => 4,
            'name' => 'tallui-flags-square',
            'repository' => 'https://github.com/usetall/tallui-flags-square',
            'composer' => 'usetall/tallui-flags-square',
            'ignore_rule' => '/^(?:o|s)-/',
            'outline_rule' => '/^o-/',
        ],
    ];

    public function name(): string
    {
        return (string) Str::of($this->name)->replace('-', ' ')->title();
    }
}
