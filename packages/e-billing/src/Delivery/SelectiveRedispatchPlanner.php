<?php

declare(strict_types=1);

namespace Moox\EBilling\Delivery;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
use InvalidArgumentException;
use Moox\EBilling\Contracts\DeliveryChannelInterface;
use Moox\EBilling\Data\SelectiveRedispatchPlan;
use Moox\EBilling\Models\EbillingDeliveryAttempt;

/**
 * Builds selective-redispatch modal defaults from configured channels + attempts (ADR 0008).
 */
final class SelectiveRedispatchPlanner
{
    /**
     * @return list<string>
     */
    public function configuredChannelKeys(): array
    {
        $channels = config('e-billing.delivery.channels', []);

        if (! is_array($channels)) {
            throw new InvalidArgumentException("config('e-billing.delivery.channels') must be an array of class names.");
        }

        $keys = [];

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
            $keys[] = $channel->key();
        }

        return $keys;
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
