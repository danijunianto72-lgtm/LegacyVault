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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class DeadManSwitchController extends Controller
{
    /**
     * 1. Konfirmasi Sehat Manual dari Tombol Dashboard
     */
    public function confirmManual(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->confirmCheckIn();

        ActivityLog::record('CHECK_IN_CONFIRM', 'Pengguna mengonfirmasi kondisi sehat & mereset timer sakelar.');

        return back()->with('success', 'Konfirmasi sehat berhasil! Timer dikembalikan ke interval rutin (' . ($user->check_in_interval_days ?: 180) . ' hari).');
    }

    /**
     * 2. Konfirmasi Sehat dari Klik Link di Dalam Email
     */
    public function confirmFromEmail(Request $request, User $user): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Tautan konfirmasi tidak valid atau sudah kadaluarsa.');
        }

        $user->confirmCheckIn();

        ActivityLog::record('EMAIL_CHECK_IN_CONFIRM', "Pengguna #{$user->id} mengonfirmasi kondisi sehat melalui tautan Email.");

        return redirect('/dashboard')->with('success', 'Terima kasih! Konfirmasi sehat via Email berhasil diterima. Timer telah kembali ke setelan interval normal.');
    }

    /**
     * Helper internal untuk mengirim Email Peringatan Konfirmasi Sehat via API
     */
    protected function sendWarningEmailViaApi(User $user): bool
    {
        $confirmUrl = URL::temporarySignedRoute(
            'switch.email-confirm',
            now()->addHours(24),
            ['user' => $user->id]
        );

        // Render view Blade menjadi string HTML
        $htmlContent = view('emails.check-in-warning', [
            'user' => $user,
            'confirmUrl' => $confirmUrl,
        ])->render();

        return EmailApiService::send(
            $user->email,
            $user->name,
            '⚠️ [PENTING] Konfirmasi Bahwa Anda Masih Sehat - Warisan Digital',
            $htmlContent
        );
    }

    /**
     * 3. Tombol "Simulasi Email Pengingat" (Kirim Email Beneran via API)
     */
   public function sendSimulationEmail(Request $request): RedirectResponse
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

        // Tambahkan waktu (H:i:s) agar Gmail tidak mengelompokkan ke 1 thread lama
        $timestamp = now()->timezone('Asia/Jakarta')->format('H:i:s');
        $subject = "⚠️ [PENTING] Konfirmasi Bahwa Anda Masih Sehat ({$timestamp}) - Warisan Digital";

        $sent = EmailApiService::send(
            $user->email,
            $user->name,
            $subject,
            $htmlContent
        );

        if (!$sent) {
            return back()->with('error', 'Gagal mengirim email via Brevo. Periksa log atau environment variables Vercel.');
        }

        $user->update([
            'warning_sent_at' => now(),
            'inheritance_dispatched_at' => null,
        ]);

        ActivityLog::record('SIMULATION_EMAIL_SENT', "Email konfirmasi sehat dikirim ke {$user->email}");

        return back()->with('success', "Email konfirmasi baru berhasil dikirim ke {$user->email} pada {$timestamp}! Silakan cek Inbox.");
    }

    /**
     * 4. Endpoint Heartbeat Otomatis (5 Menit Awal Login & 10 Menit Eksekusi Aset)
     */
    public function checkTimerStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        // Inisialisasi timer 5 menit sekali saja saat awal login
        if (!$user->initial_test_done && !$user->warning_sent_at) {
            if (!$user->next_check_in_deadline || $user->next_check_in_deadline->gt(now()->addMinutes(6))) {
                $user->update([
                    'last_check_in_at' => now(),
                    'next_check_in_deadline' => now()->addMinutes(5),
                    'switch_status' => 'active',
                ]);
            }
        }

        // TAHAP 1: Sudah 5 menit sejak login pertama -> Kirim Email Peringatan via API & kembalikan jadwal normal
        if (
            !$user->initial_test_done &&
            !$user->warning_sent_at &&
            $user->next_check_in_deadline &&
            now()->gte($user->next_check_in_deadline)
        ) {
            $this->sendWarningEmailViaApi($user);

            $interval = $user->check_in_interval_days ?: 180;
            $user->update([
                'initial_test_done' => true,
                'warning_sent_at' => now(),
                'next_check_in_deadline' => now()->addDays($interval),
            ]);

            return response()->json([
                'stage' => 'warning_sent',
                'message' => "Waktu 5 menit tercapai! Email konfirmasi sehat telah dikirim otomatis ke {$user->email}.",
                'reload' => true,
            ]);
        }

        // TAHAP 2: Sudah 5 menit sejak Email Peringatan dikirim (Total 10 Menit Tanpa Respon) -> Kirim Aset ke Semua Ahli Waris
        if (
            $user->warning_sent_at &&
            !$user->inheritance_dispatched_at &&
            now()->gte($user->warning_sent_at->copy()->addMinutes(5))
        ) {
            $sentCount = $this->dispatchAssetsToBeneficiaries($user);

            $user->update([
                'inheritance_dispatched_at' => now(),
                'warning_sent_at' => null,
                'switch_status' => 'triggered',
            ]);

            return response()->json([
                'stage' => 'assets_dispatched',
                'message' => "Batas 10 menit tanpa respon terlewati! Seluruh aset telah dikirimkan ke Email & WhatsApp {$sentCount} Ahli Waris.",
                'reload' => true,
            ]);
        }

        $secondsToWarning = (!$user->initial_test_done && $user->next_check_in_deadline)
            ? max(0, (int) now()->diffInSeconds($user->next_check_in_deadline, false))
            : null;

        $secondsToRelease = ($user->warning_sent_at && !$user->inheritance_dispatched_at)
            ? max(0, (int) now()->diffInSeconds($user->warning_sent_at->copy()->addMinutes(5), false))
            : null;

        return response()->json([
            'stage' => 'monitoring',
            'seconds_to_warning' => $secondsToWarning,
            'seconds_to_release' => $secondsToRelease,
        ]);
    }

    /**
     * Helper: Kirim Aset ke Email (via API) & WhatsApp Masing-Masing Ahli Waris
     */
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

            if ($benAssets->isEmpty()) {
                continue;
            }

            // 1. Kirim ke Email Ahli Waris via API Brevo
            if (!empty($ben->email)) {
                $htmlRelease = view('emails.beneficiary-release', [
                    'owner' => $user,
                    'beneficiary' => $ben,
                    'assets' => $benAssets,
                ])->render();

                EmailApiService::send(
                    $ben->email,
                    $ben->name,
                    "🔐 Penyerahan Aset Warisan Digital dari {$user->name}",
                    $htmlRelease
                );
            }

            // 2. Kirim ke WhatsApp Ahli Waris
            if (!empty($ben->phone)) {
                $this->sendWhatsAppToBeneficiary($user, $ben, $benAssets);
            }

            $count++;
        }

        ActivityLog::record(
            'INHERITANCE_DISPATCHED',
            "Sakelar otomatis mengeksekusi pengiriman aset milik {$user->name} ke {$count} ahli waris via API Email & WA."
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
            . "Karena *{$owner->name}* tidak merespon konfirmasi sehat dalam batas waktu yang ditentukan, berikut rincian aset yang diamanatkan kepada Anda:\n\n";

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
            } catch (\Throwable $e) {
                // Abaikan jika gagal
            }
        }
    }
    /**
     * Merespon tombol Konfirmasi Sehat via fetch() AJAX di Blade kamu
     */
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

    /**
     * Menyimpan perubahan Interval Check-in dari Modal
     */
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
    
}