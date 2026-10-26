<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailApiService
{
    /**
     * Kirim email otomatis menggunakan SMTP Gmail Resmi
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlContent): bool
    {
        try {
            $senderEmail = config('mail.from.address', env('MAIL_FROM_ADDRESS'));
            $senderName = config('mail.from.name', env('MAIL_FROM_NAME', 'LegacyVault'));

            Mail::html($htmlContent, function ($message) use ($toEmail, $toName, $subject, $senderEmail, $senderName) {
                $message->from($senderEmail, $senderName)
                        ->to(trim($toEmail), trim($toName) ?: null)
                        ->subject($subject);
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('SMTP Gmail Gagal Mengirim Email: ' . $e->getMessage());
            return false;
        }
    }
}