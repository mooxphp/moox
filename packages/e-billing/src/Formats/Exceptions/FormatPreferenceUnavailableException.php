<?php

declare(strict_types=1);

namespace Moox\EBilling\Formats\Exceptions;

use RuntimeException;

/**
 * The recipient format preference could not be looked up (the resolver failed), as opposed to "no preference".
 */
final class FormatPreferenceUnavailableException extends RuntimeException
{
}
