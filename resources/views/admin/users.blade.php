@extends('layouts.app')

@section('content')
<div x-data="{
    editModalOpen: false,
    deleteModalOpen: false,
    selectedUser: {
        id: null,
        name: '',
        email: '',
        phone: '',
        gender: '',
        birth_date: '',
        role: 'user',
        plan_id: '',
        plan_expires_at: '',
        avatar_url: ''
    },
    openEditModal(user) {
        this.selectedUser = { ...user };
        this.editModalOpen = true;
    },
    openDeleteModal(user) {
        this.selectedUser = { ...user };
        this.deleteModalOpen = true;
    }
}" class="space-y-6">

    <!-- Header & Ringkasan Statistik -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-users-gear text-amber-500"></i>
                <span>Manajemen Pengguna & Hak Akses</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Kelola seluruh biodata pengguna, pantau sisa masa aktif langganan, ubah role, atau hapus akun.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div class="px-4 py-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-bold text-xs">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 uppercase font-bold">Total User</div>
                    <div class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $stats['total'] }}</div>
                </div>
            </div>

            <div class="px-4 py-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold text-xs">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 uppercase font-bold">Admin</div>
                    <div class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $stats['admins'] }}</div>
                </div>
            </div>

            <div class="px-4 py-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-sky-500/10 text-sky-500 flex items-center justify-center font-bold text-xs">
                    <i class="fa-solid fa-gem"></i>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 uppercase font-bold">Berlangganan</div>
                    <div class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $stats['subscribed'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bar Pencarian & Filter -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
        <form action="{{ route('admin.users.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Input Pencarian -->
            <div class="lg:col-span-2 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Cari nama, email, no. WhatsApp, atau ID..." 
                       class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
            </div>

            <!-- Filter Role -->
            <div>
                <select name="role" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    <option value="">Semua Role</option>
                    <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User Biasa</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                </select>
            </div>

            <!-- Filter Paket -->
            <div>
                <select name="plan_id" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                    <option value="">Semua Paket</option>
                    <option value="free" {{ request('plan_id') === 'free' ? 'selected' : '' }}>Free / Tanpa Paket</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ (string)request('plan_id') === (string)$plan->id ? 'selected' : '' }}>
                            Paket {{ $plan->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Tombol Filter & Reset -->
            <div class="flex items-center gap-2">
                <button type="submit" class="w-full py-2 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-xs transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-filter"></i> Terapkan
                </button>
                @if(request()->anyFilled(['search', 'role', 'plan_id']))
                    <a href="{{ route('admin.users.index') }}" class="py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 text-xs font-bold transition" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Pengguna -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-2">Pengguna & ID</th>
                        <th class="py-3 px-2">Biodata & Kontak</th>
                        <th class="py-3 px-2">Role</th>
                        <th class="py-3 px-2">Paket & Masa Berlaku</th>
                        <th class="py-3 px-2">Isi Brankas</th>
                        <th class="py-3 px-2 text-right">Aksi</th>
                    </tr>
                </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    @forelse($users as $u)
                        @php
                            $activeSub = $u->activeSubscription;
                            $latestSub = $u->subscription;
                            $currentPlan = $u->currentPlan();
                            $planExpires = $activeSub && $activeSub->ends_at ? \Carbon\Carbon::parse($activeSub->ends_at) : null;
                            $daysLeft = $planExpires ? (int) now()->startOfDay()->diffInDays($planExpires->startOfDay(), false) : null;
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                            <!-- Pengguna & Avatar -->
                            <td class="py-3.5 px-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-500 font-bold overflow-hidden shrink-0">
                                        @if($u->avatar_url)
                                            <img src="{{ $u->avatar_url }}" alt="{{ $u->name }}" class="w-full h-full object-cover">
                                        @else
                                            <span>{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                            <span>{{ $u->name }}</span>
                                            @if($u->id === auth()->id())
                                                <span class="px-1.5 py-0.5 text-[9px] rounded bg-emerald-500/20 text-emerald-400 font-mono">Anda</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono">#{{ str_pad($u->id, 5, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Biodata & Kontak -->
                            <td class="py-3.5 px-2 space-y-1">
                                <div class="text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <i class="fa-regular fa-envelope text-slate-400 w-3.5"></i>
                                    <span>{{ $u->email }}</span>
                                </div>
                                <div class="text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <i class="fa-brands fa-whatsapp text-emerald-500 w-3.5"></i>
                                    <span>{{ $u->phone ?? '-' }}</span>
                                </div>
                                <div class="text-[11px] text-slate-400 flex items-center gap-2">
                                    <span>
                                        <i class="fa-solid fa-venus-mars mr-1"></i>
                                        {{ $u->gender === 'male' ? 'Laki-laki' : ($u->gender === 'female' ? 'Perempuan' : '-') }}
                                    </span>
                                    <span>•</span>
                                    <span>
                                        <i class="fa-solid fa-cake-candles mr-1"></i>
                                        {{ $u->birth_date ? $u->birth_date->format('d M Y') : '-' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Role -->
                            <td class="py-3.5 px-2">
                                @if($u->role === 'admin')
                                    <span class="px-2.5 py-1 rounded-lg bg-amber-500/15 border border-amber-500/30 text-amber-500 font-bold text-[11px] inline-flex items-center gap-1">
                                        <i class="fa-solid fa-user-shield"></i> Admin
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold text-[11px] inline-flex items-center gap-1">
                                        <i class="fa-solid fa-user"></i> User
                                    </span>
                                @endif
                            </td>

                            <!-- Paket & Sisa Waktu -->
                            <td class="py-3.5 px-2">
                                @if($u->role === 'admin')
                                    <span class="text-xs font-mono text-amber-500 font-semibold">Unlimited (Admin)</span>
                                @elseif($activeSub && $activeSub->plan && $activeSub->plan->slug !== 'free')
                                    <div class="space-y-1">
                                        <span class="px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-400 font-bold text-[11px] inline-flex items-center gap-1">
                                            <i class="fa-solid fa-gem text-[10px]"></i> {{ $activeSub->plan->name }}
                                        </span>
                                        @if($planExpires)
                                            <div class="text-[11px]">
                                                @if($daysLeft === 0)
                                                    <span class="text-amber-500 font-bold">Berakhir Hari Ini</span>
                                                @elseif($daysLeft <= 7)
                                                    <span class="text-amber-500 font-semibold">Sisa {{ $daysLeft }} hari lagi</span>
                                                @else
                                                    <span class="text-emerald-500 font-medium">Sisa {{ $daysLeft }} hari</span>
                                                @endif
                                                <div class="text-[10px] text-slate-400">s/d {{ $planExpires->format('d M Y') }}</div>
                                            </div>
                                        @else
                                            <div class="text-[11px] text-emerald-400 font-semibold">Aktif Selamanya (&infin;)</div>
                                        @endif
                                    </div>
                                @else
                                    <div class="space-y-0.5">
                                        <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 font-semibold text-[11px]">
                                            {{ $currentPlan->name ?? 'Free' }}
                                        </span>
                                        @if($latestSub && $latestSub->ends_at && \Carbon\Carbon::parse($latestSub->ends_at)->isPast())
                                            <div class="text-[10px] text-rose-500">
                                                Eks {{ $latestSub->plan->name ?? '' }} (Habis {{ \Carbon\Carbon::parse($latestSub->ends_at)->format('d M Y') }})
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <!-- Statistik Brankas User -->
                            <td class="py-3.5 px-2">
                                <div class="text-slate-700 dark:text-slate-300 font-semibold">
                                    {{ $u->assets_count }} Aset
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $u->beneficiaries_count }} Ahli Waris
                                </div>
                            </td>

                            <!-- Tombol Edit & Hapus -->
                            <td class="py-3.5 px-2 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button"
                                            @click="openEditModal({
                                                id: {{ $u->id }},
                                                name: '{{ addslashes($u->name) }}',
                                                email: '{{ addslashes($u->email) }}',
                                                phone: '{{ addslashes($u->phone ?? '') }}',
                                                gender: '{{ $u->gender ?? '' }}',
                                                birth_date: '{{ $u->birth_date ? $u->birth_date->format('Y-m-d') : '' }}',
                                                role: '{{ $u->role }}',
                                                plan_id: '{{ $activeSub ? $activeSub->plan_id : ($currentPlan->id ?? '') }}',
                                                plan_expires_at: '{{ $planExpires ? $planExpires->format('Y-m-d') : '' }}',
                                                avatar_url: '{{ $u->avatar_url ?? '' }}'
                                            })"
                                            class="px-3 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-500 font-bold text-xs transition inline-flex items-center gap-1">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>

                                    @if($u->id !== auth()->id())
                                        <button type="button"
                                                @click="openDeleteModal({
                                                    id: {{ $u->id }},
                                                    name: '{{ addslashes($u->name) }}',
                                                    email: '{{ addslashes($u->email) }}'
                                                })"
                                                class="p-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 transition"
                                                title="Hapus Pengguna">
                                            <i class="fa-regular fa-trash-can px-1"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">
                                Tidak ada data pengguna yang sesuai dengan pencarian/filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- MODAL EDIT BIODATA, ROLE & PAKET USER      -->
    <!-- ========================================== -->
    <template x-teleport="body">
        <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm overflow-y-auto">
            <div @click.away="editModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-xl w-full p-6 shadow-2xl my-8">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-user-pen text-amber-500"></i>
                        <span>Edit Data Lengkap Pengguna</span>
                    </h3>
                    <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-white">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form :action="'/admin/users/' + selectedUser.id" method="POST" class="space-y-4 pt-4">
                    @csrf
                    @method('PUT')

                    <!-- Baris 1: Nama & Email -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Nama Lengkap *</label>
                            <input type="text" name="name" x-model="selectedUser.name" required class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Alamat Email *</label>
                            <input type="email" name="email" x-model="selectedUser.email" required class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Baris 2: WhatsApp, Gender, Tanggal Lahir -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Nomor WhatsApp</label>
                            <input type="text" name="phone" x-model="selectedUser.phone" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Jenis Kelamin</label>
                            <select name="gender" x-model="selectedUser.gender" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white">
                                <option value="">- Belum Diatur -</option>
                                <option value="male">Laki-laki</option>
                                <option value="female">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Tanggal Lahir</label>
                            <input type="date" name="birth_date" x-model="selectedUser.birth_date" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Baris 3: Pengaturan Role & Langganan Paket -->
                    <div class="p-4 rounded-2xl bg-amber-500/5 border border-amber-500/20 space-y-3">
                        <div class="text-xs font-bold text-amber-500 flex items-center gap-1.5">
                            <i class="fa-solid fa-crown"></i> Hak Akses Role & Paket Langganan
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Role Akun *</label>
                                <select name="role" x-model="selectedUser.role" required class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                                    <option value="user">User Biasa</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Tier Paket</label>
                                <select name="plan_id" x-model="selectedUser.plan_id" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white">
                                    <option value="">Free (Tanpa Paket)</option>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Berlaku Sampai</label>
                                <input type="date" name="plan_expires_at" x-model="selectedUser.plan_expires_at" :disabled="!selectedUser.plan_id" class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white disabled:opacity-40">
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-400">
                            * Kosongkan kolom "Berlaku Sampai" jika paket yang dipilih aktif selamanya (Lifetime).
                        </p>
                    </div>

                    <!-- Baris 4: Reset Password Opsional -->
                    <div x-data="{ showNewPass: false }" class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            <i class="fa-solid fa-key text-emerald-500 mr-1"></i> Reset Password Pengguna (Opsional)
                        </label>
                        <!-- <div class="relative">
                            <input :type="showNewPass ? 'text' : 'password'" 
                                   name="password" 
                                   placeholder="Biarkan kosong jika tidak ingin mengubah password user" 
                                   class="w-full px-3.5 py-2 pr-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white">
                            <button type="button" @click="showNewPass = !showNewPass" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-200">
                                <i class="fa-solid text-xs" :class="showNewPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div> -->
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-extrabold shadow-lg shadow-amber-500/20">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- ========================================== -->
    <!-- MODAL KONFIRMASI HAPUS USER                -->
    <!-- ========================================== -->
    <template x-teleport="body">
        <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
            <div @click.away="deleteModalOpen = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl text-center space-y-4">
                <div class="w-14 h-14 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-500 flex items-center justify-center mx-auto text-2xl">
                    <i class="fa-solid fa-user-xmark"></i>
                </div>

                <div class="space-y-1">
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Hapus Akun Pengguna?</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Anda yakin ingin menghapus permanen akun <strong class="text-slate-900 dark:text-white" x-text="selectedUser.name"></strong> (<span x-text="selectedUser.email"></span>)? Seluruh aset brankas dan ahli waris milik pengguna ini akan ikut terhapus.
                    </p>
                </div>

                <form :action="'/admin/users/' + selectedUser.id" method="POST" class="flex items-center justify-center gap-2 pt-2">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-white text-xs font-extrabold shadow-lg shadow-rose-500/20">
                        Ya, Hapus Permanen
                    </button>
                </form>
            </div>
        </div>
    </template>

</div>
@endsection