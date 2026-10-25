<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // 1. Tambahkan di dalam array $fillable:
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'last_check_in_at',
        'next_check_in_deadline',
        'check_in_interval_days',
        'switch_status',
        'initial_test_done',
        'warning_sent_at',
        'inheritance_dispatched_at',
        'gender',
        'birth_date',
        'avatar_url',
    ];

    // 2. Tambahkan di dalam method casts():
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_check_in_at' => 'datetime',
            'next_check_in_deadline' => 'datetime',
            'warning_sent_at' => 'datetime',
            'inheritance_dispatched_at' => 'datetime',
            'initial_test_done' => 'boolean',
            'birth_date' => 'date',
        ];
    }

    // 3. Perbarui method confirmCheckIn():
    public function confirmCheckIn(): void
    {
        $interval = $this->check_in_interval_days ?: 180;
        $this->update([
            'last_check_in_at' => now(),
            'next_check_in_deadline' => now()->addDays($interval),
            'switch_status' => 'active',
            'initial_test_done' => true, // Tandai simulasi 5 menit sudah selesai
            'warning_sent_at' => null,   // Reset status peringatan
            'inheritance_dispatched_at' => null,
        ]);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')
                      ->orWhere('ends_at', '>', now());
            })
            ->latestOfMany();
    }

    // Helper: Ambil paket aktif saat ini (Fallback ke paket Free jika langganan berbayar sudah expired)
    public function currentPlan(): Plan
    {
        $sub = $this->activeSubscription;
        if ($sub && $sub->plan) {
            return $sub->plan;
        }
        return Plan::where('slug', 'free')->first();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function canCreateAsset(): bool
    {
        $plan = $this->currentPlan();
        if ($plan->max_assets < 0 || $plan->max_assets >= 9999) {
            return true;
        }
        return $this->assets()->count() < $plan->max_assets;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')
                      ->orWhere('ends_at', '>', now());
            })
            ->exists();
    }
}