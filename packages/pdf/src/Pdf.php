<?php

declare(strict_types=1);

namespace Moox\Pdf;

use Illuminate\Http\Response;
use Moox\Pdf\Contracts\PdfDocument;
use Moox\Pdf\Engines\TcpdfEngine;
use Moox\Pdf\Exceptions\CouldNotRenderPdf;

class Pdf
{
    private ?PdfDocument $document = null;

    public static function new(): self
    {
        return new self;
    }

    public function render(PdfDocument $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function output(): string
    {
        return (new TcpdfEngine)->render($this->requiredDocument());
    }

    public function inline(string $filename = 'document.pdf'): Response
    {
        $safeName = str_replace(['"', "\r", "\n"], '', basename($filename));

        return response($this->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$safeName.'"',
        ]);
    }

    private function requiredDocument(): PdfDocument
    {
        if ($this->document === null) {
            throw new CouldNotRenderPdf('Call render() with a PdfDocument before output().');
        }

        return $this->document;
    }
}
