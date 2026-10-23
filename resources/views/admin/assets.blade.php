@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 inline-flex items-center gap-1.5">
                <i class="fa-solid fa-user-shield"></i> Administrator Area
            </span>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-2">Pemantauan & Verifikasi Masa Berlaku Aset</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Pantau siklus masa aktif aset seluruh pengguna dan setujui pembukaan akses perpanjangan waktu.</p>
        </div>
    </div>

    <!-- 3 Kotak Ringkasan Status -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="{{ route('admin.assets.index', ['status' => 'pending']) }}" class="p-5 rounded-2xl bg-white dark:bg-slate-900 border {{ $status === 'pending' ? 'border-amber-500 ring-1 ring-amber-500' : 'border-slate-200 dark:border-slate-800' }} shadow-sm transition hover:border-amber-500">
            <div class="text-xs text-amber-500 font-bold uppercase tracking-wider flex items-center gap-1.5">
                <i class="fa-solid fa-hourglass-start"></i> Antrean Permohonan
            </div>
            <div class="text-2xl font-extrabold text-amber-500 mt-2">{{ $pendingCount }} <span class="text-xs font-normal text-slate-400">Permohonan</span></div>
        </a>

        <a href="{{ route('admin.assets.index', ['status' => 'expired']) }}" class="p-5 rounded-2xl bg-white dark:bg-slate-900 border {{ $status === 'expired' ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-200 dark:border-slate-800' }} shadow-sm transition hover:border-rose-500">
            <div class="text-xs text-rose-500 font-bold uppercase tracking-wider flex items-center gap-1.5">
                <i class="fa-solid fa-lock"></i> Aset Terkunci (Kadaluarsa)
            </div>
            <div class="text-2xl font-extrabold text-rose-500 mt-2">{{ $expiredCount }} <span class="text-xs font-normal text-slate-400">Aset</span></div>
        </a>

        <a href="{{ route('admin.assets.index', ['status' => 'expiring_soon']) }}" class="p-5 rounded-2xl bg-white dark:bg-slate-900 border {{ $status === 'expiring_soon' ? 'border-amber-400 ring-1 ring-amber-400' : 'border-slate-200 dark:border-slate-800' }} shadow-sm transition hover:border-amber-400">
            <div class="text-xs text-amber-400 font-bold uppercase tracking-wider flex items-center gap-1.5">
                <i class="fa-solid fa-clock"></i> Mau Kadaluarsa (&le; 7 Hari)
            </div>
            <div class="text-2xl font-extrabold text-amber-400 mt-2">{{ $expiringSoonCount }} <span class="text-xs font-normal text-slate-400">Aset</span></div>
        </a>
    </div>

    <!-- Filter & Pencarian -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.assets.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold border {{ $status === 'all' ? 'bg-slate-900 text-white dark:bg-slate-800 border-slate-700' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800' }}">Semua</a>
            <a href="{{ route('admin.assets.index', ['status' => 'pending']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold border {{ $status === 'pending' ? 'bg-amber-500 text-slate-950 font-bold border-amber-500' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800' }}">Permohonan Perpanjangan</a>
            <a href="{{ route('admin.assets.index', ['status' => 'expired']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold border {{ $status === 'expired' ? 'bg-rose-500 text-white border-rose-500' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800' }}">Kadaluarsa</a>
            <a href="{{ route('admin.assets.index', ['status' => 'expiring_soon']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold border {{ $status === 'expiring_soon' ? 'bg-amber-400 text-slate-950 border-amber-400' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800' }}">Mendekati Kadaluarsa</a>
        </div>

        <form action="{{ route('admin.assets.index') }}" method="GET" class="relative w-full sm:w-64">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama aset/pemilik..." class="w-full rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 pl-3.5 pr-9 py-2 text-xs text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
            <button type="submit" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-emerald-500"><i class="fa-solid fa-magnifying-glass text-xs"></i></button>
        </form>
    </div>

    <!-- Tabel Daftar Aset -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3">Pemilik</th>
                        <th class="py-3">Nama Aset</th>
                        <th class="py-3">Kategori</th>
                        <th class="py-3">Masa Berlaku</th>
                        <th class="py-3">Status Akses</th>
                        <th class="py-3 text-right">Tindakan Admin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    @forelse($assets as $item)
                        @php $isExpired = $item->isExpired(); @endphp
                        <tr>
                            <td class="py-3 text-slate-900 dark:text-white font-medium">
                                <div>{{ $item->user->name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $item->user->email }}</div>
                            </td>
                            <td class="py-3 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $item->title }}
                                <div class="text-[10px] text-slate-400 font-mono italic">Isi data terenkripsi Zero-Knowledge (Aman)</div>
                            </td>
                            <td class="py-3 text-slate-600 dark:text-slate-400">{{ $item->category }}</td>
                            <td class="py-3 text-slate-600 dark:text-slate-400">
                                @if($item->is_lifetime)
                                    <span class="text-emerald-500 font-bold">&infin; Selamanya</span>
                                @elseif($item->expires_at)
                                    <div>Berakhir: {{ $item->expires_at->format('d/m/Y') }}</div>
                                    <div class="text-[10px] {{ $isExpired ? 'text-rose-500 font-bold' : ($item->isExpiringSoon() ? 'text-amber-400' : 'text-slate-400') }}">
                                        {{ $isExpired ? 'Telah Habis' : $item->daysRemaining() . ' hari lagi' }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3">
                                @if($isExpired)
                                    <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-500 font-bold text-[10px] flex items-center gap-1 w-fit">
                                        <i class="fa-solid fa-lock"></i> Terkunci
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 font-bold text-[10px] flex items-center gap-1 w-fit">
                                        <i class="fa-solid fa-circle-check"></i> Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 text-right">
                                @if($item->extension_status === 'pending')
                                    <div class="flex items-center justify-end gap-1.5">
                                        <form action="{{ route('admin.assets.approveExtension', $item) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] transition" title="Buka Kunci untuk Edit User">
                                                Setujui Perpanjangan
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.assets.rejectExtension', $item) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 font-bold text-[10px] transition">
                                                Tolak
                                            </button>
                                        </form>
                                    </div>
                                @elseif($item->extension_status === 'approved')
                                    <span class="text-[11px] text-emerald-500 italic">Kunci Dibuka (Menunggu User Edit)</span>
                                @else
                                    <span class="text-[11px] text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-500">Tidak ada aset pada filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800">
            {{ $assets->links() }}
        </div>
    </div>
</div>
@endsection