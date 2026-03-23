<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    private const TRACKED_UPDATE_FIELDS = [
        'item_name',
        'category',
        'quantity',
        'unit',
        'unit_price',
        'reorder_level',
        'description',
    ];

    // Display a listing of inventory items
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

    // Store a newly created inventory item
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

        $userName = auth()->user()?->name ?? 'User';
        $details = sprintf(
            '%s added %s with category %s, quantity %s %s, unit price %s, reorder level %s.',
            $userName,
            $item->item_name,
            $item->category,
            $item->quantity,
            $item->unit,
            $this->formatPrice($item->unit_price),
            $item->reorder_level
        );

        $this->logInventoryActivity($request, 'inventory_create', $details);

        return response()->json([
            'message' => 'Inventory item created successfully',
            'item' => $item
        ], 201);
    }

    // Display the specified inventory item
    public function show($id)
    {
        $item = InventoryItem::findOrFail($id);
        return response()->json($item);
    }

    // Update the specified inventory item
    public function update(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);
        $before = $item->only(self::TRACKED_UPDATE_FIELDS);

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

        $after = $item->only(self::TRACKED_UPDATE_FIELDS);
        $changes = [];

        foreach (self::TRACKED_UPDATE_FIELDS as $field) {
            if (!array_key_exists($field, $request->all())) {
                continue;
            }

            $beforeValue = $before[$field] ?? null;
            $afterValue = $after[$field] ?? null;

            if ((string) $beforeValue === (string) $afterValue) {
                continue;
            }

            $changes[] = sprintf(
                '%s from "%s" to "%s"',
                $this->fieldLabel($field),
                $this->formatFieldValue($field, $beforeValue),
                $this->formatFieldValue($field, $afterValue)
            );
        }

        $userName = auth()->user()?->name ?? 'User';
        $details = count($changes) > 0
            ? sprintf('%s edited %s: %s.', $userName, $before['item_name'] ?? $item->item_name, implode('; ', $changes))
            : sprintf('%s edited %s with no field changes detected.', $userName, $item->item_name);

        $this->logInventoryActivity($request, 'inventory_update', $details);

        return response()->json([
            'message' => 'Inventory item updated successfully',
            'item' => $item
        ]);
    }

    // Remove the specified inventory item
    public function destroy($id)
    {
        $item = InventoryItem::findOrFail($id);
        $userName = auth()->user()?->name ?? 'User';
        $details = sprintf(
            '%s deleted %s with final quantity %s %s.',
            $userName,
            $item->item_name,
            $item->quantity,
            $item->unit
        );

        $item->delete();

        $this->logInventoryActivity(request(), 'inventory_delete', $details);

        return response()->json([
            'message' => 'Inventory item deleted successfully'
        ]);
    }

    // Get inventory statistics
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

    // Adjust inventory quantity
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
        $oldQuantity = $item->quantity;
        $newQuantity = $item->quantity + $request->adjustment;

        if ($newQuantity < 0) {
            return response()->json([
                'message' => 'Adjustment would result in negative quantity'
            ], 400);
        }

        $item->quantity = $newQuantity;
        $item->save();

        $userName = auth()->user()?->name ?? 'User';
        $deltaSign = $request->adjustment >= 0 ? '+' : '';
        $details = sprintf(
            '%s adjusted the %s quantity from %s to %s (%s%s).',
            $userName,
            $item->item_name,
            $oldQuantity,
            $newQuantity,
            $deltaSign,
            $request->adjustment
        );

        $this->logInventoryActivity($request, 'inventory_adjust', $details);

        return response()->json([
            'message' => 'Inventory adjusted successfully',
            'item' => $item
        ]);
    }

    private function logInventoryActivity(Request $request, string $action, ?string $details = null): void
    {
        $user = auth()->user();

        if (!$user) {
            return;
        }

        try {
            SystemLog::logActivity($user, $action, $request, 'web', $details);
        } catch (\Throwable $e) {
            // Avoid failing inventory transactions if logging fails.
        }
    }

    private function formatPrice($value): string
    {
        return 'PHP ' . number_format((float) $value, 2);
    }

    private function fieldLabel(string $field): string
    {
        $labels = [
            'item_name' => 'name',
            'category' => 'category',
            'quantity' => 'quantity',
            'unit' => 'unit',
            'unit_price' => 'unit price',
            'reorder_level' => 'reorder level',
            'description' => 'description',
        ];

        return $labels[$field] ?? $field;
    }

    private function formatFieldValue(string $field, $value): string
    {
        if ($value === null || $value === '') {
            return 'none';
        }

        if ($field === 'unit_price') {
            return $this->formatPrice($value);
        }

        return (string) $value;
    }
}
