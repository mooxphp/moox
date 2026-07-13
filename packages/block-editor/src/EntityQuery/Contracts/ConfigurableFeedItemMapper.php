<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Contracts;

use Moox\BlockEditor\EntityQuery\Mapping\FeedItemMapping;

interface ConfigurableFeedItemMapper extends FeedItemMapper
{
    public function withMapping(FeedItemMapping $mapping): self;
}
