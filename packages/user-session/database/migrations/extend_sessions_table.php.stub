<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('user_type')->nullable()->after('id');
            $table->unsignedBigInteger('device_id')->nullable()->after('user_id');
            $table->boolean('whitlisted')->nullable()->after('last_activity');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn(['user_type', 'device_id', 'whitlisted']);
        });
    }
};
