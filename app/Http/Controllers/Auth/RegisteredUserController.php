<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Tahap 1: Validasi input form, upload avatar ke Supabase, simpan session, & kirim WA OTP.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:25'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'gender' => ['required', 'in:male,female'],
            'birth_date' => ['required', 'date', 'before:today'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'gender.required' => 'Pilih jenis kelamin Anda.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'birth_date.before' => 'Tanggal lahir tidak valid.',
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        // Upload avatar ke Supabase Storage jika ada
        $avatarUrl = null;
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $fileName = 'avatars/' . uniqid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('supabase')->put($fileName, file_get_contents($file), 'public');
            $avatarUrl = Storage::disk('supabase')->url($fileName);
        }

        $otpCode = (string) random_int(100000, 999999);

        // Simpan data lengkap ke session sementara
        session([
            'pending_registration' => [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'birth_date' => $request->birth_date,
                'avatar_url' => $avatarUrl,
                'password' => Hash::make($request->password),
                'otp_code' => $otpCode,
                'expires_at' => now()->addMinutes(5),
            ]
        ]);

        // Kirim pesan WhatsApp
        $this->sendWhatsAppOtp($request->phone, $request->name, $otpCode);

        return redirect()->route('register.otp')
            ->with('success', "Kode OTP 6 digit telah dikirimkan ke nomor WhatsApp {$request->phone}.");
    }

    /**
     * Tampilkan halaman input 6 digit OTP.
     */
    public function showOtpForm()
    {
        if (!session()->has('pending_registration')) {
            return redirect()->route('register');
        }

        $pending = session('pending_registration');
        return view('auth.verify-otp', compact('pending'));
    }

    /**
     * Tahap 2: Verifikasi kode OTP & simpan user ke database.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'numeric', 'digits:6'],
        ], [
            'otp.required' => 'Masukkan 6 digit kode OTP.',
            'otp.digits' => 'Kode OTP harus terdiri dari 6 angka.',
        ]);

        $pending = session('pending_registration');

        if (!$pending) {
            return redirect()->route('register')->withErrors(['email' => 'Sesi pendaftaran berakhir, silakan isi ulang.']);
        }

        if (now()->greaterThan($pending['expires_at'])) {
            return back()->withErrors(['otp' => 'Kode OTP sudah kedaluwarsa. Silakan kirim ulang OTP baru.']);
        }

        if ((string) $request->otp !== (string) $pending['otp_code']) {
            return back()->withErrors(['otp' => 'Kode OTP yang Anda masukkan salah!']);
        }

        // OTP Cocok -> Buat User Resmi di Database dari data pending session
        $user = User::create([
            'name' => $pending['name'],
            'email' => $pending['email'],
            'phone' => $pending['phone'],
            'gender' => $pending['gender'],
            'birth_date' => $pending['birth_date'],
            'avatar_url' => $pending['avatar_url'],
            'password' => $pending['password'],
            'role' => 'user',
            'check_in_interval_days' => 180,
            'last_check_in_at' => now(),
            'next_check_in_deadline' => now()->addDays(180),
            'switch_status' => 'active',
        ]);

        // Hapus session sementara
        session()->forget('pending_registration');

        event(new Registered($user));

        ActivityLog::record('REGISTER', "User berhasil mendaftar & verifikasi OTP WA: {$user->email}");

        return redirect()->route('login')->with('success', 'Verifikasi WhatsApp berhasil! Silakan masuk menggunakan akun Anda.');
    }

    /**
     * Kirim ulang kode OTP baru.
     */
    public function resendOtp(): RedirectResponse
    {
        $pending = session('pending_registration');

        if (!$pending) {
            return redirect()->route('register');
        }

        $newOtp = (string) random_int(100000, 999999);
        $pending['otp_code'] = $newOtp;
        $pending['expires_at'] = now()->addMinutes(5);

        session(['pending_registration' => $pending]);

        $this->sendWhatsAppOtp($pending['phone'], $pending['name'], $newOtp);

        return back()->with('success', 'Kode OTP baru berhasil dikirim ulang ke WhatsApp Anda.');
    }

    /**
     * Helper kirim pesan via WhatsApp Gateway (Fonnte).
     */
    private function sendWhatsAppOtp(string $phone, string $name, string $otp): void
    {
        $token = env('FONNTE_TOKEN');
        if (!$token) {
            return;
        }

        try {
            Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $phone,
                'message' => "*LEGACYVAULT - VERIFIKASI AKUN*\n\nHalo *{$name}*,\nKode OTP pendaftaran Brankas Digital Anda adalah:\n\n*{$otp}*\n\nKode berlaku selama 5 menit. Jangan berikan kode ini kepada siapa pun.",
                'countryCode' => '62',
            ]);
        } catch (\Exception $e) {
            // Abaikan jika koneksi internet gateway gagal
        }
    }
}