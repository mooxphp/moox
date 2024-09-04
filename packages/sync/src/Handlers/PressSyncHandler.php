<?php

namespace Moox\Sync\Handlers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PressSyncHandler
{
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
            throw $e;
        }
    }

    protected function syncMainRecord()
    {
        $mainTableData = $this->getMainTableData();
        $idField = $this->getIdField();

        return $this->modelClass::updateOrCreate(
            [$idField => $this->modelData[$idField]],
            $mainTableData
        );
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
