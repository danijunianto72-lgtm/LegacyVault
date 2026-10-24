<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('subscriptions.pricing', [
            'plans' => Plan::orderBy('price')->get(),
            'currentPlan' => $user->currentPlan(),
            'payments' => $user->payments()->with('plan')->latest()->get(),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'bank_destination' => 'nullable|in:BCA,BRI',
            'bank_sender_name' => 'required|string|max:150',
            'proof_image' => 'required|image|mimes:jpg,jpeg,png|max:4096', // Maks 4MB
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        abort_if($plan->price === 0, 400, 'Paket Free tidak memerlukan pembayaran.');

        // Cegah spam upload jika masih ada transaksi pending
        $hasPending = $request->user()->payments()->where('status', 'pending')->exists();
        if ($hasPending) {
            return back()->with('error', 'Anda masih memiliki transaksi yang sedang menunggu verifikasi Admin.');
        }

        // Format info Bank Tujuan + Nama Pengirim (Contoh: "[BRI] Budi Santoso")
        $bankDest = $validated['bank_destination'] ?? 'BCA';
        $senderName = trim($validated['bank_sender_name']);
        if (!str_contains(strtoupper($senderName), $bankDest)) {
            $senderInfo = "[{$bankDest}] {$senderName}";
        } else {
            $senderInfo = $senderName;
        }

        // SIMPAN KE SUPABASE
        $path = $request->file('proof_image')->store('payment_proofs', 'supabase_proofs');

        $paymentData = [
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'proof_image_path' => $path,
            'status' => 'pending',
        ];

        // Cek otomatis: jika tabel payments punya kolom bank_sender_name, simpan di sana.
        // Jika tidak ada kolomnya, simpan di admin_note agar tetap terbaca di Cashflow & Admin.
        if (Schema::hasColumn('payments', 'bank_sender_name')) {
            $paymentData['bank_sender_name'] = $senderInfo;
        } else {
            $paymentData['admin_note'] = "Transfer ke {$senderInfo}";
        }

        $request->user()->payments()->create($paymentData);

        ActivityLog::record(
            'CHECKOUT_PLAN',
            "Mengunggah bukti pembayaran paket {$plan->name} melalui {$bankDest} (Pengirim: {$senderName})"
        );

        return back()->with('success', "Bukti transfer ke Bank {$bankDest} berhasil dikirim! Admin akan memverifikasi maksimal 1x24 jam.");
    }
}