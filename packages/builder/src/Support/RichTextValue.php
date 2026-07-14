<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\Str;

final class RichTextValue
{
    public static function sanitizeForPersist(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_array($value) && self::isTipTapDocument($value)) {
            return RichContentRenderer::make($value)->toHtml();
        }

        if (is_string($value)) {
            return Str::sanitizeHtml($value);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $document
     */
    public static function isTipTapDocument(array $document): bool
    {
        return ($document['type'] ?? null) === 'doc';
    }

    public static function isEmpty(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_array($value)) {
            return self::isEmptyDocument($value);
        }

        if (! is_string($value)) {
            return blank($value);
        }

        return self::isEmptyHtml($value);
    }

    /**
     * @param  array<string, mixed>  $document
     */
    protected static function isEmptyDocument(array $document): bool
    {
        if (($document['type'] ?? null) !== 'doc') {
            return blank($document);
        }

        $content = $document['content'] ?? [];

        if ($content === []) {
            return true;
        }

        foreach ($content as $node) {
            if (! is_array($node) || ! self::isEmptyJsonNode($node)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>|null  $node
     */
    protected static function isEmptyJsonNode(?array $node): bool
    {
        if ($node === null) {
            return true;
        }

        $type = $node['type'] ?? null;

        if ($type === 'paragraph' || $type === 'heading') {
            $inline = $node['content'] ?? [];

            if ($inline === []) {
                return true;
            }

            foreach ($inline as $child) {
                if (! is_array($child) || ! self::isEmptyInlineNode($child)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected static function isEmptyInlineNode(array $node): bool
    {
        $type = $node['type'] ?? null;

        if ($type === 'hardBreak') {
            return true;
        }

        if ($type === 'text') {
            return trim(str_replace("\xc2\xa0", ' ', (string) ($node['text'] ?? ''))) === '';
        }

        return false;
    }

    protected static function isEmptyHtml(string $value): bool
    {
        $normalized = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/<br\s*\/?>/i', '', $normalized) ?? $normalized;
        $text = strip_tags($normalized);
        $text = str_replace(["\xc2\xa0", '&nbsp;'], ' ', $text);

        return trim($text) === '';
    }
}
