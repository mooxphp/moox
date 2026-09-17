# Manual check

Run this after `cd vendor/spatie/mjml-php && npm install`. Paste the block into `php artisan tinker`. Every line should print `ok`.

Fixtures live in `tests/Fixtures/` (`login.mjml`, `invoice.mjml`, `login-with-include.mjml`, `invoice-invalid-attr.mjml`, `partial-footer.mjml`).

```php
use Moox\Mjml\Enums\ValidationLevel;
use Moox\Mjml\Exceptions\CouldNotRenderMjml;
use Moox\Mjml\Mjml;

$fixtures = base_path('vendor/moox/mjml/tests/Fixtures');
if (! is_dir($fixtures)) {
    $fixtures = base_path('packages/mjml/tests/Fixtures');
}

$login = file_get_contents($fixtures.'/login.mjml');
$invoice = file_get_contents($fixtures.'/invoice.mjml');
$withInclude = file_get_contents($fixtures.'/login-with-include.mjml');
$invalidInvoice = file_get_contents($fixtures.'/invoice-invalid-attr.mjml');
$spatieBin = base_path('vendor/spatie/mjml-php/bin');

$check = function (string $label, bool $ok): void {
    echo ($ok ? 'ok   ' : 'FAIL ').$label.PHP_EOL;
};

foreach ([true => 'php', false => 'node'] as $usePhp => $engine) {
    config(['mjml.use_php_renderer' => $usePhp]);
    echo PHP_EOL.'==== '.$engine.' ===='.PHP_EOL;

    $html = Mjml::new()->toHtml($login);
    $check('login toHtml', str_contains($html, 'Sign in') && str_contains($html, 'signature=abc123') && ! str_contains($html, '<mjml'));
    $check('login keepComments', str_contains(Mjml::new()->keepComments()->toHtml($login), 'marker-comment'));
    $check('login hideComments', ! str_contains(Mjml::new()->hideComments()->toHtml($login), 'marker-comment'));
    $check('login minify', strlen(Mjml::new()->minify()->toHtml($login)) < strlen($html));
    $check('login beautify', str_contains(Mjml::new()->beautify()->toHtml($login), ">\n"));
    $check('login hideComments+minify', str_contains(Mjml::new()->hideComments()->minify()->toHtml($login), 'Sign in'));

    $invoiceHtml = Mjml::new()->toHtml($invoice);
    $check('invoice toHtml', str_contains($invoiceHtml, 'Widget A') && str_contains($invoiceHtml, '{invoiceNumber}') && ! str_contains($invoiceHtml, '<mj-table'));
    $check('invoice minify options array', strlen(Mjml::new()->toHtml($invoice, ['minify' => true])) < strlen($invoiceHtml));

    $loaded = Mjml::new()->filePath($fixtures)->ignoreIncludes(false)->toHtml($withInclude);
    $ignored = Mjml::new()->filePath($fixtures)->ignoreIncludes(true)->toHtml($withInclude);
    $check('include footer', str_contains($loaded, 'INCLUDED-FOOTER'));
    $check('ignoreIncludes skips footer', ! str_contains($ignored, 'INCLUDED-FOOTER'));

    $skip = Mjml::new()->validationLevel(ValidationLevel::Skip)->convert($invalidInvoice);
    $soft = Mjml::new()->validationLevel(ValidationLevel::Soft)->convert($invalidInvoice);
    $check('validation skip', $skip->hasErrors() === false && str_contains($skip->html(), '{invoiceNumber}'));
    $check('validation soft', $soft->hasErrors() === true && str_contains($soft->html(), '{invoiceNumber}'));
    try {
        Mjml::new()->validationLevel(ValidationLevel::Strict)->toHtml($invalidInvoice);
        $check('validation strict throws', false);
    } catch (CouldNotRenderMjml) {
        $check('validation strict throws', true);
    }

    try {
        Mjml::new()->sidecar()->toHtml($login);
        $check('sidecar', ! $usePhp);
    } catch (CouldNotRenderMjml) {
        $check('sidecar throws on PHP / without sidecar package', $usePhp || ! class_exists(\Spatie\MjmlSidecar\MjmlFunction::class));
    }

    try {
        $wdHtml = Mjml::new()->workingDirectory($spatieBin)->toHtml($login);
        $check('workingDirectory Spatie bin converts', ! $usePhp && str_contains($wdHtml, 'Sign in'));
    } catch (CouldNotRenderMjml) {
        $check('workingDirectory throws on PHP', $usePhp);
    }
}
```

Pest covers the same fixtures:

```bash
php artisan test --compact packages/mjml/tests
```

Optional: if `moox/mail-template` records exist, render ids 1 and 4 the same way (`MailTemplateRenderer::toMjml` then `Mjml::new()->toHtml`) and confirm doctype plus no leftover `<mjml`.
