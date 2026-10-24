@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Header Laporan Cashflow -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-chart-line text-emerald-500"></i>
                <span>Laporan Arus Kas & Pemasukan (Cashflow)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Pantau total pendapatan langganan yang telah terverifikasi, rincian per rekening bank, dan masa aktif pelanggan.
            </p>
        </div>
    </div>

    <!-- 3 Card Ringkasan Pemasukan -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- Card 1: Total Pemasukan -->
        <div class="p-5 rounded-2xl bg-gradient-to-br from-emerald-500/15 via-white to-white dark:from-emerald-500/15 dark:via-slate-900 dark:to-slate-900 border border-emerald-500/30 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Total Pemasukan
                </span>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-500 flex items-center justify-center text-base">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
            <div class="my-3">
                <div class="text-2xl font-extrabold text-slate-900 dark:text-white">
                    Rp {{ number_format($stats['total_income'], 0, ',', '.') }}
                </div>
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between pt-2 border-t border-emerald-500/20">
                <span>Total Transaksi Sukses:</span>
                <strong class="text-emerald-600 dark:text-emerald-400">{{ $stats['total_trx'] }} Pembayaran</strong>
            </div>
        </div>

        <!-- Card 2: BCA / BNI -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-sky-500">
                        Rekening BCA / BNI
                    </span>
                    <div class="text-[10px] text-slate-400 font-mono">8810-2938-44</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-sky-500/10 text-sky-500 flex items-center justify-center text-base">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
            </div>
            <div class="my-3">
                <div class="text-2xl font-extrabold text-slate-900 dark:text-white">
                    Rp {{ number_format($stats['bca_bni_income'], 0, ',', '.') }}
                </div>
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                <span>Kontribusi Transaksi:</span>
                <strong class="text-slate-700 dark:text-slate-200">{{ $stats['bca_bni_count'] }} Transaksi</strong>
            </div>
        </div>

        <!-- Card 3: BRI -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-500">
                        Rekening Bank BRI
                    </span>
                    <div class="text-[10px] text-slate-400 font-mono">5932 0103 8807 531</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-base">
                    <i class="fa-solid fa-landmark"></i>
                </div>
            </div>
            <div class="my-3">
                <div class="text-2xl font-extrabold text-slate-900 dark:text-white">
                    Rp {{ number_format($stats['bri_income'], 0, ',', '.') }}
                </div>
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                <span>Kontribusi Transaksi:</span>
                <strong class="text-slate-700 dark:text-slate-200">{{ $stats['bri_count'] }} Transaksi</strong>
            </div>
        </div>
    </div>

    <!-- Bar Form Pencarian & Filter Gabungan -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <form action="{{ route('admin.cashflow.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            
            <!-- 1. Input Pencarian -->
            <div class="lg:col-span-2 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Cari nama pengguna, email, atau nama pengirim..." 
                       class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <!-- 2. Combobox Periode Waktu -->
            <div>
                <select name="period" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    <option value="all" {{ $period === 'all' ? 'selected' : '' }}>Semua Waktu</option>
                    <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hari Ini</option>
                    <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Minggu Ini</option>
                    <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bulan Ini</option>
                </select>
            </div>

            <!-- 3. Combobox Pilihan Bank -->
            <div>
                <select name="bank" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    <option value="">Semua Rekening Bank</option>
                    <option value="BCA_BNI" {{ $bankFilter === 'BCA_BNI' ? 'selected' : '' }}>BCA / BNI</option>
                    <option value="BRI" {{ $bankFilter === 'BRI' ? 'selected' : '' }}>Bank BRI</option>
                </select>
            </div>

            <!-- 4. Tombol Aksi Terapkan & Reset -->
            <div class="flex items-center gap-2">
                <button type="submit" class="w-full py-2 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-xs transition flex items-center justify-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                @if(!empty($search) || !empty($bankFilter) || $period !== 'all')
                    <a href="{{ route('admin.cashflow.index') }}" class="py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 text-xs font-bold transition flex items-center justify-center shrink-0" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>

        </form>
    </div>

    <!-- ========================================== -->
    <!-- BAGIAN 3 VISUALISASI GRAFIK (CHART.JS)     -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        
        <!-- 1. Diagram Garis: Tren Pemasukan Harian (Lebar 2 Kolom di Desktop) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-chart-area text-emerald-500"></i>
                        <span>Tren Pemasukan dari Hari ke Hari</span>
                    </h3>
                    <p class="text-[11px] text-slate-400">Grafik pergerakan nominal transaksi yang disetujui</p>
                </div>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="incomeLineChart"></canvas>
            </div>
        </div>

        <!-- 2. Diagram Lingkaran: Status Pembayaran -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
            <div class="mb-4">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-chart-pie text-amber-500"></i>
                    <span>Rasio Status Pembayaran</span>
                </h3>
                <p class="text-[11px] text-slate-400">Perbandingan Menunggu, Approved & Rejected</p>
            </div>
            <div class="relative h-52 w-full flex items-center justify-center">
                <canvas id="statusPieChart"></canvas>
            </div>
            <div class="grid grid-cols-3 gap-2 pt-3 mt-2 border-t border-slate-100 dark:border-slate-800 text-center text-[11px]">
                <div>
                    <span class="text-amber-500 font-bold block">{{ $chartData['pie']['pending'] }}</span>
                    <span class="text-slate-400">Menunggu</span>
                </div>
                <div>
                    <span class="text-emerald-500 font-bold block">{{ $chartData['pie']['approved'] }}</span>
                    <span class="text-slate-400">Approved</span>
                </div>
                <div>
                    <span class="text-rose-500 font-bold block">{{ $chartData['pie']['rejected'] }}</span>
                    <span class="text-slate-400">Rejected</span>
                </div>
            </div>
        </div>

        <!-- 3. Diagram Batang: Perbandingan Bank BCA/BNI vs BRI (Full Width di Bawahnya) -->
        <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-chart-column text-sky-500"></i>
                        <span>Perbandingan Pemasukan Bank (BCA/BNI vs BRI)</span>
                    </h3>
                    <p class="text-[11px] text-slate-400">Perbandingan total nominal rupiah yang masuk ke masing-masing rekening</p>
                </div>
            </div>
            <div class="relative h-56 w-full">
                <canvas id="bankBarChart"></canvas>
            </div>
        </div>

    </div>

    <!-- Tabel Rincian Data Cashflow -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 uppercase text-[11px]">
                    <tr>
                        <th class="py-3 px-3">Tanggal Bayar</th>
                        <th class="py-3 px-3">Pelanggan</th>
                        <th class="py-3 px-3">Paket & Nominal</th>
                        <th class="py-3 px-3">Bank & Pengirim</th>
                        <th class="py-3 px-3">Status & Masa Aktif Sampai</th>
                        <th class="py-3 px-3">Keterangan / Bukti</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    @forelse($cashflows as $pay)
                        @php
                            $u = $pay->user;
                            $activeSub = $u ? $u->activeSubscription : null;
                            $latestSub = $u ? $u->subscription : null;
                            $subToUse = $activeSub ?: $latestSub;

                            $endsAt = ($subToUse && $subToUse->ends_at) ? \Carbon\Carbon::parse($subToUse->ends_at) : null;
                            $daysLeft = $endsAt ? (int) now()->startOfDay()->diffInDays($endsAt->startOfDay(), false) : null;

                            $isBri = stripos($pay->bank_sender_name ?? '', 'BRI') !== false || stripos($pay->admin_note ?? '', 'BRI') !== false;
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                            <td class="py-3.5 px-3 whitespace-nowrap">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $pay->created_at->format('d M Y') }}
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    Pukul {{ $pay->created_at->format('H:i') }} WIB
                                </div>
                            </td>

                            <td class="py-3.5 px-3">
                                @if($u)
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $u->name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $u->email }}</div>
                                @else
                                    <span class="italic text-slate-400">User Terhapus</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-3">
                                <span class="px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-500 font-bold text-[10px] uppercase">
                                    {{ $pay->plan->name ?? 'Paket' }}
                                </span>
                                <div class="font-extrabold text-slate-900 dark:text-white text-sm mt-1">
                                    Rp {{ number_format($pay->amount, 0, ',', '.') }}
                                </div>
                            </td>

                            <td class="py-3.5 px-3">
                                @if($isBri)
                                    <span class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-500 font-bold text-[10px] inline-flex items-center gap-1">
                                        <i class="fa-solid fa-landmark"></i> BANK BRI
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-sky-500/15 text-sky-500 font-bold text-[10px] inline-flex items-center gap-1">
                                        <i class="fa-solid fa-building-columns"></i> BCA / BNI
                                    </span>
                                @endif
                                <div class="text-slate-700 dark:text-slate-300 font-medium mt-1">
                                    a.n {{ $pay->bank_sender_name ?? '-' }}
                                </div>
                            </td>

                            <td class="py-3.5 px-3">
                                @if($activeSub)
                                    <div class="space-y-1">
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-500 font-bold text-[10px] inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Sedang Aktif
                                        </span>
                                        @if($endsAt)
                                            <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                                s/d {{ $endsAt->format('d M Y') }}
                                            </div>
                                            <div class="text-[11px] {{ $daysLeft <= 7 ? 'text-amber-500 font-bold' : 'text-emerald-500' }}">
                                                (Sisa {{ max(0, $daysLeft) }} hari lagi)
                                            </div>
                                        @else
                                            <div class="text-[11px] text-emerald-400 font-semibold">Aktif Selamanya (&infin;)</div>
                                        @endif
                                    </div>
                                @else
                                    <div class="space-y-1">
                                        <span class="px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-500 font-bold text-[10px] inline-flex items-center gap-1">
                                            <i class="fa-solid fa-clock"></i> Sudah Berakhir
                                        </span>
                                        @if($endsAt)
                                            <div class="text-[11px] text-slate-400">
                                                Habis pada: {{ $endsAt->format('d M Y') }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td class="py-3.5 px-3 space-y-1.5">
                                <div class="text-slate-600 dark:text-slate-300">
                                    {{ $pay->admin_note ?: 'Pembayaran diverifikasi & paket diaktifkan' }}
                                </div>
                                @if($pay->proof_image_path)
                                    <a href="{{ Storage::disk('supabase_proofs')->url($pay->proof_image_path) }}" 
                                       target="_blank" 
                                       class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-500 hover:underline">
                                        <i class="fa-solid fa-receipt"></i> Lihat Bukti Transfer
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-400">
                                <i class="fa-solid fa-file-invoice-dollar text-2xl mb-2 block opacity-50"></i>
                                Belum ada data pemasukan terverifikasi pada periode/filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($cashflows->hasPages())
            <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                {{ $cashflows->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Load Chart.js via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const textColor = '#94a3b8';
    const gridColor = 'rgba(148, 163, 184, 0.1)';

    // 1. Diagram Garis: Tren Pemasukan Harian
    const lineCtx = document.getElementById('incomeLineChart').getContext('2d');
    const lineGradient = lineCtx.createLinearGradient(0, 0, 0, 240);
    lineGradient.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
    lineGradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: @json($chartData['line']['labels']),
            datasets: [{
                label: 'Total Pemasukan (Rp)',
                data: @json($chartData['line']['data']),
                borderColor: '#10b981',
                backgroundColor: lineGradient,
                borderWidth: 2.5,
                pointBackgroundColor: '#10b981',
                pointRadius: 4,
                fill: true,
                tension: 0.35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' Rp ' + Number(context.raw).toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: textColor, font: { size: 11 } },
                    grid: { color: gridColor }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: textColor,
                        font: { size: 11 },
                        callback: function(value) {
                            return 'Rp ' + Number(value).toLocaleString('id-ID');
                        }
                    },
                    grid: { color: gridColor }
                }
            }
        }
    });

    // 2. Diagram Lingkaran: Status Pembayaran (Menunggu, Approved, Rejected)
    const pieCtx = document.getElementById('statusPieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: ['Menunggu Verifikasi', 'Approved (Disetujui)', 'Rejected (Ditolak)'],
            datasets: [{
                data: [
                    {{ $chartData['pie']['pending'] }},
                    {{ $chartData['pie']['approved'] }},
                    {{ $chartData['pie']['rejected'] }}
                ],
                backgroundColor: ['#f59e0b', '#10b981', '#f43f5e'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: textColor, font: { size: 11 }, boxWidth: 12 }
                }
            }
        }
    });

    // 3. Diagram Batang: Perbandingan Bank BCA/BNI vs BRI
    const barCtx = document.getElementById('bankBarChart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: ['Rekening BCA / BNI', 'Rekening Bank BRI'],
            datasets: [{
                label: 'Nominal Pemasukan (Rp)',
                data: [
                    {{ $chartData['bar']['bca_bni'] }},
                    {{ $chartData['bar']['bri'] }}
                ],
                backgroundColor: [
                    'rgba(14, 165, 233, 0.75)', // Biru (BCA/BNI)
                    'rgba(245, 158, 11, 0.75)'  // Kuning/Amber (BRI)
                ],
                borderColor: ['#0ea5e9', '#f59e0b'],
                borderWidth: 1.5,
                borderRadius: 10,
                maxBarThickness: 75
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' Rp ' + Number(context.raw).toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: textColor, font: { size: 12, weight: 'bold' } },
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: textColor,
                        font: { size: 11 },
                        callback: function(value) {
                            return 'Rp ' + Number(value).toLocaleString('id-ID');
                        }
                    },
                    grid: { color: gridColor }
                }
            }
        }
    });
});
</script>
@endsection