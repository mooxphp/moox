<?php

declare(strict_types=1);

use Moox\MailTesting\Support\NormalizedHtml;
use Tests\TestCase;

uses(TestCase::class);

it('collapses whitespace for hashes but keeps line breaks for display', function (): void {
    $html = "<html>\n  <body>Größe</body>\n</html>";

    expect(NormalizedHtml::normalize($html))->toBe('<html> <body>Größe</body> </html>')
        ->and(NormalizedHtml::forDisplay($html))->toBe("<html>\n  <body>Größe</body>\n</html>");
});

it('pretty-prints minified html for display', function (): void {
    expect(NormalizedHtml::forDisplay('<html><body>Hallo</body></html>'))
        ->toBe("<html>\n<body>Hallo</body>\n</html>");
});
