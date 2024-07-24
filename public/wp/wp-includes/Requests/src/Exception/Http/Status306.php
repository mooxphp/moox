<?php
/**
 * Exception for 306 Switch Proxy responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 306 Switch Proxy responses
 */
final class Status306 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 306;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Switch Proxy';
}
