<?php
/**
 * Exception for 410 Gone responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 410 Gone responses
 */
final class Status410 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 410;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Gone';
}
