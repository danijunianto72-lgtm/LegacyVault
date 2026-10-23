<!DOCTYPE html>
<html lang="id" 
      x-data="{ 
          darkMode: localStorage.getItem('theme') !== 'light',
          sidebarOpen: false,
          profileModalOpen: false,
          helpModalOpen: false,
          activeTab: 'assets'
      }" 
      x-init="$watch('darkMode', val => {
          localStorage.setItem('theme', val ? 'dark' : 'light');
          document.documentElement.classList.toggle('dark', val);
      })" 
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'LegacyVault - Digital Inheritance SaaS' }}</title>

    <!-- 1. SCRIPT ANTI-FLASH -->
    <script>
        if (localStorage.getItem('theme') !== 'light') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- 2. FALLBACK CSS INSTAN -->
    <style>
        @view-transition { navigation: auto; }
        [x-cloak] { display: none !important; }
        html { background-color: #f8fafc; color: #0f172a; }
        html.dark { background-color: #020617; color: #f1f5f9; color-scheme: dark; }
        body { background-color: inherit; color: inherit; }
    </style>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen flex flex-col selection:bg-emerald-500 selection:text-white">

@php
    $isMinimalPage = request()->routeIs('login') || request()->routeIs('register') || request()->routeIs('register.otp') || request()->routeIs('onboarding');
@endphp

@if(!$isMinimalPage && auth()->check())
    <div class="flex min-h-screen w-full">

        <!-- OVERLAY GELAP UNTUK SIDEBAR MOBILE -->
        <div x-show="sidebarOpen" 
             x-cloak
             x-transition.opacity
             @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm lg:hidden"></div>

        <!-- ========================================== -->
        <!-- SIDEBAR (MOBILE & DESKTOP)                 -->
        <!-- ========================================== -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed inset-y-0 left-0 z-50 w-72 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between transition-transform duration-300 ease-in-out lg:static lg:shrink-0">
            
            <!-- Bagian Atas Sidebar: Brand & Menu Navigasi -->
            <div class="p-5 space-y-6 overflow-y-auto flex-1">
                
                <!-- Brand Logo & Tombol Tutup Mobile -->
                <div class="flex items-center justify-between">
                    <a href="{{ url('/') }}" class="flex items-center gap-2.5 font-bold text-lg tracking-tight">
                        <span class="w-9 h-9 rounded-xl bg-emerald-500/15 border border-emerald-500/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <i class="fa-solid fa-vault"></i>
                        </span>
                        <span>Legacy<span class="text-emerald-600 dark:text-emerald-400">Vault</span></span>
                    </a>
                    <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Navigasi Menu -->
                <nav class="space-y-1.5 text-sm">
                    @if(!auth()->user()->isAdmin())
                        <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Menu Brankas
                        </div>

                        <!-- 1. Ringkasan / Brankas Saya -->
                        <a href="{{ request()->routeIs('dashboard') ? '#' : route('dashboard') }}"
                           @if(request()->routeIs('dashboard')) @click.prevent="activeTab = 'overview'; sidebarOpen = false" @endif
                           :class="(activeTab === 'overview' && '{{ request()->routeIs('dashboard') }}') ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-semibold border-emerald-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 border-transparent'"
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border transition">
                            <i class="fa-solid fa-shield-halved w-5 text-center"></i>
                            <span>Ringkasan Brankas</span>
                        </a>

                        <!-- 2. Daftar Aset -->
                        <a href="{{ request()->routeIs('dashboard') ? '#assets' : route('dashboard') . '?tab=assets' }}"
                           @if(request()->routeIs('dashboard')) @click.prevent="activeTab = 'assets'; sidebarOpen = false" @endif
                           :class="activeTab === 'assets' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-semibold border-emerald-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 border-transparent'"
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border transition">
                            <i class="fa-solid fa-key w-5 text-center"></i>
                            <span>Daftar Aset</span>
                        </a>

                        <!-- 3. Daftar Ahli Waris -->
                        <a href="{{ request()->routeIs('dashboard') ? '#beneficiaries' : route('dashboard') . '?tab=beneficiaries' }}"
                           @if(request()->routeIs('dashboard')) @click.prevent="activeTab = 'beneficiaries'; sidebarOpen = false" @endif
                           :class="activeTab === 'beneficiaries' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-semibold border-emerald-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 border-transparent'"
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border transition">
                            <i class="fa-solid fa-users-line w-5 text-center"></i>
                            <span>Daftar Ahli Waris</span>
                        </a>

                        <!-- 4. Log Aktivitas Keamanan -->
                        <a href="{{ request()->routeIs('dashboard') ? '#logs' : route('dashboard') . '?tab=logs' }}"
                           @if(request()->routeIs('dashboard')) @click.prevent="activeTab = 'logs'; sidebarOpen = false" @endif
                           :class="activeTab === 'logs' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-semibold border-emerald-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 border-transparent'"
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border transition">
                            <i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>
                            <span>Log Aktivitas Keamanan</span>
                        </a>

                        <div class="pt-4 px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Layanan & Dukungan
                        </div>

                        <!-- 5. Paket Langganan -->
                        <a href="{{ route('pricing') }}" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl border transition {{ request()->routeIs('pricing') ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-semibold border-emerald-500/30' : 'border-transparent text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                            <i class="fa-solid fa-gem w-5 text-center"></i>
                            <span>Paket Langganan</span>
                        </a>
                    @else
                        <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Panel Administrator
                        </div>
                        <a href="{{ route('admin.payments.index') }}" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                            <i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i>
                            <span>Verifikasi Pembayaran</span>
                        </a>
                    @endif

                    <!-- 6. Pusat Bantuan (Muncul untuk Semua User) -->
                    <button type="button"
                            @click="helpModalOpen = true; sidebarOpen = false"
                            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl border border-transparent text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 transition text-left">
                        <i class="fa-solid fa-headset w-5 text-center text-emerald-500"></i>
                        <span>Pusat Bantuan</span>
                    </button>
                </nav>
            </div>

            <!-- Bagian Bawah Sidebar: Tema, Info Profil & Logout -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 space-y-3 bg-slate-50/50 dark:bg-slate-950/40">
                
                <!-- Toggle Tema -->
                <button @click="darkMode = !darkMode" 
                        type="button"
                        class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:border-emerald-500 transition flex items-center justify-between text-xs font-medium">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid" :class="darkMode ? 'fa-sun text-amber-400' : 'fa-moon text-indigo-600'"></i>
                        <span x-text="darkMode ? 'Mode Terang' : 'Mode Gelap'"></span>
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">Ubah</span>
                </button>

                <!-- Kartu Profil (Klik untuk Buka Modal Profil Lengkap) -->
                <div @click="profileModalOpen = true; sidebarOpen = false" 
                     class="p-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-emerald-500/50 transition cursor-pointer flex items-center gap-3 group">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-xl object-cover border border-emerald-500/40 shrink-0">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-500 font-bold shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-slate-900 dark:text-white truncate group-hover:text-emerald-500 transition flex items-center gap-1">
                            <span class="truncate">{{ auth()->user()->name }}</span>
                            <i class="fa-solid fa-circle-info text-[10px] text-slate-400 group-hover:text-emerald-500 shrink-0"></i>
                        </div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                            @if(auth()->user()->isAdmin())
                                Admin Verifikator
                            @else
                                Tier: <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ auth()->user()->currentPlan()->name }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tombol Logout -->
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-3.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-semibold transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-power-off"></i>
                        <span>Keluar dari Akun</span>
                    </button>
                </form>

            </div>
        </aside>

        <!-- ========================================== -->
        <!-- AREA KONTEN UTAMA                          -->
        <!-- ========================================== -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- Header Bar Khusus Mobile (Untuk Buka Sidebar) -->
            <header class="lg:hidden sticky top-0 z-30 h-16 px-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <button @click="sidebarOpen = true" type="button" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <a href="{{ url('/') }}" class="flex items-center gap-2 font-bold text-base">
                    <span class="w-8 h-8 rounded-xl bg-emerald-500/15 border border-emerald-500/40 flex items-center justify-center text-emerald-500">
                        <i class="fa-solid fa-vault text-sm"></i>
                    </span>
                    <span>Legacy<span class="text-emerald-500">Vault</span></span>
                </a>
                <button @click="profileModalOpen = true" type="button" class="w-9 h-9 rounded-xl overflow-hidden border border-emerald-500/40 flex items-center justify-center bg-emerald-500/10 text-emerald-500">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" class="w-full h-full object-cover">
                    @else
                        <i class="fa-solid fa-user text-sm"></i>
                    @endif
                </button>
            </header>

            <!-- Flash Notifications -->
            <div class="max-w-6xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4">
                @if(session('success'))
                    <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-sm flex items-center gap-2.5">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-sm flex items-center gap-2.5">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500 text-base"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if($errors->any())
                    <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-sm">
                        <div class="font-semibold mb-1 flex items-center gap-2">
                            <i class="fa-solid fa-circle-xmark"></i>
                            <span>Terdapat kesalahan input:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Main Content -->
            <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
                @yield('content')
            </main>

            <footer class="border-t border-slate-200 dark:border-slate-900 py-5 text-center text-xs text-slate-500">
                <i class="fa-solid fa-lock text-emerald-500 mr-1"></i> LegacyVault SaaS &bull; Enkripsi Brankas Digital AES-256-CBC
            </footer>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL DETAIL INFO PROFIL LENGKAP           -->
    <!-- ========================================== -->
    <template x-teleport="body">
        <div x-show="profileModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
            <div @click.away="profileModalOpen = false" 
                 class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-5">
                
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3.5">
                        @if(auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" class="w-14 h-14 rounded-2xl object-cover border-2 border-emerald-500 shadow-md">
                        @else
                            <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-500 text-xl font-bold">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">{{ auth()->user()->name }}</h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">ID Brankas: #{{ str_pad(auth()->user()->id, 5, '0', STR_PAD_LEFT) }}</p>
                        </div>
                    </div>
                    <button @click="profileModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition p-1">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="space-y-2.5 max-h-[60vh] overflow-y-auto pr-1">
                    <!-- Email -->
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 flex items-center gap-2">
                            <i class="fa-regular fa-envelope text-emerald-500 w-4"></i> Email
                        </span>
                        <span class="text-xs font-bold text-slate-900 dark:text-white">{{ auth()->user()->email }}</span>
                    </div>

                    <!-- WhatsApp -->
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 flex items-center gap-2">
                            <i class="fa-brands fa-whatsapp text-emerald-500 w-4"></i> WhatsApp
                        </span>
                        <span class="text-xs font-bold text-slate-900 dark:text-white">{{ auth()->user()->phone ?? 'Belum diatur' }}</span>
                    </div>

                    <!-- Jenis Kelamin -->
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 flex items-center gap-2">
                            <i class="fa-solid fa-venus-mars text-emerald-500 w-4"></i> Jenis Kelamin
                        </span>
                        <span class="text-xs font-bold text-slate-900 dark:text-white">
                            {{ auth()->user()->gender === 'male' ? 'Laki-laki' : (auth()->user()->gender === 'female' ? 'Perempuan' : 'Belum diatur') }}
                        </span>
                    </div>

                    <!-- Tanggal Lahir & Usia -->
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 flex items-center gap-2">
                            <i class="fa-regular fa-calendar text-emerald-500 w-4"></i> Tanggal Lahir
                        </span>
                        <span class="text-xs font-bold text-slate-900 dark:text-white">
                            @if(auth()->user()->birth_date)
                                {{ \Carbon\Carbon::parse(auth()->user()->birth_date)->translatedFormat('d M Y') }} ({{ \Carbon\Carbon::parse(auth()->user()->birth_date)->age }} thn)
                            @else
                                Belum diatur
                            @endif
                        </span>
                    </div>

                    <!-- Tier Paket -->
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 flex items-center gap-2">
                            <i class="fa-solid fa-gem text-emerald-500 w-4"></i> Tier Paket
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                {{ auth()->user()->isAdmin() ? 'Administrator' : auth()->user()->currentPlan()->name }}
                            </span>
                            @if(!auth()->user()->isAdmin())
                                <a href="{{ route('pricing') }}" class="px-2.5 py-1 rounded-lg bg-emerald-500/15 text-emerald-500 text-[11px] font-bold hover:bg-emerald-500/25">Upgrade</a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <button type="button" @click="profileModalOpen = false" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- ========================================== -->
    <!-- MODAL PUSAT BANTUAN (SAMA SEPERTI LANDING) -->
    <!-- ========================================== -->
    <template x-teleport="body">
        <div x-show="helpModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
            <div @click.away="helpModalOpen = false" 
                 class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-5">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-emerald-500">
                            <i class="fa-solid fa-headset text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Pusat Bantuan LegacyVault</h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Dukungan teknis, keamanan enkripsi & konsultasi warisan</p>
                        </div>
                    </div>
                    <button @click="helpModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition p-1">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="space-y-3 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-1">
                        <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-shield-halved text-emerald-500"></i> Bagaimana cara kerja Sakelar Otomatis?
                        </div>
                        <p class="text-slate-500 dark:text-slate-400">
                            Sistem mengirimkan tautan Check-in ke Email/WhatsApp Anda secara berkala. Jika melewati tenggat waktu tanpa respon, akses brankas diteruskan otomatis ke Ahli Waris terdaftar.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-1">
                        <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-lock text-emerald-500"></i> Apakah aset saya bisa dilihat oleh admin?
                        </div>
                        <p class="text-slate-500 dark:text-slate-400">
                            Tidak. Seluruh data dienkripsi menggunakan standar AES-256-CBC. Hanya Anda dan Ahli Waris yang ditunjuk yang dapat membuka isi brankas.
                        </p>
                    </div>

                    <!-- Kontak Support Resmi -->
                    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-between gap-3">
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white">Butuh Bantuan Lebih Lanjut?</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">Hubungi tim legal & teknis kami via Email atau WhatsApp</div>
                        </div>
                        <a href="mailto:helplegalcyvault@gmail.com" class="px-3.5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shrink-0 transition">
                            Kirim Email
                        </a>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <button type="button" @click="helpModalOpen = false" class="px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </template>

@else
    <!-- Tampilan Minimal Tanpa Sidebar (Untuk Login, Register, OTP, Onboarding) -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>
@endif

</body>
</html>