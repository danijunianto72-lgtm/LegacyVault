@extends('layouts.app')

@section('content')
<div class="min-h-[85vh] flex flex-col justify-center items-center py-6 px-2 sm:px-4"
     x-data="{ 
         step: 1, 
         scrolledToBottom: false, 
         agreed: false,
         checkScroll(el) {
             if (el.scrollTop + el.clientHeight >= el.scrollHeight - 15) {
                 this.scrolledToBottom = true;
             }
         }
     }">

    <!-- ========================================== -->
    <!-- TAHAP 1: SYARAT & HUKUM LEGALITAS WARISAN  -->
    <!-- ========================================== -->
    <div x-show="step === 1" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="w-full max-w-lg bg-white dark:bg-[#0b1329] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
        
        <!-- Header Kartu -->
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-emerald-500 text-xl shrink-0 mt-0.5">
                <i class="fa-regular fa-file-lines"></i>
            </div>
            <div class="space-y-1">
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white leading-snug">
                    Syarat & Hukum Legalitas Warisan
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Harap baca dan setujui syarat kerahasiaan sebelum melanjutkan
                </p>
            </div>
        </div>

        <!-- Kotak Scroll Isi Syarat & Ketentuan -->
        <div class="relative">
            <div @scroll="checkScroll($el)" 
                 x-init="setTimeout(() => { if ($el.scrollHeight <= $el.clientHeight) scrolledToBottom = true }, 200)"
                 class="h-64 overflow-y-auto rounded-2xl bg-slate-50 dark:bg-[#070d1e] border border-slate-200 dark:border-slate-800/90 p-4 sm:p-5 space-y-4 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed scroll-smooth">
                
                <div class="space-y-1.5">
                    <h3 class="font-bold text-slate-900 dark:text-white">1. Kerahasiaan & Zero-Knowledge Architecture</h3>
                    <p>
                        LegacyVault beroperasi dengan arsitektur Zero-Knowledge Encryption. Kata sandi utama dan Kunci Privat Anda dienkripsi di sisi klien sebelum dikirim. Tim teknis LegacyVault tidak memiliki akses teknis untuk mendekripsi aset Anda.
                    </p>
                </div>

                <div class="space-y-1.5">
                    <h3 class="font-bold text-slate-900 dark:text-white">2. Sakelar Otomatis (Dead Man's Switch)</h3>
                    <p>
                        Sistem ini mengandalkan konfirmasi keberadaan (Check-in) berkala via Email/WhatsApp. Apabila masa tenggang berlanjut tanpa respon, sistem secara otomatis akan mengirimkan petunjuk enkripsi dan berkas kepada Ahli Waris terdaftar.
                    </p>
                </div>

                <div class="space-y-1.5">
                    <h3 class="font-bold text-slate-900 dark:text-white">3. Keabsahan Ahli Waris</h3>
                    <p>
                        Pengguna bertanggung jawab penuh atas keakuratan informasi kontak Ahli Waris yang ditunjuk. LegacyVault tidak bertanggung jawab atas kesalahan pengiriman akibat alamat email/nomor WhatsApp yang salah dari Pengguna.
                    </p>
                </div>

                <div class="space-y-1.5 pb-2">
                    <h3 class="font-bold text-slate-900 dark:text-white">4. Kebijakan Bantuan & Bantuan Hukum</h3>
                    <p>
                        Untuk pertanyaan terkait hukum atau klarifikasi regulasi, Pengguna dapat menghubungi tim resmi kami melalui email support di 
                        <a href="mailto:helplegalcyvault@gmail.com" class="text-emerald-500 hover:underline font-medium">helplegalcyvault@gmail.com</a>.
                    </p>
                </div>

            </div>

            <!-- Indikator Perintah Scroll ke Bawah -->
            <div x-show="!scrolledToBottom" 
                 x-transition.opacity
                 class="mt-2 text-center text-[11px] font-medium text-amber-600 dark:text-amber-400 flex items-center justify-center gap-1.5 animate-pulse">
                <i class="fa-solid fa-angles-down"></i>
                <span>Gulir (scroll) teks di atas sampai ke bawah untuk menyetujui</span>
            </div>
        </div>

        <!-- Kotak Centang Persetujuan -->
        <label :class="scrolledToBottom ? 'cursor-pointer hover:border-emerald-500/40' : 'opacity-50 cursor-not-allowed'" 
               class="flex items-start gap-3.5 p-4 rounded-2xl bg-slate-50 dark:bg-[#070d1e]/60 border border-slate-200 dark:border-slate-800 transition">
            <input type="checkbox" 
                   x-model="agreed" 
                   :disabled="!scrolledToBottom"
                   class="mt-1 w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-emerald-500 focus:ring-emerald-500 bg-white dark:bg-slate-900 disabled:opacity-50">
            <span class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed select-none">
                Saya memahami bahwa saya memegang kunci enkripsi aset saya sendiri dan menyetujui seluruh ketentuan Layanan & Perjanjian Hukum LegacyVault.
            </span>
        </label>

        <!-- Tombol Setujui & Lanjutkan -->
        <button type="button" 
                @click="if(agreed && scrolledToBottom) { step = 2; window.scrollTo({ top: 0, behavior: 'smooth' }); }"
                :disabled="!agreed || !scrolledToBottom"
                class="w-full py-4 px-6 rounded-2xl bg-emerald-500 hover:bg-emerald-400 disabled:bg-slate-300 dark:disabled:bg-slate-800 text-slate-950 disabled:text-slate-500 font-extrabold text-sm transition shadow-lg shadow-emerald-500/20 disabled:shadow-none flex items-center justify-center gap-2">
            <span>Setujui & Lanjutkan</span>
            <i class="fa-solid fa-check"></i>
        </button>

    </div>

    <!-- PANDUAN PENGGUNAAN (STEPPER) -->
<div class="mt-8 bg-slate-900 border border-slate-800 rounded-3xl p-6 md:p-8" x-data="{ step: 1 }">
    <div class="text-center mb-8">
        <h2 class="text-emerald-500 font-bold tracking-widest text-xs uppercase mb-2">Panduan Penggunaan</h2>
        <h3 class="text-2xl md:text-3xl font-extrabold text-white">4 Langkah Mudah Mengamankan Warisan Digital</h3>
    </div>

    <!-- Kontainer Step -->
    <div class="relative max-w-2xl mx-auto min-h-[200px]">
        
        <!-- STEP 1 (Sebelumnya Step 2) -->
        <div x-show="step === 1" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             class="bg-slate-800/50 border border-slate-700 rounded-2xl p-6 md:p-8 text-center" style="display: none;">
            <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 font-bold flex items-center justify-center text-xl mb-4">
                1
            </div>
            <h4 class="text-xl font-bold text-white mb-3">Daftarkan Ahli Waris</h4>
            <p class="text-slate-400 text-sm md:text-base leading-relaxed">
                Tentukan siapa yang berhak menerima akses aset jika Anda mendadak berhalangan tetap.
            </p>
        </div>

        <!-- STEP 2 (Sebelumnya Step 1) -->
        <div x-show="step === 2" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             class="bg-slate-800/50 border border-slate-700 rounded-2xl p-6 md:p-8 text-center" style="display: none;">
            <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 font-bold flex items-center justify-center text-xl mb-4">
                2
            </div>
            <h4 class="text-xl font-bold text-white mb-3">Simpan Aset Terenkripsi</h4>
            <p class="text-slate-400 text-sm md:text-base leading-relaxed">
                Masukkan akun bank, kunci crypto, surat tanah, atau password penting ke dalam Brankas.
            </p>
        </div>

        <!-- STEP 3 -->
        <div x-show="step === 3" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             class="bg-slate-800/50 border border-slate-700 rounded-2xl p-6 md:p-8 text-center" style="display: none;">
            <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 font-bold flex items-center justify-center text-xl mb-4">
                3
            </div>
            <h4 class="text-xl font-bold text-white mb-3">Gunakan Check-in Email/WA</h4>
            <p class="text-slate-400 text-sm md:text-base leading-relaxed">
                Cukup klik tombol pada Email/WA pengingat otomatis rutin. <strong class="text-white">Tidak wajib login</strong> ke web!
            </p>
        </div>

        <!-- STEP 4 -->
        <div x-show="step === 4" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             class="bg-slate-800/50 border border-slate-700 rounded-2xl p-6 md:p-8 text-center" style="display: none;">
            <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 font-bold flex items-center justify-center text-xl mb-4">
                4
            </div>
            <h4 class="text-xl font-bold text-white mb-3">Eksekusi Sakelar Otomatis</h4>
            <p class="text-slate-400 text-sm md:text-base leading-relaxed">
                Jika Anda tidak merespon check-in dalam batas waktu, aset aman otomatis diteruskan ke Ahli Waris.
            </p>
        </div>

    </div>

    <!-- Navigasi & Indikator Titik (Dots) -->
    <div class="max-w-2xl mx-auto mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
        
        <!-- Tombol Sebelumnya -->
        <div class="w-full md:w-1/3 flex justify-start">
            <button x-show="step > 1" @click="step--" type="button" class="px-5 py-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition font-medium text-sm flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Sebelumnya
            </button>
        </div>

        <!-- Titik Indikator -->
        <div class="w-full md:w-1/3 flex justify-center gap-2">
            <template x-for="i in 4" :key="i">
                <div class="w-2.5 h-2.5 rounded-full transition-colors duration-300"
                     :class="step === i ? 'bg-emerald-500 scale-110' : 'bg-slate-700'"></div>
            </template>
        </div>

        <!-- Tombol Selanjutnya / Selesai -->
        <div class="w-full md:w-1/3 flex justify-end">
            <!-- Next Button -->
            <button x-show="step < 4" @click="step++" type="button" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white transition font-medium text-sm border border-slate-700 flex items-center gap-2">
                Selanjutnya <i class="fa-solid fa-arrow-right"></i>
            </button>
            
            <!-- Submit Form (Tampil di langkah ke-4) -->
            <!-- Pastikan action routenya sesuai dengan pengaturan onboarding Anda (misal: route('onboarding.complete')) -->
            <form x-show="step === 4" action="{{ route('dashboard') }}" method="GET" class="w-full md:w-auto">
                <button type="submit" class="w-full md:w-auto px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20">
                    Saya Mengerti, Buka Dashboard <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>
        
    </div>
</div>

</div>
@endsection