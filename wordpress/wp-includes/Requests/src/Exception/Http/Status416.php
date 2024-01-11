<?php
/**
 * Exception for 416 Requested Range Not Satisfiable responses
 */

namespace WpOrg\Requests\Exception\Http;

use WpOrg\Requests\Exception\Http;

/**
 * Exception for 416 Requested Range Not Satisfiable responses
 */
final class Status416 extends Http
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 416;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Requested Range Not Satisfiable';
}
