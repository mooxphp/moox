<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

final readonly class TestModeRecipientPlan
{
    /**
     * @param  list<string>  $delivered
     * @param  list<string>  $redirected
     */
    public function __construct(
        public array $delivered,
        public array $redirected,
    ) {
    }

    public function allDelivered(): bool
    {
        return $this->redirected === [] && $this->delivered !== [];
    }

    public function allRedirected(): bool
    {
        return $this->delivered === [] && $this->redirected !== [];
    }

    public function isMixed(): bool
    {
        return $this->delivered !== [] && $this->redirected !== [];
    }

    public function hasDelivered(): bool
    {
        return $this->delivered !== [];
    }

    public function wasSuppressed(): bool
    {
        return $this->redirected !== [];
    }
}
