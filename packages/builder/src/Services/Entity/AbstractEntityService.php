<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Moox\Builder\Services\ContextAwareService;
use Moox\Builder\Traits\ValidatesEntity;

abstract class AbstractEntityService extends ContextAwareService
{
    use ValidatesEntity;
}
