<?php

/**
 * Capability interface declaring the known capabilities.
 */

namespace WpOrg\Requests;

/**
 * Capability interface declaring the known capabilities.
 *
 * This is used as the authoritative source for which capabilities can be queried.
 */
interface Capability
{
    /**
     * Support for SSL.
     *
     * @var string
     */
    const SSL = 'ssl';

    /**
     * Collection of all capabilities supported in Requests.
     *
     * Note: this does not automatically mean that the capability will be supported for your chosen transport!
     *
     * @var string[]
     */
    const ALL = [
        self::SSL,
    ];
}
