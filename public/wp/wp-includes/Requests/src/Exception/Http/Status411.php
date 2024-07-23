<?php
/**
 * Exception for 411 Length Required responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 411 Length Required responses
 */
final class Status411 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 411;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Length Required';
}
