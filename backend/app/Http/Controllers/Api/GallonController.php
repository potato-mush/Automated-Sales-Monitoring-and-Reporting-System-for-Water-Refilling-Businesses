<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gallon;
use App\Models\GallonLog;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GallonController extends Controller
{
    /**
     * Get all gallons with pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 50);
        $status = $request->input('status');
        $search = $request->input('search');

        $query = Gallon::with(['lastTransaction']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where('gallon_code', 'LIKE', "%{$search}%");
        }

        $gallons = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($gallons);
    }

    /**
     * Get single gallon by code
     */
    public function show($code)
    {
        $gallon = Gallon::where('gallon_code', $code)
            ->with(['lastTransaction.employee', 'logs.performer'])
            ->firstOrFail();

        return response()->json($gallon);
    }

    /**
     * Scan gallon QR code
     */
    public function scan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gallon_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Debug logging
        \Log::info('Gallon Scan Request', [
            'gallon_code' => $request->gallon_code,
            'code_length' => strlen($request->gallon_code),
            'raw_input' => $request->all()
        ]);

        $gallon = Gallon::where('gallon_code', $request->gallon_code)
            ->with(['lastTransaction'])
            ->first();

        if (!$gallon) {
            \Log::warning('Gallon not found', [
                'searched_code' => $request->gallon_code
            ]);
            
            return response()->json([
                'message' => 'Gallon not found',
                'exists' => false
            ], 404);
        }

        \Log::info('Gallon found', [
            'gallon_code' => $gallon->gallon_code
        ]);

        return response()->json([
            'exists' => true,
            'gallon' => $gallon
        ]);
    }

    /**
     * Return gallon to station
     */
    public function returnGallon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gallon_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $gallon = Gallon::where('gallon_code', $request->gallon_code)->first();

        if (!$gallon) {
            return response()->json([
                'message' => 'Gallon not found'
            ], 404);
        }

        if ($gallon->status === 'IN') {
            return response()->json([
                'message' => 'Gallon is already in the station'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $oldStatus = $gallon->status;

            // Update gallon status to IN
            $gallon->markAsIn();

            // Create gallon log
            GallonLog::create([
                'gallon_id' => $gallon->id,
                'action' => 'RETURN',
                'old_status' => $oldStatus,
                'new_status' => 'IN',
                'performed_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Gallon returned successfully',
                'gallon' => $gallon->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Return failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get gallon status summary
     */
    public function statusSummary()
    {
        $summary = Gallon::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return response()->json([
            'total' => Gallon::count(),
            'in_station' => $summary->get('IN')->count ?? 0,
            'out' => $summary->get('OUT')->count ?? 0,
            'missing' => $summary->get('MISSING')->count ?? 0,
            'overdue' => Gallon::where('is_overdue', true)->count(),
        ]);
    }

    /**
     * Get overdue gallons
     */
    public function overdue()
    {
        $overdueGallons = Gallon::where('is_overdue', true)
            ->with(['lastTransaction'])
            ->orderBy('overdue_days', 'desc')
            ->get();

        return response()->json($overdueGallons);
    }

    /**
     * Get missing gallons
     */
    public function missing()
    {
        $missingGallons = Gallon::where('status', 'MISSING')
            ->with(['lastTransaction'])
            ->orderBy('last_borrowed_date')
            ->get();

        return response()->json($missingGallons);
    }

    /**
     * Get gallon history/logs
     */
    public function history($code)
    {
        $gallon = Gallon::where('gallon_code', $code)->firstOrFail();

        $logs = GallonLog::where('gallon_id', $gallon->id)
            ->with(['transaction', 'performer'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }

    /**
     * Update overdue gallons (cron job endpoint)
     */
    public function updateOverdue()
    {
        $outGallons = Gallon::where('status', 'OUT')->get();

        foreach ($outGallons as $gallon) {
            $gallon->updateOverdueStatus();
        }

        return response()->json([
            'message' => 'Overdue status updated',
            'processed' => $outGallons->count()
        ]);
    }

    /**
     * Bulk create gallons
     */
    public function bulkCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gallons' => 'required|array',
            'gallons.*.gallon_code' => 'required|string|unique:gallons,gallon_code',
            'gallons.*.status' => 'nullable|in:IN,OUT,MISSING',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $created = [];
            $skipped = [];

            foreach ($request->gallons as $gallonData) {
                // Check if gallon already exists
                $exists = Gallon::where('gallon_code', $gallonData['gallon_code'])->exists();
                
                if ($exists) {
                    $skipped[] = $gallonData['gallon_code'];
                    continue;
                }

                $gallon = Gallon::create([
                    'gallon_code' => $gallonData['gallon_code'],
                    'status' => $gallonData['status'] ?? 'IN',
                    'is_overdue' => false,
                    'overdue_days' => 0,
                ]);

                $created[] = $gallon->gallon_code;

                // Create gallon log
                GallonLog::create([
                    'gallon_id' => $gallon->id,
                    'action' => 'CREATED',
                    'old_status' => null,
                    'new_status' => $gallon->status,
                    'performed_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Gallons created successfully',
                'created' => count($created),
                'skipped' => count($skipped),
                'created_codes' => $created,
                'skipped_codes' => $skipped,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Bulk creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
