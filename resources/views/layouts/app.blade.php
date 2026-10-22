<!DOCTYPE html>
<html lang="id" 
      x-data="{ 
          darkMode: localStorage.getItem('theme') !== 'light',
          mobileMenuOpen: false 
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

    <!-- 1. SCRIPT ANTI-FLASH: Berjalan instan sebelum halaman dirender untuk mengunci class 'dark' -->
    <script>
        if (localStorage.getItem('theme') !== 'light') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- 2. FALLBACK CSS INSTAN: Memberi warna background sebelum Tailwind CDN selesai di-generate -->
    <style>
        @view-transition {
            navigation: auto;
        }
        [x-cloak] { display: none !important; }
        
        /* Warna dasar instan (slate-50 untuk terang, slate-950 untuk gelap) */
        html {
            background-color: #f8fafc;
            color: #0f172a;
        }
        html.dark {
            background-color: #020617;
            color: #f1f5f9;
            color-scheme: dark;
        }
        body {
            background-color: inherit;
            color: inherit;
        }
    </style>

    <!-- Tailwind CSS CDN dengan konfigurasi Dark Mode Class -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen flex flex-col selection:bg-emerald-500 selection:text-white transition-colors duration-200">
@unless(request()->routeIs('login') || request()->routeIs('register') || request()->routeIs('register.otp') || request()->routeIs('onboarding'))    <!-- Top Navigation Bar Responsif -->
    <header x-data="{ profileModalOpen: false, mobileMenuOpen: false }" class="border-b border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/90 backdrop-blur sticky top-0 z-40 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="h-16 flex items-center justify-between">
                
                <!-- Kiri: Brand & Navigasi Desktop -->
                <div class="flex items-center gap-6">
                    <a href="{{ url('/') }}" class="flex items-center gap-2.5 font-bold text-lg tracking-tight">
                        <span class="w-9 h-9 rounded-xl bg-emerald-500/15 border border-emerald-500/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <i class="fa-solid fa-vault"></i>
                        </span>
                        <span>Legacy<span class="text-emerald-600 dark:text-emerald-400">Vault</span></span>
                    </a>

                    @auth
                        <!-- Menu Desktop (Tampil di layar md ke atas) -->
                        <nav class="hidden md:flex items-center gap-1 text-sm">
                            @if(!auth()->user()->isAdmin())
                                <a href="{{ route('dashboard') }}" 
                                   class="px-3.5 py-2 rounded-xl font-medium transition flex items-center gap-2 {{ request()->routeIs('dashboard') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    <span>Brankas Saya</span>
                                </a>
                                <a href="{{ route('pricing') }}" 
                                   class="px-3.5 py-2 rounded-xl font-medium transition flex items-center gap-2 {{ request()->routeIs('pricing') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                                    <i class="fa-solid fa-gem"></i>
                                    <span>Paket Langganan</span>
                                </a>
                            @else
                                <a href="{{ route('admin.payments.index') }}" 
                                   class="px-3.5 py-2 rounded-xl font-medium transition flex items-center gap-2 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                    <span>Verifikasi Pembayaran</span>
                                </a>
                            @endif
                        </nav>
                    @endauth
                </div>

                <!-- Kanan: Theme Switcher, User Info & Logout (Desktop) -->
                <div class="hidden md:flex items-center gap-3">
                    <!-- Toggle Dark / Light Mode -->
                    <button @click="darkMode = !darkMode" 
                            type="button"
                            title="Ubah Tema Tampilan"
                            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800/70 text-slate-700 dark:text-slate-300 hover:border-emerald-500 transition flex items-center gap-2 text-xs font-medium">
                        <i class="fa-solid" :class="darkMode ? 'fa-sun text-amber-400' : 'fa-moon text-indigo-600'"></i>
                        <span x-text="darkMode ? 'Mode Putih' : 'Mode Gelap'"></span>
                    </button>

                    @auth
                        <div class="h-6 w-px bg-slate-200 dark:bg-slate-800 mx-1"></div>

                        <!-- Tombol Profil Pengguna -->
                        <button type="button" 
                                @click="profileModalOpen = true"
                                title="Klik untuk melihat info profil lengkap"
                                class="text-right p-1.5 px-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/60 transition group cursor-pointer border border-transparent hover:border-slate-200 dark:hover:border-slate-700">
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center justify-end gap-1.5 group-hover:text-emerald-500 transition">
                                <i class="fa-solid {{ auth()->user()->isAdmin() ? 'fa-user-tie text-amber-500' : 'fa-circle-user text-emerald-500' }}"></i>
                                <span>{{ auth()->user()->name }}</span>
                                <i class="fa-solid fa-circle-info text-[10px] text-slate-400 group-hover:text-emerald-500"></i>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                @if(auth()->user()->isAdmin())
                                    Administrator Verifikasi
                                @else
                                    Tier: <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ auth()->user()->currentPlan()->name }}</span>
                                @endif
                            </div>
                        </button>

                        <form action="{{ route('logout') }}" method="POST" class="ml-2">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-semibold transition flex items-center gap-1.5">
                                <i class="fa-solid fa-power-off"></i>
                                <span>Keluar</span>
                            </button>
                        </form>
                    @endauth
                </div>

                <!-- Tombol Hamburger & Theme (Mobile) -->
                <div class="flex items-center gap-2 md:hidden">
                    <button @click="darkMode = !darkMode" 
                            type="button"
                            class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <i class="fa-solid" :class="darkMode ? 'fa-sun text-amber-400' : 'fa-moon text-indigo-600'"></i>
                    </button>

                    @auth
                        <button @click="mobileMenuOpen = !mobileMenuOpen" 
                                type="button"
                                class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:border-emerald-500 focus:outline-none">
                            <i class="fa-solid text-lg" :class="mobileMenuOpen ? 'fa-xmark' : 'fa-bars'"></i>
                        </button>
                    @endauth
                </div>

            </div>
        </div>

        <!-- Dropdown Menu Mobile -->
        @auth
            <div x-show="mobileMenuOpen" 
                 x-cloak
                 x-transition
                 @click.away="mobileMenuOpen = false"
                 class="md:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 pt-3 pb-4 space-y-3">
                
                <!-- Info Akun di Mobile (Dapat diklik untuk membuka modal profil) -->
                <div @click="profileModalOpen = true; mobileMenuOpen = false" class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800 cursor-pointer hover:opacity-80 transition">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-500">
                            <i class="fa-solid {{ auth()->user()->isAdmin() ? 'fa-user-tie' : 'fa-user' }}"></i>
                        </div>
                        <div>
                            <div class="text-sm font-bold flex items-center gap-1.5">
                                <span>{{ auth()->user()->name }}</span>
                                <i class="fa-solid fa-circle-info text-xs text-emerald-500"></i>
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                @if(auth()->user()->isAdmin())
                                    Role: Admin Verifikator
                                @else
                                    Paket: <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ auth()->user()->currentPlan()->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Lihat Profil &rarr;</span>
                </div>

                <!-- Link Navigasi Mobile -->
                <div class="space-y-1.5">
                    @if(!auth()->user()->isAdmin())
                        <a href="{{ route('dashboard') }}" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            <i class="fa-solid fa-shield-halved w-5 text-center"></i>
                            <span>Brankas Saya</span>
                        </a>
                        <a href="{{ route('pricing') }}" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('pricing') ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            <i class="fa-solid fa-gem w-5 text-center"></i>
                            <span>Paket Langganan</span>
                        </a>
                    @else
                        <a href="{{ route('admin.payments.index') }}" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium bg-amber-500/15 text-amber-600 dark:text-amber-400">
                            <i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i>
                            <span>Verifikasi Pembayaran</span>
                        </a>
                    @endif
                </div>

                <!-- Tombol Logout Mobile -->
                <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-2.5 px-3.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 text-sm font-semibold flex items-center justify-center gap-2">
                            <i class="fa-solid fa-power-off"></i>
                            <span>Keluar dari Akun</span>
                        </button>
                    </form>
                </div>
            </div>
        @endauth

        <!-- MODAL DETAIL INFO PROFIL -->
       <!-- MODAL DETAIL INFO PROFIL (DITELEPORT KE BODY AGAR PRESISI DI TENGAH) -->
        @auth
            <template x-teleport="body">
                <div x-show="profileModalOpen" 
                     x-cloak 
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm">
                    <div @click.away="profileModalOpen = false" 
                         class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-6">
                        
                        <!-- Header Modal -->
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                    <i class="fa-solid fa-id-badge"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Informasi Profil Akun</h3>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Data akun terdaftar di sistem LegacyVault</p>
                                </div>
                            </div>
                            <button @click="profileModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition p-1">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <!-- Isi Data Profil (Per Baris) -->
                        <div class="space-y-3">
                            <!-- Nama Lengkap -->
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <div class="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5">
                                        <i class="fa-regular fa-user text-emerald-500"></i>
                                        <span>NAMA LENGKAP</span>
                                    </div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ auth()->user()->name }}</div>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <div class="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5">
                                        <i class="fa-regular fa-envelope text-emerald-500"></i>
                                        <span>ALAMAT EMAIL</span>
                                    </div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ auth()->user()->email }}</div>
                                </div>
                            </div>

                            <!-- Nomor WhatsApp -->
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <div class="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5">
                                        <i class="fa-brands fa-whatsapp text-emerald-500"></i>
                                        <span>NOMOR WHATSAPP</span>
                                    </div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ auth()->user()->phone ?? 'Belum diatur' }}</div>
                                </div>
                            </div>

                            <!-- Status Paket / Role -->
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <div class="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5">
                                        <i class="fa-solid fa-gem text-emerald-500"></i>
                                        <span>TIER / PAKET SAAT INI</span>
                                    </div>
                                    <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-2">
                                        @if(auth()->user()->isAdmin())
                                            <span>Administrator Verifikator</span>
                                        @else
                                            <span>{{ auth()->user()->currentPlan()->name }}</span>
                                        @endif
                                    </div>
                                </div>
                                @if(!auth()->user()->isAdmin())
                                    <a href="{{ route('pricing') }}" @click="profileModalOpen = false" class="px-3 py-1.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold transition">
                                        Upgrade
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- Footer Modal -->
                        <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                            <button type="button" 
                                    @click="profileModalOpen = false" 
                                    class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition">
                                Tutup
                            </button>
                        </div>

                    </div>
                </div>
            </template>
        @endauth
    </header>
@endunless
    <!-- Flash Notifications -->
    <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4">
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-sm flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-sm flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-triangle-exclamation text-rose-500 text-base"></i>
                    <span>{{ session('error') }}</span>
                </div>
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
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 dark:border-slate-900 py-6 text-center text-xs text-slate-500">
        <i class="fa-solid fa-lock text-emerald-500 mr-1"></i> LegacyVault SaaS &bull; Enkripsi Brankas Digital AES-256-CBC
    </footer>

</body>
</html>