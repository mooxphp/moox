<?php

namespace Moox\Press\Transformer;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Transformer\AbstractTransformer;

class WpUserTransformer extends AbstractTransformer
{
    use LogLevel;

    protected $tableName;

    protected $metaTableName;

    public function __construct($query)
    {
        parent::__construct($query);
        $this->tableName = $this->model->getTable();
        $this->metaTableName = str_replace('users', 'usermeta', $this->tableName);
    }

    public function transform(): array
    {
        $data = parent::transform();
        $this->logInfo('Moox Sync: Starting WpUser transform', ['data' => $data]);

        $mainData = $this->getMainTableData($data);
        $metaData = $this->getMetaData($data);

        $this->checkDataTypes($mainData);

        $transformedData = array_merge($mainData, $metaData);

        $this->logInfo('Moox Sync: WpUser transform completed', ['transformed_data' => $transformedData]);

        return $transformedData;
    }

    protected function transformCustomFields(array $data): array
    {
        // This method is now handled by getMainTableData and getMetaData
        return $data;
    }

    protected function getMainTableData(array $data): array
    {
        $mainFields = $this->getMainFields();
        $mainTableData = array_intersect_key($data, array_flip($mainFields));

        $mainTableData['user_url'] = $mainTableData['user_url'] ?? '';
        $mainTableData['user_activation_key'] = $mainTableData['user_activation_key'] ?? '';

        return $mainTableData;
    }

    protected function getMetaData(array $data): array
    {
        $defaultMeta = Config::get('press.default_user_meta', []);
        $this->logInfo('Moox Sync: Default meta keys', ['default_meta' => $defaultMeta]);

        $metaData = array_intersect_key($data, $defaultMeta);
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

    public function getDelay(): int
    {
        return 5;
    }
}
