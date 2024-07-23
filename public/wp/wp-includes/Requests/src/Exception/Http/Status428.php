<?php
/**
 * Exception for 428 Precondition Required responses
 *
 * @link https://tools.ietf.org/html/rfc6585
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 428 Precondition Required responses
 *
 * @link https://tools.ietf.org/html/rfc6585
 */
final class Status428 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 428;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Precondition Required';
}
