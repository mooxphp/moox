<?php

declare(strict_types=1);

namespace Moox\Invoice\Support;

use InvalidArgumentException;

/**
 * BT-22 invoice notes: a text with an optional BT-21 subject code.
 *
 * Plain strings are the shape before subject codes and read as notes without a code. Stored notes keep
 * their text as written ({@see fromStored()}); notes coming in for a new invoice are cleaned ({@see normalize()}).
 *
 * @phpstan-type InvoiceNote array{text: string, subject_code: ?string}
 */
final class InvoiceNotes
{
    /**
     * Brings stored notes into the {text, subject_code} shape without changing any text, so indexes and
     * contents stay as recorded.
     *
     * @return list<InvoiceNote>
     */
    public static function fromStored(mixed $notes): array
    {
        if (! is_array($notes)) {
            return [];
        }

        $shaped = [];

        foreach ($notes as $note) {
            $text = is_array($note) ? ($note['text'] ?? null) : $note;

            if (! is_string($text)) {
                continue;
            }

            $shaped[] = ['text' => $text, 'subject_code' => self::subjectCode($note)];
        }

        return $shaped;
    }

    /**
     * Cleans incoming notes: trims each text and drops empty ones.
     *
     * @return list<InvoiceNote>
     */
    public static function normalize(mixed $notes): array
    {
        $normalized = [];

        foreach (self::fromStored($notes) as $note) {
            $text = trim($note['text']);

            if ($text !== '') {
                $normalized[] = ['text' => $text, 'subject_code' => $note['subject_code']];
            }
        }

        return $normalized;
    }

    /**
     * @throws InvalidArgumentException when the subject code is not a string
     */
    private static function subjectCode(mixed $note): ?string
    {
        $code = is_array($note) ? ($note['subject_code'] ?? null) : null;

        if ($code !== null && ! is_string($code)) {
            throw new InvalidArgumentException('A note subject code must be a string, '.get_debug_type($code).' given.');
        }

        $code = $code !== null ? trim($code) : '';

        return $code !== '' ? $code : null;
    }
}
