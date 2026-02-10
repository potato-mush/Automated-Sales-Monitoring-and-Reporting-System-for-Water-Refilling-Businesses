<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role
        if ($request->has('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active == '1');
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $employees = $query->get()->makeHidden(['password', 'remember_token']);

        return response()->json($employees);
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,employee',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $employee = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => $request->input('is_active', true),
        ]);

        return response()->json([
            'message' => 'Employee created successfully',
            'employee' => $employee->makeHidden(['password', 'remember_token'])
        ], 201);
    }

    /**
     * Display the specified employee
     */
    public function show($id)
    {
        $employee = User::findOrFail($id);
        return response()->json($employee->makeHidden(['password', 'remember_token']));
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, $id)
    {
        $employee = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'role' => 'sometimes|required|in:admin,employee',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $request->only(['name', 'email', 'role', 'is_active']);

        // Only update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $employee->update($updateData);

        return response()->json([
            'message' => 'Employee updated successfully',
            'employee' => $employee->makeHidden(['password', 'remember_token'])
        ]);
    }

    /**
     * Remove the specified employee (with admin protection)
     */
    public function destroy($id)
    {
        $employee = User::findOrFail($id);

        // Prevent deletion of admin accounts
        if ($employee->role === 'admin') {
            return response()->json([
                'message' => 'Admin accounts cannot be deleted'
            ], 403);
        }

        // Check if employee has transactions
        $transactionCount = $employee->transactions()->count();
        if ($transactionCount > 0) {
            return response()->json([
                'message' => 'Cannot delete employee with existing transactions. Consider deactivating instead.',
                'transaction_count' => $transactionCount
            ], 400);
        }

        $employee->delete();

        return response()->json([
            'message' => 'Employee deleted successfully'
        ]);
    }

    /**
     * Toggle employee active status
     */
    public function toggleStatus($id)
    {
        $employee = User::findOrFail($id);
        $employee->is_active = !$employee->is_active;
        $employee->save();

        return response()->json([
            'message' => 'Employee status updated successfully',
            'employee' => $employee->makeHidden(['password', 'remember_token'])
        ]);
    }

    /**
     * Get employee statistics
     */
    public function statistics()
    {
        $totalEmployees = User::count();
        $activeEmployees = User::active()->count();
        $adminCount = User::admin()->count();
        $employeeCount = User::employee()->count();

        return response()->json([
            'total_employees' => $totalEmployees,
            'active_employees' => $activeEmployees,
            'admin_count' => $adminCount,
            'employee_count' => $employeeCount,
        ]);
    }
}
