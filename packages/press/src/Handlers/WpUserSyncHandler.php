<?php

namespace Moox\Press\Handlers;

use Illuminate\Support\Facades\DB;
use Moox\Sync\Handlers\AbstractSyncHandler;

class WpUserSyncHandler extends AbstractSyncHandler
{
    protected $tableName;

    protected $metaTableName;

    public function __construct(string $modelClass, array $modelData, string $eventType)
    {
        parent::__construct($modelClass, $modelData, $eventType);
        $this->tableName = (new $modelClass)->getTable();
        $this->metaTableName = str_replace('users', 'usermeta', $this->tableName);
    }

    protected function syncModel()
    {
        $mainRecordId = $this->syncMainRecord();
        $this->syncMetaData($mainRecordId);
    }

    protected function deleteModel()
    {
        $idField = $this->getIdField();
        DB::table($this->tableName)->where($idField, $this->modelData[$idField])->delete();
        DB::table($this->metaTableName)->where($this->getForeignKeyName(), $this->modelData[$idField])->delete();
    }

    protected function syncMainRecord()
    {
        $mainTableData = $this->getMainTableData();
        $mainTableData = $this->ensureAllFieldsHaveValue($mainTableData);

        $idField = $this->getIdField();

        DB::table($this->tableName)->updateOrInsert(
            [$idField => $mainTableData[$idField]],
            $mainTableData
        );

        return $mainTableData[$idField];
    }

    protected function syncMetaData($mainRecordId)
    {
        $metaData = $this->getMetaData();
        $metaData = $this->ensureAllFieldsHaveValue($metaData);
        $foreignKeyName = $this->getForeignKeyName();

        foreach ($metaData as $key => $value) {
            DB::table($this->metaTableName)->updateOrInsert(
                [
                    $foreignKeyName => $mainRecordId,
                    'meta_key' => $key,
                ],
                ['meta_value' => $value]
            );
        }
    }

    protected function ensureAllFieldsHaveValue(array $data): array
    {
        return array_map(function ($value) {
            return $value ?? '';
        }, $data);
    }

    protected function getMainTableData(): array
    {
        $mainFields = $this->getMainFields();

        return array_intersect_key($this->modelData, array_flip($mainFields));
    }

    protected function getMetaData(): array
    {
        $defaultMeta = config('press.default_user_meta', []);

        return array_intersect_key($this->modelData, $defaultMeta);
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
