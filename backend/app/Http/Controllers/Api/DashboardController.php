<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Gallon;
use App\Models\User;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // Get dashboard overview
    public function index()
    {
        // Update overdue status for all gallons
        Gallon::updateAllOverdueStatus();
        
        // Today's stats
        $todayTransactions = Transaction::today()->get();
        $todayRevenue = $todayTransactions->sum('total_amount');
        $todayGallons = $todayTransactions->sum('quantity');

        // This week's stats
        $weekTransactions = Transaction::thisWeek()->get();
        $weekRevenue = $weekTransactions->sum('total_amount');

        // This month's stats
        $monthTransactions = Transaction::thisMonth()->get();
        $monthRevenue = $monthTransactions->sum('total_amount');

        // Gallon stats
        $gallonStats = [
            'total' => Gallon::count(),
            'in_station' => Gallon::inStation()->count(),
            'out' => Gallon::out()->count(),
            'missing' => Gallon::missing()->count(),
            'overdue' => Gallon::overdue()->count(),
        ];

        // Recent transactions
        $recentTransactions = Transaction::with(['employee'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'today' => [
                'transactions' => $todayTransactions->count(),
                'revenue' => $todayRevenue,
                'gallons_sold' => $todayGallons,
            ],
            'week' => [
                'transactions' => $weekTransactions->count(),
                'revenue' => $weekRevenue,
            ],
            'month' => [
                'transactions' => $monthTransactions->count(),
                'revenue' => $monthRevenue,
            ],
            'gallons' => $gallonStats,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    // Get sales chart data
    public function salesChart(Request $request)
    {
        $period = $request->input('period', 'week'); // daily, week, month, year
        $data = [];

        switch ($period) {
            case 'daily':
                // Show last 7 days (Sunday to Saturday)
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::today()->subDays($i);
                    $transactions = Transaction::whereDate('created_at', $date)->get();

                    $data[] = [
                        'date' => $date->format('Y-m-d'),
                        'label' => $date->format('D'), // Mon, Tue, etc.
                        'transactions' => $transactions->count(),
                        'revenue' => $transactions->sum('total_amount'),
                        'gallons' => $transactions->sum('quantity'),
                    ];
                }
                break;

            case 'month':
                // Show all 12 months of current year (January to December)
                $currentYear = Carbon::now()->year;
                for ($month = 1; $month <= 12; $month++) {
                    $startDate = Carbon::create($currentYear, $month, 1)->startOfMonth();
                    $endDate = Carbon::create($currentYear, $month, 1)->endOfMonth();
                    $transactions = Transaction::whereBetween('created_at', [$startDate, $endDate])->get();

                    $data[] = [
                        'date' => $startDate->format('Y-m'),
                        'label' => $startDate->format('M'),
                        'transactions' => $transactions->count(),
                        'revenue' => $transactions->sum('total_amount'),
                        'gallons' => $transactions->sum('quantity'),
                    ];
                }
                break;

            case 'weekly':
                // Show last 5 weeks with labels like "Week 1", "Week 2", etc. from current year
                $now = Carbon::now();
                $currentWeek = $now->weekOfYear;
                $currentYear = $now->year;
                
                // Display at least 5 weeks going back from current week
                for ($i = 4; $i >= 0; $i--) {
                    $weekNumber = $currentWeek - $i;
                    
                    // Handle week number going into previous year
                    $year = $currentYear;
                    if ($weekNumber <= 0) {
                        $year = $currentYear - 1;
                        // Get the last week of previous year
                        $weekNumber = Carbon::create($year, 12, 28)->weekOfYear + $weekNumber;
                    }
                    
                    // Calculate start and end of the week (Monday to Sunday)
                    $startDate = Carbon::now()->setISODate($year, $weekNumber)->startOfWeek();
                    $endDate = Carbon::now()->setISODate($year, $weekNumber)->endOfWeek();
                    
                    $transactions = Transaction::whereBetween('created_at', [$startDate, $endDate])->get();

                    $data[] = [
                        'date' => $startDate->format('Y-m-d'),
                        'label' => 'Week ' . $weekNumber,
                        'transactions' => $transactions->count(),
                        'revenue' => $transactions->sum('total_amount'),
                        'gallons' => $transactions->sum('quantity'),
                        'week_range' => $startDate->format('M d') . ' - ' . $endDate->format('M d'),
                    ];
                }
                break;

            case 'year':
                // Show current year and 3 previous years (4 years total)
                $currentYear = Carbon::now()->year;
                for ($i = 3; $i >= 0; $i--) {
                    $year = $currentYear - $i;
                    $startDate = Carbon::create($year, 1, 1)->startOfYear();
                    $endDate = Carbon::create($year, 12, 31)->endOfYear();
                    $transactions = Transaction::whereBetween('created_at', [$startDate, $endDate])->get();

                    $data[] = [
                        'date' => $year,
                        'label' => (string)$year,
                        'transactions' => $transactions->count(),
                        'revenue' => $transactions->sum('total_amount'),
                        'gallons' => $transactions->sum('quantity'),
                    ];
                }
                break;

            default:
                // week - Show last 7 days
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::today()->subDays($i);
                    $transactions = Transaction::whereDate('created_at', $date)->get();

                    $data[] = [
                        'date' => $date->format('Y-m-d'),
                        'label' => $date->format('M d'),
                        'transactions' => $transactions->count(),
                        'revenue' => $transactions->sum('total_amount'),
                        'gallons' => $transactions->sum('quantity'),
                    ];
                }
        }

        return response()->json($data);
    }

    /**
     * Get transaction type breakdown
     */
    public function transactionTypeBreakdown(Request $request)
    {
        $period = $request->input('period', 'today');

        $query = Transaction::query();

        switch ($period) {
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
            default:
                $query->today();
        }

        $transactions = $query->get();

        return response()->json([
            'walk_in' => [
                'count' => $transactions->where('transaction_type', 'walk-in')->count(),
                'revenue' => $transactions->where('transaction_type', 'walk-in')->sum('total_amount'),
            ],
            'delivery' => [
                'count' => $transactions->where('transaction_type', 'delivery')->count(),
                'revenue' => $transactions->where('transaction_type', 'delivery')->sum('total_amount'),
            ],
            'refill_only' => [
                'count' => $transactions->where('transaction_type', 'refill-only')->count(),
                'revenue' => $transactions->where('transaction_type', 'refill-only')->sum('total_amount'),
            ],
        ]);
    }

    /**
     * Generate daily report
     */
    public function dailyReport(Request $request)
    {
        $date = $request->input('date', today()->format('Y-m-d'));
        $transactions = Transaction::whereDate('created_at', $date)->get();

        return response()->json([
            'date' => $date,
            'total_transactions' => $transactions->count(),
            'total_revenue' => $transactions->sum('total_amount'),
            'total_gallons' => $transactions->sum('quantity'),
            'by_type' => [
                'walk_in' => [
                    'count' => $transactions->where('transaction_type', 'walk-in')->count(),
                    'revenue' => $transactions->where('transaction_type', 'walk-in')->sum('total_amount'),
                    'gallons' => $transactions->where('transaction_type', 'walk-in')->sum('quantity'),
                ],
                'delivery' => [
                    'count' => $transactions->where('transaction_type', 'delivery')->count(),
                    'revenue' => $transactions->where('transaction_type', 'delivery')->sum('total_amount'),
                    'gallons' => $transactions->where('transaction_type', 'delivery')->sum('quantity'),
                ],
                'refill_only' => [
                    'count' => $transactions->where('transaction_type', 'refill-only')->count(),
                    'revenue' => $transactions->where('transaction_type', 'refill-only')->sum('total_amount'),
                    'gallons' => $transactions->where('transaction_type', 'refill-only')->sum('quantity'),
                ],
            ],
            'by_payment' => [
                'cash' => $transactions->where('payment_method', 'cash')->sum('total_amount'),
                'gcash' => $transactions->where('payment_method', 'gcash')->sum('total_amount'),
                'card' => $transactions->where('payment_method', 'card')->sum('total_amount'),
                'bank_transfer' => $transactions->where('payment_method', 'bank-transfer')->sum('total_amount'),
            ],
            'transactions' => $transactions,
        ]);
    }

    /**
     * Generate weekly report
     */
    public function weeklyReport()
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $transactions = Transaction::whereBetween('created_at', [$startOfWeek, $endOfWeek])->get();

        return response()->json([
            'period' => 'week',
            'start_date' => $startOfWeek->format('Y-m-d'),
            'end_date' => $endOfWeek->format('Y-m-d'),
            'total_transactions' => $transactions->count(),
            'total_revenue' => $transactions->sum('total_amount'),
            'total_gallons' => $transactions->sum('quantity'),
            'average_daily_revenue' => $transactions->sum('total_amount') / 7,
            'by_type' => [
                'walk_in' => [
                    'count' => $transactions->where('transaction_type', 'walk-in')->count(),
                    'revenue' => $transactions->where('transaction_type', 'walk-in')->sum('total_amount'),
                ],
                'delivery' => [
                    'count' => $transactions->where('transaction_type', 'delivery')->count(),
                    'revenue' => $transactions->where('transaction_type', 'delivery')->sum('total_amount'),
                ],
                'refill_only' => [
                    'count' => $transactions->where('transaction_type', 'refill-only')->count(),
                    'revenue' => $transactions->where('transaction_type', 'refill-only')->sum('total_amount'),
                ],
            ],
        ]);
    }

    /**
     * Generate monthly report
     */
    public function monthlyReport(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

        $transactions = Transaction::whereBetween('created_at', [$startOfMonth, $endOfMonth])->get();

        return response()->json([
            'period' => 'month',
            'month' => $month,
            'year' => $year,
            'start_date' => $startOfMonth->format('Y-m-d'),
            'end_date' => $endOfMonth->format('Y-m-d'),
            'total_transactions' => $transactions->count(),
            'total_revenue' => $transactions->sum('total_amount'),
            'total_gallons' => $transactions->sum('quantity'),
            'average_daily_revenue' => $transactions->sum('total_amount') / $startOfMonth->daysInMonth,
            'by_type' => [
                'walk_in' => [
                    'count' => $transactions->where('transaction_type', 'walk-in')->count(),
                    'revenue' => $transactions->where('transaction_type', 'walk-in')->sum('total_amount'),
                ],
                'delivery' => [
                    'count' => $transactions->where('transaction_type', 'delivery')->count(),
                    'revenue' => $transactions->where('transaction_type', 'delivery')->sum('total_amount'),
                ],
                'refill_only' => [
                    'count' => $transactions->where('transaction_type', 'refill-only')->count(),
                    'revenue' => $transactions->where('transaction_type', 'refill-only')->sum('total_amount'),
                ],
            ],
        ]);
    }

    /**
     * Generate yearly report
     */
    public function yearlyReport(Request $request)
    {
        $year = $request->input('year', now()->year);

        $startOfYear = Carbon::create($year, 1, 1)->startOfYear();
        $endOfYear = Carbon::create($year, 12, 31)->endOfYear();

        $transactions = Transaction::whereBetween('created_at', [$startOfYear, $endOfYear])->get();

        return response()->json([
            'period' => 'year',
            'year' => $year,
            'start_date' => $startOfYear->format('Y-m-d'),
            'end_date' => $endOfYear->format('Y-m-d'),
            'total_transactions' => $transactions->count(),
            'total_revenue' => $transactions->sum('total_amount'),
            'total_gallons' => $transactions->sum('quantity'),
            'average_monthly_revenue' => $transactions->sum('total_amount') / 12,
            'by_type' => [
                'walk_in' => [
                    'count' => $transactions->where('transaction_type', 'walk-in')->count(),
                    'revenue' => $transactions->where('transaction_type', 'walk-in')->sum('total_amount'),
                ],
                'delivery' => [
                    'count' => $transactions->where('transaction_type', 'delivery')->count(),
                    'revenue' => $transactions->where('transaction_type', 'delivery')->sum('total_amount'),
                ],
                'refill_only' => [
                    'count' => $transactions->where('transaction_type', 'refill-only')->count(),
                    'revenue' => $transactions->where('transaction_type', 'refill-only')->sum('total_amount'),
                ],
            ],
        ]);
    }

    /**
     * Get recent inventory management logs.
     */
    public function recentInventoryLogs()
    {
        $logs = SystemLog::query()
            ->whereIn('action', ['inventory_create', 'inventory_update', 'inventory_delete', 'inventory_adjust'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get([
                'id',
                'user_name',
                'user_role',
                'action',
                'details',
                'created_at',
            ]);

        return response()->json($logs);
    }
}
