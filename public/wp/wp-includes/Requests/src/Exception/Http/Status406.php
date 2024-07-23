<?php
/**
 * Exception for 406 Not Acceptable responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 406 Not Acceptable responses
 */
final class Status406 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 406;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Not Acceptable';
}
