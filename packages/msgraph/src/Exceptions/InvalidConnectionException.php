<?php

declare(strict_types=1);

namespace Moox\Msgraph\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a connection is missing or has incomplete credentials.
 * This is a configuration error, not a Graph API error.
 */
class InvalidConnectionException extends InvalidArgumentException {}
