<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// ... di dalam class User ...

class User extends Authenticatable
{

    use HasFactory, Notifiable;

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
    'gender',
    'birth_date',
    'avatar_url',
    
];

public function plan(): BelongsTo
{
    return $this->belongsTo(Plan::class, 'plan_id');
}

public function assets(): HasMany
{
    return $this->hasMany(Asset::class);
}

public function beneficiaries(): HasMany
{
    return $this->hasMany(Beneficiary::class);
}
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_check_in_at' => 'datetime',
            'next_check_in_deadline' => 'datetime',
            'birth_date' => 'date',
        ];
    }

    public function getAgeAttribute(): ?int
{
    return $this->birth_date ? $this->birth_date->age : null;
}
    // Helper untuk mereset timer konfirmasi sehat
    public function confirmCheckIn(): void
    {
        $interval = $this->check_in_interval_days ?: 180;
        $this->update([
            'last_check_in_at' => now(),
            'next_check_in_deadline' => now()->addDays($interval),
            'switch_status' => 'active',
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

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function canCreateAsset(): bool
    {
        $plan = $this->currentPlan();
        // Jika max_assets bernilai -1 atau >= 9999 maka Unlimited
        if ($plan->max_assets < 0 || $plan->max_assets >= 9999) {
            return true;
        }
        return $this->assets()->count() < $plan->max_assets;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    public function subscriptions(): HasMany
{
    return $this->hasMany(Subscription::class);
}

public function subscription(): HasOne
{
    return $this->hasOne(Subscription::class)->latestOfMany();
}
/**
     * Memeriksa apakah pengguna memiliki langganan berbayar yang masih aktif.
     */
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