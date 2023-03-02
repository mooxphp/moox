<?php

declare(strict_types=1);

namespace Usetall\TalluiIconsSearch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Sushi\Sushi;

class IconSet extends Model
{
    use Sushi;

    public $timestamps = false;

    protected $guarded = [];

    protected array $rows = [
        [
            'id' => 1,
            'name' => 'tallui-flags-round',
            'repository' => 'https://github.com/usetall/tallui-flags-round',
            'composer' => 'usetall/tallui-flags-round',
            'ignore_rule' => '/^(?:o|s)-/',
            'outline_rule' => '/^o-/',
        ],
    ];

    public function name(): string
    {
        return (string) Str::of($this->name)->replace('-', ' ')->title();
    }
}
