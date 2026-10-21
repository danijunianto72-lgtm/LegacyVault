<?php

namespace Database\Seeders;

use App\Models\Beneficiary;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Paket Langganan
        $freePlan = Plan::create([
            'name' => 'Free',
            'slug' => 'free',
            'price' => 0,
            'max_assets' => 3,
        ]);

        Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 99000,
            'max_assets' => 15,
        ]);

        Plan::create([
            'name' => 'Ultimate',
            'slug' => 'ultimate',
            'price' => 249000,
            'max_assets' => -1, // -1 artinya Unlimited
        ]);

        // 2. Seed Akun Admin Verifikator
        User::create([
            'name' => 'Super Admin Verifikator',
            'email' => 'admin@brankas.test',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        // 3. Seed Akun User Testing + Default Free Subscription & Contoh Ahli Waris
        $user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'user@brankas.test',
            'role' => 'user',
            'password' => Hash::make('password'),
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $freePlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => null,
        ]);

        Beneficiary::insert([
            [
                'user_id' => $user->id,
                'name' => 'Siti Aminah',
                'relationship' => 'Istri Sah',
                'email' => 'siti@keluarga.test',
                'phone' => '081234567890',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'name' => 'Raka Santoso',
                'relationship' => 'Anak Pertama',
                'email' => 'raka@keluarga.test',
                'phone' => '081298765432',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}