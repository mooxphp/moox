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
            $this->logInfo('Moox Sync: Starting sync process', [
                'model_class' => $this->modelClass,
                'model_data' => $this->modelData,
            ]);

            $existingData = DB::table($this->tableName)->where($this->getIdField(), $this->modelData[$this->getIdField()])->first();
            $this->logInfo('Moox Sync: Existing data before sync', ['existing_data' => $existingData]);

            $beforeMainData = $this->getMainRecordData($this->modelData[$this->getIdField()]);
            $this->logInfo('Moox Sync: Main record data before sync', ['before_data' => $beforeMainData]);

            $mainRecordId = $this->syncMainRecord();
            $this->syncMetaData($mainRecordId);

            $afterMainData = $this->getMainRecordData($mainRecordId);
            $this->logInfo('Moox Sync: Main record data after sync', ['after_data' => $afterMainData]);

            $mainDataDifferences = $this->compareData($beforeMainData, $afterMainData);
            $this->logInfo('Moox Sync: Main record data differences', ['differences' => $mainDataDifferences]);

            $this->logInfo('Moox Sync: About to commit transaction');
            DB::commit();
            $this->logInfo('Moox Sync: Transaction committed');

            $updatedData = DB::table($this->tableName)->where($this->getIdField(), $this->modelData[$this->getIdField()])->first();
            $this->logInfo('Moox Sync: Updated data after sync', ['updated_data' => $updatedData]);

            $this->logDebug('Moox Sync: Sync process completed successfully', [
                'main_record_id' => $mainRecordId,
            ]);

            return $mainRecordId;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logDebug('Moox Sync: Sync failed: '.$e->getMessage(), [
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

        $this->logInfo('Moox Sync: Syncing main record', [
            'table' => $this->tableName,
            'id_field' => $idField,
            'id_value' => $mainTableData[$idField],
            'data' => $mainTableData,
        ]);

        DB::enableQueryLog();

        // Check if the record already exists
        $exists = DB::table($this->tableName)
            ->where($idField, $mainTableData[$idField])
            ->exists();

        if ($exists) {
            // Update existing record
            $updateData = array_diff_key($mainTableData, [$idField => true]);
            $affected = DB::table($this->tableName)
                ->where($idField, $mainTableData[$idField])
                ->update($updateData);
            $operation = 'update';
        } else {
            // Insert new record
            $affected = DB::table($this->tableName)->insert($mainTableData);
            $operation = 'insert';
        }

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->logInfo('Moox Sync: Main record sync result', [
            'affected_rows' => $affected,
            'queries' => $queries,
            'operation' => $operation,
        ]);

        return $mainTableData[$idField];
    }

    protected function syncMetaData($mainRecordId)
    {
        $metaData = $this->getMetaData();
        $foreignKeyName = $this->getForeignKeyName();

        $this->logInfo('Moox Sync: Starting meta data sync', [
            'main_record_id' => $mainRecordId,
            'foreign_key_name' => $foreignKeyName,
            'meta_data' => $metaData,
        ]);

        foreach ($metaData as $key => $value) {
            $serializedValue = is_array($value) ? serialize($value) : $value;
            $this->logInfo('Moox Sync: Syncing meta item', [
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

        $this->logInfo('Moox Sync: Completed meta data sync', [
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
        $this->logInfo('Moox Sync: Default meta keys', ['default_meta' => $defaultMeta]);

        $metaData = array_intersect_key($this->modelData, $defaultMeta);
        $this->logInfo('Moox Sync: Initial meta data', ['meta_data' => $metaData]);

        foreach ($defaultMeta as $metaKey => $defaultValue) {
            if (! isset($metaData[$metaKey])) {
                $metaData[$metaKey] = $defaultValue;
            }
        }

        $this->logInfo('Moox Sync: Final meta data', ['meta_data' => $metaData]);

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

    protected function checkDataTypes($mainTableData)
    {
        $tableColumns = DB::getSchemaBuilder()->getColumnListing($this->tableName);
        $columnTypes = [];
        foreach ($tableColumns as $column) {
            $columnTypes[$column] = DB::getSchemaBuilder()->getColumnType($this->tableName, $column);
        }

        $potentialMismatches = [];
        foreach ($mainTableData as $field => $value) {
            if (isset($columnTypes[$field])) {
                $expectedType = $columnTypes[$field];
                $actualType = gettype($value);
                if ($this->isTypeMismatch($expectedType, $actualType)) {
                    $potentialMismatches[$field] = [
                        'expected' => $expectedType,
                        'actual' => $actualType,
                        'value' => $value,
                    ];
                }
            }
        }

        if (! empty($potentialMismatches)) {
            $this->logDebug('Moox Sync: Potential data type mismatches', ['mismatches' => $potentialMismatches]);
        }
    }

    protected function isTypeMismatch($expectedType, $actualType)
    {
        $typeMap = [
            'int' => ['integer'],
            'bigint' => ['integer'],
            'varchar' => ['string'],
            'text' => ['string'],
            'datetime' => ['string', 'object'],
            // Add more mappings as needed
        ];

        return ! in_array($actualType, $typeMap[$expectedType] ?? [$expectedType]);
    }

    protected function getMainRecordData($id)
    {
        return DB::table($this->tableName)->where($this->getIdField(), $id)->first();
    }

    protected function compareData($before, $after)
    {
        $differences = [];
        if ($before && $after) {
            foreach ((array) $before as $key => $value) {
                if ($value !== $after->$key) {
                    $differences[$key] = [
                        'before' => $value,
                        'after' => $after->$key,
                    ];
                }
            }
        }

        return $differences;
    }
}
