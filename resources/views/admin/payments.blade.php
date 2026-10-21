@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 inline-flex items-center gap-1.5">
                <i class="fa-solid fa-user-shield"></i> Administrator Area
            </span>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-2">Verifikasi Bukti Transfer Langganan</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Setujui pembayaran untuk mengaktifkan paket Pro / Ultimate pengguna secara otomatis selama 30 hari.</p>
        </div>
    </div>

    <!-- Antrean Pending -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
        <h2 class="text-sm font-bold text-amber-600 dark:text-amber-400 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-hourglass-half"></i>
            <span>Menunggu Verifikasi ({{ $pendingPayments->count() }})</span>
        </h2>

        @if($pendingPayments->isEmpty())
            <p class="text-xs text-slate-500 py-6 text-center">
                <i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i> Tidak ada antrean pembayaran baru saat ini.
            </p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($pendingPayments as $payment)
                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex flex-col justify-between gap-4">
                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-1.5">
                                    <i class="fa-solid fa-user text-emerald-500"></i> {{ $payment->user->name }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-semibold">
                                    Paket {{ $payment->plan->name }}
                                </span>
                            </div>
                            <div class="text-slate-600 dark:text-slate-400">
                                Pengirim Rekening: <strong class="text-slate-900 dark:text-slate-200">{{ $payment->bank_sender_name }}</strong>
                            </div>
                            <div class="text-slate-600 dark:text-slate-400">
                                Nominal Transfer: <strong class="text-emerald-600 dark:text-emerald-400">Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong>
                            </div>
                            <div class="pt-2">
                                <a href="{{ Storage::url($payment->proof_image_path) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 font-medium">
                                    <i class="fa-solid fa-image"></i>
                                    <span>Lihat Foto Bukti Transfer</span>
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                            <form action="{{ route('admin.payments.approve', $payment) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-check"></i>
                                    <span>Approve & Aktifkan</span>
                                </button>
                            </form>

                            <form action="{{ route('admin.payments.reject', $payment) }}" method="POST">
                                @csrf
                                <input type="hidden" name="admin_notes" value="Bukti transfer tidak sesuai / buram.">
                                <button type="submit" class="w-full py-2 px-3 rounded-xl bg-rose-600/15 hover:bg-rose-600/25 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-semibold transition flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-xmark"></i>
                                    <span>Tolak</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Riwayat Verifikasi -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fa-solid fa-list-check text-emerald-500"></i>
            <span>Riwayat Verifikasi Terdahulu</span>
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-2.5">Pengguna</th>
                        <th class="py-2.5">Paket</th>
                        <th class="py-2.5">Nominal</th>
                        <th class="py-2.5">Status</th>
                        <th class="py-2.5">Diverifikasi Pada</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    @forelse($processedPayments as $item)
                        <tr>
                            <td class="py-3 text-slate-900 dark:text-white font-medium">{{ $item->user->name }}</td>
                            <td class="py-3 text-slate-700 dark:text-slate-300">{{ $item->plan->name }}</td>
                            <td class="py-3 text-slate-700 dark:text-slate-300">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                            <td class="py-3">
                                @if($item->status === 'approved')
                                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check"></i> Approved
                                    </span>
                                @else
                                    <span class="text-rose-600 dark:text-rose-400 font-semibold flex items-center gap-1">
                                        <i class="fa-solid fa-circle-xmark"></i> Rejected
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 text-slate-500 dark:text-slate-400">{{ $item->verified_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-slate-500">Belum ada transaksi yang diproses.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <!-- Log Aktivitas Seluruh Sistem (Panel Audit Admin) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fa-solid fa-shield-halved text-amber-500"></i>
            <span>Log Aktivitas Pengguna & Audit Sistem</span>
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-2.5">Waktu</th>
                        <th class="py-2.5">Pengguna</th>
                        <th class="py-2.5">Kode Aksi</th>
                        <th class="py-2.5">Deskripsi Aktivitas</th>
                        <th class="py-2.5">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    @forelse($activityLogs as $log)
                        <tr>
                            <td class="py-3 text-slate-500 dark:text-slate-400">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td class="py-3 font-semibold text-slate-900 dark:text-white">
                                {{ $log->user->name ?? 'User Terhapus' }}
                            </td>
                            <td class="py-3">
                                <span class="px-2 py-0.5 rounded bg-amber-500/10 text-amber-600 dark:text-amber-400 font-mono text-[10px] font-bold">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-3 text-slate-700 dark:text-slate-300">{{ $log->description }}</td>
                            <td class="py-3 font-mono text-slate-400">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-slate-500">Belum ada aktivitas tercatat di sistem.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection