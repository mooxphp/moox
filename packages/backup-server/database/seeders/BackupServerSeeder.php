<?php

declare(strict_types=1);

namespace Moox\BackupServerUi\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Spatie\BackupServer\Enums\BackupStatus;
use Spatie\BackupServer\Models\Backup;
use Spatie\BackupServer\Models\Destination;
use Spatie\BackupServer\Models\Source;

class BackupServerSeeder extends Seeder
{
    /**
     * Seed destination, sources and 10 fake backups for the Backups list.
     */
    public function run(): void
    {
        $destination = Destination::query()->firstOrCreate(
            ['name' => 'Local Backup Disk'],
            [
                'disk_name' => 'local',
                'keep_all_backups_for_days' => 7,
                'keep_daily_backups_for_days' => 16,
                'keep_weekly_backups_for_weeks' => 8,
                'keep_monthly_backups_for_months' => 4,
                'keep_yearly_backups_for_years' => 2,
                'delete_oldest_backups_when_using_more_megabytes_than' => 500000,
                'healthy_maximum_backup_age_in_days_per_source' => 2,
                'healthy_maximum_storage_in_mb_per_source' => 10000,
                'healthy_maximum_storage_in_mb' => 500000,
                'healthy_maximum_inode_usage_percentage' => 70,
            ],
        );

        $sourceDefinitions = [
            [
                'name' => 'shop-production',
                'host' => 'shop.example.com',
                'ssh_user' => 'forge',
            ],
            [
                'name' => 'cms-staging',
                'host' => 'cms-staging.example.com',
                'ssh_user' => 'deploy',
            ],
            [
                'name' => 'api-production',
                'host' => 'api.example.com',
                'ssh_user' => 'backup',
            ],
        ];

        $sources = collect($sourceDefinitions)->map(function (array $definition) use ($destination): Source {
            return Source::query()->firstOrCreate(
                ['name' => $definition['name']],
                [
                    'host' => $definition['host'],
                    'ssh_user' => $definition['ssh_user'],
                    'ssh_port' => 22,
                    'cron_expression' => '0 2 * * *',
                    'includes' => ['/var/www/html'],
                    'excludes' => ['/var/www/html/storage/logs', '/var/www/html/node_modules'],
                    'destination_id' => $destination->id,
                    'healthy' => true,
                    'keep_all_backups_for_days' => 7,
                    'keep_daily_backups_for_days' => 16,
                    'keep_weekly_backups_for_weeks' => 8,
                    'keep_monthly_backups_for_months' => 4,
                    'keep_yearly_backups_for_years' => 2,
                    'delete_oldest_backups_when_using_more_megabytes_than' => 50000,
                    'healthy_maximum_backup_age_in_days' => 2,
                    'healthy_maximum_storage_in_mb' => 10000,
                ],
            );
        });

        if (Backup::query()->exists()) {
            return;
        }

        /** @var list<array{status: BackupStatus, days_ago: int, duration_minutes: int|null, size_in_kb: int|null, real_size_in_kb: int|null, source: int}> $entries */
        $entries = [
            ['status' => BackupStatus::Completed, 'days_ago' => 1, 'duration_minutes' => 42, 'size_in_kb' => 5_242_880, 'real_size_in_kb' => 2_097_152, 'source' => 0],
            ['status' => BackupStatus::Completed, 'days_ago' => 2, 'duration_minutes' => 38, 'size_in_kb' => 4_194_304, 'real_size_in_kb' => 1_572_864, 'source' => 0],
            ['status' => BackupStatus::Completed, 'days_ago' => 3, 'duration_minutes' => 55, 'size_in_kb' => 8_388_608, 'real_size_in_kb' => 3_145_728, 'source' => 1],
            ['status' => BackupStatus::Completed, 'days_ago' => 4, 'duration_minutes' => 29, 'size_in_kb' => 2_097_152, 'real_size_in_kb' => 1_048_576, 'source' => 1],
            ['status' => BackupStatus::Completed, 'days_ago' => 5, 'duration_minutes' => 61, 'size_in_kb' => 10_485_760, 'real_size_in_kb' => 4_194_304, 'source' => 2],
            ['status' => BackupStatus::Completed, 'days_ago' => 6, 'duration_minutes' => 47, 'size_in_kb' => 6_291_456, 'real_size_in_kb' => 2_621_440, 'source' => 2],
            ['status' => BackupStatus::Failed, 'days_ago' => 1, 'duration_minutes' => 12, 'size_in_kb' => 524_288, 'real_size_in_kb' => 0, 'source' => 0],
            ['status' => BackupStatus::Pending, 'days_ago' => 0, 'duration_minutes' => null, 'size_in_kb' => null, 'real_size_in_kb' => null, 'source' => 1],
            ['status' => BackupStatus::InProgress, 'days_ago' => 0, 'duration_minutes' => null, 'size_in_kb' => 1_048_576, 'real_size_in_kb' => 262_144, 'source' => 2],
            ['status' => BackupStatus::Deleting, 'days_ago' => 8, 'duration_minutes' => 33, 'size_in_kb' => 3_145_728, 'real_size_in_kb' => 1_048_576, 'source' => 0],
        ];

        foreach ($entries as $index => $entry) {
            /** @var Source $source */
            $source = $sources[$entry['source']];
            $createdAt = Carbon::now()->subDays($entry['days_ago'])->subHours($index)->subMinutes(15);
            $durationMinutes = $entry['duration_minutes'];
            $sizeInKb = $entry['size_in_kb'];
            $status = $entry['status'];

            $completedAt = null;
            if (
                $durationMinutes !== null
                && in_array($status, [BackupStatus::Completed, BackupStatus::Failed, BackupStatus::Deleting], true)
            ) {
                $completedAt = $createdAt->copy()->addMinutes($durationMinutes);
            }

            $pathStamp = $createdAt->format('YmdHis');
            $rsyncSummary = null;
            $rsyncAverageSpeed = null;

            if ($status === BackupStatus::Completed && $sizeInKb !== null && $durationMinutes !== null) {
                $rsyncSummary = 'Number of files: 12480  Total file size: '.number_format($sizeInKb * 1024).' bytes';
                $rsyncAverageSpeed = (string) round(($sizeInKb / 1024) / max($durationMinutes, 1), 2);
            }

            Backup::query()->create([
                'status' => $status,
                'source_id' => $source->id,
                'destination_id' => $destination->id,
                'disk_name' => $destination->disk_name,
                'path' => "{$source->name}/{$pathStamp}",
                'size_in_kb' => $sizeInKb,
                'real_size_in_kb' => $entry['real_size_in_kb'],
                'completed_at' => $completedAt,
                'rsync_summary' => $rsyncSummary,
                'rsync_time_in_seconds' => $durationMinutes !== null ? $durationMinutes * 60 : null,
                'rsync_average_transfer_speed_in_MB_per_second' => $rsyncAverageSpeed,
                'rsync_current_transfer_speed' => $status === BackupStatus::InProgress ? '12.40MB/s' : null,
                'created_at' => $createdAt,
                'updated_at' => $completedAt ?? $createdAt,
            ]);
        }
    }
}
