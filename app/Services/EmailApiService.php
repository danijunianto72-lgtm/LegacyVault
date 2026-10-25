<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailApiService
{
    /**
     * Kirim email otomatis via Brevo API
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlContent): bool
    {
        $apiKey = env('BREVO_API_KEY');
        $senderEmail = env('MAIL_FROM_ADDRESS');
        $senderName = env('MAIL_FROM_NAME', 'Brankas Warisan Digital');

        if (empty($apiKey) || empty($senderEmail)) {
            Log::error('BREVO_API_KEY atau MAIL_FROM_ADDRESS belum disetel di environment.');
            return false;
        }

        try {
            $payload = [
                'sender' => [
                    'name' => $senderName,
                    'email' => trim($senderEmail),
                ],
                'to' => [
                    [
                        'email' => trim($toEmail),
                        'name' => !empty(trim($toName)) ? trim($toName) : trim($toEmail),
                    ]
                ],
                'subject' => $subject,
                'htmlContent' => $htmlContent,
            ];

            $response = Http::timeout(15)->withHeaders([
                'accept' => 'application/json',
                'api-key' => trim($apiKey),
                'content-type' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', $payload);

            if ($response->failed()) {
                Log::error('Brevo API Error Status (' . $response->status() . '): ' . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Koneksi Brevo API Gagal: ' . $e->getMessage());
            return false;
        }
    }
}