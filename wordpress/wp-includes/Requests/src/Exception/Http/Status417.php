<?php
/**
 * Exception for 417 Expectation Failed responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 417 Expectation Failed responses
 */
final class Status417 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 417;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Expectation Failed';
}
