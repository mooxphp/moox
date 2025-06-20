<?php

namespace Moox\Core\Services;

class DnsLookupService
{
    public static function getIpAddress(string $domain): ?string
    {
        if (filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            $ip = gethostbyname($domain);

            return $ip !== $domain ? $ip : null;
        }

        return null;
    }
}
