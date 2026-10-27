<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('category', 100)->change();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->enum('category', [
                'Finansial',
                'Kredensial Akun',
                'Dokumen Rahasia',
                'Pesan Wasiat'
            ])->change();
        });
    }
};