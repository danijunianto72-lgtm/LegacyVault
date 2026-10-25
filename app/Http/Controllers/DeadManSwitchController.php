<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\EmailApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class DeadManSwitchController extends Controller
{
    public function checkinAjax(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->confirmCheckIn();
        $user->refresh();

        ActivityLog::record('CHECK_IN_CONFIRM', 'Pengguna mengonfirmasi kondisi sehat (Manual Realtime).');

        $daysLeft = (int) now()->startOfDay()->diffInDays($user->next_check_in_deadline->copy()->startOfDay(), false);

        return response()->json([
            'success' => true,
            'last_check_in' => $user->last_check_in_at->format('d M Y, H:i') . ' WIB',
            'deadline_text' => "{$daysLeft} Hari Lagi (" . $user->next_check_in_deadline->format('d F Y') . ")",
        ]);
    }

    public function updateInterval(Request $request): RedirectResponse
    {
        $user = $request->user();
        $allowed = $user->hasActiveSubscription() ? [30, 90, 180, 365] : [90, 180, 365];

        $validated = $request->validate([
            'interval_days' => ['required', 'integer', \Illuminate\Validation\Rule::in($allowed)],
        ]);

        $days = (int) $validated['interval_days'];

        $user->update([
            'check_in_interval_days' => $days,
            'next_check_in_deadline' => ($user->initial_test_done) ? now()->addDays($days) : $user->next_check_in_deadline,
        ]);

        ActivityLog::record('UPDATE_INTERVAL', "Mengubah interval check-in menjadi {$days} hari.");

        return back()->with('success', "Interval check-in berhasil diubah menjadi {$days} hari.");
    }

    public function confirmFromEmail(Request $request, User $user): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Tautan konfirmasi tidak valid atau sudah kadaluarsa.');
        }

        $user->confirmCheckIn();
        ActivityLog::record('EMAIL_CHECK_IN_CONFIRM', "Pengguna #{$user->id} mengonfirmasi kondisi sehat melalui Email.");

        return redirect('/dashboard')->with('success', 'Konfirmasi sehat via Email berhasil! Timer dikembalikan ke setelan normal.');
    }

    public function sendSimulationEmail(Request $request)
    {
        $user = $request->user();

        $confirmUrl = URL::temporarySignedRoute(
            'switch.email-confirm',
            now()->addHours(24),
            ['user' => $user->id]
        );

        $htmlContent = view('emails.check-in-warning', [
            'user' => $user,
            'confirmUrl' => $confirmUrl,
        ])->render();

        $timestamp = now()->timezone('Asia/Jakarta')->format('H:i:s');
        $subject = "⚠️ [PENTING] Konfirmasi Bahwa Anda Masih Sehat ({$timestamp}) - Warisan Digital";

        $apiKey = env('BREVO_API_KEY');
        $senderEmail = env('MAIL_FROM_ADDRESS');
        $senderName = env('MAIL_FROM_NAME', 'Brankas Warisan Digital');

        $payload = [
            'sender' => [
                'name' => $senderName,
                'email' => trim((string)$senderEmail),
            ],
            'to' => [
                [
                    'email' => trim((string)$user->email),
                    'name' => trim((string)$user->name),
                ]
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ];

        try {
            $response = Http::timeout(15)->withHeaders([
                'accept' => 'application/json',
                'api-key' => trim((string)$apiKey),
                'content-type' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', $payload);

            dd([
                'status_code' => $response->status(),
                'is_successful' => $response->successful(),
                'response_body' => $response->json() ?? $response->body(),
                'api_key_used' => substr((string)$apiKey, 0, 10) . '...',
                'sender_email_used' => $senderEmail,
                'recipient_target' => $user->email,
            ]);
        } catch (\Throwable $e) {
            dd([
                'error_exception' => $e->getMessage(),
                'trace' => $e->getFile() . ':' . $e->getLine(),
            ]);
        }
    }

    public function checkTimerStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Timer 5 menit awal login
        if (!$user->initial_test_done && !$user->warning_sent_at) {
            if (!$user->next_check_in_deadline || $user->next_check_in_deadline->gt(now()->addMinutes(6))) {
                $user->update([
                    'last_check_in_at' => now(),
                    'next_check_in_deadline' => now()->addMinutes(5),
                    'switch_status' => 'active',
                ]);
            }
        }

        // 2. Jika 5 menit pertama habis -> kirim email otomatis
        if (!$user->initial_test_done && !$user->warning_sent_at && $user->next_check_in_deadline && now()->gte($user->next_check_in_deadline)) {
            $confirmUrl = URL::temporarySignedRoute(
                'switch.email-confirm',
                now()->addHours(24),
                ['user' => $user->id]
            );

            $htmlContent = view('emails.check-in-warning', [
                'user' => $user,
                'confirmUrl' => $confirmUrl,
            ])->render();

            EmailApiService::send(
                $user->email,
                $user->name,
                '⚠️ [PENTING] Konfirmasi Bahwa Anda Masih Sehat - Warisan Digital',
                $htmlContent
            );

            $interval = $user->check_in_interval_days ?: 180;
            $user->update([
                'initial_test_done' => true,
                'warning_sent_at' => now(),
                'next_check_in_deadline' => now()->addDays($interval),
            ]);

            return response()->json([
                'stage' => 'warning_sent',
                'reload' => true,
            ]);
        }

        // 3. Jika 5 menit setelah email peringatan tidak ada konfirmasi -> serahkan aset
        if ($user->warning_sent_at && !$user->inheritance_dispatched_at && now()->gte($user->warning_sent_at->copy()->addMinutes(5))) {
            $this->dispatchAssetsToBeneficiaries($user);

            $user->update([
                'inheritance_dispatched_at' => now(),
                'warning_sent_at' => null,
                'switch_status' => 'triggered',
            ]);

            return response()->json([
                'stage' => 'assets_dispatched',
                'reload' => true,
            ]);
        }

        $secondsToWarning = null;
        if (!$user->initial_test_done && $user->next_check_in_deadline && now()->lt($user->next_check_in_deadline)) {
            $secondsToWarning = max(0, (int) now()->diffInSeconds($user->next_check_in_deadline));
        }

        $secondsToRelease = null;
        if ($user->warning_sent_at && !$user->inheritance_dispatched_at) {
            $target = $user->warning_sent_at->copy()->addMinutes(5);
            if (now()->lt($target)) {
                $secondsToRelease = max(0, (int) now()->diffInSeconds($target));
            }
        }

        return response()->json([
            'stage' => 'monitoring',
            'seconds_to_warning' => $secondsToWarning,
            'seconds_to_release' => $secondsToRelease,
        ]);
    }

    protected function dispatchAssetsToBeneficiaries(User $user): int
    {
        $beneficiaries = $user->beneficiaries()->get();
        $allAssets = $user->assets()->get();
        $count = 0;

        foreach ($beneficiaries as $ben) {
            $benAssets = $allAssets->filter(function ($asset) use ($ben) {
                return empty($asset->beneficiary_id) || (int) $asset->beneficiary_id === (int) $ben->id;
            });

            if ($benAssets->isEmpty()) {
                $benAssets = $allAssets;
            }

            if ($benAssets->isEmpty()) continue;

            $benEmail = trim((string) $ben->email);
            if (!empty($benEmail) && filter_var($benEmail, FILTER_VALIDATE_EMAIL)) {
                $htmlRelease = view('emails.beneficiary-release', [
                    'owner' => $user,
                    'beneficiary' => $ben,
                    'assets' => $benAssets,
                ])->render();

                $mailSubject = "🔐 Penyerahan Aset Warisan Digital dari {$user->name}";
                EmailApiService::send($benEmail, $ben->name, $mailSubject, $htmlRelease);
            }

            if (!empty(trim((string) $ben->phone))) {
                $this->sendWhatsAppToBeneficiary($user, $ben, $benAssets);
            }

            $count++;
        }

        ActivityLog::record(
            'INHERITANCE_DISPATCHED',
            "Sakelar otomatis menyerahkan aset ke {$count} ahli waris."
        );

        return $count;
    }

    protected function sendWhatsAppToBeneficiary(User $owner, $ben, $assets): void
    {
        $activeSub = $owner->activeSubscription;
        $planExpiry = ($activeSub && $activeSub->ends_at)
            ? $activeSub->ends_at->format('d M Y')
            : 'Selamanya (Aktif)';

        $waText = "*[PENYERAHAN ASET WARISAN DIGITAL]*\n\n"
            . "Halo *{$ben->name}*,\n"
            . "Berdasarkan sistem Sakelar Otomatis, *{$owner->name}* tidak merespons batas konfirmasi sehat. Berikut rincian aset yang diserahkan:\n\n";

        foreach ($assets as $idx => $asset) {
            $num = $idx + 1;
            $name = $asset->name ?? $asset->title ?? 'Aset Digital';
            $category = strtoupper($asset->category ?? $asset->type ?? 'Umum');
            $expiry = $asset->expires_at ? \Carbon\Carbon::parse($asset->expires_at)->format('d M Y') : $planExpiry;

            $rawContent = $asset->content ?? $asset->credentials ?? $asset->secret_data ?? $asset->description ?? '-';
            try {
                $decrypted = Crypt::decryptString($rawContent);
            } catch (\Throwable $e) {
                $decrypted = $rawContent;
            }

            $rawPath = $asset->file_path ?? $asset->attachment_path ?? $asset->image_path ?? null;
            $fileUrl = $rawPath
                ? (str_starts_with($rawPath, 'http') ? $rawPath : Storage::disk('supabase')->url($rawPath))
                : 'Tidak ada lampiran';

            $waText .= "----------------------------------\n"
                . "*{$num}. {$name}*\n"
                . "• *Kategori:* {$category}\n"
                . "• *Ahli Waris:* {$ben->name}\n"
                . "• *Masa Berlaku:* {$expiry}\n"
                . "• *Isi Aset:* {$decrypted}\n"
                . "• *Gambar/File:* {$fileUrl}\n";
        }

        $fonnteToken = env('FONNTE_TOKEN');
        if (!empty($fonnteToken)) {
            try {
                Http::withHeaders(['Authorization' => $fonnteToken])
                    ->post('https://api.fonnte.com/send', [
                        'target' => $ben->phone,
                        'message' => $waText,
                    ]);
            } catch (\Throwable $e) {}
        }
    }
}