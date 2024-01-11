<?php
/**
 * Exception for 505 HTTP Version Not Supported responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 505 HTTP Version Not Supported responses
 */
final class Status505 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 505;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'HTTP Version Not Supported';
}
