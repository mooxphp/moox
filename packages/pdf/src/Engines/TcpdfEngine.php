<?php

declare(strict_types=1);

namespace Moox\Pdf\Engines;

use Moox\Pdf\Contracts\PdfDocument;
use Moox\Pdf\Exceptions\CouldNotRenderPdf;
use TCPDF;
use Throwable;

final class TcpdfEngine
{
    public static function packageRoot(): string
    {
        return self::canonicalPath(dirname(__DIR__, 2));
    }

    public static function fontPath(): string
    {
        return self::canonicalPath(self::packageRoot().DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'fonts');
    }

    /**
     * @return list<string>
     */
    public static function allowedPaths(): array
    {
        $logicalRoot = dirname(__DIR__, 2);
        $candidates = [
            $logicalRoot,
            $logicalRoot.DIRECTORY_SEPARATOR.'resources',
            $logicalRoot.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'fonts',
            self::packageRoot(),
            self::fontPath(),
        ];

        if (function_exists('config')) {
            $extra = config('pdf.allowed_paths', []);
            if (is_array($extra)) {
                foreach ($extra as $path) {
                    if (is_string($path) && $path !== '') {
                        $candidates[] = $path;
                    }
                }
            }
        }

        $paths = [];
        foreach ($candidates as $candidate) {
            $normalized = rtrim($candidate, '/\\');
            if ($normalized === '') {
                continue;
            }

            $paths[] = $normalized;

            $resolved = realpath($normalized);
            if ($resolved !== false) {
                $paths[] = $resolved;
            }
        }

        return array_values(array_unique($paths));
    }

    public static function registerFontPath(): void
    {
        if (! defined('K_PATH_FONTS')) {
            define('K_PATH_FONTS', self::fontPath());
        }
    }

    public function render(PdfDocument $document): string
    {
        self::registerFontPath();

        if (! defined('K_ALLOWED_PATHS')) {
            define('K_ALLOWED_PATHS', self::allowedPaths());
        }

        try {
            $tcpdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $tcpdf->setPrintHeader(false);
            $tcpdf->setPrintFooter(false);
            $tcpdf->SetCreator('moox/pdf');
            $tcpdf->SetTitle('PDF');
            $tcpdf->SetMargins(20, 16, 18);
            $tcpdf->SetAutoPageBreak(false, 0);
            $tcpdf->AddPage();
            $tcpdf->SetFont('dejavusans', '', 10);

            $document->draw(new TcpdfCanvas($tcpdf));

            $output = $tcpdf->Output('', 'S');
        } catch (CouldNotRenderPdf $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new CouldNotRenderPdf($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        if ($output === '' || ! str_starts_with($output, '%PDF-')) {
            throw new CouldNotRenderPdf('TCPDF produced an empty or invalid PDF.');
        }

        return $output;
    }

    private static function canonicalPath(string $path): string
    {
        $path = rtrim($path, '/\\');
        $resolved = realpath($path);

        return $resolved !== false ? $resolved : $path;
    }
}
