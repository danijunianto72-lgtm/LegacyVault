<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminCashflowController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'all');
        $bankFilter = $request->get('bank', '');
        $search = $request->get('search', '');

        $hasSenderCol = Schema::hasColumn('payments', 'bank_sender_name');

        $isBriPayment = function ($pay) {
            return stripos($pay->bank_sender_name ?? '', 'BRI') !== false
                || stripos($pay->admin_note ?? '', 'BRI') !== false;
        };

        // Helper filter rentang waktu
        $filterByPeriod = function ($collection, $p) {
            if ($p === 'today') {
                return $collection->filter(fn($item) => $item->created_at->isToday());
            } elseif ($p === 'week') {
                return $collection->filter(fn($item) => $item->created_at->gte(now()->subDays(6)->startOfDay()));
            } elseif ($p === 'month') {
                return $collection->filter(fn($item) => $item->created_at->isCurrentMonth() && $item->created_at->isCurrentYear());
            }
            return $collection;
        };

        // Ambil data transaksi
        $allPaymentsRaw = Payment::with('plan')->orderBy('created_at')->get();
        // HANYA MENGHITUNG YANG SUDAH APPROVED
        $allApprovedRaw = $allPaymentsRaw->where('status', 'approved');

        $chartPeriods = ['today', 'week', 'month', 'all'];
        $chartData = [
            'line' => [],
            'bar' => [],
            'pie' => [],
        ];

        foreach ($chartPeriods as $p) {
            $periodAll = $filterByPeriod($allPaymentsRaw, $p);
            $periodApproved = $filterByPeriod($allApprovedRaw, $p);

            // 1. Pie Chart
            $chartData['pie'][$p] = [
                'pending' => $periodAll->where('status', 'pending')->count(),
                'approved' => $periodAll->where('status', 'approved')->count(),
                'rejected' => $periodAll->where('status', 'rejected')->count(),
            ];

            // 2. Bar Chart
            $briInPeriod = $periodApproved->filter($isBriPayment)->sum('amount');
            $totalInPeriod = $periodApproved->sum('amount');
            $chartData['bar'][$p] = [
                'bca_bni' => max(0, $totalInPeriod - $briInPeriod),
                'bri' => $briInPeriod,
            ];

            // 3. Line Chart
            $labels = [];
            $values = [];
            $details = [];

            if ($p === 'today') {
                $groupedHour = $periodApproved->groupBy(fn($item) => $item->created_at->format('H:00'));
                if ($groupedHour->isEmpty()) {
                    $labels[] = today()->format('d M Y') . ' (Hari Ini)';
                    $values[] = 0;
                    $details[] = ['count' => 0, 'bca_bni' => 0, 'bri' => 0, 'full_date' => today()->format('d M Y')];
                } else {
                    foreach ($groupedHour as $hour => $items) {
                        $briVal = $items->filter($isBriPayment)->sum('amount');
                        $totVal = $items->sum('amount');
                        $labels[] = 'Pukul ' . $hour;
                        $values[] = $totVal;
                        $details[] = [
                            'count' => $items->count(),
                            'bca_bni' => max(0, $totVal - $briVal),
                            'bri' => $briVal,
                            'full_date' => today()->format('d M Y') . ' - ' . $hour . ' WIB',
                        ];
                    }
                }
            } elseif ($p === 'week') {
                $groupedDay = $periodApproved->groupBy(fn($item) => $item->created_at->format('Y-m-d'));
                for ($i = 6; $i >= 0; $i--) {
                    $d = now()->subDays($i);
                    $key = $d->format('Y-m-d');
                    $items = $groupedDay->get($key, collect());
                    $briVal = $items->filter($isBriPayment)->sum('amount');
                    $totVal = $items->sum('amount');

                    $labels[] = $d->format('d M');
                    $values[] = $totVal;
                    $details[] = [
                        'count' => $items->count(),
                        'bca_bni' => max(0, $totVal - $briVal),
                        'bri' => $briVal,
                        'full_date' => $d->format('d M Y'),
                    ];
                }
            } elseif ($p === 'month') {
                $groupedDay = $periodApproved->groupBy(fn($item) => $item->created_at->format('Y-m-d'));
                $daysInMonth = now()->daysInMonth;
                for ($i = 1; $i <= $daysInMonth; $i++) {
                    $d = Carbon::createFromDate(now()->year, now()->month, $i);
                    $key = $d->format('Y-m-d');
                    $items = $groupedDay->get($key, collect());
                    $briVal = $items->filter($isBriPayment)->sum('amount');
                    $totVal = $items->sum('amount');

                    $labels[] = $d->format('d M');
                    $values[] = $totVal;
                    $details[] = [
                        'count' => $items->count(),
                        'bca_bni' => max(0, $totVal - $briVal),
                        'bri' => $briVal,
                        'full_date' => $d->format('d F Y'),
                    ];
                }
            } else {
                $groupedDay = $periodApproved->groupBy(fn($item) => $item->created_at->format('Y-m-d'));
                if ($groupedDay->isEmpty()) {
                    for ($i = 6; $i >= 0; $i--) {
                        $d = now()->subDays($i);
                        $labels[] = $d->format('d M');
                        $values[] = 0;
                        $details[] = ['count' => 0, 'bca_bni' => 0, 'bri' => 0, 'full_date' => $d->format('d M Y')];
                    }
                } else {
                    foreach ($groupedDay as $date => $items) {
                        $d = Carbon::parse($date);
                        $briVal = $items->filter($isBriPayment)->sum('amount');
                        $totVal = $items->sum('amount');

                        $labels[] = $d->format('d M Y');
                        $values[] = $totVal;
                        $details[] = [
                            'count' => $items->count(),
                            'bca_bni' => max(0, $totVal - $briVal),
                            'bri' => $briVal,
                            'full_date' => $d->format('d F Y'),
                        ];
                    }
                }
            }

            $chartData['line'][$p] = [
                'labels' => $labels,
                'data' => $values,
                'details' => $details,
            ];
        }

        // ========================================================
        // QUERY UNTUK CARD ATAS & TABEL BAWAH
        // ========================================================
        $baseQuery = Payment::with(['user.activeSubscription.plan', 'user.subscription.plan', 'plan'])
            ->where('status', 'approved');

        if ($period === 'today') {
            $baseQuery->whereDate('created_at', today());
        } elseif ($period === 'week') {
            $baseQuery->where('created_at', '>=', now()->subDays(6)->startOfDay());
        } elseif ($period === 'month') {
            $baseQuery->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
        }

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

        $allApprovedInTablePeriod = (clone $baseQuery)->get();
        $totalIncome = $allApprovedInTablePeriod->sum('amount');
        $briCollection = $allApprovedInTablePeriod->filter($isBriPayment);
        $briIncome = $briCollection->sum('amount');
        $briCount = $briCollection->count();

        $bcaBniIncome = max(0, $totalIncome - $briIncome);
        $bcaBniCount = $allApprovedInTablePeriod->count() - $briCount;

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

        $cashflows = $baseQuery->latest()->paginate(10)->withQueryString();

        $stats = [
            'total_income' => $totalIncome,
            'total_trx' => $allApprovedInTablePeriod->count(),
            'bri_income' => $briIncome,
            'bri_count' => $briCount,
            'bca_bni_income' => $bcaBniIncome,
            'bca_bni_count' => $bcaBniCount,
        ];

        return view('admin.cashflow', compact('cashflows', 'stats', 'chartData', 'period', 'bankFilter', 'search'));
    }
}