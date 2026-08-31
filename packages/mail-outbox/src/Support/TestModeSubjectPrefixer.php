<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

final class TestModeSubjectPrefixer
{
    /**
     * @param  list<string>  $originalRecipients
     */
    public function prefix(string $template, ?string $subject, array $originalRecipients): string
    {
        $label = implode(', ', $originalRecipients);
        $prefix = str_contains($template, '%s')
            ? sprintf($template, $label)
            : $template.$label.' ';

        $base = $subject ?? '';

        return $prefix.$base;
    }
}
