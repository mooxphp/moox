<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

use Moox\MailTemplate\Models\MailTemplate;
use Moox\MailTemplate\Support\MailTemplateRenderer;
use Moox\Mjml\Enums\ValidationLevel;
use Moox\Mjml\Mjml;

final class MailTestingConverter
{
    public function __construct(
        private readonly MailTemplateRenderer $renderer,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function compose(MailTemplate $template, array $data): string
    {
        return $this->renderer->toMjml($template, $data);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function convert(string $mjml, array $options): string
    {
        $engine = Mjml::new();

        $validation = $options['validation_level'] ?? null;
        if (is_string($validation) && $validation !== '') {
            $engine->validationLevel(ValidationLevel::from($validation));
        }

        if (($options['minify'] ?? false) === true) {
            $engine->minify();
        }

        if (($options['beautify'] ?? false) === true) {
            $engine->beautify();
        }

        if (array_key_exists('keep_comments', $options)) {
            $engine->keepComments((bool) $options['keep_comments']);
        }

        if (array_key_exists('ignore_includes', $options)) {
            $engine->ignoreIncludes((bool) $options['ignore_includes']);
        }

        return $engine->toHtml($mjml);
    }
}
