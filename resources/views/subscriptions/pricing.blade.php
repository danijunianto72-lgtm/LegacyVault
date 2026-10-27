@extends('layouts.app')

@section('content')
<div x-data="{ 
    selectedPlanId: null, 
    selectedPlanName: '', 
    selectedPlanPrice: 0,
    selectedBank: 'BCA',
    copied: false,
    banks: {
        'BCA': {
            name: 'BCA (Bank Central Asia)',
            number: '3201-4205-88',
            rawNumber: '3201420588',
            holder: 'PT Brankas Warisan Digital'
        },
        'BRI': {
            name: 'BRI (Bank Rakyat Indonesia)',
            number: '5932 0103 8807 531',
            rawNumber: '593201038807531',
            holder: 'PT Brankas Warisan Digital'
        }
    },
    copyRekening() {
        navigator.clipboard.writeText(this.banks[this.selectedBank].rawNumber);
        this.copied = true;
        setTimeout(() => this.copied = false, 2000);
    }
}" class="space-y-10">

    <!-- Header Pricing -->
    <div class="text-center max-w-2xl mx-auto space-y-2">
        <span class="px-3.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 inline-flex items-center gap-1.5">
            <i class="fa-solid fa-crown"></i> Kapasitas Brankas Digital
        </span>
        <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white">Pilih Kapasitas Brankas Warisan Anda</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400">
            Upgrade kapan saja melalui transfer bank manual. Masa aktif langsung bertambah 30 hari setelah diverifikasi Admin.
        </p>
    </div>

    <!-- 3 Kartu Tier Langganan -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($plans as $plan)
            @php 
                $isCurrent = ($currentPlan->id === $plan->id); 
                $isUnlimited = ($plan->max_assets < 0 || $plan->max_assets >= 9999);
            @endphp
            <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border {{ $isCurrent ? 'border-emerald-500 ring-2 ring-emerald-500/20' : ($plan->slug === 'pro' ? 'border-emerald-500/60 shadow-xl shadow-emerald-950/20' : 'border-slate-200 dark:border-slate-800') }} flex flex-col justify-between relative shadow-sm transition">
                
                @if($isCurrent)
                    <span class="absolute -top-3 right-6 px-3 py-0.5 rounded-full text-[11px] font-bold bg-emerald-600 text-white uppercase tracking-wider flex items-center gap-1 shadow">
                        <i class="fa-solid fa-circle-check"></i> Paket Aktif
                    </span>
                @elseif($plan->slug === 'pro')
                    <span class="absolute -top-3 right-6 px-3 py-0.5 rounded-full text-[11px] font-bold bg-amber-500 text-slate-950 uppercase tracking-wider flex items-center gap-1 shadow">
                        <i class="fa-solid fa-fire"></i> Paling Populer
                    </span>
                @endif

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ $plan->name }}</h3>
                        <i class="fa-solid {{ $plan->slug === 'free' ? 'fa-seedling text-slate-400' : ($plan->slug === 'pro' ? 'fa-shield-halved text-emerald-500' : 'fa-gem text-amber-500') }} text-xl"></i>
                    </div>

                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-extrabold text-slate-900 dark:text-white">
                            Rp {{ number_format($plan->price, 0, ',', '.') }}
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $plan->price > 0 ? '/ 30 hari' : '/ selamanya' }}</span>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">{{ $plan->description }}</p>

                    <ul class="space-y-2.5 text-xs text-slate-700 dark:text-slate-300 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-check text-emerald-500"></i>
                            <span>Kapasitas hingga <strong>{{ $isUnlimited ? 'Unlimited (Tanpa Batas)' : $plan->max_assets . ' Aset Digital' }}</strong></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-check text-emerald-500"></i>
                            <span>Enkripsi Teks <code class="text-emerald-600 dark:text-emerald-400 font-mono font-bold">AES-256-CBC</code></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-check text-emerald-500"></i>
                            <span>Lampiran Dokumen PDF / Gambar (Maks 5MB)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-check text-emerald-500"></i>
                            <span>Relasi Multi-Ahli Waris</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fa-solid fa-check text-emerald-500"></i>
                            <span>
                                Interval Sakelar: 
                                <strong>{{ ($plan->slug === 'free') ? '3 Bulan, 6 Bulan, & 1 Tahun' : 'Bisa per 1 Bulan (Realtime)' }}</strong>
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="mt-8">
                    @if($isCurrent)
                        <button disabled class="w-full py-2.5 px-4 rounded-xl bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 text-xs font-bold border border-emerald-500/30 cursor-not-allowed flex items-center justify-center gap-2">
                            <i class="fa-solid fa-badge-check"></i>
                            <span>Anda sedang menggunakan paket ini</span>
                        </button>
                    @elseif($plan->price === 0)
                        <button disabled class="w-full py-2.5 px-4 rounded-xl bg-slate-100 dark:bg-slate-800/60 text-slate-400 dark:text-slate-500 text-xs font-semibold cursor-not-allowed flex items-center justify-center gap-2">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>Paket Dasar Gratis</span>
                        </button>
                    @else
                        <button
                            type="button"
                            @click="selectedPlanId = {{ $plan->id }}; selectedPlanName = '{{ $plan->name }}'; selectedPlanPrice = '{{ number_format($plan->price, 0, ',', '.') }}'"
                            class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-arrow-up-right-dots"></i>
                            <span>Pilih Paket {{ $plan->name }}</span>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal / Box Checkout & Upload Bukti Transfer -->
    <div x-show="selectedPlanId !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
        <div @click.away="selectedPlanId = null" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div>
                    <span class="text-xs uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-receipt"></i> Instruksi Pembayaran Manual
                    </span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-0.5">Checkout Paket <span x-text="selectedPlanName"></span></h3>
                </div>
                <button @click="selectedPlanId = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Pilihan Metode Bank (BCA / BRI) -->
            <div class="space-y-2.5">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Pilih Rekening Tujuan Transfer:
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button"
                            @click="selectedBank = 'BCA'; copied = false"
                            :class="selectedBank === 'BCA' ? 'border-emerald-500 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 ring-2 ring-emerald-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:border-slate-300'"
                            class="p-3 rounded-xl border text-left transition flex items-center justify-between">
                        <div>
                            <div class="text-xs font-extrabold">Bank BCA</div>
                            <div class="text-[10px] opacity-75 font-mono">8810-2938-44</div>
                        </div>
                        <i class="fa-solid fa-building-columns text-base" :class="selectedBank === 'BCA' ? 'text-emerald-500' : 'text-slate-400'"></i>
                    </button>

                    <button type="button"
                            @click="selectedBank = 'BRI'; copied = false"
                            :class="selectedBank === 'BRI' ? 'border-emerald-500 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 ring-2 ring-emerald-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:border-slate-300'"
                            class="p-3 rounded-xl border text-left transition flex items-center justify-between">
                        <div>
                            <div class="text-xs font-extrabold">Bank BRI</div>
                            <div class="text-[10px] opacity-75 font-mono">5932 0103 8807 531</div>
                        </div>
                        <i class="fa-solid fa-building-columns text-base" :class="selectedBank === 'BRI' ? 'text-emerald-500' : 'text-slate-400'"></i>
                    </button>
                </div>
            </div>

            <!-- Detail Rekening yang Dipilih -->
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2.5 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Bank Tujuan:</span>
                    <span class="font-bold text-slate-800 dark:text-white" x-text="banks[selectedBank].name"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Nomor Rekening:</span>
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-sm" x-text="banks[selectedBank].number"></span>
                        <button type="button" 
                                @click="copyRekening()" 
                                class="px-2 py-0.5 rounded bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-500 text-[10px] font-bold transition flex items-center gap-1">
                            <i class="fa-regular" :class="copied ? 'fa-circle-check' : 'fa-copy'"></i>
                            <span x-text="copied ? 'Tersalin' : 'Salin'"></span>
                        </button>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 dark:text-slate-400">Atas Nama:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300" x-text="banks[selectedBank].holder"></span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-slate-200 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Nominal Harus Ditransfer:</span>
                    <span class="font-bold text-amber-600 dark:text-amber-400 text-sm">Rp <span x-text="selectedPlanPrice"></span></span>
                </div>
            </div>

            <form action="{{ route('pricing.checkout') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
    @csrf
   <input type="hidden" name="plan_id" :value="selectedPlanId">

    <!-- Gunakan input radio tersembunyi dengan x-model (100% didukung Alpine.js) -->
    <div class="hidden">
        <input type="radio" name="bank_destination" value="BCA" x-model="selectedBank">
        <input type="radio" name="bank_destination" value="BRI" x-model="selectedBank">
    </div>
    <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
            <i class="fa-solid fa-user-check mr-1 text-emerald-500"></i> Nama Pengirim & Bank Asal
        </label>
        <input type="text" name="bank_sender_name" required :placeholder="'Contoh: Budi Santoso (Transfer ke ' + selectedBank + ')'"
               class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
    </div>

    <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
            <i class="fa-solid fa-receipt mr-1 text-emerald-500"></i> Upload Foto / Struk Bukti Transfer (JPG/PNG)
        </label>
        <input type="file" name="proof_image" required accept="image/jpeg,image/png"
               class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-emerald-500/15 file:text-emerald-600 dark:file:text-emerald-400 hover:file:bg-emerald-500/25 cursor-pointer">
    </div>

    <div class="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
        <button type="button" @click="selectedPlanId = null" class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-medium">Batal</button>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-lg shadow-emerald-600/20">
            <i class="fa-solid fa-paper-plane"></i>
            <span>Kirim Bukti Pembayaran</span>
        </button>
    </div>
</form>
        </div>
    </div>

  <!-- Pembungkus Alpine.js (Letakkan tepat di atas div/table riwayat transaksi) -->
<div x-data="{ 
        previewModal: false, 
        proofUrl: '', 
        planName: '', 
        trxCode: '',
        printProof() {
            const printWin = window.open('', '_blank');
            printWin.document.write(`
                <html>
                    <head>
                        <title>Struk Pembayaran - ${this.trxCode}</title>
                        <style>
                            body { font-family: sans-serif; text-align: center; padding: 20px; color: #0f172a; }
                            h2 { margin-bottom: 5px; }
                            p { color: #475569; margin-top: 0; font-size: 14px; }
                            img { max-width: 90%; max-height: 80vh; border: 1px solid #cbd5e1; border-radius: 8px; margin-top: 15px; }
                        </style>
                    </head>
                    <body>
                        <h2>Bukti Pembayaran Langganan</h2>
                        <p>Kode: <strong>${this.trxCode}</strong> | Paket: <strong>${this.planName}</strong></p>
                        <img src='${this.proofUrl}' onload='window.print(); window.close();' />
                    </body>
                </html>
            `);
            printWin.document.close();
        }
    }">

    <!-- Pastikan tag <table> dan <thead> Anda ada di sini -->
    <table class="w-full text-left text-xs">
        <!-- ... <thead> Anda ... -->

        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
            @forelse($payments as $pay)
                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30 transition">
                    <td class="py-3 px-4 text-slate-500 dark:text-slate-400">
                        {{ $pay->created_at->format('d M Y H:i') }}
                    </td>
                    <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                        {{ $pay->plan->name ?? 'Paket' }}
                    </td>
                    <td class="py-3 px-4 text-slate-700 dark:text-slate-300">
                        Rp {{ number_format($pay->amount, 0, ',', '.') }}
                    </td>
                    <td class="py-3 px-4">
                        @if($pay->proof_image_path)
                            @php
                                $fullProofUrl = Storage::disk('supabase_proofs')
                                    ->url($pay->proof_image_path);

                                $planTitle = $pay->plan?->name ?? 'Paket';

                                $invoiceCode = $pay->invoice_number
                                    ?? $pay->order_id
                                    ?? ('TRX-' . $pay->id);
                            @endphp

                            <button
                                type="button"
                                @click="
                                    proofUrl = @js($fullProofUrl);
                                    planName = @js($planTitle);
                                    trxCode = @js($invoiceCode);
                                    previewModal = true;
                                "
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg
                                       bg-emerald-50 dark:bg-emerald-500/10
                                       border border-emerald-200 dark:border-emerald-500/30
                                       text-emerald-600 dark:text-emerald-400
                                       hover:bg-emerald-100 dark:hover:bg-emerald-500/20
                                       transition-all font-medium cursor-pointer"
                            >
                                <i class="fa-solid fa-file-image"></i>
                                <span>Lihat Struk</span>
                            </button>
                        @else
                            <span class="text-slate-400 dark:text-slate-500 italic text-[11px]">
                                Tidak ada struk
                            </span>
                        @endif
                    </td>
                    <td class="py-3 px-4">
                        @if($pay->status === 'pending')
                            <span class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-600 dark:text-amber-400 font-medium inline-flex items-center gap-1">
                                <i class="fa-solid fa-hourglass-half"></i> Menunggu Verifikasi
                            </span>
                        @elseif($pay->status === 'approved')
                            <span class="px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-medium inline-flex items-center gap-1">
                                <i class="fa-solid fa-circle-check"></i> Disetujui
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded bg-rose-500/15 text-rose-600 dark:text-rose-400 font-medium inline-flex items-center gap-1">
                                <i class="fa-solid fa-circle-xmark"></i> Ditolak
                            </span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-slate-500 dark:text-slate-400">
                        {{ $pay->admin_note ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-6 text-center text-slate-400 dark:text-slate-500">
                        Belum ada riwayat transaksi.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Modal Preview Struk (Adaptif Light & Dark Mode) -->
    <div x-show="previewModal" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm"
         x-transition.opacity
         @keydown.escape.window="previewModal = false">
        
        <div class="relative w-full max-w-lg bg-white dark:bg-[#0e1726] border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden"
             @click.away="previewModal = false">
            
            <!-- Header Modal -->
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#070d1e]">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Preview Struk Pembayaran</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Kode: <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="trxCode"></span> 
                        &bull; Paket: <span class="text-emerald-600 dark:text-emerald-400 font-semibold" x-text="planName"></span>
                    </p>
                </div>
                <button type="button" @click="previewModal = false" class="text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Konten Gambar -->
            <div class="p-4 flex items-center justify-center bg-slate-100/80 dark:bg-slate-950/60 max-h-[60vh] overflow-auto">
                <img :src="proofUrl" alt="Struk Pembayaran" class="max-h-[55vh] w-auto object-contain rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm">
            </div>

            <!-- Footer: Lihat Full & Print -->
            <div class="flex items-center justify-between px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#070d1e]">
                <!-- Tombol Lihat Full (Buka Tab Baru) -->
                <a :href="proofUrl" target="_blank" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-200/80 hover:bg-slate-300/80 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold transition-all">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Lihat Full</span>
                </a>

                <div class="flex items-center gap-2">
                    <!-- Tombol Print -->
                    <button type="button" @click="printProof()"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-semibold shadow-md shadow-cyan-600/20 transition-all cursor-pointer">
                        <i class="fa-solid fa-print"></i>
                        <span>Print</span>
                    </button>
                    <!-- Tombol Tutup -->
                    <button type="button" @click="previewModal = false"
                            class="px-3 py-1.5 rounded-xl bg-slate-200/80 hover:bg-slate-300/80 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition-all cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

</div>
@endsection