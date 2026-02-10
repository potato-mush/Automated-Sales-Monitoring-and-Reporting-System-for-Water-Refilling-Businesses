<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    /**
     * Display a listing of inventory items
     */
    public function index(Request $request)
    {
        $query = InventoryItem::query();

        // Filter by category
        if ($request->has('category') && $request->category !== 'all') {
            $query->category($request->category);
        }

        // Filter by low stock
        if ($request->has('low_stock') && $request->low_stock == '1') {
            $query->lowStock();
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where('item_name', 'like', '%' . $request->search . '%');
        }

        // Sort
        $sortBy = $request->input('sort_by', 'item_name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $items = $query->get();

        return response()->json($items);
    }

    /**
     * Store a newly created inventory item
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_name' => 'required|string|max:255',
            'category' => 'required|in:caps,seals,purification,supplies',
            'quantity' => 'required|integer|min:0',
            'unit' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $item = InventoryItem::create($request->all());

        return response()->json([
            'message' => 'Inventory item created successfully',
            'item' => $item
        ], 201);
    }

    /**
     * Display the specified inventory item
     */
    public function show($id)
    {
        $item = InventoryItem::findOrFail($id);
        return response()->json($item);
    }

    /**
     * Update the specified inventory item
     */
    public function update(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'item_name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|in:caps,seals,purification,supplies',
            'quantity' => 'sometimes|required|integer|min:0',
            'unit' => 'sometimes|required|string|max:50',
            'unit_price' => 'sometimes|required|numeric|min:0',
            'reorder_level' => 'sometimes|required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $item->update($request->all());

        return response()->json([
            'message' => 'Inventory item updated successfully',
            'item' => $item
        ]);
    }

    /**
     * Remove the specified inventory item
     */
    public function destroy($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => 'Inventory item deleted successfully'
        ]);
    }

    /**
     * Get inventory statistics
     */
    public function statistics()
    {
        $totalItems = InventoryItem::count();
        $totalValue = InventoryItem::all()->sum('total_value');
        $lowStockItems = InventoryItem::lowStock()->count();
        
        $categoryStats = [
            'caps' => InventoryItem::category('caps')->count(),
            'seals' => InventoryItem::category('seals')->count(),
            'purification' => InventoryItem::category('purification')->count(),
            'supplies' => InventoryItem::category('supplies')->count(),
        ];

        return response()->json([
            'total_items' => $totalItems,
            'total_value' => $totalValue,
            'low_stock_items' => $lowStockItems,
            'category_stats' => $categoryStats,
        ]);
    }

    /**
     * Adjust inventory quantity
     */
    public function adjustQuantity(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'adjustment' => 'required|integer',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $item = InventoryItem::findOrFail($id);
        $newQuantity = $item->quantity + $request->adjustment;

        if ($newQuantity < 0) {
            return response()->json([
                'message' => 'Adjustment would result in negative quantity'
            ], 400);
        }

        $item->quantity = $newQuantity;
        $item->save();

        return response()->json([
            'message' => 'Inventory adjusted successfully',
            'item' => $item
        ]);
    }
}
