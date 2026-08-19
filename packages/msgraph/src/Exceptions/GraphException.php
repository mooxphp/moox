<?php

declare(strict_types=1);

namespace Moox\Msgraph\Exceptions;

use RuntimeException;

/**
 * Base exception for all Graph API errors (HTTP responses, transport failures).
 */
class GraphException extends RuntimeException {}
