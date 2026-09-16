<?php

declare(strict_types=1);

namespace Moox\Mjml\Contracts;

use Moox\Mjml\MjmlResult;

interface MjmlRenderer
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function convert(string $mjml, array $options = []): MjmlResult;

    /**
     * @param  array<string, mixed>  $options
     */
    public function toHtml(string $mjml, array $options = []): string;
}
