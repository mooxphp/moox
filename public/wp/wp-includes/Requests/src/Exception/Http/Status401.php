<?php
/**
 * Exception for 401 Unauthorized responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 401 Unauthorized responses
 */
final class Status401 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 401;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Unauthorized';
}
