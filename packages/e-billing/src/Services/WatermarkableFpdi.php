<?php

declare(strict_types=1);

namespace Moox\EBilling\Services;

use setasign\Fpdi\Fpdi;

/**
 * FPDI with rotation and soft alpha for a single diagonal copy watermark.
 *
 * @phpstan-type ExtGState array{params: array<string, float|string>, n?: int}
 */
final class WatermarkableFpdi extends Fpdi
{
    private float $angle = 0.0;

    /** @var array<int, ExtGState> */
    private array $extgstates = [];

    public function rotate(float $angle, float $x, float $y): void
    {
        if ($this->angle !== 0.0) {
            $this->_out('Q');
        }

        $this->angle = $angle;

        if ($angle === 0.0) {
            return;
        }

        $radians = $angle * M_PI / 180;
        $c = cos($radians);
        $s = sin($radians);
        $cx = $x * $this->k;
        $cy = ($this->h - $y) * $this->k;

        $this->_out(sprintf(
            'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
            $c,
            $s,
            -$s,
            $c,
            $cx,
            $cy,
            -$cx,
            -$cy,
        ));
    }

    public function endRotate(): void
    {
        if ($this->angle === 0.0) {
            return;
        }

        $this->_out('Q');
        $this->angle = 0.0;
    }

    public function setAlpha(float $alpha): void
    {
        $gs = $this->addExtGState([
            'ca' => $alpha,
            'CA' => $alpha,
            'BM' => '/Normal',
        ]);

        $this->_out(sprintf('/GS%d gs', $gs));
    }

    protected function _endpage(): void
    {
        if ($this->angle !== 0.0) {
            $this->angle = 0.0;
            $this->_out('Q');
        }

        parent::_endpage();
    }

    /**
     * @param  array<string, float|string>  $params
     */
    private function addExtGState(array $params): int
    {
        $n = count($this->extgstates) + 1;
        $this->extgstates[$n] = ['params' => $params];

        return $n;
    }

    protected function _putextgstates(): void
    {
        foreach ($this->extgstates as $i => $state) {
            $this->_newobj();
            $this->extgstates[$i]['n'] = $this->n;
            $this->_put('<</Type /ExtGState');

            foreach ($state['params'] as $key => $value) {
                if (is_string($value) && str_starts_with($value, '/')) {
                    $this->_put('/'.$key.' '.$value);
                } else {
                    $this->_put(sprintf('/%s %.3F', $key, (float) $value));
                }
            }

            $this->_put('>>');
            $this->_put('endobj');
        }
    }

    protected function _putresourcedict(): void
    {
        parent::_putresourcedict();

        if ($this->extgstates === []) {
            return;
        }

        $this->_put('/ExtGState <<');

        foreach ($this->extgstates as $k => $state) {
            $this->_put(sprintf('/GS%d %d 0 R', $k, $state['n'] ?? 0));
        }

        $this->_put('>>');
    }

    protected function _putresources(): void
    {
        $this->_putextgstates();
        parent::_putresources();
    }
}
