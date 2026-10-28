<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Facades\Log;
use Moox\EBilling\Contracts\RecipientFormatPreferenceResolverInterface;
use Moox\EBilling\Data\EffectiveFormat;
use Moox\EBilling\Data\FormatPreference;
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
        private RecipientFormatPreferenceResolverInterface $preferenceResolver,
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
            $preference = $this->preferenceResolver->resolve($document);
        } catch (Throwable $exception) {
            throw new FormatPreferenceUnavailableException(
                "Recipient format preference for e-billing document [{$document->getKey()}] could not be looked up.",
                previous: $exception,
            );
        }

        if ($preference === null) {
            return $this->resolveDefault();
        }

        if (! $this->registry->has($preference->format)) {
            throw new UnknownFormatException(
                "Unknown e-billing format [{$preference->format}] from recipient preference."
            );
        }

        $this->assertPreferenceProfileAllowed($preference);

        $definition = $this->registry->get($preference->format);
        $profile = $preference->profile ?? $definition->profile;

        return new EffectiveFormat($preference->format, $profile);
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
     * Preference chain: customer column → config (default true). A failing customer lookup falls back to config.
     */
    public function resolveSendVisualCopy(EbillingDocument $document): bool
    {
        try {
            $customer = $document->customer ?? (new CustomerMatcher)->forDocument($document);
        } catch (Throwable $exception) {
            Log::error('[EBilling] Customer lookup for the visual copy failed; using the configured default', [
                'ebilling_document_id' => $document->getKey(),
                'exception' => $exception,
            ]);
            $customer = null;
        }

        if ($customer !== null && $customer->send_visual_copy !== null) {
            return (bool) $customer->send_visual_copy;
        }

        return (bool) config('e-billing.send_visual_copy', true);
    }

    private function assertPreferenceProfileAllowed(FormatPreference $preference): void
    {
        $definition = $this->registry->get($preference->format);
        $profile = $preference->profile;

        if ($definition->artifactKind === ArtifactKind::Xml) {
            if ($profile !== null) {
                throw new InvalidFormatPreferenceException(
                    "Format [{$preference->format}] does not accept a profile preference; profile must be null."
                );
            }

            return;
        }

        if ($profile === null) {
            return;
        }

        AllowedProfiles::assertContains($profile, $preference->format);
    }

    private function isFrozen(EbillingDocument $document): bool
    {
        return is_string($document->xml_storage_path) && $document->xml_storage_path !== '';
    }
}
