<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Exceptions;

use Exception;

/**
 * Marker for permanent send failures that must not be retried.
 */
final class PermanentMailFailureException extends Exception
{
}
