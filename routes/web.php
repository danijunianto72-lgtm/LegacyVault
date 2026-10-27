<?php

use App\Http\Controllers\AdminAssetMonitoringController;
use App\Http\Controllers\AdminCashflowController;
use App\Http\Controllers\AdminLogController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DeadManSwitchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ForgotPasswordController;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect Halaman Utama
Route::get('/', function () {
    if (Auth::check()) {
        return Auth::user()->isAdmin()
            ? redirect()->route('admin.payments.index')
            : redirect()->route('dashboard');
    }
    return view('welcome');
})->name('welcome');

// Konfirmasi Email Bertanda Tangan (Publik)
Route::get('/switch/confirm-email/{user}', [DeadManSwitchController::class, 'confirmFromEmail'])
    ->name('switch.email-confirm')
    ->middleware('signed');

// Area Khusus Guest (Belum Login)
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        $demoUser = User::where('role', 'user')->first();
        $demoAdmin = User::where('role', 'admin')->first();
        return view('auth.login', compact('demoUser', 'demoAdmin'));
    })->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            ActivityLog::record('LOGIN', 'Berhasil masuk ke dalam sistem.');

            return Auth::user()->isAdmin()
                ? redirect()->route('admin.payments.index')->with('success', 'Selamat datang kembali, Admin Verifikator!')
                : redirect()->route('onboarding');
        }

        return back()->withErrors([
            'email' => 'Kombinasi email dan kata sandi tidak ditemukan.',
        ])->onlyInput('email');
    })->name('login.post');

    // Register & OTP
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/register/otp', [RegisteredUserController::class, 'showOtpForm'])->name('register.otp');
    Route::post('/register/otp', [RegisteredUserController::class, 'verifyOtp'])->name('register.otp.verify');
    Route::post('/register/otp/resend', [RegisteredUserController::class, 'resendOtp'])->name('register.otp.resend');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])
        ->name('password.request');

    // Proses kirim link ke email
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->name('password.email');

    // Form input password baru (dari link di email)
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])
        ->name('password.reset');

    // Proses simpan password baru
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])
        ->name('password.update');
});

// Logout
Route::post('/logout', function (Request $request) {
    ActivityLog::record('LOGOUT', 'Keluar dari sesi aplikasi.');
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login')->with('success', 'Anda telah keluar dari sesi.');
})->middleware('auth')->name('logout');

// Protected Area (Wajib Login)
Route::middleware('auth')->group(function () {

Route::post('/assets/{asset}/request-extension', [AssetController::class, 'requestExtension'])->name('assets.requestExtension');
    // Profil Pengguna (Mengatasi Route [profile.update] not defined)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update.put');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dead Man's Switch (API & Realtime)
    Route::post('/switch/checkin', [DeadManSwitchController::class, 'checkinAjax'])->name('switch.checkin');
    Route::post('/switch/simulate', [DeadManSwitchController::class, 'sendSimulationEmail'])->name('switch.simulate');
    Route::put('/switch/interval', [DeadManSwitchController::class, 'updateInterval'])->name('switch.interval');
    Route::get('/switch/status-check', [DeadManSwitchController::class, 'checkTimerStatus'])->name('switch.status-check');

    // Onboarding
    Route::get('/onboarding', function () {
        if (Auth::user()->isAdmin()) {
            return redirect()->route('admin.payments.index');
        }
        return view('onboarding');
    })->name('onboarding');

    // Dashboard & Manajemen Aset
    Route::get('/dashboard', [AssetController::class, 'index'])->name('dashboard');
    Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
    Route::post('/assets/{asset}/decrypt', [AssetController::class, 'decrypt'])->name('assets.decrypt');
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
    Route::put('/assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
    Route::post('/assets/{asset}/attachment', [AssetController::class, 'viewAttachment'])->name('assets.attachment');

    // Ahli Waris
    Route::post('/beneficiaries', [AssetController::class, 'storeBeneficiary'])->name('beneficiaries.store');
    Route::put('/beneficiaries/{beneficiary}', [AssetController::class, 'updateBeneficiary'])->name('beneficiaries.update');
    Route::delete('/beneficiaries/{beneficiary}', [AssetController::class, 'destroyBeneficiary'])->name('beneficiaries.destroy');

    // Langganan & Checkout
    Route::get('/pricing', [SubscriptionController::class, 'index'])->name('pricing');
    Route::post('/pricing/checkout', [SubscriptionController::class, 'checkout'])->name('pricing.checkout');

    // Admin Area
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/{payment}/approve', [AdminPaymentController::class, 'approve'])->name('payments.approve');
        Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('payments.reject');

        Route::get('/assets', [AdminAssetMonitoringController::class, 'index'])->name('assets.index');
        Route::post('/assets/{asset}/approve-extension', [AdminAssetMonitoringController::class, 'approveExtension'])->name('assets.approveExtension');
        Route::post('/assets/{asset}/reject-extension', [AdminAssetMonitoringController::class, 'rejectExtension'])->name('assets.rejectExtension');

        Route::get('/cashflow', [AdminCashflowController::class, 'index'])->name('cashflow.index');
        Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    });
});