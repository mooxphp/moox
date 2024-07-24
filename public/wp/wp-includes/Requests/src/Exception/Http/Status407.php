<?php
/**
 * Exception for 407 Proxy Authentication Required responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 407 Proxy Authentication Required responses
 */
final class Status407 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 407;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Proxy Authentication Required';
}
