@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col justify-center items-center py-10 px-4">
    <!-- Kotakan Card Register dengan Tombol Mode di Pojok Kanan Atas -->
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
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Daftar Akun Baru</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Amankan aset penting & wasiat rahasia Anda ke dalam brankas
            </p>
        </div>

        <!-- Form Register -->
        <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ showPass: false, showPassConfirm: false }">
            @csrf

            <!-- Nama Lengkap -->
            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    <i class="fa-regular fa-user mr-1 text-emerald-500"></i> Nama Lengkap
                </label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus placeholder="Contoh: Muhammad Hilal"
                    class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2.5 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none transition">
                @error('name')
                    <p class="text-xs text-rose-500 mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    <i class="fa-regular fa-envelope mr-1 text-emerald-500"></i> Alamat Email
                </label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="nama@email.com"
                    class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2.5 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none transition">
                @error('email')
                    <p class="text-xs text-rose-500 mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- No WhatsApp -->
            <div>
                <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    <i class="fa-brands fa-whatsapp mr-1 text-emerald-500 text-sm"></i> Nomor WhatsApp Aktif
                </label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required placeholder="Contoh: 081234567890"
                    class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2.5 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none transition">
                <p class="text-[11px] text-slate-400 mt-1">Digunakan untuk konfirmasi sakelar kesehatan otomatis.</p>
                @error('phone')
                    <p class="text-xs text-rose-500 mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Kata Sandi -->
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    <i class="fa-solid fa-lock mr-1 text-emerald-500"></i> Kata Sandi
                </label>
                <div class="relative">
                    <input id="password" :type="showPass ? 'text' : 'password'" name="password" required placeholder="Minimal 8 karakter..."
                        class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 pl-3.5 pr-11 py-2.5 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none transition">
                    <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 px-3.5 flex items-center text-slate-400 hover:text-emerald-500 transition">
                        <i class="fa-solid" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                @error('password')
                    <p class="text-xs text-rose-500 mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Konfirmasi Kata Sandi -->
            <div>
                <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    <i class="fa-solid fa-shield-halved mr-1 text-emerald-500"></i> Ulangi Kata Sandi
                </label>
                <div class="relative">
                    <input id="password_confirmation" :type="showPassConfirm ? 'text' : 'password'" name="password_confirmation" required placeholder="Ulangi kata sandi..."
                        class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 pl-3.5 pr-11 py-2.5 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none transition">
                    <button type="button" @click="showPassConfirm = !showPassConfirm" class="absolute inset-y-0 right-0 px-3.5 flex items-center text-slate-400 hover:text-emerald-500 transition">
                        <i class="fa-solid" :class="showPassConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/20 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Daftar Akun Sekarang</span>
                </button>
            </div>
        </form>

        <!-- Tautan Kembali ke Login -->
        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 text-center">
            <p class="text-xs text-slate-600 dark:text-slate-400">
                Sudah punya akun? 
                <a href="{{ route('login') }}" class="font-bold text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 hover:underline ml-1">
                    Login here &rarr;
                </a>
            </p>
        </div>

    </div>
</div>
@endsection