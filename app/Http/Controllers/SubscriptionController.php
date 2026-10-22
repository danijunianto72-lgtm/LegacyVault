<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'proof_image' => 'required|image|mimes:jpg,jpeg,png|max:4096', // Maks 4MB
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        abort_if($plan->price === 0, 400, 'Paket Free tidak memerlukan pembayaran.');

        // Cegah spam upload jika masih ada transaksi pending
        $hasPending = $request->user()->payments()->where('status', 'pending')->exists();
        if ($hasPending) {
            return back()->with('error', 'Anda masih memiliki transaksi yang sedang menunggu verifikasi Admin.');
        }

        // SIMPAN KE SUPABASE (bukan 'public')
        $path = $request->file('proof_image')->store('payment_proofs', 'supabase_proofs');

        $request->user()->payments()->create([
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'proof_image_path' => $path,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Bukti transfer berhasil dikirim! Admin akan memverifikasi maksimal 1x24 jam.');
    }
}