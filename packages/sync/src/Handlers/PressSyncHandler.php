<?php

namespace Moox\Sync\Handlers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moox\Core\Traits\LogLevel;

class PressSyncHandler
{
    use LogLevel;

    protected $modelClass;

    protected $modelData;

    public function __construct(string $modelClass, array $modelData)
    {
        $this->modelClass = $modelClass;
        $this->modelData = $modelData;
    }

    public function sync()
    {
        DB::beginTransaction();

        try {
            $mainRecord = $this->syncMainRecord();
            $this->syncMetaData($mainRecord);

            DB::commit();

            return $mainRecord;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logDebug('Sync failed: '.$e->getMessage(), [
                'model_class' => $this->modelClass,
                'model_data' => $this->modelData,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function syncMainRecord(): Model
    {
        $mainTableData = $this->getMainTableData();
        $idField = $this->getIdField();

        $model = $this->modelClass::updateOrCreate(
            [$idField => $mainTableData[$idField]],
            $mainTableData
        );

        return $model;
    }

    protected function syncMetaData(Model $mainRecord)
    {
        $metaData = $this->getMetaData();
        $metaModel = $this->getMetaModel($mainRecord);

        foreach ($metaData as $key => $value) {
            $metaModel::updateOrCreate(
                [
                    $this->getForeignKeyName($mainRecord) => $mainRecord->getKey(),
                    'meta_key' => $key,
                ],
                ['meta_value' => $value]
            );
        }
    }

    protected function getMainTableData(): array
    {
        $mainFields = $this->getMainFields();
        $mainTableData = array_intersect_key($this->modelData, array_flip($mainFields));

        $mainTableData['user_url'] = $mainTableData['user_url'] ?? '';
        $mainTableData['user_activation_key'] = $mainTableData['user_activation_key'] ?? '';

        return $mainTableData;
    }

    protected function getMetaData(): array
    {
        $defaultMeta = config('press.default_user_meta', []);
        $metaData = array_intersect_key($this->modelData, array_flip($defaultMeta));

        foreach ($defaultMeta as $key) {
            $metaData[$key] = $metaData[$key] ?? '';
        }

        return $metaData;
    }

    protected function getMainFields(): array
    {
        return [
            'ID', 'user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url',
            'user_registered', 'user_activation_key', 'user_status', 'display_name',
        ];
    }

    protected function getMetaModel(Model $mainRecord): string
    {
        switch (get_class($mainRecord)) {
            case \Moox\Press\Models\WpUser::class:
                return \Moox\Press\Models\WpUserMeta::class;
            case \Moox\Press\Models\WpPost::class:
                return \Moox\Press\Models\WpPostMeta::class;
            default:
                throw new \Exception('Unsupported model class: '.get_class($mainRecord));
        }
    }

    protected function getIdField(): string
    {
        $idFields = config('sync.local_identifier_fields', ['ID', 'uuid', 'ulid', 'id']);
        foreach ($idFields as $field) {
            if (isset($this->modelData[$field])) {
                return $field;
            }
        }
        throw new \Exception('No suitable ID field found for model');
    }

    protected function getForeignKeyName(Model $mainRecord): string
    {
        switch (get_class($mainRecord)) {
            case \Moox\Press\Models\WpUser::class:
                return 'user_id';
            case \Moox\Press\Models\WpPost::class:
                return 'post_id';
            default:
                throw new \Exception('Unsupported model class: '.get_class($mainRecord));
        }
    }
}
