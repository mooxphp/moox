<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Removes the executable XSS vectors from user-authored HTML (rich text) while
 * preserving normal formatting markup. This is defense in depth: stored rich
 * text is treated as untrusted, so scripts, dangerous elements, inline event
 * handlers and script-like URLs are stripped before the value is persisted.
 */
final class HtmlSanitizer
{
    /**
     * Elements that can execute code or load active/remote content and are
     * never legitimate rich-text formatting.
     *
     * @var list<string>
     */
    private const FORBIDDEN_ELEMENTS = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input',
        'button', 'textarea', 'select', 'option', 'link', 'meta', 'base',
        'svg', 'math', 'applet', 'frame', 'frameset', 'template',
    ];

    /**
     * URL attributes whose value must not resolve to an executable scheme.
     *
     * @var list<string>
     */
    private const URL_ATTRIBUTES = ['href', 'src', 'xlink:href', 'action', 'formaction'];

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $document = new DOMDocument;

        $previous = libxml_use_internal_errors(true);

        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="moox-sanitizer-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false) {
            return '';
        }

        $root = $document->getElementById('moox-sanitizer-root');

        if (! $root instanceof DOMElement) {
            return '';
        }

        self::sanitizeNode($root);

        $result = '';

        foreach (iterator_to_array($root->childNodes) as $child) {
            $result .= $document->saveHTML($child);
        }

        return $result;
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            if (in_array(strtolower($child->nodeName), self::FORBIDDEN_ELEMENTS, true)) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            self::sanitizeAttributes($child);
            self::sanitizeNode($child);
        }
    }

    private static function sanitizeAttributes(DOMElement $element): void
    {
        /** @var list<DOMAttr> $attributes */
        $attributes = iterator_to_array($element->attributes);

        foreach ($attributes as $attribute) {
            $name = strtolower($attribute->nodeName);

            if (str_starts_with($name, 'on')) {
                $element->removeAttribute($attribute->nodeName);

                continue;
            }

            if (in_array($name, self::URL_ATTRIBUTES, true) && self::isDangerousUrl($attribute->value)) {
                $element->removeAttribute($attribute->nodeName);
            }
        }
    }

    private static function isDangerousUrl(string $value): bool
    {
        $normalized = strtolower(trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $normalized = preg_replace('/[\x00-\x20]+/', '', $normalized) ?? $normalized;

        return str_starts_with($normalized, 'javascript:')
            || str_starts_with($normalized, 'vbscript:')
            || str_starts_with($normalized, 'data:text/html');
    }
}
