<?php

declare(strict_types=1);

namespace Moox\Address\Resources\Address\Pages\Concerns;

use Illuminate\Support\MessageBag;

trait InitializesValidationBag
{
    public function getErrorBag(): MessageBag
    {
        $bag = parent::getErrorBag();

        if ($bag === null) {
            $this->resetErrorBag();

            $bag = parent::getErrorBag();
        }

        return $bag ?? new MessageBag;
    }
}
