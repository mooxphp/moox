<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Storage;
use Moox\MailOutbox\Exceptions\MessageTooLargeException;
use RuntimeException;
use Throwable;

final class MessageSizeGuard
{
    public function assertWithinLimit(MailableContract $mailable, int $maxBytes): void
    {
        $size = $this->estimateBytes($mailable);

        if ($size > $maxBytes) {
            throw new MessageTooLargeException($size, $maxBytes);
        }
    }

    public function estimateBytes(MailableContract $mailable): int
    {
        if (! $mailable instanceof Mailable) {
            throw new RuntimeException('Message size guard requires an Illuminate\\Mail\\Mailable instance.');
        }

        $size = strlen($mailable->render());

        foreach ($mailable->attachments as $attachment) {
            $size += $this->bytesFromAttachArray($attachment);
        }

        foreach ($mailable->rawAttachments as $attachment) {
            if (is_array($attachment) && isset($attachment['data']) && is_string($attachment['data'])) {
                $size += strlen($attachment['data']);
            }
        }

        foreach ($mailable->diskAttachments as $attachment) {
            $size += $this->bytesFromDiskAttachment($attachment);
        }

        return $size;
    }

    private function bytesFromAttachArray(mixed $attachment): int
    {
        if (! is_array($attachment)) {
            return 0;
        }

        if (isset($attachment['data']) && is_string($attachment['data'])) {
            return strlen($attachment['data']);
        }

        $path = $attachment['file'] ?? $attachment['path'] ?? null;

        if (is_string($path) && is_file($path)) {
            $bytes = filesize($path);

            return $bytes === false ? 0 : $bytes;
        }

        return 0;
    }

    private function bytesFromDiskAttachment(mixed $attachment): int
    {
        if (! is_array($attachment)) {
            return 0;
        }

        $path = $attachment['path'] ?? null;
        $disk = $attachment['disk'] ?? null;

        if (! is_string($path) || $path === '') {
            return 0;
        }

        try {
            $filesystem = is_string($disk) && $disk !== ''
                ? Storage::disk($disk)
                : Storage::disk();

            return (int) $filesystem->size($path);
        } catch (Throwable) {
            return 0;
        }
    }
}
