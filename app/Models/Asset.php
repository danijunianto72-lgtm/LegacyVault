<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Crypt;

class Asset extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'category',
        'encrypted_payload',
        'attachment_path',
        'start_date',
        'expires_at',
        'is_lifetime',
        'extension_status', 
    ];

protected function casts(): array
{
    return [
        'start_date' => 'date',
        'expires_at' => 'date',
        'is_lifetime' => 'boolean',
    ];
}

/**
 * Cek apakah aset mau kadaluarsa (<= 7 hari lagi) atau sudah lewat
 */
public function isExpiringSoon(): bool
{
    if ($this->is_lifetime || !$this->expires_at) {
        return false;
    }

    $daysLeft = now()->startOfDay()->diffInDays($this->expires_at->startOfDay(), false);
    return $daysLeft <= 7; // Termasuk yang kurang dari 7 hari atau sudah minus (lewat)
}

/**
 * Hitung sisa hari
 */
public function daysRemaining(): ?int
{
    if ($this->is_lifetime || !$this->expires_at) {
        return null;
    }

    return (int) now()->startOfDay()->diffInDays($this->expires_at->startOfDay(), false);
}
    // Accessor & Mutator untuk deskripsi/enkripsi otomatis
    protected function secretContent(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (empty($this->attributes['encrypted_payload'])) {
                    return '';
                }
                try {
                    return Crypt::decryptString($this->attributes['encrypted_payload']);
                } catch (\Exception $e) {
                    return '[Gagal Mendekripsi Data]';
                }
            },
            set: fn (string $value) => [
                'encrypted_payload' => Crypt::encryptString($value),
            ],
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function beneficiaries(): BelongsToMany
    {
        return $this->belongsToMany(Beneficiary::class, 'asset_beneficiary');
    }
}