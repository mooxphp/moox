<?php

declare(strict_types=1);

namespace Moox\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;
use Moox\User\Models\User;

class UserSeeder extends Seeder
{
    public const DEMO_EMAIL_DOMAIN = 'moox.org';

    public const DEMO_EXTRA_PASSWORD = 'password';

    /**
     * Standard demo users created on every seed run (in addition to dataset-sized extras).
     *
     * @var list<array{name: string, email: string, password: string}>
     */
    public const DEFAULT_USERS = [
        [
            'name' => 'Reinhold',
            'email' => 'reinhold.jesse@heco.de',
            'password' => '123456789',
        ],
        [
            'name' => 'Demo Admin',
            'email' => 'admin@moox.org',
            'password' => 'password',
        ],
        [
            'name' => 'Demo Editor',
            'email' => 'editor@moox.org',
            'password' => 'password',
        ],
    ];

    public function run(): void
    {
        $this->seed();

        if (class_exists(\Moox\Demo\Seeding\RunsMooxDemoAssets::class)) {
            \Moox\Demo\Seeding\RunsMooxDemoAssets::invoke($this);
        }
    }

    protected function seed(): void
    {
        $extraCount = $this->resolveExtraUserCount();

        $this->purgeDemoUsers();

        foreach (self::DEFAULT_USERS as $definition) {
            User::query()->create([
                'name' => $definition['name'],
                'email' => $definition['email'],
                'password' => Hash::make($definition['password']),
                'email_verified_at' => now(),
            ]);

            $this->reportCreated("User {$definition['email']}");
        }

        if ($extraCount > 0) {
            $this->seedExtraUsers($extraCount);
        }

        $total = count(self::DEFAULT_USERS) + $extraCount;

        $this->reportDetail(sprintf(
            '%d user(s) total (%d default + %d from dataset)',
            $total,
            count(self::DEFAULT_USERS),
            $extraCount
        ));
    }

    private function seedExtraUsers(int $extraCount): void
    {
        if ($this->hasSeedOutput()) {
            $progress = \Moox\Demo\Seeding\SeedOutput::progressBar($extraCount, 'Demo users');

            for ($i = 1; $i <= $extraCount; $i++) {
                User::query()->create([
                    'name' => sprintf('Demo User %03d', $i),
                    'email' => sprintf('demo-user-%03d@%s', $i, self::DEMO_EMAIL_DOMAIN),
                    'password' => Hash::make(self::DEMO_EXTRA_PASSWORD),
                    'email_verified_at' => now(),
                ]);
                $progress->advance();
            }

            $progress->finish("{$extraCount} demo user(s)");

            return;
        }

        for ($i = 1; $i <= $extraCount; $i++) {
            User::query()->create([
                'name' => sprintf('Demo User %03d', $i),
                'email' => sprintf('demo-user-%03d@%s', $i, self::DEMO_EMAIL_DOMAIN),
                'password' => Hash::make(self::DEMO_EXTRA_PASSWORD),
                'email_verified_at' => now(),
            ]);
        }

        $this->command?->info(sprintf('Seeded %d extra demo user(s).', $extraCount));
    }

    private function reportCreated(string $label): void
    {
        if ($this->hasSeedOutput()) {
            \Moox\Demo\Seeding\SeedOutput::created($label);

            return;
        }
    }

    private function reportDetail(string $line): void
    {
        if ($this->hasSeedOutput()) {
            \Moox\Demo\Seeding\SeedOutput::detail($line);

            return;
        }

        $this->command?->info($line);
    }

    private function hasSeedOutput(): bool
    {
        return class_exists(\Moox\Demo\Seeding\SeedOutput::class)
            && \Moox\Demo\Seeding\SeedOutput::isBound();
    }

    protected function seedDemoAssets(): void
    {
        if (! class_exists(\Moox\Demo\Seeding\ImportDemoMediaToMediathek::class)) {
            return;
        }

        if (! class_exists(Media::class)) {
            return;
        }

        $users = $this->seededDemoUsers();

        $sourceDir = config('demo.media.users_path');

        if (! is_string($sourceDir) || $sourceDir === '') {
            return;
        }

        $collectionId = null;

        $imagePaths = \Moox\Demo\Seeding\ImportDemoMediaToMediathek::listImagePaths($sourceDir, count($users));

        if ($imagePaths === []) {
            if ($this->command !== null) {
                $this->command->warn('  No demo user images found in '.$sourceDir);
            }

            return;
        }

        $withAvatar = 0;

        foreach ($users as $index => $user) {
            $imagePath = $imagePaths[$index] ?? null;

            if ($imagePath === null) {
                continue;
            }

            $media = \Moox\Demo\Seeding\ImportDemoMediaToMediathek::importFromPath($imagePath, $collectionId);

            if (! $media instanceof Media) {
                continue;
            }

            MediaUsable::query()->firstOrCreate([
                'media_id' => $media->getKey(),
                'media_usable_id' => $user->getKey(),
                'media_usable_type' => User::class,
            ]);

            $user->forceFill([
                'avatar_url' => \Moox\Demo\Seeding\ImportDemoMediaToMediathek::avatarUrlFromMedia($media),
            ])->saveQuietly();

            $withAvatar++;

            if ($this->hasSeedOutput()) {
                \Moox\Demo\Seeding\SeedOutput::created("Avatar for {$user->email}");
            } elseif ($this->command?->getOutput()->isVerbose()) {
                $this->command->line("  User {$user->email}: mediathek media #{$media->getKey()} ({$media->file_name})");
            }
        }

        if ($this->hasSeedOutput()) {
            \Moox\Demo\Seeding\SeedOutput::detail("Attached avatars for {$withAvatar} user(s)");
        } elseif ($this->command !== null) {
            $this->command->info(sprintf(
                'Attached avatars for %d user(s).',
                $withAvatar
            ));
        }
    }

    private function resolveExtraUserCount(): int
    {
        $smallDefault = (int) (config('demo.dataset_sizes.small') ?? 100);

        if (class_exists(\Moox\Demo\Seeding\SeedingConfig::class)) {
            return \Moox\Demo\Seeding\SeedingConfig::resolveCount('user', $smallDefault);
        }

        return $smallDefault;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function seededDemoUsers(): \Illuminate\Database\Eloquent\Collection
    {
        $defaultEmails = array_column(self::DEFAULT_USERS, 'email');

        return User::query()
            ->whereIn('email', $defaultEmails)
            ->orWhere('email', 'like', 'demo-user-%@'.self::DEMO_EMAIL_DOMAIN)
            ->orderBy('id')
            ->get()
            ->values();
    }

    private function purgeDemoUsers(): void
    {
        $emails = array_column(self::DEFAULT_USERS, 'email');

        User::query()
            ->whereIn('email', $emails)
            ->orWhere('email', 'like', 'demo-user-%@'.self::DEMO_EMAIL_DOMAIN)
            ->forceDelete();
    }
}
