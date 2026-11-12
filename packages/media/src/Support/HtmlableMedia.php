<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Moox\Media\Models\Media as MooxMedia;
use Spatie\MediaLibrary\MediaCollections\HtmlableMedia as SpatieHtmlableMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

/**
 * Spatie Media Library rendering with optional Moox delivery profiles.
 *
 * @see https://spatie.be/docs/laravel-medialibrary/v11/advanced-usage/rendering-media
 *
 * Examples:
 *   {{ $media->img() }}
 *   {{ $media->img('thumb')->lazy()->attributes(['class' => 'rounded']) }}
 *   {{ $media->img()->profile('web')->sizes('100vw') }}
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
        if ($this->profile !== null && $this->profile !== '') {
            $media = $this->resolveMooxMedia();
            if ($media === null) {
                return '';
            }

            $attributes = $this->extraAttributes;
            if ($this->loadingAttributeValue !== '') {
                $attributes['loading'] = $this->loadingAttributeValue;
            }

            return app(MediaPictureBuilder::class)->toHtml(
                $media,
                $this->sizesHint,
                $this->altOverride,
                $attributes,
                $this->profile,
            );
        }

        return parent::toHtml();
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
