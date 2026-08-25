<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

class MailTemplatePreview
{
    /**
     * @return array<string, mixed>
     */
    public function viewData(): array
    {
        $variables = config('mail-outbox.preview_variables', []);

        return is_array($variables) ? $variables : [];
    }

    public function highlight(string $html): string
    {
        $html = $this->exposeLinkHrefs($html);

        [$html, $protected] = $this->protectNonTextContainers($html);

        $highlighted = preg_replace_callback(
            '/>([^<]+)</u',
            function (array $matches): string {
                $text = preg_replace(
                    '/\{[A-Za-z_][A-Za-z0-9_.]*\}/',
                    '<span class="mail-preview-var">$0</span>',
                    $matches[1],
                ) ?? $matches[1];

                return '>'.$text.'<';
            },
            $html,
        ) ?? $html;

        return $this->injectStyles(strtr($highlighted, $protected));
    }

    /**
     * Keep markup out of tags where the browser treats content as plain text
     * (e.g. document title) or as non-HTML source.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function protectNonTextContainers(string $html): array
    {
        $protected = [];

        $replaced = preg_replace_callback(
            '/<(title|textarea|script|style)\b[^>]*>.*?<\/\1>/is',
            function (array $matches) use (&$protected): string {
                $key = '___MAIL_PREVIEW_PROTECTED_'.count($protected).'___';
                $protected[$key] = $matches[0];

                return $key;
            },
            $html,
        );

        return [is_string($replaced) ? $replaced : $html, $protected];
    }

    private function exposeLinkHrefs(string $html): string
    {
        $replaced = preg_replace_callback(
            '/<a\b(?=[^>]*\bhref="(\{[A-Za-z_][A-Za-z0-9_.]*\})")[^>]*>(.*?)<\/a>/is',
            function (array $matches): string {
                $hrefToken = $matches[1];
                $inner = $matches[2];

                if (str_contains($inner, $hrefToken)) {
                    return $matches[0];
                }

                return (string) preg_replace('/<\/a>$/i', '<br>'.$hrefToken.'</a>', $matches[0], 1);
            },
            $html,
        );

        return is_string($replaced) ? $replaced : $html;
    }

    private function injectStyles(string $html): string
    {
        $style = <<<'CSS'
<style type="text/css">
.mail-preview-var {
  background-color: #fff3bf;
  color: #5c4d00;
  border: 1px dashed #d4a017;
  border-radius: 3px;
  padding: 1px 5px;
  font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
  font-weight: 600;
  white-space: nowrap;
}
</style>
CSS;

        if (str_contains(strtolower($html), '</head>')) {
            return (string) preg_replace('/<\/head>/i', $style.'</head>', $html, 1);
        }

        return $style.$html;
    }
}
