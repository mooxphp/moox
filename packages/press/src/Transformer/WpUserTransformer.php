<?php

namespace Moox\Press\Transformer;

use Moox\Sync\Transformer\AbstractTransformer;

class WpUserTransformer extends AbstractTransformer
{
    protected function transformCustomFields(array $data): array
    {
        $metaFields = $this->getMetaFields();
        foreach ($metaFields as $metaKey) {
            $data[$metaKey] = $this->getMetaValue($metaKey) ?? config("press.default_user_meta.{$metaKey}", '');
        }

        return $data;
    }

    protected function getMetaFields(): array
    {
        return array_keys(config('press.default_user_meta', []));
    }
}
