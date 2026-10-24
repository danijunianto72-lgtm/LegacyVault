<!DOCTYPE html>
<html lang="id" 
      x-data="{ 
          darkMode: localStorage.getItem('theme') !== 'light',
          sidebarOpen: false,
          sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
          profileModalOpen: false,
          helpModalOpen: false,
          activeTab: new URLSearchParams(window.location.search).get('tab') || 'assets'
      }" 
      x-init="
          $watch('darkMode', val => {
              localStorage.setItem('theme', val ? 'dark' : 'light');
              document.documentElement.classList.toggle('dark', val);
          });
          $watch('sidebarCollapsed', val => {
              localStorage.setItem('sidebarCollapsed', val);
          });
      " 
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
        <!-- SIDEBAR (MOBILE DRAWER & DESKTOP COLLAPSIBLE) -->
        <!-- ========================================== -->
        <aside :class="[
                   sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                   sidebarCollapsed ? 'lg:w-20' : 'lg:w-72',
                   'w-72'
               ]"
               class="fixed inset-y-0 left-0 z-50 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between transition-all duration-300 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:shrink-0">
            
            <!-- BAGIAN ATAS SIDEBAR: Brand, Profil, Tema, & Navigasi -->
            <div class="p-3.5 space-y-4 overflow-y-auto overflow-x-hidden flex-1">
                
                <!-- 1. Brand Logo & Tombol Toggle Buka-Tutup Sidebar -->
                <div class="flex items-center justify-between px-1" :class="sidebarCollapsed ? 'lg:flex-col lg:gap-3 lg:justify-center' : ''">
                    <a href="{{ url('/') }}" class="flex items-center gap-2.5 font-bold text-lg tracking-tight" :title="sidebarCollapsed ? 'LegacyVault' : ''">
                        <span class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                            <i class="fa-solid fa-vault"></i>
                        </span>
                        <span x-show="!sidebarCollapsed" x-transition.opacity class="whitespace-nowrap lg:block">
                            Legacy<span class="text-emerald-600 dark:text-emerald-400">Vault</span>
                        </span>
                    </a>

                    <!-- Tombol Collapse (Khusus Desktop) -->
                    <button @click="sidebarCollapsed = !sidebarCollapsed" 
                            type="button" 
                            :title="sidebarCollapsed ? ' Perluas Sidebar' : 'Kecilkan Sidebar'"
                            class="hidden lg:flex w-8 h-8 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800/70 text-slate-500 hover:text-emerald-500 hover:border-emerald-500/50 items-center justify-center transition shrink-0">
                        <i class="fa-solid text-xs transition-transform duration-300" :class="sidebarCollapsed ? 'fa-angles-right' : 'fa-angles-left'"></i>
                    </button>

                    <!-- Tombol Close (Khusus Mobile) -->
                    <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- 2. KARTU FOTO PROFIL & TOGGLE TEMA (DI ATAS SEBELUM MENU BRANKAS) -->
                <div class="pt-2 pb-3 border-b border-slate-200 dark:border-slate-800 space-y-2.5">
                    
                    <!-- Kartu Profil Pengguna -->
                    <div @click="profileModalOpen = true; sidebarOpen = false" 
                         :title="sidebarCollapsed ? '{{ auth()->user()->name }} (Klik untuk detail profil)' : 'Klik untuk detail profil'"
                         :class="sidebarCollapsed ? 'lg:justify-center lg:p-2' : 'p-2.5'"
                         class="rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 hover:border-emerald-500/50 transition cursor-pointer flex items-center gap-3 group">
                        @if(auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-xl object-cover border border-emerald-500/40 shrink-0">
                        @else
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-500 font-bold shrink-0">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif
                        <div x-show="!sidebarCollapsed" class="flex-1 min-w-0">
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

                    <!-- Tombol Mode Terang / Gelap -->
                    <button @click="darkMode = !darkMode" 
                            type="button"
                            :title="darkMode ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap'"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'justify-between px-3'"
                            class="w-full py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/70 text-slate-700 dark:text-slate-300 hover:border-emerald-500 transition flex items-center text-xs font-medium">
                        <span class="flex items-center gap-2.5">
                            <i class="fa-solid text-sm w-5 text-center" :class="darkMode ? 'fa-sun text-amber-400' : 'fa-moon text-indigo-500'"></i>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap" x-text="darkMode ? 'Mode Terang' : 'Mode Gelap'"></span>
                        </span>
                        <span x-show="!sidebarCollapsed" class="text-[10px] px-2 py-0.5 rounded bg-slate-200/70 dark:bg-slate-800 text-slate-500 dark:text-slate-400">Tema</span>
                    </button>
                </div>

                <!-- 3. DAFTAR MENU NAVIGASI -->
                <nav class="space-y-1.5 text-sm">
                    @if(!auth()->user()->isAdmin())
                        <div x-show="!sidebarCollapsed" class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 whitespace-nowrap">
                            Menu Utama
                        </div>

                        <!-- Menu 1: Brankas & Daftar Aset (Gabungan Deadman Switch + Aset) -->
                        <a href="{{ request()->routeIs('dashboard') ? '#assets' : route('dashboard') . '?tab=assets' }}"
                           @if(request()->routeIs('dashboard')) @click.prevent="activeTab = 'assets'; sidebarOpen = false" @endif
                           :title="sidebarCollapsed ? 'Brankas & Daftar Aset' : ''"
                           :class="[
                               (activeTab === 'assets' && '{{ request()->routeIs('dashboard') }}') ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-semibold border-emerald-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 border-transparent',
                               sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'px-3.5'
                           ]"
                           class="flex items-center gap-3 py-2.5 rounded-xl border transition">
                            <i class="fa-solid fa-shield-halved w-5 text-center text-base shrink-0"></i>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Brankas & Daftar Aset</span>
                        </a>

                        <!-- Menu 2: Daftar Ahli Waris -->
                        <a href="{{ request()->routeIs('dashboard') ? '#beneficiaries' : route('dashboard') . '?tab=beneficiaries' }}"
                           @if(request()->routeIs('dashboard')) @click.prevent="activeTab = 'beneficiaries'; sidebarOpen = false" @endif
                           :title="sidebarCollapsed ? 'Daftar Ahli Waris' : ''"
                           :class="[
                               (activeTab === 'beneficiaries' && '{{ request()->routeIs('dashboard') }}') ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-semibold border-emerald-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 border-transparent',
                               sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'px-3.5'
                           ]"
                           class="flex items-center gap-3 py-2.5 rounded-xl border transition">
                            <i class="fa-solid fa-users-line w-5 text-center text-base shrink-0"></i>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Daftar Ahli Waris</span>
                        </a>

                        <!-- Menu 3: Log Aktivitas Keamanan -->
                        <a href="{{ request()->routeIs('dashboard') ? '#logs' : route('dashboard') . '?tab=logs' }}"
                           @if(request()->routeIs('dashboard')) @click.prevent="activeTab = 'logs'; sidebarOpen = false" @endif
                           :title="sidebarCollapsed ? 'Log Aktivitas Keamanan' : ''"
                           :class="[
                               (activeTab === 'logs' && '{{ request()->routeIs('dashboard') }}') ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-semibold border-emerald-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 border-transparent',
                               sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'px-3.5'
                           ]"
                           class="flex items-center gap-3 py-2.5 rounded-xl border transition">
                            <i class="fa-solid fa-clock-rotate-left w-5 text-center text-base shrink-0"></i>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Log Aktivitas Keamanan</span>
                        </a>

                        <div x-show="!sidebarCollapsed" class="pt-3 px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 whitespace-nowrap">
                            Layanan & Bantuan
                        </div>

                        <!-- Menu 4: Paket Langganan -->
                        <a href="{{ route('pricing') }}" 
                           :title="sidebarCollapsed ? 'Paket Langganan' : ''"
                           :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'px-3.5'"
                           class="flex items-center gap-3 py-2.5 rounded-xl border transition {{ request()->routeIs('pricing') ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-semibold border-emerald-500/30' : 'border-transparent text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                            <i class="fa-solid fa-gem w-5 text-center text-base shrink-0"></i>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Paket Langganan</span>
                        </a>
                    @else
                        <div x-show="!sidebarCollapsed" class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 whitespace-nowrap">
                            Panel Administrator
                        </div>

                        <!-- 1. Verifikasi Pembayaran (Sudah Ada Sebelumnya) -->
                        <a href="{{ route('admin.payments.index') }}" 
                           :title="sidebarCollapsed ? 'Verifikasi Pembayaran' : ''"
                           :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'px-3.5'"
                           class="flex items-center gap-3 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('admin.payments.*') ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 font-semibold border border-amber-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                            <i class="fa-solid fa-file-invoice-dollar w-5 text-center text-base shrink-0"></i>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Verifikasi Pembayaran</span>
                        </a>

                        <!-- TAMBAHKAN 2 MENU BARU INI TEPAT DI SINI: -->

                        <!-- 2. Pemantauan & Perpanjangan Masa Berlaku Aset -->
                        <a href="{{ route('admin.assets.index') }}" 
                           :title="sidebarCollapsed ? 'Masa Berlaku Aset' : ''"
                           :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'px-3.5'"
                           class="flex items-center gap-3 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('admin.assets.*') ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 font-semibold border border-amber-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                            <i class="fa-solid fa-hourglass-half w-5 text-center text-base shrink-0"></i>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Masa Berlaku Aset</span>
                        </a>

                        <!-- 3. Log Audit Sistem -->
                        <a href="{{ route('admin.logs.index') }}" 
                           :title="sidebarCollapsed ? 'Log Audit Sistem' : ''"
                           :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'px-3.5'"
                           class="flex items-center gap-3 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('admin.logs.*') ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 font-semibold border border-amber-500/30' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                            <i class="fa-solid fa-shield-halved w-5 text-center text-base shrink-0"></i>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Log Audit Sistem</span>
                        </a>
                    @endif

                    <!-- Menu 5: Pusat Bantuan -->
                    <button type="button"
                            @click="helpModalOpen = true; sidebarOpen = false"
                            :title="sidebarCollapsed ? 'Pusat Bantuan' : ''"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'px-3.5'"
                            class="w-full flex items-center gap-3 py-2.5 rounded-xl border border-transparent text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 transition text-left">
                        <i class="fa-solid fa-headset w-5 text-center text-base text-emerald-500 shrink-0"></i>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Pusat Bantuan</span>
                    </button>
                </nav>
            </div>

            <!-- BAGIAN BAWAH SIDEBAR: Tombol Logout Permanen -->
            <div class="p-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" 
                            :title="sidebarCollapsed ? 'Keluar dari Akun' : ''"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'px-3.5'"
                            class="w-full py-2.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-semibold transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-power-off text-sm shrink-0"></i>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Keluar dari Akun</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- ========================================== -->
        <!-- AREA KONTEN UTAMA (OTOMATIS MENYESUAIKAN)  -->
        <!-- ========================================== -->
        <div class="flex-1 flex flex-col min-w-0 transition-all duration-300">
            
            <!-- Header Bar Khusus Mobile -->
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
           <!-- ========================================================= -->
<!-- MODAL POP-UP FLASH NOTIFIKASI (SUCCESS, ERROR, & VALIDATION) -->
<!-- ========================================================= -->
@if(session('success') || session('error') || $errors->any())
<div x-data="{ 
        showAlertModal: true,
        init() {
            // Otomatis tutup setelah 4 detik jika sukses biasa
            @if(session('success'))
                setTimeout(() => { this.showAlertModal = false; }, 4000);
            @endif
        }
     }">
    <template x-teleport="body">
        <div x-show="showAlertModal" 
             x-cloak 
             class="fixed inset-0 z-[100] flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
            
            <div @click.away="showAlertModal = false" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="bg-white dark:bg-slate-900 border @if(session('success')) border-emerald-500/40 @else border-rose-500/40 @endif rounded-3xl max-w-sm sm:max-w-md w-full p-6 shadow-2xl space-y-4 text-center">
                
                <!-- Ikon Status -->
                @if(session('success'))
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-500 text-3xl shadow-inner animate-bounce">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="space-y-1.5">
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">Berhasil!</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                            {{ session('success') }}
                        </p>
                    </div>
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" 
                                @click="showAlertModal = false" 
                                class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition shadow-lg shadow-emerald-500/20">
                            Oke, Mengerti
                        </button>
                    </div>

                @elseif(session('error'))
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-rose-500/15 border border-rose-500/30 flex items-center justify-center text-rose-500 text-3xl shadow-inner">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                    <div class="space-y-1.5">
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">Terjadi Kendala</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                            {{ session('error') }}
                        </p>
                    </div>
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" 
                                @click="showAlertModal = false" 
                                class="w-full py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white font-extrabold text-xs transition shadow-lg shadow-rose-500/20">
                            Tutup
                        </button>
                    </div>

                @elseif($errors->any())
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-rose-500/15 border border-rose-500/30 flex items-center justify-center text-rose-500 text-3xl shadow-inner">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="space-y-2 text-left">
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white text-center">Terdapat Kesalahan Input</h3>
                        <div class="p-3 rounded-2xl bg-rose-500/10 border border-rose-500/20 max-h-44 overflow-y-auto pr-1">
                            <ul class="space-y-1.5 text-xs text-rose-600 dark:text-rose-400">
                                @foreach($errors->all() as $err)
                                    <li class="flex items-start gap-2">
                                        <i class="fa-solid fa-circle text-[6px] mt-1.5 shrink-0"></i>
                                        <span>{{ $err }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" 
                                @click="showAlertModal = false" 
                                class="w-full py-2.5 rounded-xl bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold text-xs transition">
                            Perbaiki Input
                        </button>
                    </div>
                @endif

            </div>
        </div>
    </template>
</div>
@endif

            <!-- Main Content -->
            <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
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
         x-data="{ 
            isEditing: false, 
            avatarPreview: '{{ auth()->user()->avatar_url ?? '' }}' 
         }" 
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
        
        <div @click.away="profileModalOpen = false; isEditing = false" 
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-5">
            
            <!-- Header Modal -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Foto Profil -->
                    <div class="relative group">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-500 text-xl font-bold overflow-hidden shadow-inner">
                            <template x-if="avatarPreview">
                                <img :src="avatarPreview" alt="Avatar" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!avatarPreview">
                                <span>{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            </template>
                        </div>
                        
                        <!-- Overlay Ganti Foto (Hanya muncul saat mode edit) -->
                        <label x-show="isEditing" 
                               class="absolute inset-0 bg-black/60 rounded-2xl flex flex-col items-center justify-center text-white text-[10px] cursor-pointer opacity-0 group-hover:opacity-100 transition">
                            <i class="fa-solid fa-camera mb-0.5"></i>
                            <span>Ganti</span>
                            <input type="file" 
                                   form="profileForm" 
                                   name="avatar" 
                                   accept="image/*" 
                                   class="hidden" 
                                   @change="
                                        const file = $event.target.files[0];
                                        if (file) {
                                            const reader = new FileReader();
                                            reader.onload = (e) => { avatarPreview = e.target.result; };
                                            reader.readAsDataURL(file);
                                        }
                                   ">
                        </label>
                    </div>

                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white leading-tight">
                            {{ auth()->user()->name }}
                        </h3>
                        <p class="text-xs text-slate-400 font-mono mt-0.5">
                            ID Brankas: #{{ str_pad(auth()->user()->id, 5, '0', STR_PAD_LEFT) }}
                        </p>
                    </div>
                </div>

                <button type="button" @click="profileModalOpen = false; isEditing = false" class="text-slate-400 hover:text-white transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Form Edit & Detail Data -->
            <form id="profileForm" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                @method('PUT')

                <!-- Nama Lengkap (Editable) -->
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3 text-xs">
                    <span class="text-slate-400 font-medium flex items-center gap-2 shrink-0">
                        <i class="fa-solid fa-user text-emerald-500"></i> Nama Lengkap
                    </span>
                    <template x-if="!isEditing">
                        <span class="font-bold text-slate-900 dark:text-white">{{ auth()->user()->name }}</span>
                    </template>
                    <template x-if="isEditing">
                        <input type="text" name="name" value="{{ auth()->user()->name }}" required class="px-2.5 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-emerald-500/50 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none w-full max-w-[200px] text-right">
                    </template>
                </div>

                <!-- Email (Editable) -->
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3 text-xs">
                    <span class="text-slate-400 font-medium flex items-center gap-2 shrink-0">
                        <i class="fa-solid fa-envelope text-emerald-500"></i> Email
                    </span>
                    <template x-if="!isEditing">
                        <span class="font-bold text-slate-900 dark:text-white">{{ auth()->user()->email }}</span>
                    </template>
                    <template x-if="isEditing">
                        <input type="email" name="email" value="{{ auth()->user()->email }}" required class="px-2.5 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-emerald-500/50 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none w-full max-w-[200px] text-right">
                    </template>
                </div>

                <!-- WhatsApp (Read-Only) -->
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-medium flex items-center gap-2">
                        <i class="fa-brands fa-whatsapp text-emerald-500"></i> WhatsApp
                    </span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ auth()->user()->phone ?? 'Belum diatur' }}</span>
                </div>

                <!-- Jenis Kelamin (Read-Only) -->
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-medium flex items-center gap-2">
                        <i class="fa-solid fa-venus-mars text-emerald-500"></i> Jenis Kelamin
                    </span>
                    <span class="font-bold text-slate-900 dark:text-white">
                        {{ auth()->user()->gender === 'male' ? 'Laki-laki' : (auth()->user()->gender === 'female' ? 'Perempuan' : 'Belum diatur') }}
                    </span>
                </div>

                <!-- Tanggal Lahir (Read-Only) -->
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-medium flex items-center gap-2">
                        <i class="fa-solid fa-calendar-days text-emerald-500"></i> Tanggal Lahir
                    </span>
                    <span class="font-bold text-slate-900 dark:text-white">
                        {{ auth()->user()->birth_date ? \Carbon\Carbon::parse(auth()->user()->birth_date)->format('d M Y') : 'Belum diatur' }}
                    </span>
                </div>

                <!-- Tier Paket (Read-Only) -->
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-medium flex items-center gap-2">
                        <i class="fa-solid fa-gem text-emerald-500"></i> Tier Paket
                    </span>
                    @if(!auth()->user()->isAdmin())
    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
        <span class="text-slate-400 font-medium flex items-center gap-2">
            <i class="fa-solid fa-gem text-emerald-500"></i> Tier Paket
        </span>
        <div class="flex items-center gap-2">
            <span class="font-bold text-emerald-500">{{ auth()->user()->plan->name ?? 'Free' }}</span>
            <a href="{{ url('/pricing') }}" class="px-2 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-400 font-semibold hover:bg-emerald-500/20 transition">Upgrade</a>
        </div>
    </div>
@endif
                </div>

                <!-- Tombol Navigasi Modal -->
                <div class="pt-2 flex items-center justify-end gap-2">
                    <template x-if="!isEditing">
                        <div class="flex items-center gap-2 w-full justify-end">
                            <button type="button" @click="profileModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                                Tutup
                            </button>
                            <button type="button" @click="isEditing = true" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition flex items-center gap-1.5 shadow-lg shadow-emerald-500/20">
                                <i class="fa-solid fa-pen-to-square"></i> Edit Profil
                            </button>
                        </div>
                    </template>
                    <template x-if="isEditing">
                        <div class="flex items-center gap-2 w-full justify-end">
                            <button type="button" @click="isEditing = false" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition flex items-center gap-1.5 shadow-lg shadow-emerald-500/20">
                                <i class="fa-solid fa-check"></i> Simpan
                            </button>
                        </div>
                    </template>
                </div>
            </form>

        </div>
    </div>
</template>
    <!-- ========================================== -->
    <!-- MODAL PUSAT BANTUAN                        -->
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

                    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-between gap-3">
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white">Butuh Bantuan Lebih Lanjut?</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">Hubungi tim legal & teknis kami via Email resmi</div>
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