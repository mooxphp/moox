<?php

declare(strict_types=1);

namespace Moox\Builder\Storage;

use Illuminate\Contracts\Container\Container;

class ValueStoreResolver
{
    public function __construct(
        protected Container $container,
    ) {}

    public function for(): ValueStore
    {
        $driver = (string) config('builder.default_driver', 'typed');
        $class = config("builder.drivers.{$driver}");

        if (! is_string($class) || $class === '') {
            throw new \InvalidArgumentException("Builder value store driver [{$driver}] is not configured.");
        }

        return $this->container->make($class);
    }
}
