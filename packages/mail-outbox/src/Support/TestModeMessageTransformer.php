<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class TestModeMessageTransformer
{
    public const INTENDED_RECIPIENTS_HEADER = 'X-Moox-Mail-Intended-Recipients';

    public const SUPPRESSED_HEADER = 'X-Moox-Mail-Test-Mode-Suppressed';

    public const ORIGINAL_SUBJECT_HEADER = 'X-Moox-Mail-Original-Subject';

    public function __construct(
        private TestModeRecipientPlanner $planner,
        private TestModeSubjectPrefixer $prefixer,
    ) {
    }

    /**
     * Apply safe test mode to an outgoing Symfony message.
     *
     * Returns true when the message was redirected (suppressed).
     */
    public function apply(Email $message, MailOutboxConfig $config): bool
    {
        $intended = $this->recipientsFromEmail($message);
        $plan = $this->planner->plan($intended, $config);

        if ($plan->allDelivered()) {
            return false;
        }

        $headers = $message->getHeaders();

        if (! $headers->has(self::INTENDED_RECIPIENTS_HEADER)) {
            $headers->addTextHeader(self::INTENDED_RECIPIENTS_HEADER, json_encode($intended, JSON_THROW_ON_ERROR));
        }

        if (! $headers->has(self::SUPPRESSED_HEADER)) {
            $headers->addTextHeader(self::SUPPRESSED_HEADER, '1');
        }

        $originalSubject = $message->getSubject();

        if (is_string($originalSubject) && $originalSubject !== '' && ! $headers->has(self::ORIGINAL_SUBJECT_HEADER)) {
            $headers->addTextHeader(self::ORIGINAL_SUBJECT_HEADER, $originalSubject);
        }

        $redirectTo = $config->testModeRedirectTo();
        $redirectName = $config->testModeRedirectName();

        $message->to(
            $redirectName !== null && $redirectName !== ''
                ? new Address($redirectTo, $redirectName)
                : new Address($redirectTo),
        );
        $message->cc();
        $message->bcc();

        $message->subject($this->prefixer->prefix(
            $config->testModeSubjectPrefix(),
            is_string($originalSubject) ? $originalSubject : null,
            $plan->redirected,
        ));

        return true;
    }

    /**
     * @return list<string>
     */
    public function recipientsFromEmail(Email $message): array
    {
        $addresses = [];

        foreach ([$message->getTo(), $message->getCc(), $message->getBcc()] as $group) {
            foreach ($group as $address) {
                $addresses[] = strtolower($address->getAddress());
            }
        }

        return array_values(array_unique($addresses));
    }
}
