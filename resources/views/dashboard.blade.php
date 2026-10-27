@extends('layouts.app')

@section('content')
@php
$approvedExtensionAsset = $assets->firstWhere('extension_status', 'approved');
    $isUnlimited = ($currentPlan->max_assets < 0 || $currentPlan->max_assets >= 9999);
    $quotaLabel = $isUnlimited ? 'Unlimited' : $currentPlan->max_assets;
    $percent = $isUnlimited ? 100 : min(100, round(($totalAssetsCount / max(1, $currentPlan->max_assets)) * 100));

    // Filter aset yang kadaluarsa <= 7 hari lagi (dan bukan 'Selamanya')
    $expiringAssets = $assets->filter(fn($item) => $item->isExpiringSoon());
    $expiringCount = $expiringAssets->count();
@endphp

<div class="space-y-8" 
     x-data="{ 
        filterExpiringOnly: false,
        expiryAlertModalOpen: false,
        editModalOpen: false,
        deleteModalOpen: false,
        showAddAssetModal: false,
        noBeneficiaryModalOpen: false,
        
        selectedAsset: {
            id: null,
            title: '',
            category: '',
            start_date: '',
            expires_at: '',
            is_lifetime: true
        },
        openEditModal(asset) {
            this.selectedAsset = asset;
            this.editModalOpen = true;
        },
        openDeleteModal(asset) {
            this.selectedAsset = asset;
            this.deleteModalOpen = true;
        },

        /* State & Fungsi Dekripsi Teks Rahasia (Tombol Mata) */
        decryptModalOpen: false,
        targetAssetId: null,
        targetAssetTitle: '',
        confirmPassword: '',
        showConfirmPassword: false,
        decryptError: '',
        isDecrypting: false,
        unlockedSecrets: {},

        openDecryptModal(id, title) {
            if (this.unlockedSecrets[id]) {
                delete this.unlockedSecrets[id];
                return;
            }
            this.targetAssetId = id;
            this.targetAssetTitle = title;
            this.confirmPassword = '';
            this.decryptError = '';
            this.showConfirmPassword = false;
            this.decryptModalOpen = true;
        },

        async submitDecrypt() {
            if (!this.confirmPassword) {
                this.decryptError = 'Masukkan kata sandi akun Anda terlebih dahulu.';
                return;
            }
            this.isDecrypting = true;
            this.decryptError = '';
            try {
                const response = await fetch(`/assets/${this.targetAssetId}/decrypt`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ password: this.confirmPassword })
                });
                const data = await response.json();
                if (!response.ok) {
                    this.decryptError = data.message || 'Kata sandi salah!';
                } else {
                    this.unlockedSecrets[this.targetAssetId] = data.secret_content;
                    this.decryptModalOpen = false;
                    this.confirmPassword = '';
                }
            } catch (e) {
                this.decryptError = 'Terjadi kesalahan jaringan saat memverifikasi kata sandi.';
            } finally {
                this.isDecrypting = false;
            }
        },

        /* State & Fungsi untuk Proteksi Lampiran File */
        attachModalOpen: false,
        targetAttachId: null,
        targetAttachTitle: '',
        attachPassword: '',
        showAttachPassword: false,
        attachError: '',
        isVerifyingAttach: false,
        verifiedAttachUrl: '',

        openAttachModal(id, title) {
            this.targetAttachId = id;
            this.targetAttachTitle = title;
            this.attachPassword = '';
            this.attachError = '';
            this.showAttachPassword = false;
            this.attachModalOpen = true;
            this.verifiedAttachUrl = ''; // <-- Reset agar selalu minta password dulu
        },

        async submitAttachAuth() {
            if (!this.attachPassword) {
                this.attachError = 'Masukkan kata sandi akun terlebih dahulu.';
                return;
            }
            this.isVerifyingAttach = true;
            this.attachError = '';
            try {
                const res = await fetch(`/assets/${this.targetAttachId}/attachment`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ password: this.attachPassword })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.attachError = data.message || 'Kata sandi salah!';
                } else {
    // Simpan URL dari server ke state verifiedAttachUrl agar modal beralih ke tampilan preview
                    this.verifiedAttachUrl = data.download_url;
                    this.attachPassword = ''; // Kosongkan input password demi keamanan
                }
            } catch (e) {
                this.attachError = 'Terjadi kesalahan sistem.';
            } finally {
                this.isVerifyingAttach = false;
            }
        },

        /* State & Fungsi untuk Edit Ahli Waris */
        editBeneficiaryModal: false,
        editBenForm: { id: null, name: '', relationship: '', email: '', phone: '' },

        openEditBen(person) {
            this.editBenForm = { ...person };
            this.editBeneficiaryModal = true;
        }
     }"
      x-init="
        if ({{ $expiringCount > 0 ? 'true' : 'false' }} && !sessionStorage.getItem('expiry_alert_shown')) {
            expiryAlertModalOpen = true;
            sessionStorage.setItem('expiry_alert_shown', 'true');
        }">

    <!-- KOMPONEN SAKELAR OTOMATIS (DEAD MAN'S SWITCH) -->
    @php
        $deadline = auth()->user()->next_check_in_deadline ?: now()->addDays((int)(auth()->user()->check_in_interval_days ?: 180));
        $daysRemaining = max(0, (int) now()->diffInDays($deadline, false));
        
        $initialLastCheck = auth()->user()->last_check_in_at 
            ? (auth()->user()->last_check_in_at->isToday() 
                ? 'Hari ini, ' . auth()->user()->last_check_in_at->format('H:i') . ' WIB' 
                : auth()->user()->last_check_in_at->format('d M Y, H:i') . ' WIB')
            : 'Hari ini, ' . now()->format('H:i') . ' WIB';

        $initialDeadline = $daysRemaining . ' Hari Lagi (' . $deadline->translatedFormat('d F Y') . ')';
    @endphp


<div x-data="{ 
            intervalModalOpen: false,
            isCheckingIn: false,
            lastCheckInText: '{{ $initialLastCheck }}',
            deadlineText: '{{ $initialDeadline }}',
            secondsToWarning: null,
            secondsToRelease: null,

            formatTime(sec) {
                if (sec === null) return '';
                const m = String(Math.floor(sec / 60)).padStart(2, '0');
                const s = String(sec % 60).padStart(2, '0');
                return `${m}:${s}`;
            },

            init() {
                this.pollStatus();
                // Hitung mundur tiap 1 detik di layar
                setInterval(() => {
                    if (this.secondsToWarning !== null && this.secondsToWarning > 0) this.secondsToWarning--;
                    if (this.secondsToRelease !== null && this.secondsToRelease > 0) this.secondsToRelease--;
                }, 1000);
                // Sinkronisasi ke server tiap 5 detik untuk cek batas 5 menit & 10 menit
                setInterval(() => this.pollStatus(), 5000);
            },

            async pollStatus() {
                try {
                    const res = await fetch('{{ route('switch.status-check') }}', {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    this.secondsToWarning = data.seconds_to_warning;
                    this.secondsToRelease = data.seconds_to_release;

                    if (data.deadline_text) {
                        this.deadlineText = data.deadline_text;
                    }
                    if (data.reload) {
                        window.location.reload();
                    }
                } catch (e) {}
            },

            async submitCheckIn() {
                this.isCheckingIn = true;
                try {
                    const response = await fetch('{{ route('switch.checkin') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (response.ok && data.success) {
                        this.lastCheckInText = data.last_check_in;
                        this.deadlineText = data.deadline_text;
                        this.secondsToWarning = null;
                        this.secondsToRelease = null;
                    }
                } catch (e) {
                    console.error('Gagal memperbarui status check-in:', e);
                } finally {
                    this.isCheckingIn = false;
                }
            }
         }" 
         class="p-6 rounded-2xl bg-white dark:bg-[#0b1329] border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-6 transition">
        
        <!-- Baris Atas: Status & Tombol Aksi -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div class="space-y-1.5 max-w-2xl">
                <div class="flex items-center gap-2.5">
                    <span class="w-3 h-3 rounded-full animate-pulse"
                          :class="secondsToRelease !== null ? 'bg-amber-500 shadow-[0_0_10px_#f59e0b]' : 'bg-emerald-500 shadow-[0_0_10px_#10b981]'"></span>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white tracking-wide">
                        Status Sakelar Otomatis: 
                        <template x-if="secondsToRelease !== null">
                            <span class="text-amber-500">MENUNGGU KONFIRMASI SEHAT!</span>
                        </template>
                        <template x-if="secondsToRelease === null">
                            <span class="text-emerald-600 dark:text-emerald-400">AKTIF & SEHAT</span>
                        </template>
                    </h2>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Sistem pengingat otomatis dikirim langsung via <strong class="text-slate-900 dark:text-slate-200">Email & WhatsApp</strong> tanpa perlu sering login. Cukup klik tautan konfirmasi di pesan email Anda untuk memperbarui timer!
                </p>

                <!-- Banner Countdown 5 Menit Pertama (Hanya Muncul Sekali Saat Awal Login) -->
                <template x-if="secondsToWarning !== null">
                    <div class="pt-1">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-600 dark:text-amber-400 text-xs font-bold">
                            <i class="fa-solid fa-stopwatch animate-spin"></i>
                            <span>Tes Awal (Sekali Saja): Email konfirmasi sehat akan dikirim dalam <strong class="font-mono text-sm" x-text="formatTime(secondsToWarning)"></strong></span>
                        </div>
                    </div>
                </template>

                <!-- Banner Countdown Menuju Menit ke-10 (Eksekusi Kirim Aset ke Ahli Waris) -->
                <template x-if="secondsToRelease !== null">
                    <div class="pt-1">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-bold">
                            <i class="fa-solid fa-triangle-exclamation animate-bounce"></i>
                            <span>Email Terkirim! Jika tidak respon dalam <strong class="font-mono text-sm" x-text="formatTime(secondsToRelease)"></strong>, seluruh aset otomatis dikirim ke Email & WA Ahli Waris!</span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Container Tombol Sejajar di Android/Mobile -->
            <div class="grid grid-cols-2 gap-2.5 sm:flex sm:items-center w-full lg:w-auto shrink-0">
                <!-- Tombol Konfirmasi Saya Sehat Realtime -->
                <button type="button" 
                        @click="submitCheckIn()"
                        :disabled="isCheckingIn"
                        class="w-full sm:w-auto px-3.5 sm:px-5 py-3 rounded-xl bg-emerald-600 dark:bg-emerald-500 hover:bg-emerald-500 text-white dark:text-slate-950 font-bold text-xs transition shadow-lg shadow-emerald-600/20 flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2.5 disabled:opacity-60 text-center">
                    <i class="fa-solid" :class="isCheckingIn ? 'fa-spinner fa-spin' : 'fa-heart-pulse text-sm'"></i>
                    <span class="leading-tight">
                        <span x-text="isCheckingIn ? 'Memproses...' : 'Konfirmasi Sehat'"></span><br class="hidden sm:inline">
                        <span class="font-normal text-[10px] opacity-90">(Manual)</span>
                    </span>
                </button>

                <!-- Tombol Simulasi Kirim Email Pengingat -->
                <form action="{{ route('switch.simulate') }}" method="POST" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" 
                            class="w-full px-3.5 sm:px-5 py-3 rounded-xl bg-slate-100 dark:bg-[#0e1b38] hover:bg-slate-200 dark:hover:bg-[#13244c] border border-slate-300 dark:border-slate-700 text-emerald-700 dark:text-emerald-400 font-semibold text-xs transition flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2.5 text-center">
                        <i class="fa-regular fa-envelope text-sm"></i>
                        <span class="leading-tight">
                            <span>Simulasi Email</span><br class="hidden sm:inline">
                            <span class="text-[10px] text-slate-500 dark:text-slate-400">Pengingat</span>
                        </span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Baris Bawah: 3 Kotak Indikator Waktu -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-slate-200 dark:border-slate-800/80">
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-[#070d1e] border border-slate-200 dark:border-slate-800/80 space-y-1">
                <div class="text-[11px] text-slate-500 dark:text-slate-400">Pengecekan Terakhir</div>
                <div class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span x-text="lastCheckInText"></span>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 dark:bg-[#070d1e] border border-slate-200 dark:border-slate-800/80 space-y-1">
                <div class="text-[11px] text-slate-500 dark:text-slate-400">Batas Waktu Pengingat Berikutnya</div>
                <div class="text-sm font-bold text-amber-600 dark:text-amber-400">
                    <template x-if="secondsToWarning !== null">
                        <span>Tes 5 Menit (<span class="font-mono" x-text="formatTime(secondsToWarning)"></span>)</span>
                    </template>
                    <template x-if="secondsToWarning === null">
                        <span x-text="deadlineText"></span>
                    </template>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 dark:bg-[#070d1e] border border-slate-200 dark:border-slate-800/80 flex items-center justify-between">
                <div class="space-y-1">
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Interval Rutin Check-in</div>
                    <div class="text-sm font-bold text-slate-900 dark:text-white">
                        @php
                            $days = (int)(auth()->user()->check_in_interval_days ?: 180);
                            $intervalText = match($days) {
                                30 => '1 Bulan',
                                90 => '3 Bulan',
                                180 => '6 Bulan (Starter Plan)',
                                365 => '1 Tahun',
                                default => $days . ' Hari',
                            };
                        @endphp
                        {{ $intervalText }}
                    </div>
                </div>
                <button type="button" @click="intervalModalOpen = true" class="text-xs text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 font-semibold transition">
                    Ubah
                </button>
            </div>
        </div>

        <!-- MODAL UBAH INTERVAL CHECK-IN -->
        @php
            $isPaidUser = auth()->user()->hasActiveSubscription();
            $currentInterval = (int)(auth()->user()->check_in_interval_days ?: 180);
        @endphp

        <div x-show="intervalModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
            <div @click.away="intervalModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-sm w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-emerald-500"></i>
                        <span>Ubah Interval Check-in</span>
                    </h3>
                    <button @click="intervalModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form action="{{ route('switch.interval') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs text-slate-600 dark:text-slate-400 mb-1.5">Pilih Periode Pengingat:</label>
                        <select name="interval_days" class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                            @if($isPaidUser)
                                <option value="30" {{ $currentInterval === 30 ? 'selected' : '' }}>
                                    ⚡ 1 Bulan (30 Hari) — Pro & Unlimited
                                </option>
                            @else
                                <option value="" disabled class="text-slate-400">
                                    🔒 1 Bulan (30 Hari) — Khusus Pro & Unlimited
                                </option>
                            @endif
                            <option value="90" {{ $currentInterval === 90 ? 'selected' : '' }}>3 Bulan (90 Hari)</option>
                            <option value="180" {{ ($currentInterval === 180 || !$currentInterval) ? 'selected' : '' }}>6 Bulan (180 Hari) - Rekomendasi</option>
                            <option value="365" {{ $currentInterval === 365 ? 'selected' : '' }}>1 Tahun (365 Hari)</option>
                        </select>
                    </div>

                    @if(!$isPaidUser)
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-[11px] text-emerald-700 dark:text-emerald-300 flex items-center justify-between">
                            <span>Ingin check-in rutin per 1 bulan?</span>
                            <a href="{{ route('pricing') }}" class="font-bold underline ml-1">Upgrade</a>
                        </div>
                    @endif

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="intervalModalOpen = false" class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs">Batal</button>
                        <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- 4 KARTU STATISTIK RINGKASAN -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-award text-emerald-500"></i> Paket Saat Ini
                </div>
                <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $currentPlan->name }}</div>
                <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">
                    @if(!empty($subscription?->ends_at))
                        <i class="fa-regular fa-calendar-check mr-1"></i> Aktif s/d {{ \Carbon\Carbon::parse($subscription->ends_at)->format('d M Y') }}
                    @elseif(!empty($subscription?->expires_at))
                        <i class="fa-regular fa-calendar-check mr-1"></i> Aktif s/d {{ \Carbon\Carbon::parse($subscription->expires_at)->format('d M Y') }}
                    @else
                        <i class="fa-solid fa-infinity mr-1"></i> Paket Dasar Selamanya
                    @endif
                </div>
            </div>
            <a href="{{ route('pricing') }}" class="px-3.5 py-2 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs font-semibold hover:bg-emerald-500/25 transition flex items-center gap-1.5">
                <i class="fa-solid fa-rocket"></i>
                <span>Upgrade</span>
            </a>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                <i class="fa-solid fa-database text-emerald-500"></i> Kapasitas Brankas Aset
            </div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                {{ $totalAssetsCount }} / {{ $quotaLabel }}
                <span class="text-xs font-normal text-slate-500">Aset</span>
            </div>
            <div class="w-full bg-slate-200 dark:bg-slate-800 h-2 rounded-full mt-3 overflow-hidden">
                <div class="bg-emerald-500 h-full rounded-full transition-all" style="width: {{ $percent }}%"></div>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                <i class="fa-solid fa-users text-emerald-500"></i> Ahli Waris Terdaftar
            </div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $beneficiaries->count() }} <span class="text-xs font-normal text-slate-500">Orang</span></div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1.5">
                <i class="fa-solid fa-shield-halved text-emerald-500"></i> Terhubung dengan distribusi wasiat
            </div>
        </div>

        <div @click="if({{ $expiringCount }} > 0) { activeTab = 'assets'; filterExpiringOnly = true; document.getElementById('assets-section')?.scrollIntoView({ behavior: 'smooth' }); }"
             class="p-5 rounded-3xl bg-white dark:bg-[#0b1329] border {{ $expiringCount > 0 ? 'border-amber-500/50 cursor-pointer hover:border-amber-400' : 'border-slate-200 dark:border-slate-800' }} flex flex-col justify-between transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider {{ $expiringCount > 0 ? 'text-amber-500' : 'text-slate-400' }} flex items-center gap-1.5">
                    <i class="fa-solid fa-hourglass-half"></i> Masa Berlaku Aset
                </span>
                @if($expiringCount > 0)
                    <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 text-[10px] font-extrabold animate-pulse">
                        Cek!
                    </span>
                @endif
            </div>
            <div class="my-2">
                <span class="text-3xl font-extrabold {{ $expiringCount > 0 ? 'text-amber-400' : 'text-slate-900 dark:text-white' }}">
                    {{ $expiringCount }}
                </span>
                <span class="text-xs text-slate-400 ml-1">Aset</span>
            </div>
            <div class="text-xs {{ $expiringCount > 0 ? 'text-amber-400 font-medium' : 'text-emerald-500' }} flex items-center gap-1.5">
                @if($expiringCount > 0)
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Kadaluarsa &le; 7 hari (Klik lihat)</span>
                @else
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Semua masa berlaku aman</span>
                @endif
            </div>
        </div>
    </div>

    <!-- TAB 1: BRANKAS ASET DIGITAL -->
    <div x-show="activeTab === 'assets'" x-transition.opacity class="space-y-6" id="assets-section">
        <!-- Header Brankas -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                    <i class="fa-solid fa-shield-halved text-emerald-500"></i>
                    <span>Brankas Aset Digital</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Disimpan dengan Enkripsi AES-256 & Proteksi Kata Sandi Akun</p>
            </div>

            <div class="flex items-center gap-3">
                <span class="px-3.5 py-2 rounded-full text-xs font-semibold bg-slate-200/70 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                    Terpakai {{ $totalAssetsCount }} / {{ $quotaLabel }} Aset
                </span>
               <button type="button"
                    @click="if ({{ $beneficiaries->isEmpty() ? 'true' : 'false' }}) { 
                        noBeneficiaryModalOpen = true; 
                    } else { 
                        showAddAssetModal = true; 
                    }"
                    class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition shadow-lg shadow-emerald-500/20 flex items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Tambah Aset Baru</span>
            </button>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="space-y-4 border-b border-slate-200 dark:border-slate-800 pb-4">
            @php
                $activeCat = request('category', 'all');
                $categories = [
                    'all' => 'Semua Aset',
                    'Finansial' => 'Rekening Bank',
                    'Kredensial Akun' => 'Crypto / Seed Phrase',
                    'Dokumen Rahasia' => 'Dokumen Legal',
                    'Pesan Wasiat' => 'Pesan Wasiat',
                ];
            @endphp

            <!-- Banner Filter Aset Kadaluarsa -->
            <div x-show="filterExpiringOnly" x-cloak class="mb-4 p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between">
                <div class="text-xs text-amber-400 font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-filter"></i>
                    <span>Menampilkan khusus aset yang akan/sudah kadaluarsa (&le; 7 hari)</span>
                </div>
                <button @click="filterExpiringOnly = false" type="button" class="px-3 py-1 rounded-xl bg-slate-800 text-xs text-white hover:bg-slate-700">
                    Tampilkan Semua Aset
                </button>
            </div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- Pill Kategori -->
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($categories as $key => $label)
                        <a href="{{ route('dashboard', array_merge(request()->query(), ['category' => $key])) }}"
                           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition border {{ $activeCat === $key ? 'bg-slate-900 text-white dark:bg-slate-800 dark:text-white border-slate-700 shadow-sm' : 'bg-transparent text-slate-600 dark:text-slate-400 border-transparent hover:bg-slate-200/60 dark:hover:bg-slate-900' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                <!-- Form Search & Dropdown Ahli Waris -->
                <form action="{{ route('dashboard') }}" method="GET" class="flex flex-wrap sm:flex-nowrap items-center gap-2">
                    @if(request('category'))
                        <input type="hidden" name="category" value="{{ request('category') }}">
                    @endif

                    <div class="relative w-full sm:w-48">
                        <select name="beneficiary_id" onchange="this.form.submit()"
                                class="w-full rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 pl-3 pr-8 py-2 text-xs text-slate-700 dark:text-slate-300 focus:border-emerald-500 focus:outline-none">
                            <option value="">Semua Ahli Waris</option>
                            @foreach($beneficiaries as $person)
                                <option value="{{ $person->id }}" {{ request('beneficiary_id') == $person->id ? 'selected' : '' }}>
                                    {{ $person->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="relative flex-1 sm:w-56">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama aset..."
                               class="w-full rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 pl-3.5 pr-9 py-2 text-xs text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                        <button type="submit" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-emerald-500">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </button>
                    </div>

                    @if(request()->filled('search') || request()->filled('beneficiary_id') || (request()->filled('category') && request('category') !== 'all'))
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-xl bg-rose-500/10 text-rose-500 hover:bg-rose-500/20 text-xs font-semibold" title="Reset Filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Daftar Kartu Aset -->
        <div class="space-y-4">
            @forelse($assets as $asset)
            @php
    $isExpired = $asset->isExpired();
    $isLocked = $isExpired && ($asset->extension_status !== 'approved');
@endphp
                @php
                    $iconClass = match($asset->category) {
                        'Finansial' => 'fa-building-columns',
                        'Kredensial Akun' => 'fa-key',
                        'Dokumen Rahasia' => 'fa-file-contract',
                        default => 'fa-scroll',
                    };
                    $badgeLabel = match($asset->category) {
                        'Finansial' => 'BANK / FINANSIAL',
                        'Kredensial Akun' => 'CRYPTO / KREDENSIAL',
                        'Dokumen Rahasia' => 'DOKUMEN LEGAL',
                        default => 'PESAN WASIAT',
                    };
                @endphp

                <div x-show="!filterExpiringOnly || {{ $asset->isExpiringSoon() ? 'true' : 'false' }}"
                     class="bg-white dark:bg-slate-900/90 border {{ $asset->isExpiringSoon() ? 'border-amber-500/50' : 'border-slate-200 dark:border-slate-800' }} rounded-2xl p-5 space-y-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
                    
                    <!-- Baris Atas: Ikon + Judul + Tombol Edit & Hapus -->
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-500 shrink-0">
                                <i class="fa-solid {{ $iconClass }} text-lg"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $asset->title }}</h3>
                                    @if($asset->is_lifetime)
                                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-bold">
                                            &infin; Selamanya
                                        </span>
                                    @elseif($asset->isExpiringSoon())
                                        <span class="px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 text-[10px] font-bold">
                                            <i class="fa-solid fa-clock mr-1"></i>
                                            {{ $asset->daysRemaining() < 0 ? 'Sudah Kadaluarsa!' : 'Kadaluarsa ' . $asset->daysRemaining() . ' hari lagi (' . $asset->expires_at->format('d/m/Y') . ')' }}
                                        </span>
                                    @elseif($asset->expires_at)
                                        <span class="px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-400 text-[10px]">
                                            Berlaku s/d {{ $asset->expires_at->format('d/m/Y') }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] font-bold tracking-wider uppercase text-slate-400 dark:text-slate-500 block mt-0.5">
                                    {{ $badgeLabel }}
                                </span>
                            </div>
                        </div>

                       <!-- Tombol Edit & Hapus -->
<div class="flex items-center gap-1.5">
    <!-- Tombol Edit HANYA tampil jika aset TIDAK terkunci kadaluarsa -->
    @if(!$isLocked)
        <button type="button"
                @click="openEditModal({
                    id: {{ $asset->id }},
                    title: '{{ addslashes($asset->title) }}',
                    category: '{{ $asset->category }}',
                    start_date: '{{ $asset->start_date ? $asset->start_date->format('Y-m-d') : '' }}',
                    expires_at: '{{ $asset->expires_at ? $asset->expires_at->format('Y-m-d') : '' }}',
                    is_lifetime: {{ $asset->is_lifetime ? 'true' : 'false' }}
                })"
                class="p-2 rounded-lg text-slate-400 hover:text-amber-500 hover:bg-amber-500/10 transition" title="Edit Aset">
            <i class="fa-solid fa-pen-to-square text-sm"></i>
        </button>
    @endif

    <!-- Tombol Hapus tetap ada agar user tetap bisa membuang aset kadaluarsa -->
    <button type="button"
            @click="openDeleteModal({ id: {{ $asset->id }}, title: '{{ addslashes($asset->title) }}' })"
            class="p-2 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 transition" title="Hapus Aset">
        <i class="fa-regular fa-trash-can text-sm"></i>
    </button>
</div>
                    </div>

                    @if($isLocked)
                        <!-- 1. TAMPILAN JIKA ASET TERKUNCI (KADALUARSA) -->
                        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="space-y-1">
                                <div class="text-xs font-bold text-rose-500 flex items-center gap-1.5">
                                    <i class="fa-solid fa-lock"></i>
                                    <span>ASET TERKUNCI KARENA KADALUARSA</span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                    Aset tidak dapat didekripsi. Ajukan permohonan ke admin agar kunci dibuka dan Anda dapat memperbarui masa berlakunya.
                                </p>
                            </div>

                            @if($asset->extension_status === 'pending')
                                <span class="px-3 py-1.5 rounded-xl bg-amber-500/15 text-amber-500 font-bold text-xs shrink-0 flex items-center gap-1.5">
                                    <i class="fa-solid fa-clock-rotate-left"></i> Menunggu Verifikasi Admin
                                </span>
                            @else
                                <form action="{{ route('assets.requestExtension', $asset) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-xs transition shrink-0 flex items-center gap-1.5 shadow-lg shadow-amber-500/20">
                                        <i class="fa-solid fa-key"></i> Ajukan Perpanjangan ke Admin
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        <!-- 2. TAMPILAN NORMAL (DAPAT DIDEKRIPSI) -->
                        <div class="p-4 rounded-xl bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/90 flex items-center justify-between gap-4">
                            <div class="font-mono text-xs overflow-x-auto">
                                <template x-if="!unlockedSecrets[{{ $asset->id }}]">
                                    <div class="space-y-1 select-none text-slate-500 dark:text-slate-400 tracking-widest">
                                        <div>••••••••••••••••••••</div>
                                        <div>•••••••••••••••••••••••••</div>
                                    </div>
                                </template>
                                <template x-if="unlockedSecrets[{{ $asset->id }}]">
                                    <div class="text-emerald-700 dark:text-emerald-300 whitespace-pre-line leading-relaxed" x-text="unlockedSecrets[{{ $asset->id }}]"></div>
                                </template>
                            </div>

                            <!-- Tombol Mata Pembuka Dekripsi -->
                            <button type="button" 
                                    @click="openDecryptModal({{ $asset->id }}, '{{ addslashes($asset->title) }}')"
                                    class="w-9 h-9 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:border-emerald-500 hover:text-emerald-500 transition shrink-0"
                                    :title="unlockedSecrets[{{ $asset->id }}] ? 'Sembunyikan Kembali' : 'Verifikasi Password untuk Melihat Isi'">
                                <i class="fa-solid" :class="unlockedSecrets[{{ $asset->id }}] ? 'fa-eye-slash text-emerald-500' : 'fa-eye'"></i>
                            </button>
                        </div>
                    @endif
                    <!-- ======================================================== -->

                    <!-- Baris Bawah: Info Ahli Waris & Lampiran -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1 text-xs">
                        <div class="flex flex-wrap items-center gap-1.5 text-slate-500 dark:text-slate-400">
                            <i class="fa-solid fa-user-check text-emerald-500 mr-1"></i>
                            <span>Ahli Waris:</span>
                            @forelse($asset->beneficiaries as $ben)
                                <strong class="text-slate-800 dark:text-slate-200">{{ $ben->name }}{{ !$loop->last ? ',' : '' }}</strong>
                            @empty
                                <span class="italic text-slate-400">Belum ditugaskan</span>
                            @endforelse
                        </div>

                        @if($asset->attachment_path)
                            @if($isLocked)
                                <!-- Lampiran ikut terkunci jika aset kadaluarsa -->
                                <span class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-400 flex items-center gap-1.5 font-medium cursor-not-allowed" title="Aset kadaluarsa, lampiran terkunci">
                                    <i class="fa-solid fa-lock text-xs text-rose-500"></i>
                                    <span>{{ basename($asset->attachment_path) }}</span>
                                </span>
                            @else
                                <button type="button" 
                                        @click="openAttachModal({{ $asset->id }}, '{{ addslashes($asset->title) }}')"
                                        class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-emerald-600 dark:text-emerald-400 hover:border-emerald-500/50 transition flex items-center gap-1.5 font-medium">
                                    <i class="fa-solid fa-lock text-xs"></i>
                                    <span>{{ basename($asset->attachment_path) }}</span>
                                </button>
                            @endif
                        @endif
                    </div>
                    </div>
                           @empty
                <div class="p-12 rounded-2xl bg-white dark:bg-slate-900 border border-dashed border-slate-300 dark:border-slate-800 text-center text-slate-500 text-sm">
                    <i class="fa-solid fa-box-open text-3xl text-slate-400 mb-2 block"></i>
                    Tidak ada aset ditemukan. Klik tombol <strong>"+ Tambah Aset Baru"</strong> di kanan atas untuk menyimpan aset rahasia.
                </div>
            @endforelse
        </div>

        <!-- Ringkasan Log Aktivitas Terakhir -->
        <!-- <div class="mt-8 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-shield-virus text-emerald-500"></i>
                    <span>Log Aktivitas Brankas Terbaru</span>
                </h3>
                <button @click="activeTab = 'logs'" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline font-medium">
                    Lihat Semua Log &rarr;
                </button>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-800/60 text-xs">
                @forelse($activityLogs->take(5) as $log)
                    <div class="py-2.5 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-2.5">
                            <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-mono text-[10px] font-bold">
                                {{ $log->action }}
                            </span>
                            <span class="text-slate-700 dark:text-slate-300">{{ $log->description }}</span>
                        </div>
                        <span class="text-slate-400 shrink-0">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-slate-500 py-2">Belum ada aktivitas tercatat.</p>
                @endforelse
            </div>
        </div> -->
    </div>

    <!-- TAB 2: DAFTAR AHLI WARIS -->
    <div x-show="activeTab === 'beneficiaries'" x-cloak x-transition.opacity class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-5">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-user-plus text-emerald-500"></i>
                        <span>Tambah Ahli Waris Resmi</span>
                    </h2>
                    <form action="{{ route('beneficiaries.store') }}" method="POST" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap</label>
                            <input type="text" name="name" required class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Hubungan Keluarga</label>
                            <input type="text" name="relationship" required placeholder="Contoh: Istri / Anak Kandung / Adik" class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Email Aktif</label>
                            <input type="email" name="email" required class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nomor WhatsApp / Telepon</label>
                            <input type="text" name="phone" required class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                        </div>
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Simpan Ahli Waris</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-7 space-y-3">
                @forelse($beneficiaries as $person)
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-start justify-between gap-4 shadow-sm">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <div class="space-y-1">
                                <div class="font-bold text-slate-900 dark:text-white text-sm">
                                    {{ $person->name }} 
                                    <span class="text-xs font-normal text-emerald-600 dark:text-emerald-400 ml-1">({{ $person->relationship }})</span>
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                                    <i class="fa-regular fa-envelope text-[11px] w-3.5"></i>
                                    <span>{{ $person->email }}</span>
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                                    <i class="fa-solid fa-phone text-[11px] w-3.5"></i>
                                    <span>{{ $person->phone }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" 
                                    @click="openEditBen({{ json_encode($person) }})"
                                    class="p-2 rounded-lg text-slate-400 hover:text-amber-500 hover:bg-amber-500/10 transition" title="Edit Ahli Waris">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                            </button>
                            <form action="{{ route('beneficiaries.destroy', $person) }}" method="POST" onsubmit="return confirm('Hapus ahli waris ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 transition" title="Hapus Ahli Waris">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-12 rounded-2xl bg-white dark:bg-slate-900 border border-dashed border-slate-300 dark:border-slate-800 text-center text-slate-500 text-sm">
                        <i class="fa-solid fa-users-slash text-3xl text-slate-400 mb-2 block"></i>
                        Belum ada data ahli waris yang didaftarkan.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

  <div x-show="activeTab === 'logs'" x-cloak x-transition.opacity class="space-y-6">
    <h2 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="fa-solid fa-clock-rotate-left text-emerald-500"></i>
        <span>Riwayat Aktivitas & Audit Keamanan Akun</span>
    </h2>
    <div class="overflow-x-auto bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
        <table class="w-full text-left text-xs">
            <thead class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="py-2.5">Waktu</th>
                    <th class="py-2.5">Aksi</th>
                    <th class="py-2.5">Keterangan Aktivitas</th>
                    <th class="py-2.5">Alamat IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                @forelse($activityLogs as $log)
                    <tr>
                        <td class="py-3 text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                        <td class="py-3">
                            <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-mono text-[10px] font-bold">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="py-3 text-slate-800 dark:text-slate-200">{{ $log->description }}</td>
                        <td class="py-3 font-mono text-slate-400">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-4 text-center text-slate-500">Belum ada catatan aktivitas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Link Navigasi Pagination 10 Item -->
        @if($activityLogs->hasPages())
            <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <div>
                    Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $activityLogs->firstItem() }}</span> sampai <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $activityLogs->lastItem() }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $activityLogs->total() }}</span> log
                </div>
                <div>
                    {{ $activityLogs->appends(['tab' => 'logs'])->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

    <!-- MODAL 1: VERIFIKASI DEKRIPSI TEKS RAHASIA                  -->
    <div x-show="decryptModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
        <div @click.away="decryptModalOpen = false; decryptError = ''; confirmPassword = '';" 
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-user-lock text-emerald-500"></i>
                    <span>Verifikasi Keamanan Brankas</span>
                </h3>
                <button @click="decryptModalOpen = false; decryptError = ''; confirmPassword = '';" 
                        type="button"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-400 mt-3 leading-relaxed">
                Masukkan kata sandi akun Anda untuk mendekripsi dan melihat isi rahasia dari aset <strong class="text-slate-900 dark:text-white" x-text="targetAssetTitle"></strong>.
            </p>

            <form @submit.prevent="submitDecrypt()" class="mt-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        <i class="fa-solid fa-key mr-1 text-emerald-500"></i> Kata Sandi Akun
                    </label>
                    <div class="relative">
                        <input :type="showConfirmPassword ? 'text' : 'password'" 
                               x-model="confirmPassword"
                               placeholder="Masukkan password akun..."
                               required
                               class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 pl-3.5 pr-10 py-2.5 text-sm text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                        <button type="button" 
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-emerald-500">
                            <i class="fa-solid" :class="showConfirmPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    <template x-if="decryptError">
                        <p class="text-xs text-rose-500 mt-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span x-text="decryptError"></span>
                        </p>
                    </template>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" 
                            @click="decryptModalOpen = false; decryptError = ''; confirmPassword = '';" 
                            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-medium">
                        Batal
                    </button>
                    <button type="submit" 
                            :disabled="isDecrypting" 
                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold flex items-center gap-1.5 disabled:opacity-50">
                        <i class="fa-solid fa-unlock-keyhole"></i>
                        <span x-text="isDecrypting ? 'Memverifikasi...' : 'Buka Dekripsi'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: SIMPAN ASET BARU KE BRANKAS                       -->
   <template x-teleport="body">
    <div x-show="showAddAssetModal" 
         x-cloak 
         x-data="{ isLifetime: true, startDate: '' }"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm overflow-y-auto">
        <div @click.away="showAddAssetModal = false" 
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl my-8">

            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-file-shield text-emerald-500"></i>
                    <span>Simpan Aset Baru ke Brankas</span>
                </h2>
                <button @click="showAddAssetModal = false" 
                        type="button"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('assets.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                        <i class="fa-solid fa-heading mr-1 text-emerald-500"></i> Nama / Judul Aset
                    </label>
                    <input type="text" name="title" required placeholder="Contoh: Deposito & E-Banking BCA Utama"
                           class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                        <i class="fa-solid fa-tags mr-1 text-emerald-500"></i> Kategori Aset
                    </label>
                    <select name="category" 
                            class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                        <option value="Finansial">Rekening Bank / Finansial</option>
                        <option value="Kredensial Akun">Crypto / Seed Phrase</option>
                        <option value="Dokumen Rahasia">Dokumen Legal / Sertifikat</option>
                        <option value="Pesan Wasiat">Pesan Wasiat Pribadi</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                        <i class="fa-solid fa-key mr-1 text-emerald-500"></i> Isi Kredensial / Instruksi Rahasia (Akan Dienkripsi)
                    </label>
                    <textarea name="secret_content" rows="4" required placeholder="Tuliskan nomor rekening, PIN, seed phrase, atau instruksi pencairan..."
                              class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2 text-slate-900 dark:text-white font-mono text-xs focus:border-emerald-500 focus:outline-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                        <i class="fa-solid fa-paperclip mr-1 text-emerald-500"></i> Lampiran Dokumen (Opsional, PDF/JPG/PNG maks 5MB)
                    </label>
                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-slate-200 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-300">
                </div>

                <!-- Masa Berlaku Dokumen / Aset -->
                <div class="space-y-3 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
                            <i class="fa-regular fa-calendar-check mr-1 text-emerald-500"></i> Masa Berlaku Aset
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="is_lifetime" value="1" x-model="isLifetime" class="rounded border-slate-700 text-emerald-500 focus:ring-emerald-500">
                            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Berlaku Selamanya (&infin;)</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1">Tanggal Mulai / Terbit (Opsional)</label>
                            <input type="date" name="start_date" x-model="startDate"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div x-show="!isLifetime" x-transition>
                            <label class="block text-[11px] text-amber-500 font-semibold mb-1">Tanggal Kadaluarsa *</label>
                            <input type="date" name="expires_at" :required="!isLifetime" :min="startDate || null"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-amber-500/50 text-xs text-slate-900 dark:text-white focus:border-amber-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Ahli Waris Penerima -->
                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        <i class="fa-solid fa-user-check mr-1 text-emerald-500"></i> Tugaskan ke Ahli Waris Penerima
                    </label>
                    @if($beneficiaries->isEmpty())
                        <p class="text-xs text-amber-600 dark:text-amber-400">
                            <i class="fa-solid fa-circle-info mr-1"></i> Belum ada ahli waris. Tambahkan di menu "Daftar Ahli Waris".
                        </p>
                    @else
                        <div class="space-y-1.5 max-h-32 overflow-y-auto p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800">
                            @foreach($beneficiaries as $person)
                                <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer hover:text-emerald-500">
                                    <input type="checkbox" name="beneficiary_ids[]" value="{{ $person->id }}" 
                                           class="rounded border-slate-400 dark:border-slate-700 text-emerald-500 focus:ring-emerald-500">
                                    <span>{{ $person->name }} <span class="text-slate-400">({{ $person->relationship }})</span></span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="showAddAssetModal = false" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-medium">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs transition flex items-center gap-2">
                        <i class="fa-solid fa-lock"></i>
                        <span>Enkripsi & Simpan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
    <!-- MODAL 3: BUKA LAMPIRAN TERPROTEKSI PASSWORD               -->
<div x-show="attachModalOpen" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 dark:bg-black/75 p-4 backdrop-blur-sm"
     @keydown.escape.window="attachModalOpen = false; verifiedAttachUrl = ''">
    
    <div @click.away="attachModalOpen = false; verifiedAttachUrl = ''" 
         :class="verifiedAttachUrl ? 'max-w-lg' : 'max-w-md'"
         class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full shadow-2xl overflow-hidden transition-all">
        
        <!-- Header Modal -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid text-emerald-500" :class="verifiedAttachUrl ? 'fa-eye' : 'fa-shield-halved'"></i>
                <span x-text="verifiedAttachUrl ? 'Preview Lampiran Aset' : 'Buka Berkas Lampiran Rahasia'"></span>
            </h3>
            <button type="button" @click="attachModalOpen = false; verifiedAttachUrl = ''" class="text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- TAHAP 1: Form Verifikasi Password (Muncul sebelum terverifikasi) -->
        <div x-show="!verifiedAttachUrl" class="p-6">
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Lampiran dari aset <strong class="text-slate-900 dark:text-white" x-text="targetAttachTitle"></strong> diamankan. Masukkan kata sandi akun Anda untuk membuka preview berkas.
            </p>

            <form @submit.prevent="submitAttachAuth()" class="mt-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1.5">Kata Sandi Akun</label>
                    <div class="relative">
                        <input :type="showAttachPassword ? 'text' : 'password'" 
                               x-model="attachPassword"
                               placeholder="Masukkan password..."
                               required
                               class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 pl-3.5 pr-10 py-2.5 text-sm text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                        <button type="button" @click="showAttachPassword = !showAttachPassword"
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-emerald-500">
                            <i class="fa-solid" :class="showAttachPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    <template x-if="attachError">
                        <p class="text-xs text-rose-500 mt-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span x-text="attachError"></span>
                        </p>
                    </template>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="attachModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-medium hover:bg-slate-200 dark:hover:bg-slate-700 transition">Batal</button>
                    <button type="submit" :disabled="isVerifyingAttach" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer">
                        <i class="fa-solid fa-unlock-keyhole"></i>
                        <span x-text="isVerifyingAttach ? 'Memverifikasi...' : 'Buka Preview'"></span>
                    </button>
                </div>
            </form>
        </div>

        <!-- TAHAP 2: Tampilan Preview Lampiran (Muncul setelah password benar) -->
        <div x-show="verifiedAttachUrl" x-cloak>
            <!-- Sub-info Nama Aset -->
            <div class="px-6 py-2.5 bg-emerald-500/10 border-b border-emerald-500/20 flex items-center justify-between">
                <span class="text-xs text-slate-600 dark:text-slate-300">
                    Aset: <strong class="text-emerald-600 dark:text-emerald-400" x-text="targetAttachTitle"></strong>
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-semibold">
                    Terverifikasi
                </span>
            </div>

            <!-- Area Preview Gambar / Dokumen -->
            <div class="p-4 flex items-center justify-center bg-slate-100/80 dark:bg-slate-950/60 max-h-[60vh] overflow-auto">
                <template x-if="verifiedAttachUrl && verifiedAttachUrl.toLowerCase().includes('.pdf')">
                    <iframe :src="verifiedAttachUrl" class="w-full h-[55vh] rounded-lg border border-slate-200 dark:border-slate-800"></iframe>
                </template>

                <template x-if="verifiedAttachUrl && !verifiedAttachUrl.toLowerCase().includes('.pdf')">
                    <img :src="verifiedAttachUrl" 
                         alt="Preview Lampiran" 
                         class="max-h-[55vh] w-auto object-contain rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm">
                </template>
            </div>

            <!-- Footer: Hanya Tombol Lihat Full & Tutup (Tanpa Print) -->
            <div class="flex items-center justify-between px-6 py-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50">
                <a :href="verifiedAttachUrl" 
                   target="_blank" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-md shadow-emerald-600/20 transition-all">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Lihat Full</span>
                </a>

                <button type="button" 
                        @click="attachModalOpen = false; verifiedAttachUrl = ''"
                        class="px-4 py-2 rounded-xl bg-slate-200/80 hover:bg-slate-300/80 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition-all cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>

    </div>
</div>
    <!-- MODAL 4: EDIT DATA AHLI WARIS                             -->
    <div x-show="editBeneficiaryModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
        <div @click.away="editBeneficiaryModal = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-user-pen text-amber-500"></i>
                    <span>Perbarui Data Ahli Waris</span>
                </h3>
                <button @click="editBeneficiaryModal = false" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form :action="`/beneficiaries/${editBenForm.id}`" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" x-model="editBenForm.name" required class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Hubungan Keluarga</label>
                    <input type="text" name="relationship" x-model="editBenForm.relationship" required placeholder="Contoh: Istri / Anak Kandung / Adik" class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Email Aktif</label>
                    <input type="email" name="email" x-model="editBenForm.email" required class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nomor WhatsApp / Telepon</label>
                    <input type="text" name="phone" x-model="editBenForm.phone" required class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="editBeneficiaryModal = false" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-medium">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: PERINGATAN ASET MAU KADALUARSA (TELEPORT)         -->
    @if($expiringCount > 0)
    <template x-teleport="body">
        <div x-show="expiryAlertModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
            <div @click.away="expiryAlertModalOpen = false" class="bg-white dark:bg-slate-900 border border-amber-500/40 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-5">
                <div class="flex items-start gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 text-xl shrink-0">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Peringatan Masa Berlaku Aset!</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Terdapat <strong class="text-amber-400">{{ $expiringCount }} aset brankas</strong> yang masa berlakunya kurang dari 7 hari atau telah habis.
                        </p>
                    </div>
                </div>

                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    @foreach($expiringAssets as $expAsset)
                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-amber-500/20 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">{{ $expAsset->title }}</div>
                                <div class="text-[11px] text-slate-400">Berakhir: {{ $expAsset->expires_at->format('d M Y') }}</div>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg bg-amber-500/15 text-amber-400 text-[10px] font-bold">
                                {{ $expAsset->daysRemaining() < 0 ? 'Lewat ' . abs($expAsset->daysRemaining()) . ' hr' : $expAsset->daysRemaining() . ' hari lagi' }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="expiryAlertModalOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold">
                        Nanti Saja
                    </button>
                    <button type="button" 
                            @click="expiryAlertModalOpen = false; activeTab = 'assets'; filterExpiringOnly = true; setTimeout(() => document.getElementById('assets-section')?.scrollIntoView({ behavior: 'smooth' }), 150)" 
                            class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-extrabold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-eye"></i> Lihat Aset Kadaluarsa
                    </button>
                </div>
            </div>
        </div>
    </template>
    @endif

    <!-- MODAL 6: EDIT ASET (WAJIB INPUT PASSWORD AKUN)             -->
    <template x-teleport="body">
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
            <div @click.away="editModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-emerald-500"></i> Edit Aset Brankas
                </h3>

                <form :action="'/assets/' + selectedAsset.id" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Nama / Judul Aset</label>
                        <input type="text" name="title" x-model="selectedAsset.title" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-sm text-slate-900 dark:text-white">
                    </div>
                    <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                        <i class="fa-solid fa-key mr-1 text-emerald-500"></i> Isi Kredensial / Instruksi Rahasia
                    </label>
                    <textarea name="secret_content" 
                              x-model="selectedAsset.secret_content" 
                              rows="4" 
                              placeholder="Kosongkan jika tidak ingin mengubah isi kredensial rahasia, atau ketik isi baru di sini..."
                              class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2 text-slate-900 dark:text-white font-mono text-xs focus:border-emerald-500 focus:outline-none"></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">
                        * Biarkan kosong jika tidak ingin mengubah data rahasia sebelumnya.
                    </p>

                <div class="space-y-3 pt-2 border-t border-slate-200 dark:border-slate-800">
    <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-400">Masa Berlaku</span>
        <label class="inline-flex items-center gap-2 cursor-pointer">
            <!-- Hidden input fallback saat uncheck -->
            <input type="hidden" name="is_lifetime" value="0">
            <input type="checkbox" 
                   name="is_lifetime" 
                   value="1" 
                   x-model="selectedAsset.is_lifetime" 
                   class="rounded border-slate-700 text-emerald-500">
            <span class="text-xs font-bold text-emerald-500">Berlaku Selamanya (&infin;)</span>
        </label>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-[11px] text-slate-400 mb-1">Tanggal Mulai</label>
            <input type="date" 
                   name="start_date" 
                   x-model="selectedAsset.start_date" 
                   class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white">
        </div>
        <div x-show="!selectedAsset.is_lifetime">
            <label class="block text-[11px] text-amber-400 font-semibold mb-1">Tanggal Kadaluarsa *</label>
            <input type="date" 
                   name="expires_at" 
                   x-model="selectedAsset.expires_at" 
                   :required="!selectedAsset.is_lifetime" 
                   class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-amber-500/40 text-xs text-slate-900 dark:text-white">
        </div>
    </div>
</div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Perbarui Lampiran Dokumen/Foto (Opsional)</label>
                        <input type="file" name="attachment" class="w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-emerald-500/15 file:text-emerald-400 file:font-bold">
                    </div>
                    <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Kategori Aset</label>
                    <select name="category" 
                            x-model="selectedAsset.category" 
                            required 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                        <option value="Finansial">Rekening Bank / Finansial</option>
                        <option value="Kredensial Akun">Crypto / Seed Phrase</option>
                        <option value="Dokumen Rahasia">Dokumen Legal / Sertifikat</option>
                        <option value="Pesan Wasiat">Pesan Wasiat Pribadi</option>
                    </select>
                    
                </div><div>
                       <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 space-y-1.5"
                        x-data="{ showPass: false }">
                        <label class="block text-xs font-bold text-amber-400">
                            <i class="fa-solid fa-lock mr-1"></i> Konfirmasi Kata Sandi Akun Anda *
                        </label>
                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" 
                                name="account_password" 
                                required 
                                placeholder="Masukkan password login untuk menyimpan perubahan" 
                                class="w-full px-3.5 py-2 pr-10 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                            <button type="button" 
                                    @click="showPass = !showPass" 
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                                <i class="fa-solid text-xs" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                </div>
                  
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-extrabold">Simpan Perubahan</button>
                    </div>
                 
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL 7: HAPUS ASET (WAJIB INPUT PASSWORD AKUN)           -->
    <template x-teleport="body">
        <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
            <div @click.away="deleteModalOpen = false" class="bg-white dark:bg-slate-900 border border-rose-500/40 rounded-3xl max-w-md w-full p-6 shadow-2xl">
                <h3 class="text-base font-extrabold text-rose-500 mb-1 flex items-center gap-2">
                    <i class="fa-solid fa-trash-can"></i> Konfirmasi Hapus Aset
                </h3>
                <p class="text-xs text-slate-400 mb-4">
                    Anda akan menghapus aset <strong class="text-white" x-text="selectedAsset.title"></strong>. Masukkan kata sandi akun Anda untuk melanjutkan.
                </p>

                <form :action="'/assets/' + selectedAsset.id" method="POST" class="space-y-4">
                    @csrf
                    @method('DELETE')

                   <div class="space-y-1.5 text-left" x-data="{ showPass: false }">
                        <label class="block text-xs font-bold text-rose-500 dark:text-rose-400">
                            <i class="fa-solid fa-key mr-1"></i> Masukkan Password untuk Konfirmasi Hapus *
                        </label>
                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" 
                                name="account_password" 
                                 required 
                                placeholder="Ketik password akun Anda" 
                                class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-slate-50 dark:bg-slate-950 border border-rose-500/30 focus:border-rose-500 focus:outline-none text-xs text-slate-900 dark:text-white">
                            <button type="button" 
                                    @click="showPass = !showPass" 
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                                <i class="fa-solid text-xs" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-rose-500 hover:bg-rose-600 text-white text-xs font-extrabold">Ya, Hapus Permanen</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

<!-- MODAL: PERINGATAN WAJIB DAFTAR AHLI WARIS TERLEBIH DAHULU -->
<!-- ========================================================= -->
<template x-teleport="body">
    <div x-show="noBeneficiaryModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
        <div @click.away="noBeneficiaryModalOpen = false" 
             class="bg-white dark:bg-slate-900 border border-amber-500/40 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-5 text-center">
            
            <!-- Ikon Peringatan -->
            <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-500 text-2xl shadow-inner">
                <i class="fa-solid fa-user-shield"></i>
            </div>

            <!-- Teks Peringatan -->
            <div class="space-y-2">
                <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">
                    Belum Memiliki Ahli Waris
                </h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Setiap aset brankas digital wajib memiliki penerima hak waris. Silakan daftarkan minimal <strong>1 Ahli Waris</strong> terlebih dahulu sebelum menambahkan data aset rahasia.
                </p>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-end gap-2.5">
                <button type="button" 
                        @click="noBeneficiaryModalOpen = false" 
                        class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                    Nanti Saja
                </button>
                <button type="button" 
                        @click="noBeneficiaryModalOpen = false; activeTab = 'beneficiaries'; window.scrollTo({ top: 0, behavior: 'smooth' });" 
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-extrabold transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Daftarkan Ahli Waris Sekarang</span>
                </button>
            </div>
        </div>
    </div>
</template>
@if($approvedExtensionAsset)
<template x-teleport="body">
    <div x-data="{ approvedModalOpen: true }" x-show="approvedModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 border border-emerald-500/40 rounded-3xl max-w-md w-full p-6 shadow-2xl text-center space-y-4">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-500/15 text-emerald-500 flex items-center justify-center text-3xl">
                <i class="fa-solid fa-unlock-keyhole"></i>
            </div>
            <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">Admin Telah Menyetujui Perpanjangan!</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400">
                Akses ke aset <strong class="text-white">{{ $approvedExtensionAsset->title }}</strong> telah dibuka sementara. Harap segera lakukan pembaharuan tanggal masa berlaku sekarang.
            </p>
            <button type="button" 
                    @click="approvedModalOpen = false; openEditModal({
                        id: {{ $approvedExtensionAsset->id }},
                        title: '{{ addslashes($approvedExtensionAsset->title) }}',
                        category: '{{ $approvedExtensionAsset->category }}',
                        start_date: '{{ $approvedExtensionAsset->start_date ? $approvedExtensionAsset->start_date->format('Y-m-d') : '' }}',
                        expires_at: '{{ $approvedExtensionAsset->expires_at ? $approvedExtensionAsset->expires_at->format('Y-m-d') : '' }}',
                        is_lifetime: {{ $approvedExtensionAsset->is_lifetime ? 'true' : 'false' }}
                    })"
                    class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition">
                Edit Tanggal Sekarang
            </button>
        </div>
    </div>
</template>
@endif
</div> <!-- Penutup <div class="space-y-8" x-data="..."> -->

@endsection 