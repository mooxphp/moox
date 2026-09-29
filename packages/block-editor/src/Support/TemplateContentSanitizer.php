<?php

namespace Moox\BlockEditor\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TemplateContentSanitizer
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_TAGS = [
        'a',
        'p',
        'div',
        'span',
        'br',
        'strong',
        'em',
        'b',
        'i',
        'u',
        's',
        'blockquote',
        'pre',
        'code',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'ul',
        'ol',
        'li',
        'hr',
        'img',
        'video',
        'table',
        'thead',
        'tbody',
        'tfoot',
        'tr',
        'th',
        'td',
    ];

    /**
     * @var array<int, string>
     */
    private const ALLOWED_ATTRIBUTES = [
        'href',
        'target',
        'rel',
        'title',
        'src',
        'alt',
        'controls',
        'poster',
        'colspan',
        'rowspan',
    ];

    /**
     * @param  array<int, mixed>  $blocks
     * @return array<int, mixed>
     */
    public function sanitizeBlocks(array $blocks): array
    {
        return Arr::map($blocks, function ($block): mixed {
            if (! is_array($block)) {
                return $block;
            }

            return $this->sanitizeNode($block);
        });
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public function sanitizeMeta(array $meta): array
    {
        return $this->sanitizeNode($meta);
    }

    /**
     * @param  array<string|int, mixed>  $node
     * @return array<string|int, mixed>
     */
    private function sanitizeNode(array $node): array
    {
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = $this->sanitizeNode($value);

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $normalizedKey = Str::lower((string) $key);
            if (in_array($normalizedKey, [
                'content',
                'text',
                'question',
                'answer',
            ], true)) {
                $node[$key] = $this->sanitizeHtml($value);

                continue;
            }

            if ($normalizedKey === 'id') {
                $node[$key] = $this->sanitizeId($value);

                continue;
            }

            if (in_array($normalizedKey, [
                'href',
                'src',
                'url',
                'poster',
                'linkurl',
                'imageurl',
                'videourl',
                'videoposter',
                'embedurl',
            ], true)) {
                $node[$key] = $this->sanitizeUrl($value);

                continue;
            }

            if (in_array($normalizedKey, [
                'name',
                'slug',
                'title',
                'alt',
                'htmlid',
                'classes',
                'style',
                'linktext',
                'imagealt',
                'imagetitle',
                'videotitle',
                'embedtitle',
            ], true)) {
                $node[$key] = trim(strip_tags($value));
            }
        }

        return $node;
    }

    private function sanitizeHtml(string $content): string
    {
        if (trim($content) === '') {
            return '';
        }

        if (! class_exists(DOMDocument::class)) {
            return strip_tags($content);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div>'.$content.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );

        $root = $document->documentElement;
        if (! $root instanceof DOMElement) {
            return strip_tags($content);
        }

        $this->sanitizeElement($root);

        $innerHtml = '';
        foreach ($root->childNodes as $childNode) {
            $innerHtml .= $document->saveHTML($childNode);
        }

        return $innerHtml;
    }

    private function sanitizeElement(DOMElement $element): void
    {
        $tagName = Str::lower($element->tagName);
        if ($tagName !== 'div' && ! in_array($tagName, self::ALLOWED_TAGS, true)) {
            $this->unwrapElementAndSanitizeChildren($element);

            return;
        }

        for ($index = $element->attributes->length - 1; $index >= 0; $index--) {
            $attribute = $element->attributes->item($index);
            if ($attribute === null) {
                continue;
            }

            $name = Str::lower($attribute->name);
            $value = trim($attribute->value);

            if (Str::startsWith($name, 'on')) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (! in_array($name, self::ALLOWED_ATTRIBUTES, true)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (in_array($name, ['href', 'src', 'poster'], true) && $this->sanitizeUrl($value) === '') {
                $element->removeAttribute($attribute->name);
            }
        }

        if ($tagName === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }

        /** @var list<DOMNode> $childNodes */
        $childNodes = [];
        foreach ($element->childNodes as $childNode) {
            $childNodes[] = $childNode;
        }

        foreach ($childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $this->sanitizeElement($childNode);
            }
        }
    }

    private function unwrapElementAndSanitizeChildren(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if ($parent === null) {
            return;
        }

        /** @var list<DOMNode> $movedChildren */
        $movedChildren = [];

        while ($element->firstChild !== null) {
            $child = $element->firstChild;
            $parent->insertBefore($child, $element);
            $movedChildren[] = $child;
        }

        $parent->removeChild($element);

        foreach ($movedChildren as $childNode) {
            if ($childNode instanceof DOMElement) {
                $this->sanitizeElement($childNode);
            }
        }
    }

    private function sanitizeId(string $id): string
    {
        $normalized = trim($id);
        if ($normalized === '') {
            return '';
        }

        $safe = preg_replace('/[^A-Za-z0-9_-]/', '', $normalized);

        return is_string($safe) ? $safe : '';
    }

    private function sanitizeUrl(string $url): string
    {
        $normalizedUrl = trim($url);
        if ($normalizedUrl === '') {
            return '';
        }

        $lowercaseUrl = Str::lower($normalizedUrl);
        if (Str::startsWith($lowercaseUrl, 'javascript:')) {
            return '';
        }

        if (
            Str::startsWith($lowercaseUrl, 'http://')
            || Str::startsWith($lowercaseUrl, 'https://')
            || Str::startsWith($lowercaseUrl, 'mailto:')
            || Str::startsWith($lowercaseUrl, 'tel:')
            || Str::startsWith($lowercaseUrl, '/')
            || Str::startsWith($lowercaseUrl, '#')
            || Str::startsWith($lowercaseUrl, 'blob:')
        ) {
            return $normalizedUrl;
        }

        if ($this->isAllowedDataImageUrl($lowercaseUrl)) {
            return $normalizedUrl;
        }

        return '';
    }

    private function isAllowedDataImageUrl(string $lowercaseUrl): bool
    {
        foreach (['data:image/png', 'data:image/jpeg', 'data:image/jpg', 'data:image/gif', 'data:image/webp'] as $prefix) {
            if (Str::startsWith($lowercaseUrl, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
