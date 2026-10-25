<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CheckInWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $confirmUrl;

    public function __construct(User $user)
    {
        $this->user = $user;
        // Tautan aman bertanda tangan (Signed URL) yang bisa langsung diklik dari email tanpa perlu login ulang
        $this->confirmUrl = URL::temporarySignedRoute(
            'switch.email-confirm',
            now()->addHours(24),
            ['user' => $user->id]
        );
    }

    public function build()
    {
        return $this->subject('⚠️ [PENTING] Konfirmasi Bahwa Anda Masih Sehat - Warisan Digital')
                    ->view('emails.check-in-warning');
    }
}