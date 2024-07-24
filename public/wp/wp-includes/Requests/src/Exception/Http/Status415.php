<?php
/**
 * Exception for 415 Unsupported Media Type responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 415 Unsupported Media Type responses
 */
final class Status415 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 415;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Unsupported Media Type';
}
