<?php

namespace Moox\Jobs\Traits;

trait FormatSeconds
{
    public function formatSeconds(int $seconds): string
    {
        $days = floor($seconds / (60 * 60 * 24));
        $seconds -= $days * (60 * 60 * 24);

        $hours = floor($seconds / (60 * 60));
        $seconds -= $hours * (60 * 60);

        $minutes = floor($seconds / 60);
        $seconds = $seconds - ($minutes * 60);

        $formattedSeconds = '';

        if ($days > 0) {
            $formattedSeconds .= "$days d ";
        }

        if ($hours > 0 or $days > 0) {
            $formattedSeconds .= "$hours h ";
        }

        if ($minutes > 0 or $hours > 0 or $days > 0) {
            $formattedSeconds .= "$minutes m ";
        }

        if ($days == 0) {
            if ($seconds > 0 or $minutes > 0 or $hours > 0) {
                $formattedSeconds .= "$seconds s";
            }
        }

        return $formattedSeconds;
    }
}
