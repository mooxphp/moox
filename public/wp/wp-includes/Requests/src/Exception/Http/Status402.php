<?php
/**
 * Exception for 402 Payment Required responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 402 Payment Required responses
 */
final class Status402 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 402;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Payment Required';
}
