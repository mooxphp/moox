<?php

namespace Moox\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Moox\Jobs\Models\JobManager;

class PublishScheduledContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $progressLastUpdated;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🚀 Starting scheduled content publishing job at '.now());

        try {
            $this->publishScheduledContent();
            $this->unpublishScheduledContent();

            Log::info('✅ Completed scheduled content publishing job at '.now());
        } catch (\Exception $e) {
            Log::error('❌ Error in scheduled content publishing job: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());
            throw $e;
        }
    }

    public function setProgress(int $progress): void
    {
        if (! class_exists(JobManager::class)) {
            return;
        }

        $progress = min(100, max(0, $progress));

        if (! $monitor = $this->getJobMonitor()) {
            return;
        }

        $monitor->update([
            'progress' => $progress,
        ]);

        $this->progressLastUpdated = time();
    }

    protected function getJobMonitor(): ?Model
    {
        if (! class_exists(JobManager::class)) {
            return null;
        }

        if (! property_exists($this, 'job')) {
            return null;
        }

        if (! $this->job) {
            return null;
        }

        if (! $jobId = JobManager::getJobId($this->job)) {
            return null;
        }

        $model = JobManager::getModel();

        return $model::whereJobId($jobId)
            ->orderBy('started_at', 'desc')
            ->first();
    }

    private function publishScheduledContent(): void
    {
        $translationTables = $this->getTranslationTablesWithPublishFields();
        $totalTables = count($translationTables);
        $processedTables = 0;

        foreach ($translationTables as $table) {
            $processedTables++;
            $progress = $totalTables > 0 ? round(($processedTables / $totalTables) * 100) : 100;
            $this->setProgress((int) $progress);

            try {
                $this->processTableForPublishing($table);
            } catch (\Exception $e) {
                Log::error("❌ Error processing table {$table} for publishing: ".$e->getMessage());
            }
        }

        $this->setProgress(100);
    }

    private function unpublishScheduledContent(): void
    {
        $translationTables = $this->getTranslationTablesWithUnpublishFields();
        $totalTables = count($translationTables);
        $processedTables = 0;

        foreach ($translationTables as $table) {
            $processedTables++;
            $progress = $totalTables > 0 ? round(($processedTables / $totalTables) * 100) : 100;
            $this->setProgress((int) $progress);

            try {
                $this->processTableForUnpublishing($table);
            } catch (\Exception $e) {
                Log::error("❌ Error processing table {$table} for unpublishing: ".$e->getMessage());
            }
        }

        $this->setProgress(100);
    }

    private function getTranslationTablesWithPublishFields(): array
    {
        $tables = [];

        try {
            $allTables = DB::select("SHOW TABLES LIKE '%_translations'");

            foreach ($allTables as $table) {
                $tableName = array_values((array) $table)[0];

                $hasPublishField = DB::select("SHOW COLUMNS FROM `{$tableName}` LIKE 'to_publish_at'");

                if (! empty($hasPublishField)) {
                    $tables[] = $tableName;
                }
            }
        } catch (\Exception $e) {
            Log::error('❌ Error getting translation tables: '.$e->getMessage());
        }

        return $tables;
    }

    private function getTranslationTablesWithUnpublishFields(): array
    {
        $tables = [];

        try {
            $allTables = DB::select("SHOW TABLES LIKE '%_translations'");

            foreach ($allTables as $table) {
                $tableName = array_values((array) $table)[0];

                $hasUnpublishField = DB::select("SHOW COLUMNS FROM `{$tableName}` LIKE 'to_unpublish_at'");

                if (! empty($hasUnpublishField)) {
                    $tables[] = $tableName;
                }
            }
        } catch (\Exception $e) {
            Log::error('❌ Error getting translation tables for unpublishing: '.$e->getMessage());
        }

        return $tables;
    }

    private function processTableForPublishing(string $tableName): void
    {
        $translationsToPublish = DB::table($tableName)
            ->whereNotNull('to_publish_at')
            ->where('to_publish_at', '<=', now())
            ->whereNull('published_at')
            ->get();

        if ($translationsToPublish->count() > 0) {
            foreach ($translationsToPublish as $translation) {
                $this->publishTranslation($tableName, $translation);
            }
        }
    }

    private function processTableForUnpublishing(string $tableName): void
    {
        $translationsToUnpublish = DB::table($tableName)
            ->whereNotNull('to_unpublish_at')
            ->where('to_unpublish_at', '<=', now())
            ->whereNull('unpublished_at')
            ->whereNotNull('published_at')
            ->get();

        if ($translationsToUnpublish->count() > 0) {
            foreach ($translationsToUnpublish as $translation) {
                $this->unpublishTranslation($tableName, $translation);
            }
        }
    }

    private function publishTranslation(string $tableName, $translation): void
    {
        try {
            DB::transaction(function () use ($tableName, $translation) {
                DB::table($tableName)
                    ->where('id', $translation->id)
                    ->update([
                        'published_at' => now(),
                        'published_by_id' => 15,
                        'published_by_type' => $this->publisherMorphType(),
                        'to_publish_at' => null,
                        'translation_status' => 'published',
                        'unpublished_at' => null,
                        'unpublished_by_id' => null,
                        'unpublished_by_type' => null,
                    ]);
            });
        } catch (\Exception $e) {
            Log::error("❌ Error publishing translation {$translation->id}: ".$e->getMessage());
        }
    }

    private function unpublishTranslation(string $tableName, $translation): void
    {
        try {
            DB::transaction(function () use ($tableName, $translation) {
                DB::table($tableName)
                    ->where('id', $translation->id)
                    ->update([
                        'unpublished_at' => now(),
                        'unpublished_by_id' => 15,
                        'unpublished_by_type' => $this->publisherMorphType(),
                        'to_unpublish_at' => null,
                        'translation_status' => 'draft',
                    ]);
            });
        } catch (\Exception $e) {
            Log::error("❌ Error unpublishing translation {$translation->id}: ".$e->getMessage());
        }
    }

    /** @return class-string */
    private function publisherMorphType(): string
    {
        $model = config('auth.providers.users.model');

        if (is_string($model) && class_exists($model)) {
            return $model;
        }

        return User::class;
    }
}
