<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Modifikasi / Buat Tabel Users (Jika memakai fresh install, tambahkan kolom role)
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['user', 'admin'])->default('user')->after('email');
            });
        }

        // 2. Tabel Plans (Tier Langganan)
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Pro, Ultimate
            $table->string('slug')->unique();
            $table->unsignedInteger('price'); // Dalam Rupiah
            $table->integer('max_assets'); // -1 untuk Unlimited
            $table->timestamps();
        });

        // 3. Tabel Subscriptions (Status Langganan User Aktif)
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['active', 'expired'])->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable(); // Null untuk paket Free selamanya
            $table->timestamps();
        });

        // 4. Tabel Beneficiaries (Ahli Waris)
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship'); // Contoh: Istri, Anak Kandung, Adik, Pengacara
            $table->string('email');
            $table->string('phone', 25);
            $table->timestamps();
        });

        // 5. Tabel Assets (Brankas Aset Digital)
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('category', ['Finansial', 'Dokumen Rahasia', 'Kredensial Akun', 'Pesan Wasiat']);
            $table->text('encrypted_payload'); // Otomatis dienkripsi via Crypt Laravel
            $table->string('attachment_path')->nullable(); // Path file PDF/Gambar di storage lokal
            $table->timestamps();
        });

        // 6. Tabel Pivot: asset_beneficiary (Relasi Many-to-Many)
        Schema::create('asset_beneficiary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->unique(['asset_id', 'beneficiary_id']);
        });

        // 7. Tabel Payments (Verifikasi Bukti Transfer Manual)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->string('proof_image_path');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('admin_note')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('asset_beneficiary');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('beneficiaries');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
        }
    }
};