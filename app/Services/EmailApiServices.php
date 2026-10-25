<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailApiService
{
    /**
     * Kirim email otomatis ke siapa saja menggunakan HTTP API Brevo
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlContent): bool
    {
        $apiKey = env('BREVO_API_KEY');
        $senderEmail = env('MAIL_FROM_ADDRESS', 'noreply@warisandigital.com');
        $senderName = env('MAIL_FROM_NAME', 'Brankas Warisan Digital');

        if (empty($apiKey)) {
            Log::error('BREVO_API_KEY belum diisi di file .env');
            return false;
        }

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $apiKey,
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => $senderName,
                'email' => $senderEmail,
            ],
            'to' => [
                [
                    'email' => $toEmail,
                    'name' => $toName ?: $toEmail,
                ]
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ]);

        if ($response->failed()) {
            Log::error('Brevo API Error: ' . $response->body());
            return false;
        }

        return true;
    }
}