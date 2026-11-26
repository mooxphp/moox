<?php

declare(strict_types=1);

namespace Moox\Support\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
{
    return [
        \Astrotomic\Translatable\TranslatableServiceProvider::class,
    ];
}

    protected function setUpDatabase(): void
    {
        Schema::create('active_models', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('archive_models', function (Blueprint $table): void {
            $table->id();
            $table->datetime('archived_at')->nullable();
            $table->integer('archived_by_id')->nullable();
            $table->string('archived_by_type')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('audit_models', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
        
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('created_by_type')->nullable();
        
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('updated_by_type')->nullable();
        
            $table->timestamps();
        });
        Schema::create('data_models', function (Blueprint $table): void {
            $table->id();
            $table->json('data')->nullable();
            $table->timestamps();
        });
        Schema::create('external_models', function (Blueprint $table): void {
        $table->id();
        $table->string('external_reference')->nullable();
        $table->text('external_info')->nullable();
            $table->string('external_status')->nullable();
            $table->timestamps();
        });
        Schema::create('identifier_models', function (Blueprint $table): void {
            $table->id();
            $table->string('ulid')->unique();
            $table->uuid('uuid')->unique();
            $table->timestamps();
        });
        Schema::create('merges_columns_models', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('enabled')->default(false);
            $table->json('data')->nullable();
            $table->string('status')->default('draft');
            $table->string('type')->default('default');
            $table->timestamps();
        });
        Schema::create('publish_models', function (Blueprint $table): void {
            $table->id();
            $table->datetime('published_at')->nullable();
            $table->integer('published_by_id')->nullable();
            $table->string('published_by_type')->nullable();
            $table->datetime('unpublished_at')->nullable();
            $table->integer('unpublished_by_id')->nullable();
            $table->string('unpublished_by_type')->nullable();
            $table->timestamps();
        });

        Schema::create('relation_models', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('related_model_id')->nullable();
            $table->string('owner_type')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->timestamps();
        });
    
        Schema::create('related_models', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    
        Schema::create('morph_owner_models', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
        
        Schema::create('soft_delete_models', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by_id')->nullable();
            $table->string('deleted_by_type')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->unsignedBigInteger('restored_by_id')->nullable();
            $table->string('restored_by_type')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by_id')->nullable();
            $table->string('published_by_type')->nullable();
            $table->timestamp('unpublished_at')->nullable();
            $table->unsignedBigInteger('unpublished_by_id')->nullable();
            $table->string('unpublished_by_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    
        Schema::create('product_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by_id')->nullable();
            $table->string('published_by_type')->nullable();
            $table->timestamp('unpublished_at')->nullable();
            $table->unsignedBigInteger('unpublished_by_id')->nullable();
            $table->string('unpublished_by_type')->nullable();
    
            $table->unique(['product_id', 'locale']);
        });

    }
}