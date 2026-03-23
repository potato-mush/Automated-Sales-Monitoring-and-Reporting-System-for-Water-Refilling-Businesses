<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LogController extends Controller
{
    // Get system logs with pagination and filters
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 50);
        $action = $request->input('action');
        $role = $request->input('role');
        $platform = $request->input('platform');
        $search = $request->input('search');

        $query = SystemLog::query()->orderBy('created_at', 'desc');

        if ($action) {
            $query->where('action', $action);
        }

        if ($role) {
            $query->where('user_role', $role);
        }

        if ($platform) {
            $query->where('platform', $platform);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('user_name', 'LIKE', "%{$search}%")
                  ->orWhere('user_email', 'LIKE', "%{$search}%")
                  ->orWhere('ip_address', 'LIKE', "%{$search}%");
            });
        }

        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    // Get system logs statistics
    public function statistics()
    {
        $totalLogs = SystemLog::count();
        $todayLogs = SystemLog::whereDate('created_at', today())->count();
        $logins = SystemLog::logins()->count();
        $logouts = SystemLog::logouts()->count();
        
        $adminLogs = SystemLog::byRole('admin')->count();
        $employeeLogs = SystemLog::byRole('employee')->count();
        
        $webLogs = SystemLog::byPlatform('web')->count();
        $mobileLogs = SystemLog::byPlatform('mobile')->count();

        return response()->json([
            'total_logs' => $totalLogs,
            'today_logs' => $todayLogs,
            'logins' => $logins,
            'logouts' => $logouts,
            'by_role' => [
                'admin' => $adminLogs,
                'employee' => $employeeLogs,
            ],
            'by_platform' => [
                'web' => $webLogs,
                'mobile' => $mobileLogs,
            ],
        ]);
    }

    // Export system logs as CSV
    public function export(Request $request)
    {
        $action = $request->input('action');
        $role = $request->input('role');
        $platform = $request->input('platform');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = SystemLog::query()->orderBy('created_at', 'desc');

        if ($action) {
            $query->where('action', $action);
        }

        if ($role) {
            $query->where('user_role', $role);
        }

        if ($platform) {
            $query->where('platform', $platform);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $logs = $query->get();

        // Create CSV content
        $csv = "ID,User Name,Email,Role,Action,Details,Platform,Device,IP Address,Date/Time\n";
        
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $log->id,
                $this->escapeCsv($log->user_name),
                $this->escapeCsv($log->user_email),
                ucfirst($log->user_role),
                ucfirst($log->action),
                $this->escapeCsv($log->details ?? 'N/A'),
                ucfirst($log->platform ?? 'N/A'),
                $this->escapeCsv($log->device ?? 'N/A'),
                $log->ip_address ?? 'N/A',
                $log->created_at
            );
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="system_logs_' . date('Y-m-d_His') . '.csv"');
    }

    // Clear all system logs (requires password confirmation)
    public function clear(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Password is required',
                'errors' => $validator->errors()
            ], 422);
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid password'
            ], 403);
        }

        // Only admins can clear logs
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Only admins can clear system logs.'
            ], 403);
        }

        try {
            $count = SystemLog::count();
            
            // Delete all logs
            SystemLog::query()->delete();

            return response()->json([
                'message' => 'System logs cleared successfully',
                'deleted_count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear system logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Helper function to escape CSV values
    private function escapeCsv($value)
    {
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }
}
