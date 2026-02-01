<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Get stock movements for a product.
     */
    public function movements(Request $request, Product $product): JsonResponse
    {
        $query = $product->stockMovements()->with('user:id,name');

        // Filter by type
        if ($request->has('type') && in_array($request->type, ['in', 'out'])) {
            $query->where('type', $request->type);
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $movements = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Stock movements retrieved successfully',
            'data' => $movements->items(),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ],
        ]);
    }

    /**
     * Add stock to a product (Stock In).
     */
    public function stockIn(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $previousQuantity = $product->quantity;
        $addQuantity = (int) $validated['quantity'];
        $newQuantity = $previousQuantity + $addQuantity;

        DB::transaction(function () use ($product, $validated, $previousQuantity, $addQuantity, $newQuantity) {
            // Update product quantity
            $product->update(['quantity' => $newQuantity]);

            // Record stock movement
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => 'in',
                'quantity' => $addQuantity,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        $product->refresh();

        return response()->json([
            'status' => 'success',
            'message' => "Successfully added {$addQuantity} units to stock",
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'previous_quantity' => $previousQuantity,
                'added_quantity' => $addQuantity,
                'new_quantity' => $newQuantity,
                'status' => $product->status,
            ],
        ]);
    }

    /**
     * Remove stock from a product (Stock Out).
     */
    public function stockOut(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $previousQuantity = $product->quantity;
        $removeQuantity = (int) $validated['quantity'];

        // Validation: cannot remove more than available
        if ($removeQuantity > $previousQuantity) {
            return response()->json([
                'status' => 'error',
                'message' => "Insufficient stock. Available: {$previousQuantity}, Requested: {$removeQuantity}",
                'data' => null,
            ], 422);
        }

        $newQuantity = $previousQuantity - $removeQuantity;

        DB::transaction(function () use ($product, $validated, $previousQuantity, $removeQuantity, $newQuantity) {
            // Update product quantity
            $product->update(['quantity' => $newQuantity]);

            // Record stock movement
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => 'out',
                'quantity' => $removeQuantity,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        $product->refresh();

        return response()->json([
            'status' => 'success',
            'message' => "Successfully removed {$removeQuantity} units from stock",
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'previous_quantity' => $previousQuantity,
                'removed_quantity' => $removeQuantity,
                'new_quantity' => $newQuantity,
                'status' => $product->status,
            ],
        ]);
    }

    /**
     * Get all stock movements (for analytics/reporting).
     */
    public function allMovements(Request $request): JsonResponse
    {
        $query = StockMovement::with(['product:id,name,sku', 'user:id,name']);

        // Filter by type
        if ($request->has('type') && in_array($request->type, ['in', 'out'])) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $movements = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Stock movements retrieved successfully',
            'data' => $movements->items(),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ],
        ]);
    }

    /**
     * Get stock statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $fromDate = $request->get('from_date', now()->subDays(30)->toDateString());
        $toDate = $request->get('to_date', now()->toDateString());

        $stockInTotal = StockMovement::stockIn()
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->sum('quantity');

        $stockOutTotal = StockMovement::stockOut()
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->sum('quantity');

        $stockInCount = StockMovement::stockIn()
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->count();

        $stockOutCount = StockMovement::stockOut()
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->count();

        // Daily breakdown for charts
        $dailyStats = StockMovement::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw("SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) as stock_in"),
            DB::raw("SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) as stock_out")
        )
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Stock statistics retrieved successfully',
            'data' => [
                'period' => [
                    'from' => $fromDate,
                    'to' => $toDate,
                ],
                'totals' => [
                    'stock_in' => $stockInTotal,
                    'stock_out' => $stockOutTotal,
                    'net_change' => $stockInTotal - $stockOutTotal,
                ],
                'counts' => [
                    'stock_in_transactions' => $stockInCount,
                    'stock_out_transactions' => $stockOutCount,
                ],
                'daily_breakdown' => $dailyStats,
            ],
        ]);
    }
}
