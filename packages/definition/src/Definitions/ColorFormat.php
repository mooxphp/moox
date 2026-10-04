<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

enum ColorFormat: string
{
    case Hex = 'hex';
    case Rgb = 'rgb';
    case Rgba = 'rgba';
    case Hsl = 'hsl';
    case Hsla = 'hsla';
}
