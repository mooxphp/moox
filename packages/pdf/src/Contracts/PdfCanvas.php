<?php

declare(strict_types=1);

namespace Moox\Pdf\Contracts;

interface PdfCanvas
{
    public function width(): float;

    public function height(): float;

    public function leftMargin(): float;

    public function rightMargin(): float;

    public function x(): float;

    public function y(): float;

    public function remainingHeight(): float;

    public function stringHeight(float $width, string $text): float;

    public function setPosition(float $x, float $y): self;

    public function setMargins(float $left, float $top, float $right, float $bottom = 0): self;

    public function setAutoPageBreak(bool $enabled, float $margin = 0): self;

    public function setCellPaddings(float $left, float $top, float $right, float $bottom): self;

    public function setFont(string $family, string $style = '', float $size = 10): self;

    public function setTextColor(int $r, int $g, int $b): self;

    public function setDrawColor(int $r, int $g, int $b): self;

    public function setFillColor(int $r, int $g, int $b): self;

    public function setLineWidth(float $width): self;

    public function text(float $x, float $y, string $text): self;

    /**
     * @param  array{align?: string, fill?: bool, border?: int|string, valign?: string}  $options
     */
    public function multiCell(float $width, float $lineHeight, string $text, array $options = []): self;

    public function image(string $path, float $x, float $y, float $width, float $height = 0): self;

    public function svg(string $path, float $x, float $y, float $width, float $height = 0): self;

    public function line(float $x1, float $y1, float $x2, float $y2): self;

    /**
     * @param  callable(self): void  $draw
     */
    public function rotate(float $angle, float $x, float $y, callable $draw): self;

    public function addPage(): self;

    public function page(): int;

    public function pageCount(): int;

    public function usePage(int $page): self;

    public function pageAlias(): string;

    public function totalPagesAlias(): string;

    /**
     * @param  list<list<string>>  $rows
     * @param  array{
     *     widths?: list<float>,
     *     header?: bool,
     *     fontSize?: float,
     *     lineHeight?: float,
     *     border?: int|string,
     *     align?: string|list<string>|list<list<string>>,
     *     headerAlign?: string|list<string>|list<list<string>>|null,
     *     headerFill?: array{0: int, 1: int, 2: int},
     *     headerColor?: array{0: int, 1: int, 2: int},
     *     fill?: array{0: int, 1: int, 2: int},
     *     altFill?: array{0: int, 1: int, 2: int},
     *     textColor?: array{0: int, 1: int, 2: int},
     *     beforePageBreak?: callable(PdfCanvas): void,
     *     afterPageBreak?: callable(PdfCanvas): void
     * }  $options
     */
    public function table(array $rows, array $options = []): self;

    public function html(string $html): self;
}
