<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\EmailApiService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Tampilkan halaman form lupa kata sandi
     */
    public function showLinkRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Kirim tautan reset password ke email user
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Kami tidak menemukan akun dengan alamat email tersebut.',
            ])->onlyInput('email');
        }

        // Buat token acak 64 karakter
        $token = Str::random(64);

        // Simpan ke tabel password_reset_tokens (hapus token lama jika ada)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now(),
            ]
        );

        // Buat URL Reset Password
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);

        // Render template email
        $htmlContent = view('emails.reset-password', [
            'user' => $user,
            'resetUrl' => $resetUrl,
        ])->render();

        $subject = "🔑 Permintaan Reset Kata Sandi - LegacyVault";

        $sent = EmailApiService::send(
            $user->email,
            $user->name,
            $subject,
            $htmlContent
        );

        if (!$sent) {
            return back()->with('error', 'Gagal mengirim email pemulihan. Silakan coba beberapa saat lagi.');
        }

        return back()->with('status', 'Tautan pemulihan kata sandi telah dikirim ke email Anda! Silakan cek Inbox atau folder Spam.');
    }

    /**
     * Tampilkan form pembuatan kata sandi baru
     */
    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Proses pembaruan kata sandi baru ke database
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.min' => 'Kata sandi minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        // Validasi keberadaan token & kecocokan hash
        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Token pemulihan tidak valid atau sudah digunakan.']);
        }

        // Validasi masa berlaku token (60 menit)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['email' => 'Tautan pemulihan sudah kadaluarsa (lebih dari 60 menit). Silakan ajukan ulang.']);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'Pengguna tidak ditemukan.']);
        }

        // Update password user
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Hapus token agar tidak bisa dipakai dua kali
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        if (class_exists(ActivityLog::class)) {
            ActivityLog::record('PASSWORD_RESET', 'Berhasil melakukan pengaturan ulang kata sandi melalui email.', $user->id);
        }

        return redirect()->route('login')->with('success', 'Kata sandi Anda berhasil diperbarui! Silakan login dengan kata sandi baru.');
    }
}