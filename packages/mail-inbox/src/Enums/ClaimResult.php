<?php

declare(strict_types=1);

namespace Moox\MailInbox\Enums;

/**
 * Result of attempting to claim a message for exclusive processing.
 */
enum ClaimResult: string
{
    case Won = 'won';
    case AlreadyHeld = 'already_held';
    case MoveFailed = 'move_failed';
}
