<?php

use Valet\Drivers\LaravelValetDriver;

class LocalValetDriver extends LaravelValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return true;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        $public = '/public';
        $wpslug = '/wp';

        if (str_contains($uri, $wpslug.'/') || str_ends_with($uri, $wpslug)) {

            if (str_contains($uri, '/wp-admin')) {

                if (str_ends_with($uri, '/wp-admin/') || str_ends_with($uri, '/wp-admin') || str_ends_with($uri, '/wp-admin/index.php')) {
                    return $this->convertForWindows($sitePath.$public.$wpslug.'/wp-admin/index.php');
                }

                return $this->convertForWindows($sitePath.$public.$uri);
            }

            if (str_contains($uri, 'wp-login.php')) {
                return $this->convertForWindows($sitePath.$public.$wpslug.'/wp-login.php');
            }

            return $this->convertForWindows($sitePath.$public.$wpslug.'/index.php');
        }

        return $this->convertForWindows($sitePath.$public.'/index.php');
    }

    public function convertForWindows($url)
    {
        return str_replace('/', '\\', $url);
    }
}
