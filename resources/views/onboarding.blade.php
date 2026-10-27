@extends('layouts.app')

@section('content')
<div class="min-h-[85vh] flex flex-col justify-center items-center py-6 px-2 sm:px-4"
     x-data="{ 
         step: 1, 
         guideStep: 1,
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
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-4"
     class="w-full max-w-lg bg-white dark:bg-[#0b1329] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
    
    <!-- Header Kartu -->
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-emerald-500 text-xl shrink-0 mt-0.5">
            <i class="fa-regular fa-file-lines"></i>
        </div>
        <div class="space-y-1">
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white leading-snug">
                Syarat & Ketentuan Layanan dan Kebijakan Privasi
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Aplikasi: Legacy Vault
            </p>
        </div>
    </div>

    <!-- Kotak Scroll Isi Syarat & Ketentuan -->
    <div class="relative">
        <div @scroll="checkScroll($el)" 
             x-init="setTimeout(() => { if ($el.scrollHeight <= $el.clientHeight) scrolledToBottom = true }, 200)"
             class="h-64 overflow-y-auto rounded-2xl bg-slate-50 dark:bg-[#070d1e] border border-slate-200 dark:border-slate-800/90 p-4 sm:p-5 space-y-4 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed scroll-smooth">
            
            <p class="text-xs text-slate-500 dark:text-slate-400 italic">
                Dengan menekan tombol "Saya Setuju", Anda mengikatkan diri secara hukum pada ketentuan berikut sesuai Pasal 1320 & 1338 KUHPerdata serta UU ITE.
            </p>

            <div class="space-y-1.5">
                <h3 class="font-bold text-slate-900 dark:text-white">1. Syarat Pengguna & Status PSE</h3>
                <ul class="list-disc pl-4 space-y-1">
                    <li><strong class="text-slate-800 dark:text-slate-200">Usia Minimum:</strong> Pengguna berusia minimal 21 tahun atau telah/pernah menikah (Pasal 330 KUHPerdata).</li>
                    <li><strong class="text-slate-800 dark:text-slate-200">Legalitas Operasional:</strong> Dikelola sesuai PP No. 71/2019 tentang Penyelenggaraan Sistem dan Transaksi Elektronik (PP PSTE).</li>
                </ul>
            </div>

            <div class="space-y-1.5">
                <h3 class="font-bold text-slate-900 dark:text-white">2. Batasan Peran Aplikasi (Disclaimer)</h3>
                <ul class="list-disc pl-4 space-y-1">
                    <li><strong class="text-slate-800 dark:text-slate-200">Bukan Eksekutor Wasiat/Notaris:</strong> Aplikasi berfungsi sebagai media penyimpanan bukti awal (initial evidence) dokumen elektronik (Pasal 5 UU ITE).</li>
                    <li><strong class="text-slate-800 dark:text-slate-200">Bukan Pengalih Hak Otomatis:</strong> Aplikasi TIDAK menggantikan Wasiat Otentik Notaris, Kompilasi Hukum Islam (KHI), atau Balai Harta Peninggalan.</li>
                    <li><strong class="text-slate-800 dark:text-slate-200">Prosedur Fisik & Finansial:</strong> Balik nama sertifikat tanah tetap wajib lewat BPN/PPAT (UUPA/PP 24/1997). Pencairan tabungan, investasi, atau aset kripto tetap tunduk pada prosedur legalitas lembaga keuangan terkait.</li>
                </ul>
            </div>

            <div class="space-y-1.5">
                <h3 class="font-bold text-slate-900 dark:text-white">3. Mekanisme Dead-Man's Switch & Verifikasi</h3>
                <ul class="list-disc pl-4 space-y-1">
                    <li><strong class="text-slate-800 dark:text-slate-200">Notifikasi Peringatan:</strong> Sistem meminta konfirmasi keberadaan (check-in) berkala dari Anda.</li>
                    <li><strong class="text-slate-800 dark:text-slate-200">Kontak Terpercaya:</strong> Sebelum data dikirim ke Ahli Waris Utama, sistem wajib mengirimkan verifikasi ke 1–2 Kontak Terpercaya untuk mencegah kesalahan pengalihan saat kondisi darurat (koma, HP hilang, amnesia, dll.).</li>
                    <li><strong class="text-slate-800 dark:text-slate-200">Pelepasan Klaim:</strong> Jika seluruh siklus peringatan tidak direspons, Aplikasi dibebaskan dari tuntutan privasi atau sengketa pengalihan data.</li>
                </ul>
            </div>

            <div class="space-y-1.5">
                <h3 class="font-bold text-slate-900 dark:text-white">4. Pelindungan Data Pribadi (UU PDP No. 27/2022)</h3>
                <ul class="list-disc pl-4 space-y-1">
                    <li><strong class="text-slate-800 dark:text-slate-200">Persetujuan Eksplisit:</strong> Anda memberikan izin eksplisit (explicit consent) pemrosesan dan enkripsi data pribadi/sensitif (Pasal 16 & 20).</li>
                    <li><strong class="text-slate-800 dark:text-slate-200">Hak Subjek Data:</strong> Anda berhak mengedit, mengekspor, atau menghapus data Anda kapan saja (Pasal 5–10).</li>
                    <li><strong class="text-slate-800 dark:text-slate-200">Notifikasi Kebocoran & DPO:</strong> Kami wajib menginformasikan gangguan keamanan data maksimal 2 &times; 24 jam (Pasal 46). Layanan DPO dapat dihubungi via email resmi.</li>
                </ul>
            </div>

            <div class="space-y-1.5 pb-2">
                <h3 class="font-bold text-slate-900 dark:text-white">5. Klausul Keadaan Kahar (Force Majeure) & HKI</h3>
                <ul class="list-disc pl-4 space-y-1">
                    <li><strong class="text-slate-800 dark:text-slate-200">Bebas Tanggung Jawab Kahar:</strong> Aplikasi tidak bertanggung jawab atas kegagalan pengiriman data akibat bencana alam, gangguan cloud server (Vercel/AWS), serangan siber (DDoS/peretasan), atau kerusakan perangkat Anda.</li>
                    <li><strong class="text-slate-800 dark:text-slate-200">Perlindungan HKI:</strong> Seluruh UI/UX, merek, dan domain legacy-vault-black.vercel.app dilindungi HKI (Pasal 23 & 24 UU ITE).</li>
                </ul>
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
            Saya telah membaca, memahami, dan menyetujui seluruh Syarat & Ketentuan Layanan, Kebijakan Privasi, Mekanisme Dead-Man's Switch, serta Batasan Tanggung Jawab di atas.
        </span>
    </label>

    <!-- Tombol Setujui & Lanjutkan -->
    <button type="button" 
            @click="if(agreed && scrolledToBottom) { step = 2; window.scrollTo({ top: 0, behavior: 'smooth' }); }"
            :disabled="!agreed || !scrolledToBottom"
            class="w-full py-4 px-6 rounded-2xl bg-emerald-500 hover:bg-emerald-400 disabled:bg-slate-300 dark:disabled:bg-slate-800 text-slate-950 disabled:text-slate-500 font-extrabold text-sm transition shadow-lg shadow-emerald-500/20 disabled:shadow-none flex items-center justify-center gap-2">
        <span>Setuju & Lanjutkan</span>
        <i class="fa-solid fa-check"></i>
    </button>

</div>

    <!-- ========================================== -->
    <!-- TAHAP 2: PANDUAN PENGGUNAAN (STEPPER)      -->
    <!-- ========================================== -->
    <div x-show="step === 2" 
         x-cloak
         x-transition:enter="transition ease-out duration-500 delay-200"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-3xl p-6 md:p-10 shadow-2xl">
        
        <div class="text-center mb-8">
            <h2 class="text-emerald-500 font-bold tracking-widest text-xs uppercase mb-2">Panduan Penggunaan</h2>
            <h3 class="text-2xl md:text-3xl font-extrabold text-white">4 Langkah Mengamankan Warisan Digital</h3>
        </div>

        <!-- Kontainer Slide (guideStep) -->
        <div class="relative min-h-[220px]">
            
            <!-- SLIDE 1 (Daftarkan Ahli Waris) -->
            <div x-show="guideStep === 1" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 class="bg-slate-800/50 border border-slate-700 rounded-2xl p-6 md:p-8 text-center absolute inset-0">
                <div class="w-14 h-14 mx-auto rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 font-bold flex items-center justify-center text-xl mb-4">
                    1
                </div>
                <h4 class="text-xl font-bold text-white mb-3">Daftarkan Ahli Waris</h4>
                <p class="text-slate-400 text-sm md:text-base leading-relaxed">
                    Tentukan siapa yang berhak menerima akses aset jika Anda mendadak berhalangan tetap.
                </p>
            </div>

            <!-- SLIDE 2 (Simpan Aset Terenkripsi) -->
            <div x-show="guideStep === 2" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 class="bg-slate-800/50 border border-slate-700 rounded-2xl p-6 md:p-8 text-center absolute inset-0">
                <div class="w-14 h-14 mx-auto rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 font-bold flex items-center justify-center text-xl mb-4">
                    2
                </div>
                <h4 class="text-xl font-bold text-white mb-3">Simpan Aset Terenkripsi</h4>
                <p class="text-slate-400 text-sm md:text-base leading-relaxed">
                    Masukkan akun bank, kunci crypto, surat tanah, atau password penting ke dalam Brankas Anda.
                </p>
            </div>

            <!-- SLIDE 3 (Check-in Email/WA) -->
            <div x-show="guideStep === 3" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 class="bg-slate-800/50 border border-slate-700 rounded-2xl p-6 md:p-8 text-center absolute inset-0">
                <div class="w-14 h-14 mx-auto rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 font-bold flex items-center justify-center text-xl mb-4">
                    3
                </div>
                <h4 class="text-xl font-bold text-white mb-3">Gunakan Check-in Email/WA</h4>
                <p class="text-slate-400 text-sm md:text-base leading-relaxed">
                    Cukup klik tombol pada Email/WA pengingat otomatis rutin. <strong class="text-white">Tidak wajib login</strong> ke web!
                </p>
            </div>

            <!-- SLIDE 4 (Eksekusi Sakelar Otomatis) -->
            <div x-show="guideStep === 4" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 class="bg-slate-800/50 border border-slate-700 rounded-2xl p-6 md:p-8 text-center absolute inset-0">
                <div class="w-14 h-14 mx-auto rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 font-bold flex items-center justify-center text-xl mb-4">
                    4
                </div>
                <h4 class="text-xl font-bold text-white mb-3">Eksekusi Sakelar Otomatis</h4>
                <p class="text-slate-400 text-sm md:text-base leading-relaxed">
                    Jika Anda tidak merespon check-in dalam batas waktu, aset aman otomatis diteruskan ke Ahli Waris.
                </p>
            </div>

        </div>

        <!-- Navigasi Slider & Tombol Submit -->
        <div class="mt-8">
            
            <!-- Baris Navigasi: Prev, Dots, Next -->
            <div class="flex items-center justify-between gap-4">
                
                <!-- Tombol Sebelumnya -->
                <div class="w-1/3 flex justify-start">
                    <button x-show="guideStep > 1" @click="guideStep--" type="button" class="px-4 py-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition font-medium text-sm flex items-center gap-2">
                        <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Sebelumnya</span>
                    </button>
                </div>

                <!-- Titik Indikator -->
                <div class="w-1/3 flex justify-center gap-2.5">
                    <template x-for="i in 4" :key="i">
                        <div class="w-2.5 h-2.5 rounded-full transition-all duration-300"
                             :class="guideStep === i ? 'bg-emerald-500 w-5' : 'bg-slate-700'"></div>
                    </template>
                </div>

                <!-- Tombol Selanjutnya -->
                <div class="w-1/3 flex justify-end h-[36px]">
                    <button x-show="guideStep < 4" @click="guideStep++" type="button" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white transition font-medium text-sm border border-slate-700 flex items-center gap-2 h-full">
                        Selanjutnya <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
                
            </div>

            <!-- Tombol "Buka Dashboard" (Memanjang di bawah, hanya muncul di Slide 4) -->
            <form x-show="guideStep === 4" 
                  x-transition:enter="transition ease-out duration-300 delay-150"
                  x-transition:enter-start="opacity-0 translate-y-4"
                  x-transition:enter-end="opacity-100 translate-y-0"
                  action="{{ route('dashboard') }}" 
                  method="GET" 
                  class="mt-6 w-full">
                <button type="submit" class="w-full py-4 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white font-extrabold transition-all flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40">
                    Saya Mengerti, Buka Dashboard <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

        </div>
    </div>

</div>
@endsection