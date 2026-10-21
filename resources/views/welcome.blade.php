@extends('layouts.app')

@section('content')
<div x-data="{ helpModalOpen: false }" class="py-8 sm:py-16 px-2 sm:px-6 max-w-5xl mx-auto space-y-14">

    <!-- HERO SECTION -->
    <div class="text-center max-w-2xl mx-auto space-y-6">
        
        <!-- Badge Atas -->
        <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm font-medium shadow-sm">
            <i class="fa-solid fa-shield-check"></i>
            <span>Sistem Warisan Digital Terenkripsi End-to-End</span>
        </div>

        <!-- Judul Utama -->
        <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 dark:text-white leading-tight tracking-tight">
            Lindungi Masa Depan Keluarga Anda dengan <span class="text-emerald-500">LegacyVault</span>
        </h1>

        <!-- Deskripsi -->
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 leading-relaxed">
            Kunci enkripsi aman, aset kripto, rekening bank, dokumen legal, dan pesan rahasia yang akan diserahkan secara otomatis kepada Ahli Waris jika Anda tidak lagi aktif.
        </p>

        <!-- Tombol CTA (Masuk/Daftar & Pusat Bantuan) -->
        <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3.5 max-w-md mx-auto">
            <a href="{{ route('login') }}" 
               class="w-full py-4 px-6 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-sm transition shadow-lg shadow-emerald-500/25 flex items-center justify-center gap-2.5">
                <span>Masuk / Daftar Akun</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>

            <button type="button" 
                    @click="helpModalOpen = true"
                    class="w-full py-4 px-6 rounded-2xl bg-slate-100 dark:bg-[#0d1527] hover:bg-slate-200 dark:hover:bg-slate-800/80 border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 font-bold text-sm transition flex items-center justify-center gap-2">
                <i class="fa-regular fa-circle-question text-slate-500 dark:text-slate-400"></i>
                <span>Pusat Bantuan</span>
            </button>
        </div>
    </div>

    <!-- 3 KARTU FITUR UTAMA -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        
        <!-- Kartu 1: Enkripsi AES-256 -->
        <div class="p-6 rounded-3xl bg-white dark:bg-[#0b1222] border border-slate-200 dark:border-slate-800/90 shadow-sm space-y-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-emerald-500 text-lg">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div class="space-y-1.5">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Enkripsi AES-256</h3>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Hanya Anda yang memegang kunci utama. Tim LegacyVault tidak dapat membaca rahasia Anda.
                </p>
            </div>
        </div>

        <!-- Kartu 2: Dead Man's Switch -->
        <div class="p-6 rounded-3xl bg-white dark:bg-[#0b1222] border border-slate-200 dark:border-slate-800/90 shadow-sm space-y-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-emerald-500 text-lg">
                <i class="fa-solid fa-heart-pulse"></i>
            </div>
            <div class="space-y-1.5">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Dead Man's Switch</h3>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Pengingat berkala via Email & WA. Cukup 1-Klik di Email untuk mengonfirmasi kesehatan tanpa login.
                </p>
            </div>
        </div>

        <!-- Kartu 3: Penyerahan Otomatis -->
        <div class="p-6 rounded-3xl bg-white dark:bg-[#0b1222] border border-slate-200 dark:border-slate-800/90 shadow-sm space-y-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-emerald-500 text-lg">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="space-y-1.5">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Penyerahan Otomatis</h3>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Aset diserahkan secara otomatis hanya kepada ahli waris yang Anda tunjuk jika kondisi darurat terpenuhi.
                </p>
            </div>
        </div>

    </div>

    <!-- MODAL PUSAT BANTUAN -->
    <template x-teleport="body">
        <div x-show="helpModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
            <div @click.away="helpModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fa-regular fa-circle-question text-emerald-500"></i>
                        <span>Cara Kerja LegacyVault</span>
                    </h3>
                    <button @click="helpModalOpen = false" class="text-slate-400 hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="space-y-3 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    <p><strong>1. Simpan Aset & Wasiat:</strong> Unggah dokumen penting, akun finansial, atau pesan rahasia. Seluruh data dienkripsi dengan standar AES-256.</p>
                    <p><strong>2. Tunjuk Ahli Waris:</strong> Daftarkan anggota keluarga terpercaya yang berhak menerima akses atas masing-masing dokumen.</p>
                    <p><strong>3. Sakelar Otomatis (Dead Man's Switch):</strong> Sistem memantau status aktif Anda secara berkala. Jika melewati batas waktu tanpa konfirmasi sehat, wasiat akan diteruskan ke ahli waris.</p>
                </div>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <button type="button" @click="helpModalOpen = false" class="px-4 py-2 rounded-xl bg-emerald-500 text-slate-950 font-bold text-xs">Mengerti</button>
                </div>
            </div>
        </div>
    </template>
<!-- TOMBOL BANTUAN MELAYANG (FLOATING HELP BUTTON) DI KANAN BAWAH -->
    <div x-data="{ contactOpen: false }" class="fixed bottom-6 right-6 z-40">
        
        <!-- Pop-up Box Bantuan Cepat -->
        <div x-show="contactOpen" 
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 scale-95"
             @click.away="contactOpen = false"
             class="mb-3 w-72 sm:w-80 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-4 space-y-3">
            
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h4 class="text-xs font-extrabold text-slate-900 dark:text-white">Pusat Bantuan LegacyVault</h4>
                </div>
                <button @click="contactOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                 mengalami kendala terkait enkripsi brankas, pembayaran, atau klaim ahli waris? Hubungi tim teknis kami melalui email resmi:
            </p>

            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-[11px] font-mono text-emerald-600 dark:text-emerald-400 text-center select-all">
                helplegalcyvault@gmail.com
            </div>

            <!-- Tombol Langsung Kirim Email -->
            <a href="mailto:helplegalcyvault@gmail.com?subject=Bantuan%20Layanan%20LegacyVault&body=Halo%20Tim%20Support%20LegacyVault,%0A%0ASaya%20membutuhkan%20bantuan%20terkait:%20"
               class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition flex items-center justify-center gap-2 shadow-md shadow-emerald-600/20">
                <i class="fa-regular fa-paper-plane"></i>
                <span>Kirim Email Sekarang</span>
            </a>
        </div>

        <!-- Tombol Bulat/Kapsul Pemicu di Pojok Kanan Bawah -->
        <button type="button"
                @click="contactOpen = !contactOpen"
                class="ml-auto px-4 py-3 rounded-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs shadow-xl shadow-emerald-500/25 hover:scale-105 transition flex items-center gap-2 border border-emerald-400">
            <i class="fa-solid text-base" :class="contactOpen ? 'fa-xmark' : 'fa-headset'"></i>
            <!-- <span>Butuh Bantuan?</span> -->
        </button>

    </div>
</div>
@endsection