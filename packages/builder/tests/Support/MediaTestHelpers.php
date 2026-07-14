<?php

declare(strict_types=1);

namespace Moox\Builder\Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class MediaTestHelpers
{
    public static function ensureMediaTableExists(): void
    {
        if (Schema::hasTable('media')) {
            self::ensureMediaColumns();

            return;
        }

        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('scope')->nullable();
            $table->string('disk');
            $table->unsignedBigInteger('size')->default(0);
            $table->json('manipulations');
            $table->json('custom_properties')->nullable();
            $table->json('generated_conversions')->nullable();
            $table->json('responsive_images')->nullable();
            $table->timestamps();
        });
    }

    public static function seedMedia(
        int $id,
        string $fileName = 'test.jpg',
        string $mimeType = 'image/jpeg',
        ?string $scope = null,
    ): void {
        self::ensureMediaTableExists();

        if (DB::table('media')->where('id', $id)->exists()) {
            DB::table('media')->where('id', $id)->update([
                'file_name' => $fileName,
                'mime_type' => $mimeType,
                'scope' => $scope,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('media')->insert([
            'id' => $id,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'scope' => $scope,
            'disk' => 'public',
            'size' => 0,
            'manipulations' => '[]',
            'custom_properties' => '[]',
            'generated_conversions' => '[]',
            'responsive_images' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected static function ensureMediaColumns(): void
    {
        if (! Schema::hasColumn('media', 'mime_type')) {
            Schema::table('media', function (Blueprint $table): void {
                $table->string('mime_type')->nullable();
            });
        }

        if (! Schema::hasColumn('media', 'scope')) {
            Schema::table('media', function (Blueprint $table): void {
                $table->string('scope')->nullable();
            });
        }
    }
}
