<?php
/**
 * Exception for 305 Use Proxy responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 305 Use Proxy responses
 */
final class Status305 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 305;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Use Proxy';
}
