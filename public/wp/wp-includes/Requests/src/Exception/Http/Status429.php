<?php
/**
 * Exception for 429 Too Many Requests responses
 *
 * @link https://tools.ietf.org/html/draft-nottingham-http-new-status-04
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 429 Too Many Requests responses
 *
 * @link https://tools.ietf.org/html/draft-nottingham-http-new-status-04
 */
final class Status429 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 429;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Too Many Requests';
}
