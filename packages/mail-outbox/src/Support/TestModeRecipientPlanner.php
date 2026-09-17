<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

final class TestModeRecipientPlanner
{
    public function __construct(
        private TestModeRecipientMatcher $matcher,
    ) {
    }

    /**
     * @param  list<string>  $intended
     */
    public function plan(array $intended, MailOutboxConfig $config): TestModeRecipientPlan
    {
        $delivered = [];
        $redirected = [];
        $patterns = $config->testModeAllowlistPatterns();
        $redirectTo = strtolower(trim($config->testModeRedirectTo()));

        foreach ($intended as $email) {
            $normalized = strtolower($email);

            // Sandbox address matching an intended recipient is not a redirect:
            // that person still receives the mail (badge would otherwise say
            // "Direkt" while status is wrongly "suppressed").
            if (
                $this->matcher->matches($normalized, $patterns)
                || ($redirectTo !== '' && $normalized === $redirectTo)
            ) {
                $delivered[] = $normalized;

                continue;
            }

            $redirected[] = $normalized;
        }

        return new TestModeRecipientPlan(
            delivered: array_values(array_unique($delivered)),
            redirected: array_values(array_unique($redirected)),
        );
    }
}
