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
    ];

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