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
        dd(
        \App\Models\Payment::latest()->take(10)->get([
            'id', 'amount', 'status', 'bank_sender_name', 'admin_note', 'created_at'
        ])->toArray()
    );
        $user = $request->user();

        return view('subscriptions.pricing', [
            'plans' => Plan::orderBy('price')->get(),
            'currentPlan' => $user->currentPlan(),
            'payments' => $user->payments()->with('plan')->latest()->get(),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
    //     dd([
    //     'bank_destination' => $request->input('bank_destination'),
    //     'bank_sender_name' => $request->input('bank_sender_name'),
    //     'all_request'      => $request->all(),
    // ]);
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'bank_destination' => 'nullable|string',
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

        // 1. Deteksi Bank Tujuan secara kuat (Cek dari input hidden maupun teks pengirim)
        $rawBank = strtoupper(trim((string) $request->input('bank_destination', '')));
        $senderInput = trim($validated['bank_sender_name']);

        if ($rawBank === 'BRI' || stripos($senderInput, 'BRI') !== false) {
            $bankDest = 'BRI';
        } else {
            $bankDest = 'BCA';
        }

        // 2. Bersihkan tag lama jika user menulis tag ganda, lalu pasang prefix [BRI] atau [BCA]
        $cleanSenderName = preg_replace('/^\[(BCA|BRI)\]\s*/i', '', $senderInput);
        $senderInfo = "[{$bankDest}] {$cleanSenderName}";

        // SIMPAN KE SUPABASE
        $path = $request->file('proof_image')->store('payment_proofs', 'supabase_proofs');

        $paymentData = [
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'proof_image_path' => $path,
            'status' => 'pending',
        ];

        // Simpan ke kolom bank_sender_name jika ada, atau ke admin_note
        if (Schema::hasColumn('payments', 'bank_sender_name')) {
            $paymentData['bank_sender_name'] = $senderInfo;
        } else {
            $paymentData['admin_note'] = "Transfer ke {$senderInfo}";
        }

        $request->user()->payments()->create($paymentData);

        ActivityLog::record(
            'CHECKOUT_PLAN',
            "Mengunggah bukti pembayaran paket {$plan->name} melalui {$bankDest} (Pengirim: {$cleanSenderName})"
        );

        return back()->with('success', "Bukti transfer ke Bank {$bankDest} berhasil dikirim! Admin akan memverifikasi maksimal 1x24 jam.");
    }
}