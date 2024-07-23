<?php
/**
 * Exception for 504 Gateway Timeout responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 504 Gateway Timeout responses
 */
final class Status504 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 504;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Gateway Timeout';
}
