<?php

namespace Moox\Sync\Handlers;

use Carbon\Carbon;
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
            $this->syncTaxonomies($mainRecord);

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

    protected function syncMainRecord()
    {
        $this->logDebug('Starting syncMainRecord', ['modelClass' => $this->modelClass, 'modelData' => $this->modelData]);
        $mainTableData = $this->getMainTableData();
        $idField = $this->getIdField();

        // Ensure user_registered is set to current time if it's missing or invalid
        if ($this->modelClass === \Moox\Press\Models\WpUser::class) {
            $mainTableData['user_registered'] = $this->sanitizeDate($mainTableData['user_registered'] ?? null);
        }

        $this->logDebug('Syncing main record', [
            'model_class' => $this->modelClass,
            'id_field' => $idField,
            'id_value' => $this->modelData[$idField],
            'main_table_data' => $mainTableData,
        ]);

        try {
            $model = $this->modelClass::updateOrCreate(
                [$idField => $this->modelData[$idField]],
                $mainTableData
            );

            $this->logDebug('Main record synced successfully', [
                'model_class' => $this->modelClass,
                'id' => $model->getKey(),
                'attributes' => $model->getAttributes(),
            ]);

            return $model;
        } catch (\Exception $e) {
            $this->logDebug('Error syncing main record', [
                'model_class' => $this->modelClass,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function syncWpUser(array $mainTableData, string $idField)
    {
        $this->logDebug('Syncing WpUser', ['mainTableData' => $mainTableData, 'idField' => $idField]);

        // Always set user_registered to now() for new users
        if (! isset($mainTableData[$idField])) {
            $mainTableData['user_registered'] = now();
        } else {
            // For existing users, keep the original value or use now() if it's invalid
            $mainTableData['user_registered'] = $this->sanitizeDate($mainTableData['user_registered'] ?? null);
        }

        $this->logDebug('Prepared user data', ['mainTableData' => $mainTableData]);

        $user = \Moox\Press\Models\WpUser::updateOrCreate(
            [$idField => $this->modelData[$idField]],
            $mainTableData
        );

        $this->logDebug('WpUser synced', ['user' => $user->toArray()]);

        // Sync meta fields
        $metaFields = array_diff_key($this->modelData, array_flip($this->getMainFields()));
        foreach ($metaFields as $key => $value) {
            $user->addOrUpdateMeta($key, $value);
        }

        $this->logDebug('WpUser meta synced', ['metaFields' => $metaFields]);

        return $user;
    }

    protected function sanitizeDateFields(array $data): array
    {
        $dateFields = $this->getDateFields();

        foreach ($dateFields as $field) {
            if (isset($data[$field])) {
                $originalValue = $data[$field];
                $data[$field] = $this->sanitizeDate($data[$field]);
                $this->logDebug('Sanitized date field', [
                    'field' => $field,
                    'original_value' => $originalValue,
                    'sanitized_value' => $data[$field],
                ]);
            }
        }

        return $data;
    }

    protected function sanitizeDate($date)
    {
        if (empty($date) || $date === '0000-00-00 00:00:00') {
            $this->logDebug('Empty or invalid date, using current time', ['original_date' => $date]);

            return now();
        }

        try {
            $parsedDate = Carbon::parse($date);
            $this->logDebug('Date parsed successfully', ['original_date' => $date, 'parsed_date' => $parsedDate]);

            return $parsedDate;
        } catch (\Exception $e) {
            $this->logDebug('Failed to parse date, using current time', ['date' => $date, 'error' => $e->getMessage()]);

            return now();
        }
    }

    protected function getDateFields(): array
    {
        switch ($this->modelClass) {
            case \Moox\Press\Models\WpUser::class:
                return ['user_registered'];
                // ... other cases ...
            default:
                return [];
        }
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

    protected function syncTaxonomies(Model $mainRecord)
    {
        // TODO: Implement taxonomy syncing
    }

    protected function getMainTableData(): array
    {
        $mainFields = $this->getMainFields();

        return array_intersect_key($this->modelData, array_flip($mainFields));
    }

    protected function getMetaData(): array
    {
        $mainFields = $this->getMainFields();

        return array_diff_key($this->modelData, array_flip($mainFields));
    }

    protected function getMainFields(): array
    {
        switch ($this->modelClass) {
            case \Moox\Press\Models\WpUser::class:
                return ['ID', 'user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_activation_key', 'user_status', 'display_name'];
                // ... other cases ...
            default:
                throw new \Exception("Unsupported model class: {$this->modelClass}");
        }
    }

    protected function getMetaModel(Model $mainRecord): string
    {
        switch (get_class($mainRecord)) {
            case \Moox\Press\Models\WpUser::class:
                return \Moox\Press\Models\WpUserMeta::class;
            case \Moox\Press\Models\WpPost::class:
                return \Moox\Press\Models\WpPostMeta::class;
                // Add cases for other Press models as needed
            default:
                throw new \Exception('Unsupported model class: '.get_class($mainRecord));
        }
    }

    protected function getIdField(): string
    {
        return 'ID'; // Assuming all Press models use 'ID' as their primary key
    }

    protected function getForeignKeyName(Model $mainRecord): string
    {
        switch (get_class($mainRecord)) {
            case \Moox\Press\Models\WpUser::class:
                return 'user_id';
            case \Moox\Press\Models\WpPost::class:
                return 'post_id';
                // Add cases for other Press models as needed
            default:
                throw new \Exception('Unsupported model class: '.get_class($mainRecord));
        }
    }
}
