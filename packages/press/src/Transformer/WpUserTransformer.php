<?php

namespace Moox\Press\Transformer;

use Moox\Core\Traits\LogLevel;
use Moox\Press\Models\WpUser;
use Moox\Sync\Transformer\AbstractTransformer;

class WpUserTransformer extends AbstractTransformer
{
    use LogLevel;

    protected $wpUser;

    public function __construct(WpUser $wpUser)
    {
        parent::__construct($wpUser::query());
        $this->wpUser = $wpUser;
    }

    protected function transformCustomFields(array $data): array
    {
        $this->logDebug('Moox Press: Transform custom fields', [
            'data' => $data,
        ]);

        $mainFields = $this->getMainFields();

        $this->logDebug('Moox Press: Main fields', [
            'main_fields' => $mainFields,
        ]);

        foreach ($mainFields as $field) {
            if (! isset($data[$field])) {
                $data[$field] = $this->wpUser->$field ?? null;
            }
        }

        $this->logDebug('Moox Press: Main fields after', [
            'data' => $data,
        ]);

        $metaFields = $this->getMetaFields();
        foreach ($metaFields as $metaKey) {
            if (! isset($data[$metaKey])) {
                $data[$metaKey] = $this->wpUser->getMeta($metaKey) ?? config("press.default_user_meta.{$metaKey}", '');
            }
        }

        $this->logDebug('Moox Press: Meta fields after', [
            'data' => $data,
        ]);

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
        return 10;
    }

    public function transform(): array
    {
        $this->logDebug('WpUserTransformer: Starting transformation', ['user_id' => $this->wpUser->ID]);

        $mainFields = $this->getMainFields();
        $metaFields = $this->getMetaFields();

        $transformedData = [];

        foreach ($mainFields as $field) {
            $transformedData[$field] = $this->wpUser->$field;
        }

        foreach ($metaFields as $field) {
            $transformedData[$field] = $this->wpUser->$field;
        }

        $this->logDebug('WpUserTransformer: Transformed data', ['data' => $transformedData]);

        return $transformedData;
    }
}
