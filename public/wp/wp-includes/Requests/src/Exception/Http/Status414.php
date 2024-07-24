<?php
/**
 * Exception for 414 Request-URI Too Large responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 414 Request-URI Too Large responses
 */
final class Status414 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 414;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Request-URI Too Large';
}
