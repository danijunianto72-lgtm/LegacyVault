@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col justify-center items-center py-10 px-4">
    <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-8 shadow-2xl space-y-6">
        
        <!-- Tombol Toggle Mode Terang / Gelap di Pojok Kanan Atas -->
        <div class="absolute top-6 right-6">
            <button type="button" 
                    @click="darkMode = !darkMode"
                    class="px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-sm">
                <i class="fa-solid" :class="darkMode ? 'fa-sun text-amber-400' : 'fa-moon text-indigo-500'"></i>
                <span x-text="darkMode ? 'Mode Putih' : 'Mode Gelap'"></span>
            </button>
        </div>

        <!-- Header -->
        <div class="text-center space-y-2 pt-2">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-emerald-500 text-2xl shadow-inner">
                <i class="fa-brands fa-whatsapp"></i>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Verifikasi WhatsApp</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                Masukkan 6 digit kode OTP yang telah dikirim ke nomor:<br>
                <strong class="text-emerald-600 dark:text-emerald-400 text-sm">{{ $pending['phone'] }}</strong>
            </p>
        </div>

        <!-- Alert Sukses Kirim OTP -->
        @if (session('success'))
            <div class="p-3 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-check shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Box Simulasi OTP (Muncul jika FONNTE_TOKEN belum diatur di .env agar mudah ditest) -->
        @if(!env('FONNTE_TOKEN'))
            <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-300 text-xs space-y-1">
                <div class="font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-flask"></i>
                    <span>Mode Simulasi Lokal (Tanpa API Key WA):</span>
                </div>
                <p class="text-[11px] opacity-90">
                    Gunakan kode OTP berikut untuk pengujian: 
                    <strong class="font-mono text-sm px-2 py-0.5 rounded bg-amber-500/20 text-amber-900 dark:text-amber-200 tracking-widest select-all">{{ $pending['otp_code'] }}</strong>
                </p>
            </div>
        @endif

        <!-- Form Input OTP -->
        <form method="POST" action="{{ route('register.otp.verify') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 text-center mb-2">
                    Kode Verifikasi (6 Digit)
                </label>
                <input type="text" 
                       name="otp" 
                       maxlength="6" 
                       required 
                       autofocus 
                       placeholder="••••••"
                       class="w-full text-center tracking-[0.6em] font-mono text-2xl font-extrabold rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-4 py-3.5 text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none transition">
                @error('otp')
                    <p class="text-xs text-rose-500 mt-2 text-center flex items-center justify-center gap-1">
                        <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/20 transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-shield-check"></i>
                <span>Verifikasi & Buat Akun</span>
            </button>
        </form>

        <!-- Kirim Ulang OTP & Ganti Nomor -->
        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
            <form method="POST" action="{{ route('register.otp.resend') }}">
                @csrf
                <button type="submit" class="font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1">
                    <i class="fa-solid fa-rotate-right"></i>
                    <span>Kirim Ulang OTP</span>
                </button>
            </form>

            <a href="{{ route('register') }}" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                &larr; Ubah Data
            </a>
        </div>

    </div>
</div>
@endsection