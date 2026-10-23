@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 inline-flex items-center gap-1.5">
                <i class="fa-solid fa-user-shield"></i> Administrator Area
            </span>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-2">Log Audit & Keamanan Sistem</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Audit forensik terhadap seluruh aksi sensitif pengguna, perpanjangan aset, verifikasi langganan, dan check-in.</p>
        </div>

        <form action="{{ route('admin.logs.index') }}" method="GET" class="relative w-full sm:w-72">
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari aksi, user, IP, atau keterangan..." class="w-full rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 pl-3.5 pr-9 py-2 text-xs text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
            <button type="submit" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-emerald-500">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
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
                            <td colspan="5" class="py-6 text-center text-slate-500">Tidak ada log aktivitas yang cocok dengan pencarian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800">
            {{ $activityLogs->links() }}
        </div>
    </div>
</div>
@endsection