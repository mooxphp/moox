<?php
/**
 * Exception for 502 Bad Gateway responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 502 Bad Gateway responses
 */
final class Status502 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 502;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Bad Gateway';
}
