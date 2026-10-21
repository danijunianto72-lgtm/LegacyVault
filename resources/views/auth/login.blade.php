@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col justify-center items-center py-10 px-4">
    <!-- Kotakan Card Login dengan Tombol Mode di Pojok Kanan Atas -->
    <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-8 shadow-2xl space-y-6">
        
        <!-- Tombol Toggle Mode Terang / Gelap di Dalam Kartu -->
        <div class="absolute top-6 right-6">
            <button type="button" 
                    @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light'); if(darkMode){ document.documentElement.classList.add('dark') } else { document.documentElement.classList.remove('dark') }"
                    class="px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-sm">
                <i class="fa-solid" :class="darkMode ? 'fa-sun text-amber-400' : 'fa-moon text-indigo-500'"></i>
                <span x-text="darkMode ? 'Mode Terang' : 'Mode Gelap'"></span>
            </button>
        </div>

        <!-- Header -->
        <div class="text-center space-y-2 pt-2">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 text-xl shadow-inner">
                <i class="fa-solid fa-vault"></i>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Masuk ke LegacyVault</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Masukkan akun untuk mengelola brankas & wasiat Anda
            </p>
        </div>

        <!-- Alert Notifikasi Flash -->
        @if (session('success'))
            <div class="p-3.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-sm shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Form Login -->
        <form method="POST" action="{{ route('login.post') }}" class="space-y-4" x-data="{ showLoginPass: false }">
            @csrf

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    <i class="fa-regular fa-envelope mr-1 text-emerald-500"></i> Alamat Email
                </label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="nama@email.com"
                    class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2.5 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none transition">
                @error('email')
                    <p class="text-xs text-rose-500 mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    <i class="fa-solid fa-lock mr-1 text-emerald-500"></i> Kata Sandi
                </label>
                <div class="relative">
                    <input :type="showLoginPass ? 'text' : 'password'" name="password" required placeholder="Masukkan kata sandi..."
                        class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 pl-3.5 pr-11 py-2.5 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none transition">
                    <button type="button" @click="showLoginPass = !showLoginPass" class="absolute inset-y-0 right-0 px-3.5 flex items-center text-slate-400 hover:text-emerald-500 transition">
                        <i class="fa-solid" :class="showLoginPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                @error('password')
                    <p class="text-xs text-rose-500 mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="inline-flex items-center gap-2 cursor-pointer text-slate-600 dark:text-slate-400">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 bg-slate-50 dark:bg-slate-950">
                    <span>Ingat Saya</span>
                </label>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/20 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    <span>Masuk ke Akun</span>
                </button>
            </div>
        </form>

        <!-- Akun Demo Cepat (Jika Ada) -->
        <!-- @if(isset($demoUser) || isset($demoAdmin))
            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80 space-y-2 text-[11px] text-slate-500 dark:text-slate-400">
                <div class="font-semibold text-slate-700 dark:text-slate-300">Akun Pengujian Cepat:</div>
                <div class="flex flex-wrap gap-2">
                    @if(isset($demoUser))
                        <span class="px-2 py-1 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">User: {{ $demoUser->email }}</span>
                    @endif
                    @if(isset($demoAdmin))
                        <span class="px-2 py-1 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">Admin: {{ $demoAdmin->email }}</span>
                    @endif
                </div>
            </div>
        @endif -->

        <!-- Tautan ke Halaman Register -->
        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 text-center">
            <p class="text-xs text-slate-600 dark:text-slate-400">
                Belum punya akun brankas? 
                <a href="{{ route('register') }}" class="font-bold text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 hover:underline ml-1">
                    Register here &rarr;
                </a>
            </p>
        </div>

    </div>
</div>
@endsection