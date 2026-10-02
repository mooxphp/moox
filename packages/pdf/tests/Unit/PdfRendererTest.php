<?php

declare(strict_types=1);

use Moox\Pdf\Contracts\PdfCanvas;
use Moox\Pdf\Contracts\PdfDocument;
use Moox\Pdf\Engines\TcpdfCanvas;
use Moox\Pdf\Engines\TcpdfEngine;
use Moox\Pdf\Exceptions\CouldNotRenderPdf;
use Moox\Pdf\Pdf;

function publicPdfSources(): array
{
    $src = dirname(__DIR__, 2).'/src';

    return [
        $src.'/Pdf.php',
        $src.'/PdfServiceProvider.php',
        $src.'/Contracts/PdfCanvas.php',
        $src.'/Contracts/PdfDocument.php',
        $src.'/Exceptions/CouldNotRenderPdf.php',
    ];
}

function fixturePdfDocument(): PdfDocument
{
    return new class implements PdfDocument
    {
        public function draw(PdfCanvas $canvas): void
        {
            $canvas
                ->setFont('dejavusans', '', 12)
                ->text($canvas->leftMargin(), 40, 'Fixture');
        }
    };
}

it('registers canonical font paths that tcpdf can allowlist', function (): void {
    TcpdfEngine::registerFontPath();

    $helvetica = TcpdfEngine::fontPath().DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'helvetica.json';
    $resolvedHelvetica = realpath($helvetica);

    expect(is_file($helvetica))->toBeTrue()
        ->and($resolvedHelvetica)->toBeString()
        ->and(str_contains($helvetica, DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR.'core'))->toBeFalse();

    $normalizedFile = strtolower(str_replace('\\', '/', $resolvedHelvetica));
    $roots = array_map(
        static function (string $path): string {
            $resolved = realpath($path) ?: $path;

            return strtolower(str_replace('\\', '/', rtrim($resolved, '/\\')));
        },
        TcpdfEngine::allowedPaths(),
    );

    expect(
        collect($roots)->contains(
            fn (string $root): bool => $normalizedFile === $root || str_starts_with($normalizedFile, $root.'/'),
        ),
    )->toBeTrue();

    expect(realpath(TcpdfEngine::fontPath()))->toBe(realpath(base_path('packages/pdf/resources/fonts')));
});

it('can read helvetica.json through tcpdfs realpath file allowlist', function (): void {
    TcpdfEngine::registerFontPath();

    $paths = [];
    foreach (TcpdfEngine::allowedPaths() as $candidate) {
        $real = realpath($candidate);
        if ($real !== false) {
            $paths[] = $real;
        }
    }

    $file = new Com\Tecnick\File\File(allowedPaths: array_values(array_unique($paths)));
    $helvetica = TcpdfEngine::fontPath().DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'helvetica.json';

    expect($file->getFileData($helvetica))
        ->toBeString()
        ->toContain('"type"');
});

it('renders a pdf binary from a fixture document', function (): void {
    $output = Pdf::new()->render(fixturePdfDocument())->output();

    expect($output)
        ->toStartWith('%PDF-')
        ->and(strlen($output))->toBeGreaterThan(1000);
});

it('returns an inline pdf response', function (): void {
    $response = Pdf::new()->render(fixturePdfDocument())->inline('fixture.pdf');

    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain('inline')
        ->and($response->headers->get('Content-Disposition'))->toContain('fixture.pdf')
        ->and($response->getContent())->toStartWith('%PDF-');
});

it('raises when output is called before render', function (): void {
    expect(fn (): string => Pdf::new()->output())
        ->toThrow(CouldNotRenderPdf::class, 'Call render() with a PdfDocument before output().');
});

it('wraps document failures as CouldNotRenderPdf', function (): void {
    $document = new class implements PdfDocument
    {
        public function draw(PdfCanvas $canvas): void
        {
            throw new RuntimeException('layout failed');
        }
    };

    expect(fn (): string => Pdf::new()->render($document)->output())
        ->toThrow(CouldNotRenderPdf::class, 'layout failed');
});

it('renders a styled table with header fill and page break hooks', function (): void {
    $document = new class implements PdfDocument
    {
        public function draw(PdfCanvas $canvas): void
        {
            $canvas
                ->setMargins(20, 16, 18, 16)
                ->setPosition($canvas->leftMargin(), 40)
                ->table([
                    ['A', 'B'],
                    ['1', '2'],
                    ["wraps onto\na second line", '4'],
                ], [
                    'header' => true,
                    'widths' => [40, 40],
                    'border' => 0,
                    'headerFill' => [0, 93, 157],
                    'headerColor' => [255, 255, 255],
                    'fill' => [255, 255, 255],
                    'altFill' => [242, 242, 242],
                    'align' => [
                        ['C', 'C'],
                        ['L', 'R'],
                        ['L', 'R'],
                    ],
                    'beforePageBreak' => function (PdfCanvas $canvas): void {
                        $canvas->text($canvas->leftMargin(), $canvas->height() - 10, $canvas->pageAlias());
                    },
                    'afterPageBreak' => function (PdfCanvas $canvas): void {
                        $canvas->setPosition($canvas->leftMargin(), 16);
                    },
                ]);
        }
    };

    $output = Pdf::new()->render($document)->output();

    expect($output)
        ->toStartWith('%PDF-')
        ->and(strlen($output))->toBeGreaterThan(1000);
});

it('resolves table cell alignment from a string, columns or a row/column grid', function (): void {
    $canvas = (new ReflectionClass(TcpdfCanvas::class))->newInstanceWithoutConstructor();
    $alignValue = new ReflectionMethod(TcpdfCanvas::class, 'alignValue');
    $cellAlign = new ReflectionMethod(TcpdfCanvas::class, 'cellAlign');
    $alignValue->setAccessible(true);
    $cellAlign->setAccessible(true);

    expect($alignValue->invoke($canvas, 'C', 2, 1))->toBe('C')
        ->and($alignValue->invoke($canvas, ['L', 'R'], 0, 1))->toBe('R')
        ->and($alignValue->invoke($canvas, [['C', 'C'], ['L', 'R']], 1, 0))->toBe('L')
        ->and($cellAlign->invoke($canvas, 'L', 0, 0, true, 'C'))->toBe('C')
        ->and($cellAlign->invoke($canvas, 'L', 1, 0, false, 'C'))->toBe('L')
        ->and($cellAlign->invoke($canvas, [['C', 'C'], ['L', 'R']], 1, 1, false, null))->toBe('R');
});

it('paints each explicit table cell line as its own zebra row', function (): void {
    $document = new class implements PdfDocument
    {
        public function draw(PdfCanvas $canvas): void
        {
            $canvas
                ->setMargins(20, 16, 18, 16)
                ->setPosition($canvas->leftMargin(), 40)
                ->table([
                    ['Bezeichnung', 'Eigenschaften'],
                    ['Kolben', "max. 8 bar\noptional bis zu 15 bar (Edelstahl)"],
                    ['Getriebe', 'Stahl, nickel-beschichtet'],
                ], [
                    'header' => true,
                    'widths' => [40, 80],
                    'border' => 0,
                    'headerFill' => [0, 93, 157],
                    'headerColor' => [255, 255, 255],
                    'fill' => [255, 255, 255],
                    'altFill' => [242, 242, 242],
                    'align' => 'L',
                ]);
        }
    };

    $output = Pdf::new()->render($document)->output();

    expect($output)
        ->toStartWith('%PDF-')
        ->and(strlen($output))->toBeGreaterThan(1000);
});

it('writes html through tcpdf with automatic page breaks', function (): void {
    $document = new class implements PdfDocument
    {
        public function draw(PdfCanvas $canvas): void
        {
            $canvas
                ->setMargins(20, 16, 18, 16)
                ->setAutoPageBreak(true, 16)
                ->setFont('dejavusans', '', 12)
                ->html('<p>Hello <b>TCPDF</b> html</p><p>'.str_repeat('Absatz mit Umbruch. ', 80).'</p>');
        }
    };

    $output = Pdf::new()->render($document)->output();

    expect($output)
        ->toStartWith('%PDF-')
        ->and(strlen($output))->toBeGreaterThan(1000);
});

it('does not import tcpdf types on the public api', function (): void {
    foreach (publicPdfSources() as $path) {
        $source = file_get_contents($path);

        expect($source)
            ->not->toContain('use TCPDF')
            ->not->toContain('use Tecnick');
    }
});
