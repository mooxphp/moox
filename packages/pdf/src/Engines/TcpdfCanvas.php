<?php

declare(strict_types=1);

namespace Moox\Pdf\Engines;

use Moox\Pdf\Contracts\PdfCanvas;
use TCPDF;

final class TcpdfCanvas implements PdfCanvas
{
    public function __construct(private TCPDF $tcpdf)
    {
    }

    public function width(): float
    {
        return (float) $this->tcpdf->getPageWidth();
    }

    public function height(): float
    {
        return (float) $this->tcpdf->getPageHeight();
    }

    public function leftMargin(): float
    {
        return (float) $this->tcpdf->getMargins()['left'];
    }

    public function rightMargin(): float
    {
        return (float) $this->tcpdf->getMargins()['right'];
    }

    public function x(): float
    {
        return (float) $this->tcpdf->GetX();
    }

    public function y(): float
    {
        return (float) $this->tcpdf->GetY();
    }

    public function remainingHeight(): float
    {
        $bottom = (float) $this->tcpdf->getMargins()['bottom'];

        return $this->height() - $bottom - $this->y();
    }

    public function stringHeight(float $width, string $text): float
    {
        return (float) $this->tcpdf->getStringHeight($width, $text, false, true, null, 0);
    }

    public function setPosition(float $x, float $y): PdfCanvas
    {
        $this->tcpdf->SetXY($x, $y);

        return $this;
    }

    public function setMargins(float $left, float $top, float $right, float $bottom = 0): PdfCanvas
    {
        $this->tcpdf->setMargins($left, $top, $right);
        $this->tcpdf->setAutoPageBreak(false, $bottom);

        return $this;
    }

    public function setAutoPageBreak(bool $enabled, float $margin = 0): PdfCanvas
    {
        $this->tcpdf->setAutoPageBreak($enabled, $margin);

        return $this;
    }

    public function setCellPaddings(float $left, float $top, float $right, float $bottom): PdfCanvas
    {
        $this->tcpdf->setCellPaddings($left, $top, $right, $bottom);

        return $this;
    }

    public function setFont(string $family, string $style = '', float $size = 10): PdfCanvas
    {
        $this->tcpdf->SetFont($family, $style, $size);

        return $this;
    }

    public function setTextColor(int $r, int $g, int $b): PdfCanvas
    {
        $this->tcpdf->SetTextColor($r, $g, $b);

        return $this;
    }

    public function setDrawColor(int $r, int $g, int $b): PdfCanvas
    {
        $this->tcpdf->SetDrawColor($r, $g, $b);

        return $this;
    }

    public function setFillColor(int $r, int $g, int $b): PdfCanvas
    {
        $this->tcpdf->SetFillColor($r, $g, $b);

        return $this;
    }

    public function setLineWidth(float $width): PdfCanvas
    {
        $this->tcpdf->SetLineWidth($width);

        return $this;
    }

    public function text(float $x, float $y, string $text): PdfCanvas
    {
        $this->tcpdf->Text($x, $y, $text);

        return $this;
    }

    public function multiCell(float $width, float $lineHeight, string $text, array $options = []): PdfCanvas
    {
        $align = $this->tcpdfAlign($options['align'] ?? 'L');
        $border = $options['border'] ?? 0;
        $fill = (bool) ($options['fill'] ?? false);
        $valign = strtoupper((string) ($options['valign'] ?? 'T'));

        $this->tcpdf->MultiCell(
            $width,
            $lineHeight,
            $text,
            $border,
            $align,
            $fill,
            1,
            $this->tcpdf->GetX(),
            $this->tcpdf->GetY(),
            true,
            0,
            false,
            true,
            0,
            $valign,
        );

        return $this;
    }

    public function image(string $path, float $x, float $y, float $width, float $height = 0): PdfCanvas
    {
        $this->tcpdf->Image($path, $x, $y, $width, $height, '', '', '', false, 300);

        return $this;
    }

    public function svg(string $path, float $x, float $y, float $width, float $height = 0): PdfCanvas
    {
        $this->tcpdf->ImageSVG($path, $x, $y, $width, $height);

        return $this;
    }

    public function line(float $x1, float $y1, float $x2, float $y2): PdfCanvas
    {
        $this->tcpdf->Line($x1, $y1, $x2, $y2);

        return $this;
    }

    public function rotate(float $angle, float $x, float $y, callable $draw): PdfCanvas
    {
        $this->tcpdf->StartTransform();
        $this->tcpdf->Rotate($angle, $x, $y);
        $draw($this);
        $this->tcpdf->StopTransform();

        return $this;
    }

    public function addPage(): PdfCanvas
    {
        $this->tcpdf->AddPage();

        return $this;
    }

    public function page(): int
    {
        return $this->tcpdf->getPage();
    }

    public function pageCount(): int
    {
        return $this->tcpdf->getNumPages();
    }

    public function usePage(int $page): PdfCanvas
    {
        $this->tcpdf->setPage($page);

        return $this;
    }

    public function pageAlias(): string
    {
        return $this->tcpdf->getAliasNumPage();
    }

    public function totalPagesAlias(): string
    {
        return $this->tcpdf->getAliasNbPages();
    }

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
    public function table(array $rows, array $options = []): PdfCanvas
    {
        $widths = $options['widths'] ?? [];
        $hasHeader = (bool) ($options['header'] ?? false);
        $fontSize = (float) ($options['fontSize'] ?? 8);
        $lineHeight = (float) ($options['lineHeight'] ?? 5);
        $border = $options['border'] ?? 1;
        $align = $options['align'] ?? 'L';
        $headerRow = $hasHeader ? ($rows[0] ?? null) : null;
        $beforePageBreak = $options['beforePageBreak'] ?? null;
        $afterPageBreak = $options['afterPageBreak'] ?? null;
        $dataIndex = 0;

        $this->setFont('dejavusans', '', $fontSize);

        foreach ($rows as $index => $row) {
            $isHeader = $hasHeader && $index === 0;
            $visualRows = $isHeader ? [$row] : $this->visualTableRows($row);

            foreach ($visualRows as $visualRow) {
                $rowHeight = $this->tableRowHeight($visualRow, $widths, $lineHeight, $border);
                $isBlank = ! $isHeader && $this->isBlankTableRow($visualRow);

                if (! $isHeader && $this->remainingHeight() < $rowHeight + 2) {
                    if (is_callable($beforePageBreak)) {
                        $beforePageBreak($this);
                    }

                    $this->addPage();
                    $this->tcpdf->SetX($this->leftMargin());

                    if (is_callable($afterPageBreak)) {
                        $afterPageBreak($this);
                    }

                    if (is_array($headerRow)) {
                        $this->drawTableRow(
                            $headerRow,
                            $widths,
                            $this->tableRowHeight($headerRow, $widths, $lineHeight, $border),
                            true,
                            false,
                            $border,
                            $align,
                            0,
                            $options,
                        );
                    }
                }

                $this->drawTableRow(
                    $visualRow,
                    $widths,
                    $rowHeight,
                    $isHeader,
                    ! $isHeader && ! $isBlank && $dataIndex % 2 === 1,
                    $border,
                    $align,
                    $index,
                    $options,
                );

                if (! $isHeader && ! $isBlank) {
                    $dataIndex++;
                }
            }
        }

        return $this;
    }

    public function html(string $html): PdfCanvas
    {
        $this->tcpdf->writeHTML($html, true, false, true, false, '');

        return $this;
    }

    /**
     * @param  list<string>  $row
     * @return list<list<string>>
     */
    private function visualTableRows(array $row): array
    {
        $columns = [];
        $maxLines = 1;

        foreach ($row as $column => $cell) {
            $lines = explode("\n", (string) $cell);
            $columns[$column] = $lines;
            $maxLines = max($maxLines, count($lines));
        }

        $visualRows = [];

        for ($line = 0; $line < $maxLines; $line++) {
            $visualRow = [];

            foreach ($columns as $lines) {
                $visualRow[] = $lines[$line] ?? '';
            }

            $visualRows[] = $visualRow;
        }

        return $visualRows;
    }

    /**
     * @param  list<string>  $row
     */
    private function isBlankTableRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $row
     * @param  list<float>  $widths
     */
    private function tableRowHeight(array $row, array $widths, float $lineHeight, int|string $border): float
    {
        $height = $lineHeight;

        foreach ($row as $column => $cell) {
            $width = $widths[$column] ?? 30;
            $height = max(
                $height,
                (float) $this->tcpdf->getStringHeight($width, (string) $cell, false, true, null, $border),
            );
        }

        return $height;
    }

    /**
     * @param  list<string>  $row
     * @param  list<float>  $widths
     * @param  string|list<string>|list<list<string>>  $align
     * @param  array{
     *     headerFill?: array{0: int, 1: int, 2: int},
     *     headerColor?: array{0: int, 1: int, 2: int},
     *     fill?: array{0: int, 1: int, 2: int},
     *     altFill?: array{0: int, 1: int, 2: int},
     *     textColor?: array{0: int, 1: int, 2: int},
     *     headerAlign?: string|list<string>|list<list<string>>|null
     * }  $options
     */
    private function drawTableRow(
        array $row,
        array $widths,
        float $rowHeight,
        bool $header,
        bool $alt,
        int|string $border,
        string|array $align,
        int $rowIndex,
        array $options,
    ): void {
        $startX = $this->tcpdf->GetX();
        if ($startX < $this->leftMargin()) {
            $startX = $this->leftMargin();
            $this->tcpdf->SetX($startX);
        }

        $startY = $this->tcpdf->GetY();
        $headerFill = $options['headerFill'] ?? null;
        $headerColor = $options['headerColor'] ?? null;
        $fill = $options['fill'] ?? null;
        $altFill = $options['altFill'] ?? null;
        $textColor = $options['textColor'] ?? [29, 29, 27];
        $fillCell = false;

        if ($header && is_array($headerFill)) {
            $this->setFillColor(...$headerFill);
            $fillCell = true;
        } elseif ($alt && is_array($altFill)) {
            $this->setFillColor(...$altFill);
            $fillCell = true;
        } elseif (is_array($fill)) {
            $this->setFillColor(...$fill);
            $fillCell = true;
        } elseif ($header) {
            $fillCell = true;
        }

        if ($header && is_array($headerColor)) {
            $this->setTextColor(...$headerColor);
        } elseif (is_array($textColor)) {
            $this->setTextColor(...$textColor);
        }

        $this->setFont('dejavusans', $header ? 'B' : '', (float) $this->tcpdf->getFontSizePt());

        $x = $startX;
        foreach ($row as $column => $cell) {
            $width = $widths[$column] ?? 30;
            $cellAlign = $this->cellAlign($align, $rowIndex, $column, $header, $options['headerAlign'] ?? null);

            $cellText = (string) $cell;
            if ($cellText === '' && $fillCell) {
                $cellText = ' ';
            }

            $this->tcpdf->MultiCell(
                $width,
                $rowHeight,
                $cellText,
                $border,
                $cellAlign,
                $fillCell,
                0,
                $x,
                $startY,
                true,
                0,
                false,
                true,
                $rowHeight,
                'T',
            );
            $x += $width;
        }

        $this->tcpdf->SetXY($startX, $startY + max($rowHeight, (float) $this->tcpdf->getLastH()));
    }

    /**
     * @param  string|list<string>|list<list<string>>  $align
     * @param  string|list<string>|list<list<string>>|null  $headerAlign
     */
    private function cellAlign(string|array $align, int $rowIndex, int $column, bool $header, string|array|null $headerAlign): string
    {
        $source = $header && $headerAlign !== null ? $headerAlign : $align;
        $index = $header && $headerAlign !== null ? 0 : $rowIndex;

        return $this->tcpdfAlign($this->alignValue($source, $index, $column));
    }

    /**
     * @param  string|list<string>|list<list<string>>  $align
     */
    private function alignValue(string|array $align, int $rowIndex, int $column): string
    {
        if (is_string($align)) {
            return $align;
        }

        $first = $align[0] ?? 'L';
        if (is_array($first)) {
            $rowAlign = $align[$rowIndex] ?? $first;
            if (is_array($rowAlign)) {
                return (string) ($rowAlign[$column] ?? 'L');
            }

            return (string) $rowAlign;
        }

        return (string) ($align[$column] ?? 'L');
    }

    private function tcpdfAlign(string $align): string
    {
        return match (strtoupper($align)) {
            'C', 'CENTER' => 'C',
            'R', 'RIGHT' => 'R',
            'J', 'JUSTIFY' => 'J',
            default => 'L',
        };
    }
}
