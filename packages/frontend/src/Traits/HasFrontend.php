<?php

namespace Moox\Frontend\Traits;

use Illuminate\Support\Facades\Config;

trait HasFrontend
{
    // TODO: Error handling if required fields are not set, etc.

    // TODO: these fields are used in the frontend, should be defined in the resource, otherwise all fields are used
    public function frontendFields(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
        ];
    }

    public static function getFrontendUrlPath(): string
    {
        // TODO: the frontend config must be merged from all packages
        return Config::get('frontend.page.url_path', '');
    }

    public function getFrontendUrl(): string
    {
        if (! $this->hasValidFrontendFields()) {
            return '';
        }

        if ($this->getFrontendStatus() === 'deleted') {
            return route('frontend.deleted', ['uuid' => $this->uuid]);
        }

        if ($this->getFrontendStatus() === 'draft') {
            return route('frontend.draft', ['uuid' => $this->uuid]);
        }

        if ($this->getFrontendStatus() === 'scheduled') {
            return route('frontend.scheduled', ['uuid' => $this->uuid]);
        }

        return route('frontend.live', ['slug' => $this->slug]);
    }

    public function hasValidFrontendFields(): bool
    {
        return isset($this->slug) && isset($this->uuid);
    }

    public function getFrontendStatus(): string
    {
        if (property_exists($this, 'deleted_at') && $this->deleted_at !== null) {
            return 'deleted';
        }

        if (property_exists($this, 'published_at')) {
            return match (true) {
                $this->published_at === null => 'draft',
                $this->published_at > now() => 'scheduled',
                default => 'published'
            };
        }

        return 'published';
    }
}
