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

        $randomCode = strtoupper(substr(md5(uniqid()), 0, 5));
$timestamp = now()->timezone('Asia/Jakarta')->format('H:i:s');
$subject = "Peringatan Konfirmasi Sehat [#{$randomCode} - {$timestamp}]";

        $sent = EmailApiService::send(
            $user->email,
            $user->name,
            $subject,
            $htmlContent
        );

        if (!$sent) {
            return back()->with('error', 'Gagal mengirim email via Brevo. Silakan periksa konfigurasi.');
        }

        // Mulai hitung mundur 5 menit menuju pelepasan aset ke ahli waris (total 10 menit)
        $user->update([
            'warning_sent_at' => now(),
            'inheritance_dispatched_at' => null,
        ]);

        ActivityLog::record('SIMULATION_EMAIL_SENT', "Email konfirmasi sehat berhasil dikirim ke {$user->email}");

        return back()->with('success', "Email konfirmasi telah dikirim ke {$user->email}! Jika tidak ada di Kotak Masuk, periksa folder SPAM atau tab Promosi/Updates.");
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

/**
     * Menyerahkan aset ke seluruh ahli waris terdaftar melalui Email & WhatsApp
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

            // Siapkan susunan data aset bersih
            $formattedAssets = [];
            foreach ($benAssets as $asset) {
                // Logika Masa Berlaku: Selamanya vs Rentang Tanggal
                $validity = 'Selamanya';
                if (!empty($asset->expires_at)) {
                    $startDate = !empty($asset->starts_at) 
                        ? \Carbon\Carbon::parse($asset->starts_at)->format('d M Y') 
                        : ($asset->created_at ? $asset->created_at->format('d M Y') : now()->format('d M Y'));
                    $endDate = \Carbon\Carbon::parse($asset->expires_at)->format('d M Y');
                    $validity = "{$startDate} s.d {$endDate}";
                }

                // Logika Dekripsi Isi Teks Aset
             // Tambahkan $asset->secret_content di baris paling awal
            $rawText = $asset->secret_content 
                ?? $asset->content 
                ?? $asset->credentials 
                ?? $asset->secret_data 
                ?? $asset->description 
                ?? null;

            $decryptedText = 'Tidak ada catatan teks';

            if (!empty($rawText)) {
                try {
                    $decryptedText = Crypt::decryptString($rawText);
                } catch (\Throwable $e) {
                    // Jika teks tidak terenkripsi atau gagal didekripsi, tampilkan teks aslinya
                    $decryptedText = $rawText;
                }
            }
                // Logika Tautan Lampiran
                $filePath = $asset->file_path ?? $asset->attachment_path ?? $asset->image_path ?? null;
                $attachmentUrl = null;
                if (!empty($filePath)) {
                    $attachmentUrl = str_starts_with($filePath, 'http')
                        ? $filePath
                        : Storage::disk('supabase')->url($filePath);
                }

                $formattedAssets[] = [
                    'name' => $asset->name ?? $asset->title ?? 'Aset Digital',
                    'category' => ucwords(str_replace('_', ' ', $asset->category ?? $asset->type ?? 'Dokumen')),
                    'validity' => $validity,
                    'content' => $decryptedText,
                    'attachment_url' => $attachmentUrl,
                ];
            }

            // 1. Eksekusi Kirim Email ke Ahli Waris
            $benEmail = trim((string) $ben->email);
            if (!empty($benEmail) && filter_var($benEmail, FILTER_VALIDATE_EMAIL)) {
                $htmlRelease = view('emails.beneficiary-release', [
                    'owner' => $user,
                    'beneficiary' => $ben,
                    'formattedAssets' => $formattedAssets,
                ])->render();

                $mailSubject = "🔐 [LegacyVault] Penyerahan Amanat Aset Digital dari {$user->name}";
                EmailApiService::send($benEmail, $ben->name, $mailSubject, $htmlRelease);
            }

            // 2. Eksekusi Kirim WhatsApp ke Ahli Waris
            if (!empty(trim((string) $ben->phone))) {
                $this->sendWhatsAppBeneficiaryRelease($user, $ben, $formattedAssets);
            }

            $count++;
        }

        ActivityLog::record(
            'INHERITANCE_DISPATCHED',
            "Sakelar otomatis menyerahkan amanat aset kepada {$count} ahli waris melalui Email & WhatsApp."
        );

        return $count;
    }

    /**
     * Helper: Format pesan WhatsApp sesuai format resmi
     */
    protected function sendWhatsAppBeneficiaryRelease(User $owner, $beneficiary, array $formattedAssets): void
    {
        $message = "Halo *{$beneficiary->name}*,\n\n"
            . "Pesan otomatis ini dikirimkan oleh sistem *LegacyVault* secara otomatis karena Bapak/Ibu *{$owner->name}* tidak merespons konfirmasi keaktifan berkala dalam batas waktu yang telah ditentukan (melewati prosedur pengingat bertahap).\n\n"
            . "Sesuai dengan instruksi dan amanat yang telah diatur oleh beliau semasa hidup/aktif, berikut adalah rincian aset digital, dokumen, serta kredensial penting yang diwariskan secara khusus kepada Anda:\n\n";

        foreach ($formattedAssets as $idx => $asset) {
            $num = $idx + 1;
            $attachment = $asset['attachment_url'] ?: 'Tidak Ada';

            $message .= "----------------------------------\n"
                . "{$num}. *{$asset['name']}*\n"
                . "2. Kategori: {$asset['category']}\n"
                . "3. Ahli Waris Ditunjuk: {$beneficiary->name}\n"
                . "4. Masa Berlaku Aset: {$asset['validity']}\n"
                . "5. Isi Aset & Kredensial: {$asset['content']}\n"
                . "6. Dokumen Lampiran: {$attachment}\n\n";
        }

        $message .= "----------------------------------\n"
            . "*Informasi Penting & Petunjuk Selanjutnya:*\n"
            . "1. Harap simpan rincian informasi dan dokumen lampiran di atas dengan aman dan bersifat rahasia.\n"
            . "2. Jika dokumen memerlukan proses verifikasi atau klaim fisik (seperti rekening bank, polis asuransi, atau sertifikat), Anda dapat membawa salinan berkas ini ke instansi/lembaga terkait sesuai dengan prosedur hukum yang berlaku.\n"
            . "3. Apabila terdapat kekeliruan atau Anda membutuhkan bantuan teknis dalam mengunduh dokumen terenkripsi, silakan hubungi tim dukungan kami di support@legacyvault.com atau kunjungi legacyvault.com/help.\n\n"
            . "_Pesan ini disampaikan secara sah dan otomatis berdasarkan pengaturan amanat dari pemilik akun yang bersangkutan._\n\n"
            . "Hormat kami,\n"
            . "*Tim Otomasi Sistem LegacyVault*\n"
            . "Sistem Penyimpanan & Penyerahan Aset Digital Terenkripsi";

        $fonnteToken = env('FONNTE_TOKEN');
        if (!empty($fonnteToken)) {
            try {
                Http::withHeaders(['Authorization' => $fonnteToken])
                    ->post('https://api.fonnte.com/send', [
                        'target' => $beneficiary->phone,
                        'message' => $message,
                    ]);
            } catch (\Throwable $e) {}
        }
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