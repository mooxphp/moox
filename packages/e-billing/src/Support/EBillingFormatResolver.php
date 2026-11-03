<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Facades\Log;
use Moox\EBilling\Contracts\RecipientPreferenceResolver;
use Moox\EBilling\Data\EffectiveFormat;
use Moox\EBilling\Formats\ArtifactKind;
use Moox\EBilling\Formats\Exceptions\FormatPreferenceUnavailableException;
use Moox\EBilling\Formats\Exceptions\InvalidFormatPreferenceException;
use Moox\EBilling\Formats\Exceptions\UnknownFormatException;
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Models\EbillingDocument;
use Throwable;

final class EBillingFormatResolver
{
    public function __construct(
        private FormatRegistry $registry,
        private RecipientPreferenceResolver $preferenceResolver,
    ) {
    }

    /**
     * Once an artifact has been generated (xml_storage_path is set), format and
     * profile are frozen — retries use document.format + document.profile
     * (preference port is not consulted again). Both columns are required.
     *
     * @throws FormatPreferenceUnavailableException when the preference port fails (ADR 0020)
     */
    public function resolveForGeneration(EbillingDocument $document): EffectiveFormat
    {
        if ($this->isFrozen($document)) {
            $format = $document->format;
            $profile = $document->profile;

            if (! is_string($format) || $format === '' || ! is_string($profile) || $profile === '') {
                throw new InvalidFormatPreferenceException(
                    'Frozen e-billing document requires non-empty format and profile; retries use document.format and document.profile only.'
                );
            }

            return new EffectiveFormat($format, $profile);
        }

        try {
            $preferences = $this->preferenceResolver->resolve($document);
        } catch (Throwable $exception) {
            throw new FormatPreferenceUnavailableException(
                "Recipient format preference for e-billing document [{$document->getKey()}] could not be looked up.",
                previous: $exception,
            );
        }

        $format = $preferences->format;

        if ($format === null || $format === '') {
            return $this->resolveDefault();
        }

        if (! $this->registry->has($format)) {
            throw new UnknownFormatException(
                "Unknown e-billing format [{$format}] from recipient preference."
            );
        }

        $this->assertPreferenceProfileAllowed($format, $preferences->profile);

        return new EffectiveFormat($format, $preferences->profile ?? $this->registry->get($format)->profile);
    }

    /**
     * The configured default (`e-billing.default.format`) with its definition's profile.
     */
    public function resolveDefault(): EffectiveFormat
    {
        $format = (string) config('e-billing.default.format', 'zugferd');

        return new EffectiveFormat($format, $this->registry->get($format)->profile);
    }

    /**
     * Whether the human-readable XRechnung copy PDF should be attached to outbound mail.
     * The copy is always produced and downloadable; this only gates the mail attachment.
     *
     * Preference chain: recipient preference → config (default true). A failing lookup falls back to config.
     */
    public function resolveSendVisualCopy(EbillingDocument $document): bool
    {
        try {
            $sendVisualCopy = $this->preferenceResolver->resolve($document)->sendVisualCopy;
        } catch (Throwable $exception) {
            Log::error('[EBilling] Recipient preference lookup for the visual copy failed; using the default', [
                'ebilling_document_id' => $document->getKey(),
                'exception' => $exception,
            ]);
            $sendVisualCopy = null;
        }

        return $sendVisualCopy ?? (bool) config('e-billing.send_visual_copy', true);
    }

    private function assertPreferenceProfileAllowed(string $format, ?string $profile): void
    {
        if ($this->registry->get($format)->artifactKind === ArtifactKind::Xml) {
            if ($profile !== null) {
                throw new InvalidFormatPreferenceException(
                    "Format [{$format}] does not accept a profile preference; profile must be null."
                );
            }

            return;
        }

        if ($profile === null) {
            return;
        }

        AllowedProfiles::assertContains($profile, $format);
    }

    private function isFrozen(EbillingDocument $document): bool
    {
        return is_string($document->xml_storage_path) && $document->xml_storage_path !== '';
    }
}
