<?php

declare(strict_types=1);
use Com\Tecnick\Pdf\Font\Import;

$packageRoot = dirname(__DIR__);
$hostRoot = dirname($packageRoot, 2);
$mirror = $hostRoot.'/vendor/tecnickcom/tc-lib-pdf-font/util/vendor/tecnickcom/tc-font-mirror/';
$outRoot = $packageRoot.'/resources/fonts/';

require $hostRoot.'/vendor/autoload.php';

$families = [
    'core' => ['type' => '', 'needed' => null],
    'dejavu' => ['type' => '', 'needed' => ['DejaVuSans.ttf', 'DejaVuSans-Bold.ttf', 'DejaVuSans-Oblique.ttf', 'DejaVuSans-BoldOblique.ttf']],
];

foreach ($families as $family => $config) {
    $indir = $mirror.$family;
    $outdir = $outRoot.$family.DIRECTORY_SEPARATOR;
    if (! is_dir($outdir) && ! mkdir($outdir, 0755, true) && ! is_dir($outdir)) {
        fwrite(STDERR, "Cannot create {$outdir}\n");
        exit(1);
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($indir));
    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $name = $file->getFilename();
        $ext = strtolower($file->getExtension());
        if (! in_array($ext, ['ttf', 'afm', 'pfb'], true)) {
            continue;
        }

        if (is_array($config['needed']) && ! in_array($name, $config['needed'], true)) {
            continue;
        }

        $encoding = '';
        $type = $config['type'];
        if ($family === 'core') {
            if (str_contains($name, 'Symbol')) {
                $encoding = 'symbol';
            } elseif (! str_contains($name, 'ZapfDingbats')) {
                $encoding = 'cp1252';
            }
        }

        try {
            $import = new Import(
                $file->getPathname(),
                $outdir,
                $type,
                $encoding,
            );
            fwrite(STDOUT, 'OK '.$name.' -> '.$import->getFontName()."\n");
        } catch (Throwable $exception) {
            fwrite(STDERR, 'FAIL '.$name.': '.$exception->getMessage()."\n");
        }
    }
}
