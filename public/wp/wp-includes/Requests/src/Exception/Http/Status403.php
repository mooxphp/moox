<?php
/**
 * Exception for 403 Forbidden responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 403 Forbidden responses
 */
final class Status403 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 403;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Forbidden';
}
