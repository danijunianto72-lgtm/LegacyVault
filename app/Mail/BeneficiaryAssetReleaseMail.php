<?php

namespace App\Mail;

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class BeneficiaryAssetReleaseMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $owner;
    public Beneficiary $beneficiary;
    public Collection $assets;

    public function __construct(User $owner, Beneficiary $beneficiary, Collection $assets)
    {
        $this->owner = $owner;
        $this->beneficiary = $beneficiary;
        $this->assets = $assets;
    }

    public function build()
    {
        return $this->subject("🔐 Penyerahan Aset Warisan Digital dari {$this->owner->name}")
                    ->view('emails.beneficiary-release');
    }
}