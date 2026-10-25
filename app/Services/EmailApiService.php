<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailApiService
{
    public static function send(string $toEmail, string $toName, string $subject, string $htmlContent): bool
    {
        $apiKey = env('BREVO_API_KEY');
        $senderEmail = env('MAIL_FROM_ADDRESS');
        $senderName = env('MAIL_FROM_NAME', 'Brankas Warisan Digital');

        if (empty($apiKey) || empty($senderEmail)) {
            Log::error('BREVO_API_KEY atau MAIL_FROM_ADDRESS belum disetel.');
            return false;
        }

        try {
            $response = Http::timeout(10)->withHeaders([
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
                // Matikan link tracking Brevo agar tautan asli tidak dibungkus ke sendibt2.com
                'headers' => [
                    'X-Mailin-Custom' => '{"disable_click_tracking": true}'
                ]
            ]);

            if ($response->failed()) {
                Log::error('Brevo API Error: ' . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Koneksi Brevo API Gagal: ' . $e->getMessage());
            return false;
        }
    }
}