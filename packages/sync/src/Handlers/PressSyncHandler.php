<?php

namespace Moox\Sync\Handlers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Moox\Core\Traits\LogLevel;

class PressSyncHandler
{
    use LogLevel;

    protected $modelClass;

    protected $modelData;

    protected $tableName;

    protected $metaTableName;

    public function __construct(string $modelClass, array $modelData)
    {
        $this->modelClass = $modelClass;
        $this->modelData = $modelData;
        $this->tableName = (new $modelClass)->getTable();
        $this->metaTableName = str_replace('users', 'usermeta', $this->tableName);
    }

    public function sync()
    {
        DB::beginTransaction();

        try {
            $this->logDebug('Starting sync process', [
                'model_class' => $this->modelClass,
                'model_data' => $this->modelData,
            ]);

            $mainRecordId = $this->syncMainRecord();
            $this->syncMetaData($mainRecordId);

            DB::commit();

            $this->logDebug('Sync process completed successfully', [
                'main_record_id' => $mainRecordId,
            ]);

            return $mainRecordId;
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

    protected function syncMainRecord()
    {
        $mainTableData = $this->getMainTableData();
        $idField = $this->getIdField();

        $this->logDebug('Syncing main record', [
            'table' => $this->tableName,
            'id_field' => $idField,
            'id_value' => $mainTableData[$idField],
            'data' => $mainTableData,
        ]);

        DB::table($this->tableName)->updateOrInsert(
            [$idField => $mainTableData[$idField]],
            $mainTableData
        );

        return $mainTableData[$idField];
    }

    protected function syncMetaData($mainRecordId)
    {
        $metaData = $this->getMetaData();
        $foreignKeyName = $this->getForeignKeyName();

        $this->logDebug('Starting meta data sync', [
            'main_record_id' => $mainRecordId,
            'foreign_key_name' => $foreignKeyName,
            'meta_data' => $metaData,
        ]);

        foreach ($metaData as $key => $value) {
            $serializedValue = is_array($value) ? serialize($value) : $value;
            $this->logDebug('Syncing meta item', [
                'key' => $key,
                'value' => $value,
                'serialized_value' => $serializedValue,
            ]);

            DB::table($this->metaTableName)->updateOrInsert(
                [
                    $foreignKeyName => $mainRecordId,
                    'meta_key' => $key,
                ],
                ['meta_value' => $serializedValue]
            );
        }

        $this->logDebug('Completed meta data sync', [
            'table' => $this->metaTableName,
            'main_record_id' => $mainRecordId,
            'meta_data' => $metaData,
        ]);
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
        $defaultMeta = Config::get('press.default_user_meta', []);
        $this->logDebug('Default meta keys', ['default_meta' => $defaultMeta]);

        $metaData = array_intersect_key($this->modelData, $defaultMeta);
        $this->logDebug('Initial meta data', ['meta_data' => $metaData]);

        foreach ($defaultMeta as $metaKey => $defaultValue) {
            if (! isset($metaData[$metaKey])) {
                $metaData[$metaKey] = $defaultValue;
            }
        }

        $this->logDebug('Final meta data', ['meta_data' => $metaData]);

        return $metaData;
    }

    protected function getMainFields(): array
    {
        return [
            'ID', 'user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url',
            'user_registered', 'user_activation_key', 'user_status', 'display_name',
        ];
    }

    protected function getIdField(): string
    {
        return 'ID';
    }

    protected function getForeignKeyName(): string
    {
        return 'user_id';
    }
}
