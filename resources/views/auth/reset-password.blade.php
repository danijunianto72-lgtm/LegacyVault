<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Kata Sandi Baru - LegacyVault</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-7 shadow-2xl" x-data="{ showPass: false, showConfirm: false }">
        <div class="text-center mb-6">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center mx-auto mb-3 text-emerald-400">
                <i class="fa-solid fa-lock-open text-xl"></i>
            </div>
            <h1 class="text-lg font-extrabold text-white">Atur Ulang Kata Sandi</h1>
            <p class="text-xs text-slate-400 mt-1">
                Silakan buat kata sandi baru untuk mengamankan akun brankas Anda.
            </p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Alamat Email</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required readonly
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs text-slate-400 cursor-not-allowed">
                @error('email')
                    <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Kata Sandi Baru</label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'" name="password" required autofocus
                           placeholder="Minimal 8 karakter"
                           class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:border-emerald-500 focus:outline-none">
                    <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-white">
                        <i class="fa-solid text-xs" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                @error('password')
                    <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Konfirmasi Kata Sandi Baru</label>
                <div class="relative">
                    <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" required
                           placeholder="Ulangi kata sandi baru"
                           class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:border-emerald-500 focus:outline-none">
                    <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-white">
                        <i class="fa-solid text-xs" :class="showConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <button type="submit"
                    class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition shadow-lg shadow-emerald-500/20 cursor-pointer">
                Simpan Kata Sandi Baru
            </button>
        </form>
    </div>

</body>
</html>