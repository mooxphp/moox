<?php

namespace Moox\Press\Transformer;

use Moox\Sync\Transformer\AbstractTransformer;

class WpUserTransformer extends AbstractTransformer
{
    protected function transformCustomFields(array $data): array
    {
        $mainFields = $this->getMainFields();

        foreach ($mainFields as $field) {
            if (! isset($data[$field])) {
                $data[$field] = $this->model->$field ?? null;
            }
        }

        $metaFields = $this->getMetaFields();
        foreach ($metaFields as $metaKey) {
            $data[$metaKey] = $this->getMetaValue($metaKey) ?? config("press.default_user_meta.{$metaKey}", '');
        }

        return $data;
    }

    protected function getMainFields(): array
    {
        return [
            'ID', 'user_login', 'user_pass', 'user_nicename', 'user_email',
            'user_url', 'user_registered', 'user_activation_key', 'user_status',
            'display_name',
        ];
    }

    protected function getMetaFields(): array
    {
        return array_keys(config('press.default_user_meta', []));
    }

    public function getDelay(): int
    {
        return 5;
    }
}
