<?php

/**
 * Exception for 418 I'm A Teapot responses
 *
 * @link https://tools.ietf.org/html/rfc2324
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 418 I'm A Teapot responses
 *
 * @link https://tools.ietf.org/html/rfc2324
 */
final class Status418 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 418;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = "I'm A Teapot";
}
