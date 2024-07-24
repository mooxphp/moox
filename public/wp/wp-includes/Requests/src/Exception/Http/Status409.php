<?php
/**
 * Exception for 409 Conflict responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 409 Conflict responses
 */
final class Status409 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 409;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Conflict';
}
