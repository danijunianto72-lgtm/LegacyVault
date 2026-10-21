<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_check_in_at')->nullable()->after('email');
            $table->timestamp('next_check_in_deadline')->nullable()->after('last_check_in_at');
            $table->integer('check_in_interval_days')->default(180)->after('next_check_in_deadline'); // Default 6 bulan (180 hari)
            $table->string('switch_status')->default('active')->after('check_in_interval_days'); // active, warning, triggered
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_check_in_at', 'next_check_in_deadline', 'check_in_interval_days', 'switch_status']);
        });
    }
};