<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminCashflowController extends Controller
{
    public function index(Request $request)
    {
        $period =$request->get('period', 'all'); // today, week, month, all
        $bankFilter =$request->get('bank', '');  // BCA_BNI, BRI
        $search =$request->get('search', '');

        $hasSenderCol = Schema::hasColumn('payments', 'bank_sender_name');

        // Helper closure untuk terapkan filter Waktu & Pencarian ke query mana pun
        $applyFilters = function ($query) use ($period, $search,$hasSenderCol) {
            if ($period === 'today') {$query->whereDate('created_at', today());
            } elseif ($period === 'week') {$query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($period === 'month') {$query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search,$hasSenderCol) {
                    if ($hasSenderCol) {
                        $q->where('bank_sender_name', 'like', "\%{$search}%");
                    }
                    $q->orWhere('admin_note', 'like', "\%{$search}%")
                      ->orWhereHas('user', function ($uq) use ($search) {
                          $uq->where('name', 'like', "\%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }
        };

        // 1. Query untuk Diagram Lingkaran (Semua Status: pending, approved, rejected)
        $statusQuery = Payment::query();
        $applyFilters($statusQuery);
        $allStatusPayments =$statusQuery->get();

        $statusCounts = [
            'pending' => $allStatusPayments->where('status', 'pending')->count(),
            'approved' => $allStatusPayments->where('status', 'approved')->count(),
            'rejected' => $allStatusPayments->where('status', 'rejected')->count(),
        ];

        // 2. Base Query untuk Transaksi yang Sudah Approved
        $baseQuery = Payment::with(['user.activeSubscription.plan', 'user.subscription.plan', 'plan'])
            ->where('status', 'approved');
        $applyFilters($baseQuery);

        // 3. Hitung Ringkasan Pemasukan & Data Grafik Batang (BCA/BNI vs BRI)
        $allApprovedInPeriod = (clone$baseQuery)->orderBy('created_at')->get();

        $totalIncome =$allApprovedInPeriod->sum('amount');

        $isBriPayment = function ($pay) {
            return stripos($pay->bank_sender_name ?? '', 'BRI') !== false
                || stripos($pay->admin_note ?? '', 'BRI') !== false;
        };

        $briCollection = $allApprovedInPeriod->filter($isBriPayment);
        $briIncome =$briCollection->sum('amount');
        $briCount =$briCollection->count();

        $bcaBniIncome = $totalIncome -$briIncome;
        $bcaBniCount = $allApprovedInPeriod->count() -$briCount;

        // 4. Data untuk Diagram Garis (Tren Pemasukan dari Hari ke Hari)
        $dailyGrouped = $allApprovedInPeriod->groupBy(function ($pay) {
            return $pay->created_at->format('Y-m-d');
        });

        $lineLabels = [];$lineValues = [];

        if ($period === 'today') {$lineLabels[] = today()->format('d M Y');
            $lineValues[] =$totalIncome;
        } elseif ($period === 'week') {
            for ($i = 0; $i < 7; $i++) {$d = now()->startOfWeek()->addDays($i);$key = $d->format('Y-m-d');$lineLabels[] = $d->format('d M');$lineValues[] = isset($dailyGrouped[$key]) ? $dailyGrouped[$key]->sum('amount') : 0;
            }
        } elseif ($period === 'month') {$daysInMonth = now()->daysInMonth;
            for ($i = 1; $i <=$daysInMonth; $i++) {$d = Carbon::createFromDate(now()->year, now()->month, $i);$key = $d->format('Y-m-d');$lineLabels[] = $d->format('d M');$lineValues[] = isset($dailyGrouped[$key]) ? $dailyGrouped[$key]->sum('amount') : 0;
            }
        } else {
            // Semua Waktu: Jika kosong tampilkan 7 hari terakhir, jika ada tampilkan per tanggal transaksi
            if ($dailyGrouped->isEmpty()) {
                for ($i = 6; $i >= 0; $i--) {$lineLabels[] = now()->subDays($i)->format('d M');$lineValues[] = 0;
                }
            } else {
                foreach ($dailyGrouped as $date =>$items) {
                    $lineLabels[] = Carbon::parse($date)->format('d M Y');
                    $lineValues[] =$items->sum('amount');
                }
            }
        }

        // 5. Filter Tabel Berdasarkan Bank Jika Dipilih
        if ($bankFilter === 'BRI') {$baseQuery->where(function ($q) use ($hasSenderCol) {
                if ($hasSenderCol) {$q->where('bank_sender_name', 'like', '%BRI%');
                }
                $q->orWhere('admin_note', 'like', '%BRI%');
            });
        } elseif ($bankFilter === 'BCA_BNI') {$baseQuery->where(function ($q) use ($hasSenderCol) {
                if ($hasSenderCol) {$q->where(function ($s) {$s->whereNull('bank_sender_name')
                          ->orWhere('bank_sender_name', 'not like', '%BRI%');
                    });
                }
                $q->where(function ($s) {$s->whereNull('admin_note')
                      ->orWhere('admin_note', 'not like', '%BRI%');
                });
            });
        }

        // 6. Ambil Data Tabel dengan Pagination (10 per halaman)
        $cashflows =$baseQuery->latest()->paginate(10)->withQueryString();

        $stats = [
            'total_income' => $totalIncome,
            'total_trx' => $allApprovedInPeriod->count(),
            'bri_income' => $briIncome,
            'bri_count' => $briCount,
            'bca_bni_income' => $bcaBniIncome,
            'bca_bni_count' => $bcaBniCount,
        ];

        $chartData = [
            'line' => [
                'labels' => $lineLabels,
                'data' => $lineValues,
            ],
            'bar' => [
                'bca_bni' => $bcaBniIncome,
                'bri' => $briIncome,
            ],
            'pie' => [
                'pending' => $statusCounts['pending'],
                'approved' => $statusCounts['approved'],
                'rejected' => $statusCounts['rejected'],
            ],
        ];

        return view('admin.cashflow', compact('cashflows', 'stats', 'chartData', 'period', 'bankFilter', 'search'));
    }
}