<?php
/**
 * Exception for 413 Request Entity Too Large responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 413 Request Entity Too Large responses
 */
final class Status413 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 413;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Request Entity Too Large';
}
