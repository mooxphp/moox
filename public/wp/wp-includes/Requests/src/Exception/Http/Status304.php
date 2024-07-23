<?php
/**
 * Exception for 304 Not Modified responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 304 Not Modified responses
 */
final class Status304 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 304;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Not Modified';
}
