<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('initial_test_done')->default(false)->after('switch_status');
            $table->timestamp('warning_sent_at')->nullable()->after('initial_test_done');
            $table->timestamp('inheritance_dispatched_at')->nullable()->after('warning_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['initial_test_done', 'warning_sent_at', 'inheritance_dispatched_at']);
        });
    }
};