<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Mail\SentMessage as LaravelSentMessage;
use Illuminate\Support\Facades\Mail;
use Moox\MailOutbox\Enums\MailSendStatus;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;

final class TestModeSendCoordinator
{
    public function __construct(
        private MailableInspector $inspector,
        private TestModeRecipientPlanner $planner,
        private TestModeSubjectPrefixer $prefixer,
        private MailableRecipientFilter $filter,
        private OutboundMessagePreparer $preparer,
    ) {
    }

    public function send(
        Mailable $mailable,
        string $mailerName,
        MailOutboxConfig $config,
        string $correlationHeader,
        string $correlationId,
    ): TestModeSendResult {
        return TestModeOutboundGuard::whileHandling(function () use (
            $mailable,
            $mailerName,
            $config,
            $correlationHeader,
            $correlationId,
        ): TestModeSendResult {
            return $this->sendWhileGuarded($mailable, $mailerName, $config, $correlationHeader, $correlationId);
        });
    }

    private function sendWhileGuarded(
        Mailable $mailable,
        string $mailerName,
        MailOutboxConfig $config,
        string $correlationHeader,
        string $correlationId,
    ): TestModeSendResult {
        $intended = $this->inspector->recipients($mailable);
        $plan = $this->planner->plan($intended, $config);
        $originalSubject = $this->inspector->subject($mailable);

        if ($plan->allDelivered()) {
            $this->preparer->prepare($mailable, $correlationHeader, $correlationId, $mailerName);
            $sent = Mail::mailer($mailerName)->send($mailable);

            return new TestModeSendResult(
                sent: $sent,
                actualRecipients: $this->resolveActualRecipients($sent, $mailable),
                status: MailSendStatus::Sent,
            );
        }

        $actualRecipients = [];
        $primarySent = null;

        if ($plan->hasDelivered()) {
            $deliveredMailable = $this->filter->filterToOnly($mailable, $plan->delivered);
            $this->preparer->prepare($deliveredMailable, $correlationHeader, $correlationId, $mailerName);
            $sent = Mail::mailer($mailerName)->send($deliveredMailable);
            $actualRecipients = array_merge(
                $actualRecipients,
                $this->resolveActualRecipients($sent, $deliveredMailable),
            );
            $primarySent = $sent;
        }

        $redirectMailable = $this->filter->withSubject(
            $mailable,
            $this->prefixer->prefix(
                $config->testModeSubjectPrefix(),
                $originalSubject,
                $plan->redirected,
            ),
        );
        $this->preparer->prepare($redirectMailable, $correlationHeader, $correlationId, $mailerName);

        $mailer = Mail::mailer($mailerName);
        $mailer->alwaysTo($config->testModeRedirectTo(), $config->testModeRedirectName());
        $redirectSent = $mailer->send($redirectMailable);
        Mail::purge($mailerName);

        $actualRecipients[] = strtolower($config->testModeRedirectTo());
        $primarySent ??= $redirectSent;

        return new TestModeSendResult(
            sent: $primarySent,
            actualRecipients: array_values(array_unique($actualRecipients)),
            status: MailSendStatus::Suppressed,
        );
    }

    /**
     * @return list<string>
     */
    private function resolveActualRecipients(mixed $sent, Mailable $mailable): array
    {
        $symfonySent = $this->symfonySentMessage($sent);

        if ($symfonySent instanceof SymfonySentMessage) {
            $fromSent = $this->inspector->recipientsFromSent($symfonySent);

            if ($fromSent !== []) {
                return $fromSent;
            }
        }

        return $this->inspector->recipients($mailable);
    }

    private function symfonySentMessage(mixed $sent): ?SymfonySentMessage
    {
        if ($sent instanceof SymfonySentMessage) {
            return $sent;
        }

        if ($sent instanceof LaravelSentMessage) {
            return $sent->getSymfonySentMessage();
        }

        return null;
    }
}
