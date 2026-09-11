<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Support;

class MjmlDocumentComposer
{
    /**
     * @param  array<string, mixed>  $parts
     */
    public function compose(array $parts): string
    {
        $brandName = $this->string($parts['brandName'] ?? null);
        $headline = $this->string($parts['headline'] ?? null) ?: $brandName;
        $logoUrl = $this->string($parts['logoUrl'] ?? null);
        $mailContent = $this->nullableString($parts['mailContent'] ?? null);
        $footer = $this->nullableString($parts['footer'] ?? null);
        $backgroundColor = $this->string($parts['backgroundColor'] ?? null) ?: '#ECF2F6';
        $buttonColor = $this->string($parts['buttonColor'] ?? null) ?: '#005CA3';
        $textColor = $this->string($parts['textColor'] ?? null) ?: '#000000';

        $title = e($headline);
        $preview = e($headline);
        $text = e($textColor);
        $button = e($buttonColor);
        $background = e($backgroundColor);

        $body = $this->logoSection($logoUrl, $brandName)
            .$this->fragment($mailContent, wrapEmpty: true)
            .$this->fragment($footer, wrapEmpty: false);

        return <<<MJML
<mjml>
    <mj-head>
        <mj-title>{$title}</mj-title>
        <mj-preview>{$preview}</mj-preview>
        <mj-attributes>
            <mj-text color="{$text}" />
            <mj-button background-color="{$button}" color="#ffffff" />
        </mj-attributes>
    </mj-head>

    <mj-body background-color="{$background}">
{$body}    </mj-body>
</mjml>
MJML;
    }

    private function logoSection(string $logoUrl, string $brandName): string
    {
        if ($logoUrl === '') {
            return '';
        }

        $src = e($logoUrl);
        $alt = e($brandName);

        return <<<MJML
        <mj-section>
            <mj-column>
                <mj-image src="{$src}" alt="{$alt}" />
            </mj-column>
        </mj-section>

MJML;
    }

    private function fragment(?string $mjml, bool $wrapEmpty): string
    {
        if ($mjml === null) {
            return $wrapEmpty ? $this->wrapped('') : '';
        }

        if ($this->containsSection($mjml)) {
            return rtrim($mjml).PHP_EOL;
        }

        return $this->wrapped($mjml);
    }

    private function wrapped(string $mjml): string
    {
        return <<<MJML
        <mj-section>
            <mj-column>
                {$mjml}
            </mj-column>
        </mj-section>

MJML;
    }

    protected function containsSection(string $mjml): bool
    {
        return str_contains(ltrim($mjml), '<mj-section');
    }

    protected function string(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    protected function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return filled($value) ? $value : null;
    }
}
