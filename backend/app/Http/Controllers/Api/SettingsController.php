<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    // Get all system settings
    public function index()
    {
        $settings = SystemSetting::getAllSettings();
        
        return response()->json([
            'gallon_price' => $settings['gallon_price'] ?? '25.00',
            'delivery_fee' => $settings['delivery_fee'] ?? '50.00',
            'overdue_days_threshold' => $settings['overdue_days_threshold'] ?? '7',
            'missing_days_threshold' => $settings['missing_days_threshold'] ?? '30',
            'business_name' => $settings['business_name'] ?? 'Water Refilling Station',
            'business_address' => $settings['business_address'] ?? '',
            'business_phone' => $settings['business_phone'] ?? '',
        ]);
    }

    // Update system settings
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gallon_price' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
            'overdue_days_threshold' => 'nullable|integer|min:1',
            'missing_days_threshold' => 'nullable|integer|min:1',
            'business_name' => 'nullable|string|max:255',
            'business_address' => 'nullable|string',
            'business_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($request->all() as $key => $value) {
                if ($value !== null) {
                    SystemSetting::set($key, $value);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Settings updated successfully',
                'settings' => $this->index()->getData()
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get a specific setting
    public function show($key)
    {
        $value = SystemSetting::get($key);
        
        if ($value === null) {
            return response()->json([
                'message' => 'Setting not found'
            ], 404);
        }

        return response()->json([
            'key' => $key,
            'value' => $value
        ]);
    }

    // Update a specific setting
    public function updateSingle(Request $request, $key)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        SystemSetting::set($key, $request->input('value'));

        return response()->json([
            'message' => 'Setting updated successfully',
            'key' => $key,
            'value' => $request->input('value')
        ]);
    }

    // Clear application cache
    public function clearCache(Request $request)
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

        // Verify user password
        $user = Auth::user();
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid password'
            ], 401);
        }

        try {
            // Clear various Laravel caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return response()->json([
                'message' => 'Cache cleared successfully',
                'cleared' => [
                    'application_cache' => true,
                    'configuration_cache' => true,
                    'route_cache' => true,
                    'view_cache' => true,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
