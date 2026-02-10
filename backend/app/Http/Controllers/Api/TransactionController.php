<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Gallon;
use App\Models\GallonLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Get all transactions with pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $type = $request->input('type');
        $date = $request->input('date');

        $query = Transaction::with(['employee', 'items.gallon'])
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('transaction_type', $type);
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        $transactions = $query->paginate($perPage);

        return response()->json($transactions);
    }

    /**
     * Get single transaction
     */
    public function show($id)
    {
        $transaction = Transaction::with(['employee', 'items.gallon'])->findOrFail($id);

        return response()->json($transaction);
    }

    /**
     * Create new transaction
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string',
            'transaction_type' => 'required|in:walk-in,delivery,refill-only',
            'payment_method' => 'required|in:cash,gcash,card,bank-transfer',
            'unit_price' => 'required|numeric|min:0',
            'gallon_codes' => 'required|array|min:1',
            'gallon_codes.*' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Validate gallons exist and are available
            $gallons = Gallon::whereIn('gallon_code', $request->gallon_codes)->get();

            if ($gallons->count() !== count($request->gallon_codes)) {
                return response()->json([
                    'message' => 'One or more gallon codes are invalid'
                ], 422);
            }

            // Check if any gallon is already OUT (can't borrow twice)
            $outGallons = $gallons->where('status', 'OUT');
            if ($outGallons->count() > 0) {
                return response()->json([
                    'message' => 'Cannot borrow gallons that are already OUT',
                    'out_gallons' => $outGallons->pluck('gallon_code')
                ], 422);
            }

            // Create transaction
            $transaction = Transaction::create([
                'transaction_code' => Transaction::generateTransactionCode(),
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_address' => $request->customer_address,
                'transaction_type' => $request->transaction_type,
                'payment_method' => $request->payment_method,
                'quantity' => count($request->gallon_codes),
                'unit_price' => $request->unit_price,
                'total_amount' => count($request->gallon_codes) * $request->unit_price,
                'employee_id' => auth()->id(),
                'notes' => $request->notes,
            ]);

            // Create transaction items and update gallon status
            foreach ($gallons as $gallon) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'gallon_id' => $gallon->id,
                    'action' => 'BORROW',
                ]);

                // Update gallon status to OUT
                $gallon->markAsOut($transaction->id);

                // Create gallon log
                GallonLog::create([
                    'gallon_id' => $gallon->id,
                    'transaction_id' => $transaction->id,
                    'action' => 'BORROW',
                    'old_status' => 'IN',
                    'new_status' => 'OUT',
                    'performed_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Transaction created successfully',
                'transaction' => $transaction->load(['employee', 'items.gallon'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Transaction failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's transactions summary
     */
    public function todaySummary()
    {
        $today = Transaction::today()->get();

        return response()->json([
            'total_transactions' => $today->count(),
            'total_revenue' => $today->sum('total_amount'),
            'total_gallons' => $today->sum('quantity'),
            'by_type' => [
                'walk_in' => [
                    'count' => $today->where('transaction_type', 'walk-in')->count(),
                    'revenue' => $today->where('transaction_type', 'walk-in')->sum('total_amount'),
                ],
                'delivery' => [
                    'count' => $today->where('transaction_type', 'delivery')->count(),
                    'revenue' => $today->where('transaction_type', 'delivery')->sum('total_amount'),
                ],
                'refill_only' => [
                    'count' => $today->where('transaction_type', 'refill-only')->count(),
                    'revenue' => $today->where('transaction_type', 'refill-only')->sum('total_amount'),
                ],
            ],
            'by_payment' => [
                'cash' => $today->where('payment_method', 'cash')->sum('total_amount'),
                'gcash' => $today->where('payment_method', 'gcash')->sum('total_amount'),
                'card' => $today->where('payment_method', 'card')->sum('total_amount'),
                'bank_transfer' => $today->where('payment_method', 'bank-transfer')->sum('total_amount'),
            ]
        ]);
    }

    /**
     * Get transaction statistics
     */
    public function statistics(Request $request)
    {
        $period = $request->input('period', 'today'); // today, week, month

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
            'period' => $period,
            'total_transactions' => $transactions->count(),
            'total_revenue' => $transactions->sum('total_amount'),
            'total_gallons' => $transactions->sum('quantity'),
            'average_transaction' => $transactions->avg('total_amount'),
            'by_type' => [
                'walk_in' => $transactions->where('transaction_type', 'walk-in')->count(),
                'delivery' => $transactions->where('transaction_type', 'delivery')->count(),
                'refill_only' => $transactions->where('transaction_type', 'refill-only')->count(),
            ]
        ]);
    }
}
