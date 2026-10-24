<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\Beneficiary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Query Aset dengan Filter Kategori, Ahli Waris, dan Pencarian
        $query = $user->assets()->with('beneficiaries')->latest();

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('beneficiary_id')) {
            $query->whereHas('beneficiaries', function ($q) use ($request) {
                $q->where('beneficiaries.id', $request->beneficiary_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        $assets = $query->get();
        $totalAssetsCount = $user->assets()->count();
        $beneficiaries = $user->beneficiaries()->latest()->get();
        $currentPlan = $user->currentPlan();
        $subscription = $user->subscriptions()->where('status', 'active')->latest()->first();
        // Ambil 15 Log Aktivitas Terakhir Milik User Ini
        // Ambil query log per 10 item
$activityLogs = $user->activityLogs()
    ->latest()
    ->paginate(10, ['*'], 'log_page')
    ->withQueryString();
// Di dalam method index(), sebelum return view:
    if (!$user->next_check_in_deadline) {
        $user->confirmCheckIn();
    }
        return view('dashboard', compact(
            'assets',
            'totalAssetsCount',
            'beneficiaries',
            'currentPlan',
            'subscription',
            'activityLogs'
        ));
    }

    

    public function decrypt(Request $request, Asset $asset): JsonResponse
    {
        abort_unless($asset->user_id === $request->user()->id, 403);

        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, $request->user()->password)) {
            ActivityLog::record('FAILED_DECRYPT', "Gagal membuka enkripsi aset '{$asset->title}' (Kata sandi salah).");
            return response()->json([
                'message' => 'Kata sandi akun yang Anda masukkan salah!',
            ], 422);
        }

        ActivityLog::record('DECRYPT_ASSET', "Membuka dekripsi aset rahasia: {$asset->title}");

        return response()->json([
            'secret_content' => $asset->secret_content,
        ]);
    }

    public function store(Request $request): RedirectResponse
{
    $user = $request->user();

    if (!$user->canCreateAsset()) {
        return back()->with('error', 'Kuota brankas Anda telah penuh! Silakan upgrade paket langganan.');
    }

    $isLifetime = $request->boolean('is_lifetime');

    $validated = $request->validate([
        'title' => 'required|string|max:150',
        'category' => 'required|in:Finansial,Dokumen Rahasia,Kredensial Akun,Pesan Wasiat',
        'secret_content' => 'required|string',
        'attachment' => 'nullable|file|max:5120',
        'beneficiary_ids' => 'nullable|array',
        'beneficiary_ids.*' => 'exists:beneficiaries,id',
        'start_date' => 'nullable|date',
        'is_lifetime' => 'nullable|boolean',
        'expires_at' => [$isLifetime ? 'nullable' : 'required', 'nullable', 'date', 'after_or_equal:start_date'],
    ], [
        'expires_at.required' => 'Tanggal kadaluarsa wajib diisi jika aset tidak disetel selamanya.',
        'expires_at.after_or_equal' => 'Tanggal kadaluarsa tidak boleh mendahului tanggal mulai.',
    ]);

    $path = null;
    if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
        $path = $request->file('attachment')->store('', 'supabase'); 
    }

    $asset = $user->assets()->create([
        'title' => $validated['title'],
        'category' => $validated['category'],
        'encrypted_payload' => Crypt::encryptString($validated['secret_content']),
        'attachment_path' => $path,
        'start_date' => $validated['start_date'] ?? null,
        'is_lifetime' => $isLifetime,
        'expires_at' => $isLifetime ? null : ($validated['expires_at'] ?? null),
    ]);

    if (!empty($validated['beneficiary_ids'])) {
        $validBeneficiaryIds = $user->beneficiaries()
            ->whereIn('id', $validated['beneficiary_ids'])
            ->pluck('id');
        $asset->beneficiaries()->sync($validBeneficiaryIds);
    }

    ActivityLog::record('CREATE_ASSET', "Menambahkan aset terenkripsi baru: {$asset->title} ({$asset->category})");

    return back()->with('success', 'Aset dan berkas rahasia berhasil dienkripsi dan disimpan.');
}

public function update(Request $request, Asset $asset): RedirectResponse
{
    $user = $request->user();
    abort_unless($asset->user_id === $user->id, 403);

    $isLifetime = $request->boolean('is_lifetime');

    $validated = $request->validate([
        'account_password' => 'required|string',
        'title' => 'required|string|max:150',
        'category' => 'required|in:Finansial,Dokumen Rahasia,Kredensial Akun,Pesan Wasiat',
        'secret_content' => 'nullable|string',
        'attachment' => 'nullable|file|max:5120',
        'beneficiary_ids' => 'nullable|array',
        'beneficiary_ids.*' => 'exists:beneficiaries,id',
        'start_date' => 'nullable|date',
        'is_lifetime' => 'nullable|boolean',
        'expires_at' => [$isLifetime ? 'nullable' : 'required', 'nullable', 'date', 'after_or_equal:start_date'],
    ], [
        'account_password.required' => 'Kata sandi akun wajib diisi untuk mengedit aset.',
        'expires_at.required' => 'Tanggal kadaluarsa wajib diisi jika aset tidak disetel selamanya.',
        'expires_at.after_or_equal' => 'Tanggal kadaluarsa tidak boleh mendahului tanggal mulai.',
    ]);

    if (!Hash::check($validated['account_password'], $user->password)) {
        return back()->with('error', 'Gagal menyimpan perubahan: Kata sandi akun yang Anda masukkan salah!');
    }

    $path = $asset->attachment_path;
    if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
        if ($path) {
            Storage::disk('supabase')->delete($path);
        }
        $path = $request->file('attachment')->store('', 'supabase');
    }

    $updateData = [
        'title' => $validated['title'],
        'category' => $validated['category'],
        'attachment_path' => $path,
        'start_date' => $validated['start_date'] ?? null,
        'is_lifetime' => $isLifetime,
        'expires_at' => $isLifetime ? null : ($validated['expires_at'] ?? null), // <-- TAMBAHKAN BARIS INI
        'extension_status' => 'none',
    ];

    if (!empty($validated['secret_content'])) {
        $updateData['encrypted_payload'] = Crypt::encryptString($validated['secret_content']);
    }

    $asset->update($updateData);

    if (isset($validated['beneficiary_ids'])) {
        $validBeneficiaryIds = $user->beneficiaries()
            ->whereIn('id', $validated['beneficiary_ids'])
            ->pluck('id');
        $asset->beneficiaries()->sync($validBeneficiaryIds);
    }

    ActivityLog::record('UPDATE_ASSET', "Memperbarui aset brankas: {$asset->title} ({$asset->category})");

    return back()->with('success', 'Perubahan data aset berhasil disimpan.');
}

public function destroy(Request $request, Asset $asset): RedirectResponse
{
    $user = $request->user();
    abort_unless($asset->user_id === $user->id, 403);

    $validated = $request->validate([
        'account_password' => 'required|string',
    ], [
        'account_password.required' => 'Kata sandi akun wajib diisi untuk menghapus aset.',
    ]);

    if (!Hash::check($validated['account_password'], $user->password)) {
        return back()->with('error', 'Gagal menghapus aset: Kata sandi akun yang Anda masukkan salah!');
    }

    $title = $asset->title;

    if ($asset->attachment_path) {
        Storage::disk('supabase')->delete($asset->attachment_path);
    }

    $asset->beneficiaries()->detach();
    $asset->delete();

    ActivityLog::record('DELETE_ASSET', "Menghapus permanen aset dari brankas: {$title}");

    return back()->with('success', 'Aset berhasil dihapus dari brankas.');
}

    public function storeBeneficiary(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'relationship' => 'required|string|max:60',
            'email' => 'required|email|max:120',
            'phone' => 'required|string|max:30',
        ]);

        $ben = $request->user()->beneficiaries()->create($validated);

        ActivityLog::record('ADD_BENEFICIARY', "Menambahkan ahli waris baru: {$ben->name} ({$ben->relationship})");

        return back()->with('success', 'Data ahli waris berhasil ditambahkan.');
    }
public function viewAttachment(Request $request, Asset $asset)
    {
        abort_unless($asset->user_id === $request->user()->id, 403);

        $request->validate(['password' => 'required|string']);

        if (!Hash::check($request->password, $request->user()->password)) {
            ActivityLog::record('FAILED_ATTACHMENT', "Gagal membuka lampiran aset '{$asset->title}' (Kata sandi salah).");
            return response()->json(['message' => 'Kata sandi akun yang Anda masukkan salah!'], 422);
        }

        if (!$asset->attachment_path || !Storage::disk('supabase')->exists($asset->attachment_path)) {
            return response()->json(['message' => 'Berkas lampiran tidak ditemukan di penyimpanan server.'], 404);
        }

        ActivityLog::record('VIEW_ATTACHMENT', "Membuka lampiran berkas terenkripsi dari aset: {$asset->title}");

        return response()->json([
            'download_url' => Storage::disk('supabase')->url($asset->attachment_path),
        ]);
    }

    public function updateBeneficiary(Request $request, Beneficiary $beneficiary): RedirectResponse
    {
        abort_unless($beneficiary->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'relationship' => 'required|string|max:60',
            'email' => 'required|email|max:120',
            'phone' => 'required|string|max:30',
        ]);

        $beneficiary->update($validated);

        ActivityLog::record('UPDATE_BENEFICIARY', "Memperbarui data ahli waris: {$beneficiary->name} ({$beneficiary->relationship})");

        return back()->with('success', 'Data ahli waris berhasil diperbarui.');
    }
    public function destroyBeneficiary(Request $request, Beneficiary $beneficiary): RedirectResponse
    {
        abort_unless($beneficiary->user_id === $request->user()->id, 403);

        $name = $beneficiary->name;
        $beneficiary->delete();

        ActivityLog::record('DELETE_BENEFICIARY', "Menghapus data ahli waris: {$name}");

        return back()->with('success', 'Data ahli waris berhasil dihapus.');
    }
public function manualCheckIn(Request $request)
    {
        $user = $request->user();
        $user->confirmCheckIn();

        ActivityLog::record('CHECK_IN', "Melakukan konfirmasi manual status kesehatan");

        $lastCheckIn = now();
        $deadline = $user->next_check_in_deadline;
        $daysRemaining = max(0, (int) now()->diffInDays($deadline, false));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Konfirmasi kesehatan berhasil! Timer sakelar otomatis telah diperbarui.',
                'last_check_in' => 'Hari ini, ' . $lastCheckIn->format('H:i') . ' WIB',
                'deadline_text' => $daysRemaining . ' Hari Lagi (' . $deadline->translatedFormat('d F Y') . ')',
            ]);
        }

        return back()->with('success', 'Konfirmasi kesehatan berhasil! Timer sakelar otomatis telah diperbarui.');
    }
public function simulateReminder(Request $request): RedirectResponse
    {
        $user = $request->user();

        ActivityLog::record(
            'SIMULATE_SWITCH', 
            "Menjalankan simulasi pengiriman email & WhatsApp pengingat sakelar otomatis."
        );

        return back()->with(
            'success', 
            "Simulasi Terkirim! Notifikasi pengingat sakelar otomatis telah dikirim ke {$user->email} dan nomor kontak Anda."
        );
    }
public function updateInterval(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isPaid = $user->hasActiveSubscription();

        // Aturan validasi: 30 hari hanya boleh untuk paket berbayar
        $allowedIntervals = $isPaid ? '30,90,180,365' : '90,180,365';

        $validated = $request->validate([
            'interval_days' => "required|in:{$allowedIntervals}",
        ], [
            'interval_days.in' => 'Opsi 1 Bulan hanya tersedia untuk pengguna paket Pro dan Unlimited.',
        ]);

        $intervalDays = (int) $validated['interval_days'];
        $baseDate = $user->last_check_in_at ?: now();

        $user->update([
            'check_in_interval_days' => $intervalDays,
            'next_check_in_deadline' => $baseDate->copy()->addDays($intervalDays),
        ]);

        ActivityLog::record('UPDATE_SWITCH_INTERVAL', "Mengubah interval rutin check-in menjadi {$intervalDays} hari.");

        return back()->with('success', 'Interval rutin check-in berhasil diperbarui.');
    }
    public function requestExtension(Request $request, Asset $asset)
{
    abort_unless($asset->user_id === $request->user()->id, 403);

    if (!$asset->isExpired()) {
        return back()->with('error', 'Aset ini belum kadaluarsa.');
    }

    $asset->update(['extension_status' => 'pending']);

    ActivityLog::record('REQUEST_EXTENSION', "Mengajukan permohonan perpanjangan aset: {$asset->title}");

    return back()->with('success', 'Permohonan perpanjangan berhasil diajukan ke admin. Harap tunggu persetujuan.');
}
}