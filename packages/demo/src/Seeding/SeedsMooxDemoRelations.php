<?php

declare(strict_types=1);

namespace Moox\Demo\Seeding;

use Illuminate\Database\Eloquent\Model;
use Moox\User\Models\User;

trait SeedsMooxDemoRelations
{
    /**
     * Default locale for single-value media pivots when the German admin UI is primary.
     */
    protected function primaryMediaLocale(): string
    {
        return 'de_DE';
    }

    protected function requireDemoAuthor(): ?User
    {
        if (! class_exists(User::class)) {
            $this->command->error('User model not available. Install moox/user and run UserSeeder first.');

            return null;
        }

        $author = User::query()->first();

        if ($author === null) {
            $this->command->error('No user found. Run UserSeeder before this seeder.');

            return null;
        }

        return $author;
    }

    protected function assignTranslationAuthor(Model $translation, User $author): void
    {
        $translation->setAttribute('author_id', $author->getKey());
        $translation->setAttribute('author_type', $author->getMorphClass());
    }
}
