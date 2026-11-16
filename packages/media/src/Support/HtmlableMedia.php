<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Moox\Media\Models\Media as MooxMedia;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Image;
use Spatie\MediaLibrary\Conversions\ImageGenerators\ImageGeneratorFactory;
use Spatie\MediaLibrary\MediaCollections\HtmlableMedia as SpatieHtmlableMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

/**
 * Spatie-compatible rendering entrypoint with Moox delivery profiles.
 *
 * Default (no conversion): delivery profile `web` via MediaPictureBuilder.
 * Explicit conversion: simple admin/preview `<img>` (no Spatie responsive images).
 * `profile()` always wins over conversion.
 *
 * @see https://spatie.be/docs/laravel-medialibrary/v11/advanced-usage/rendering-media
 *
 * Examples:
 *   {{ $media->img() }}
 *   {{ $media->img()->sizes('100vw')->alt('Hero') }}
 *   {{ $media->img()->profile('mail') }}
 *   {{ $media->img('thumbnail')->lazy() }}
 */
class HtmlableMedia extends SpatieHtmlableMedia
{
    protected ?string $profile = null;

    protected ?string $sizesHint = null;

    protected ?string $altOverride = null;

    public function profile(string $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    public function sizes(?string $sizes): self
    {
        $this->sizesHint = $sizes;

        return $this;
    }

    public function alt(string $alt): self
    {
        $this->altOverride = $alt;

        return $this;
    }

    public function toHtml(): string
    {
        if ($this->shouldRenderDelivery()) {
            return $this->renderDelivery();
        }

        return $this->renderConversion();
    }

    protected function shouldRenderDelivery(): bool
    {
        if ($this->profile !== null && $this->profile !== '') {
            return true;
        }

        return $this->conversionName === '';
    }

    protected function renderDelivery(): string
    {
        $media = $this->resolveMooxMedia();
        if ($media === null) {
            return '';
        }

        $attributes = $this->extraAttributes;
        if ($this->loadingAttributeValue !== '') {
            $attributes['loading'] = $this->loadingAttributeValue;
        }

        $profile = ($this->profile !== null && $this->profile !== '')
            ? $this->profile
            : MediaDeliveryConversions::PROFILE_WEB;

        return app(MediaPictureBuilder::class)->toHtml(
            $media,
            $this->sizesHint,
            $this->altOverride,
            $attributes,
            $profile,
        );
    }

    /**
     * Admin/preview conversion: single <img>, never Spatie responsive views.
     */
    protected function renderConversion(): string
    {
        $imageGenerator = ImageGeneratorFactory::forMedia($this->media) ?? new Image;

        if (! $imageGenerator->canHandleMime($this->media->mime_type)) {
            return '';
        }

        try {
            $src = $this->media->getUrl($this->conversionName);
        } catch (\Throwable) {
            return '';
        }

        if ($src === '') {
            return '';
        }

        $attributes = $this->extraAttributes;

        if ($this->altOverride !== null) {
            $attributes['alt'] = $this->altOverride;
        } elseif (! array_key_exists('alt', $attributes)) {
            $attributes['alt'] = $this->resolveConversionAlt();
        }

        $loading = $this->loadingAttributeValue !== ''
            ? $this->loadingAttributeValue
            : (string) (config('media-library.default_loading_attribute_value') ?? '');

        if ($loading !== '') {
            $attributes['loading'] = $loading;
        }

        $attributes['src'] = $src;

        $parts = [];
        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            $parts[] = sprintf('%s="%s"', $name, e((string) $value));
        }

        return '<img '.implode(' ', $parts).'>';
    }

    protected function resolveConversionAlt(): string
    {
        $media = $this->resolveMooxMedia();
        if ($media === null) {
            return (string) ($this->media->name ?? $this->media->file_name ?? '');
        }

        $metadata = app(MediaLocaleResolver::class)->mediaMetadata($media->loadMissing('translations'));

        return (string) ($metadata['alt'] ?? $metadata['title'] ?? $media->file_name ?? '');
    }

    protected function resolveMooxMedia(): ?MooxMedia
    {
        /** @var BaseMedia $media */
        $media = $this->media;

        if ($media instanceof MooxMedia) {
            return $media;
        }

        if ($media->getKey() === null) {
            return null;
        }

        return MooxMedia::query()->find($media->getKey());
    }
}
