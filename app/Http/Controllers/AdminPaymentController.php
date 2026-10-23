<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminPaymentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403, 'Akses khusus Administrator.');

       $pendingPayments = Payment::with(['user', 'plan'])->where('status', 'pending')->latest()->get();
    $processedPayments = Payment::with(['user', 'plan'])->where('status', '!=', 'pending')->latest()->paginate(10);

    return view('admin.payments', compact('pendingPayments', 'processedPayments'));
    }

    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        if ($payment->status !== 'pending') {
            return back()->with('error', 'Transaksi ini sudah pernah diproses sebelumnya.');
        }

        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'approved',
                'admin_note' => 'Pembayaran valid. Paket diaktifkan selama 30 hari.',
                'verified_at' => now(),
            ]);

            Subscription::where('user_id', $payment->user_id)
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            Subscription::create([
                'user_id' => $payment->user_id,
                'plan_id' => $payment->plan_id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addDays(30),
            ]);
        });

        ActivityLog::record('APPROVE_PAYMENT', "Menyetujui pembayaran paket {$payment->plan->name} untuk user {$payment->user->name}.");
        ActivityLog::record('SUBSCRIPTION_ACTIVE', "Paket {$payment->plan->name} Anda telah diaktifkan oleh Admin.", $payment->user_id);

        return back()->with('success', "Pembayaran {$payment->user->name} disetujui! Paket {$payment->plan->name} aktif 30 hari.");
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $request->validate(['admin_notes' => 'nullable|string|max:255']);

        $payment->update([
            'status' => 'rejected',
            'admin_note' => $request->input('admin_notes', 'Bukti transfer tidak terbaca atau nominal tidak sesuai.'),
            'verified_at' => now(),
        ]);

        ActivityLog::record('REJECT_PAYMENT', "Menolak pembayaran paket {$payment->plan->name} dari user {$payment->user->name}.");

        return back()->with('success', "Transaksi {$payment->user->name} telah ditolak.");
    }
}