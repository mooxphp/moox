<?php
/**
 * Exception for 404 Not Found responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 404 Not Found responses
 */
final class Status404 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 404;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Not Found';
}
