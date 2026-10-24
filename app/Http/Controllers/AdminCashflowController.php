<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminCashflowController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'all'); // today, week, month, all
        $bankFilter = $request->get('bank', '');  // BCA_BNI, BRI
        $search = $request->get('search', '');

        $hasSenderCol = Schema::hasColumn('payments', 'bank_sender_name');

        // Base query: hanya ambil pembayaran yang sudah DISETUJUI (approved)
        $baseQuery = Payment::with(['user.activeSubscription.plan', 'user.subscription.plan', 'plan'])
            ->where('status', 'approved');

        // 1. Filter Rentang Waktu
        if ($period === 'today') {
            $baseQuery->whereDate('created_at', today());
        } elseif ($period === 'week') {
            $baseQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $baseQuery->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
        }

        // 2. Filter Pencarian Nama User / Nama Pengirim
        if (!empty($search)) {
            $baseQuery->where(function ($q) use ($search, $hasSenderCol) {
                if ($hasSenderCol) {
                    $q->where('bank_sender_name', 'like', "%{$search}%");
                }
                $q->orWhere('admin_note', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // 3. Hitung Ringkasan Pemasukan (Berdasarkan Filter Waktu Saat Ini)
        $allApprovedInPeriod = (clone $baseQuery)->get();

        $totalIncome = $allApprovedInPeriod->sum('amount');

        $isBriPayment = function ($pay) {
            return stripos($pay->bank_sender_name ?? '', 'BRI') !== false
                || stripos($pay->admin_note ?? '', 'BRI') !== false;
        };

        $briCollection = $allApprovedInPeriod->filter($isBriPayment);
        $briIncome = $briCollection->sum('amount');
        $briCount = $briCollection->count();

        $bcaBniIncome = $totalIncome - $briIncome;
        $bcaBniCount = $allApprovedInPeriod->count() - $briCount;

        // 4. Filter Tabel Berdasarkan Bank Jika Dipilih
        if ($bankFilter === 'BRI') {
            $baseQuery->where(function ($q) use ($hasSenderCol) {
                if ($hasSenderCol) {
                    $q->where('bank_sender_name', 'like', '%BRI%');
                }
                $q->orWhere('admin_note', 'like', '%BRI%');
            });
        } elseif ($bankFilter === 'BCA_BNI') {
            $baseQuery->where(function ($q) use ($hasSenderCol) {
                if ($hasSenderCol) {
                    $q->where(function ($s) {
                        $s->whereNull('bank_sender_name')
                          ->orWhere('bank_sender_name', 'not like', '%BRI%');
                    });
                }
                $q->where(function ($s) {
                    $s->whereNull('admin_note')
                      ->orWhere('admin_note', 'not like', '%BRI%');
                });
            });
        }

        // 5. Ambil Data dengan Pagination (10 per halaman)
        $cashflows = $baseQuery->latest()->paginate(10)->withQueryString();

        $stats = [
            'total_income' => $totalIncome,
            'total_trx' => $allApprovedInPeriod->count(),
            'bri_income' => $briIncome,
            'bri_count' => $briCount,
            'bca_bni_income' => $bcaBniIncome,
            'bca_bni_count' => $bcaBniCount,
        ];

        return view('admin.cashflow', compact('cashflows', 'stats', 'period', 'bankFilter', 'search'));
    }
}