<?php
/**
 * Exception for 503 Service Unavailable responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 503 Service Unavailable responses
 */
final class Status503 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 503;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Service Unavailable';
}
