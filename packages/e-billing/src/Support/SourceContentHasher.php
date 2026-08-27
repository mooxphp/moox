<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Models\EbillingDocument;
use Throwable;

final class SourceContentHasher
{
    public function hashFile(string $path): ?string
    {
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $hash = hash_file('sha256', $path);

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    public function ensureOnDocument(EbillingDocument $document): ?string
    {
        $existing = $document->source_content_hash;
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        try {
            $hash = $this->hashFile($document->sourceFullPath());
        } catch (Throwable) {
            return null;
        }

        if ($hash === null) {
            return null;
        }

        $document->source_content_hash = $hash;
        $document->save();

        return $hash;
    }
}
