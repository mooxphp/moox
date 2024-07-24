<?php
/**
 * Exception for 405 Method Not Allowed responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 405 Method Not Allowed responses
 */
final class Status405 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 405;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Method Not Allowed';
}
