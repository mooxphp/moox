<?php

declare(strict_types=1);

namespace Moox\EBilling\Delivery;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
use InvalidArgumentException;
use Moox\EBilling\Contracts\DeliveryChannelInterface;
use Moox\EBilling\Contracts\RecipientOverridableDeliveryChannel;
use Moox\EBilling\Data\SelectiveRedispatchPlan;
use Moox\EBilling\Models\EbillingDeliveryAttempt;

/**
 * Builds selective-redispatch modal defaults from configured channels + attempts (ADR 0008),
 * including the recipient-override warnings (ADR 0016).
 */
final class SelectiveRedispatchPlanner
{
    /**
     * @return list<string>
     */
    public function configuredChannelKeys(): array
    {
        return array_map(
            static fn (DeliveryChannelInterface $channel): string => $channel->key(),
            $this->configuredChannels(),
        );
    }

    /**
     * Configured channels that accept a recipient override (ADR 0016).
     *
     * @return list<string>
     */
    public function overridableChannelKeys(): array
    {
        return array_values(array_map(
            static fn (DeliveryChannelInterface $channel): string => $channel->key(),
            array_filter(
                $this->configuredChannels(),
                static fn (DeliveryChannelInterface $channel): bool => $channel instanceof RecipientOverridableDeliveryChannel,
            ),
        ));
    }

    /**
     * One warning per selected channel whose latest wave succeeded. With an override, an overridable
     * channel states the redirection instead of the generic re-send warning.
     *
     * @param  list<string>  $selected
     * @param  list<string>  $overrideAddresses
     * @param  list<string>  $resolvedAddresses
     * @return list<string>
     */
    public function warningLines(
        SelectiveRedispatchPlan $plan,
        array $selected,
        array $overrideAddresses,
        array $resolvedAddresses,
    ): array {
        $overridable = $overrideAddresses === [] ? [] : $this->overridableChannelKeys();
        $lines = [];

        foreach ($plan->warningKeys($selected) as $key) {
            $channel = $plan->labels[$key] ?? $key;

            $lines[] = in_array($key, $overridable, true)
                ? __('e-billing::fields.redispatch_override_warning_line', [
                    'channel' => $channel,
                    'override' => implode(', ', $overrideAddresses),
                    'resolved' => $resolvedAddresses === []
                        ? __('e-billing::fields.redispatch_override_no_resolved_recipient')
                        : implode(', ', $resolvedAddresses),
                ])
                : __('e-billing::fields.redispatch_success_warning_line', ['channel' => $channel]);
        }

        return $lines;
    }

    /**
     * @return list<DeliveryChannelInterface>
     */
    private function configuredChannels(): array
    {
        $channels = config('e-billing.delivery.channels', []);

        if (! is_array($channels)) {
            throw new InvalidArgumentException("config('e-billing.delivery.channels') must be an array of class names.");
        }

        $instances = [];

        foreach ($channels as $channelClass) {
            if (! is_string($channelClass) || $channelClass === '') {
                throw new InvalidArgumentException('Each delivery channel must be a non-empty class name.');
            }

            if (! is_a($channelClass, DeliveryChannelInterface::class, true)) {
                throw new InvalidArgumentException(
                    'Delivery channel must implement '.DeliveryChannelInterface::class.': '.$channelClass
                );
            }

            /** @var DeliveryChannelInterface $channel */
            $channel = app($channelClass);
            $instances[] = $channel;
        }

        return $instances;
    }

    /**
     * @param  list<string>  $configuredKeys
     * @param  iterable<int, EbillingDeliveryAttempt>  $attempts
     */
    public function plan(array $configuredKeys, iterable $attempts): SelectiveRedispatchPlan
    {
        /** @var array<string, list<EbillingDeliveryAttempt>> $byChannel */
        $byChannel = [];

        foreach ($attempts as $attempt) {
            $channel = (string) $attempt->channel;

            if ($channel === '') {
                continue;
            }

            $byChannel[$channel][] = $attempt;
        }

        $channelKeys = [];
        $labels = [];
        $hints = [];
        $defaultSelected = [];
        $successful = [];

        foreach ($configuredKeys as $key) {
            if ($key === '') {
                continue;
            }

            $channelKeys[] = $key;
            $labels[$key] = $this->labelFor($key);
            $channelAttempts = $byChannel[$key] ?? [];

            if ($channelAttempts === []) {
                $defaultSelected[] = $key;
                $hints[$key] = __('e-billing::fields.redispatch_hint_never');

                continue;
            }

            $wave = $this->latestWave($channelAttempts);
            $waveSucceeded = $wave !== [] && array_reduce(
                $wave,
                static fn (bool $carry, EbillingDeliveryAttempt $row): bool => $carry && $row->success,
                true,
            );

            if ($waveSucceeded) {
                $successful[] = $key;
            } else {
                $defaultSelected[] = $key;
            }

            $hints[$key] = $this->hintFor($wave, $waveSucceeded);
        }

        return new SelectiveRedispatchPlan(
            channelKeys: $channelKeys,
            defaultSelectedKeys: $defaultSelected,
            successfulKeys: $successful,
            labels: $labels,
            hints: $hints,
        );
    }

    private function labelFor(string $key): string
    {
        $translationKey = 'e-billing::fields.delivery_channel_'.$key;

        if (Lang::has($translationKey)) {
            return __($translationKey);
        }

        return $key;
    }

    /**
     * Attempts sharing the latest created_at (same second) — one deliver() may write many rows.
     *
     * @param  list<EbillingDeliveryAttempt>  $attempts
     * @return list<EbillingDeliveryAttempt>
     */
    private function latestWave(array $attempts): array
    {
        $maxTs = null;

        foreach ($attempts as $attempt) {
            $ts = $this->attemptTimestamp($attempt);

            if ($maxTs === null || $ts >= $maxTs) {
                $maxTs = $ts;
            }
        }

        if ($maxTs === null) {
            return $attempts;
        }

        $wave = [];

        foreach ($attempts as $attempt) {
            if ($this->attemptTimestamp($attempt) === $maxTs) {
                $wave[] = $attempt;
            }
        }

        return $wave;
    }

    private function attemptTimestamp(EbillingDeliveryAttempt $attempt): int
    {
        $createdAt = $attempt->created_at;

        return $createdAt instanceof Carbon ? $createdAt->getTimestamp() : 0;
    }

    /**
     * @param  list<EbillingDeliveryAttempt>  $wave
     */
    private function hintFor(array $wave, bool $waveSucceeded): string
    {
        $status = $waveSucceeded
            ? __('e-billing::fields.delivery_outcome_success')
            : __('e-billing::fields.delivery_outcome_failure');

        $when = '';
        $first = $wave[0] ?? null;

        if ($first instanceof EbillingDeliveryAttempt) {
            $createdAt = $first->created_at;

            if ($createdAt instanceof Carbon) {
                $when = $createdAt->timezone((string) config('app.timezone'))->format('Y-m-d H:i');
            }
        }

        $recipients = [];

        foreach ($wave as $attempt) {
            $recipient = trim((string) $attempt->recipient);

            if ($recipient !== '' && ! in_array($recipient, $recipients, true)) {
                $recipients[] = $recipient;
            }
        }

        $parts = [$status];

        if ($when !== '') {
            $parts[] = $when;
        }

        if ($recipients !== []) {
            $parts[] = implode(', ', $recipients);
        }

        return implode(' · ', $parts);
    }
}
