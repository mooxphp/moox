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
        $mainTableData = $this->getMainTableData();
        $idField = $this->getIdField();

        // Sanitize date fields
        $mainTableData = $this->sanitizeDateFields($mainTableData);

        $this->logDebug('Syncing main record', [
            'model_class' => $this->modelClass,
            'id_field' => $idField,
            'id_value' => $this->modelData[$idField],
            'main_table_data' => $mainTableData,
        ]);

        // Ensure user_registered is not empty
        if ($this->modelClass === \Moox\Press\Models\WpUser::class && empty($mainTableData['user_registered'])) {
            $mainTableData['user_registered'] = now()->toDateTimeString();
        }

        return $this->modelClass::updateOrCreate(
            [$idField => $this->modelData[$idField]],
            $mainTableData
        );
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
            return null;
        }

        try {
            $carbonDate = Carbon::parse($date);

            return $carbonDate->year > 1970 ? $carbonDate->toDateTimeString() : null;
        } catch (\Exception $e) {
            $this->logDebug('Failed to parse date', ['date' => $date, 'error' => $e->getMessage()]);

            return null;
        }
    }

    protected function getDateFields(): array
    {
        switch ($this->modelClass) {
            case \Moox\Press\Models\WpUser::class:
                return ['user_registered'];
            case \Moox\Press\Models\WpPost::class:
                return ['post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt'];
                // Add cases for other Press models as needed
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
            case \Moox\Press\Models\WpPost::class:
                return ['ID', 'post_author', 'post_date', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'comment_status', 'ping_status', 'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified', 'post_content_filtered', 'post_parent', 'guid', 'menu_order', 'post_type', 'post_mime_type', 'comment_count'];
                // Add cases for other Press models as needed
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
