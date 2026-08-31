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

        foreach ($intended as $email) {
            if ($this->matcher->matches($email, $patterns)) {
                $delivered[] = strtolower($email);

                continue;
            }

            $redirected[] = strtolower($email);
        }

        return new TestModeRecipientPlan(
            delivered: array_values(array_unique($delivered)),
            redirected: array_values(array_unique($redirected)),
        );
    }
}
