<?php
/**
 * Exception for 511 Network Authentication Required responses
 *
 * @link https://tools.ietf.org/html/rfc6585
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 511 Network Authentication Required responses
 *
 * @link https://tools.ietf.org/html/rfc6585
 */
final class Status511 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 511;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Network Authentication Required';
}
