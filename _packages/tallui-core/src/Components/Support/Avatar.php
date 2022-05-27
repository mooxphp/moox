<?php

declare(strict_types=1);

namespace TallUiCore\Components\Support;

use TallUiCore\Components\BladeComponent;
use Illuminate\Contracts\View\View;

class Avatar extends BladeComponent
{
    /** @var string */
    public $search;

    /** @var string */
    public $src;

    /** @var string */
    public $provider;

    /** @var string */
    public $fallback;

    public function __construct(string $search, string $src = '', string $provider = '', string $fallback = '')
    {
        $this->search = $search;
        $this->src = $src;
        $this->provider = $provider;
        $this->fallback = $fallback;
    }

    public function render(): View
    {
        return view('tallui-core::components.support.avatar');
    }

    public function url(): string
    {
        if ($this->src) {
            return $this->src;
        }

        $query = http_build_query(array_filter([
            'fallback' => $this->fallback,
        ]));

        if ($this->provider) {
            return sprintf('https://unavatar.now.sh/%s/%s?%s', $this->provider, $this->search, $query);
        }

        return sprintf('https://unavatar.now.sh/%s?%s', $this->search, $query);
    }
}
