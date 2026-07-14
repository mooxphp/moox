<?php

declare(strict_types=1);

namespace Moox\Page\Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Localization\Models\Localization;

trait CreatesPageSchema
{
    protected function setUpPageSchema(): void
    {
        $this->dropPageTables();

        $this->setUpUsersTable();

        Schema::create('static_languages', function (Blueprint $table): void {
            $table->id();
            $table->string('alpha2', 2);
            $table->string('common_name')->nullable();
            $table->timestamps();
        });

        Schema::create('localizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('language_id')->constrained('static_languages')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('locale_variant');
            $table->boolean('is_active_frontend')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        DB::table('static_languages')->insert([
            'id' => 1,
            'alpha2' => 'en',
            'common_name' => 'English',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('localizations')->insert([
            'id' => 1,
            'language_id' => 1,
            'title' => 'English',
            'slug' => 'en',
            'locale_variant' => 'en',
            'is_active_frontend' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pagesMigration = include dirname(__DIR__, 2).'/database/migrations/create_pages_table.php.stub';
        $pagesMigration->up();

        $translationsMigration = include dirname(__DIR__, 2).'/database/migrations/create_page_translations_table.php.stub';
        $translationsMigration->up();

        if (! Schema::hasColumn('pages', 'status')) {
            Schema::table('pages', function (Blueprint $table): void {
                $table->string('status')->nullable();
            });
        }
    }

    protected function dropPageTables(): void
    {
        Schema::dropIfExists('page_translations');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('localizations');
        Schema::dropIfExists('static_languages');
        Schema::dropIfExists('users');
    }

    protected function setUpUsersTable(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    protected function seedGermanLocalization(): Localization
    {
        DB::table('static_languages')->insert([
            'id' => 2,
            'alpha2' => 'de',
            'common_name' => 'German',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('localizations')->insert([
            'id' => 2,
            'language_id' => 2,
            'title' => 'Deutsch',
            'slug' => 'de',
            'locale_variant' => 'de',
            'is_active_frontend' => true,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Localization::query()->findOrFail(2);
    }
}
