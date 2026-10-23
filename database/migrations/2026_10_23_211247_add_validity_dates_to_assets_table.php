<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->date('start_date')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('is_lifetime')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'expires_at', 'is_lifetime']);
        });
    }
};