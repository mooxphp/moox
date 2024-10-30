<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Traits;

trait HandlesNamespacing
{
    protected function isPackageContext(): bool
    {
        return str_contains($this->entityNamespace, '\\src\\');
    }

    protected function getFilamentNamespace(string $type): string
    {
        if ($this->isPackageContext()) {
            return $this->entityNamespace.'\\'.ucfirst($type);
        }

        return $this->entityNamespace.'\\Filament\\'.ucfirst($type);
    }

    protected function getFilamentPath(string $type): string
    {
        if ($this->isPackageContext()) {
            return $this->entityPath.'/'.ucfirst($type);
        }

        return $this->entityPath.'/Filament/'.ucfirst($type);
    }
}
