<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                 $table->dropPrimary();
                 $table->string('user_type');
                 $table->primary(['email', 'user_type']);
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void

    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
                    $table->dropPrimary(['email', 'user_type']);
                    $table->dropColumn('user_type');
                    $table->primary('email');
                });
    }
};
