<?php
/**
 * Exception for 412 Precondition Failed responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 412 Precondition Failed responses
 */
final class Status412 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 412;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Precondition Failed';
}
