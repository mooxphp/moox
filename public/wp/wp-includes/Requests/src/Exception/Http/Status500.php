<?php
/**
 * Exception for 500 Internal Server Error responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 500 Internal Server Error responses
 */
final class Status500 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 500;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Internal Server Error';
}
