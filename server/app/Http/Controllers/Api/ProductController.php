<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with('categoryRelation:id,name');

        // Search by name, SKU, or description
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Filter by category
        if ($request->has('category') && $request->category !== 'all') {
            $query->byCategory($request->category);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->byStatus($request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSorts = ['name', 'sku', 'price', 'quantity', 'status', 'created_at'];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        
        if ($request->get('all') === 'true') {
            // Return all products without pagination
            $products = $query->get();
            
            return response()->json($products);
        }

        $products = $query->paginate($perPage);

        // For backwards compatibility with the frontend that expects a simple array
        if (!$request->has('paginated') || $request->paginated !== 'true') {
            return response()->json($products->items());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Products retrieved successfully',
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'category' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'description' => 'nullable|string|max:2000',
            'image_url' => 'nullable|url|max:500',
            'imageUrl' => 'nullable|url|max:500',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'status' => 'nullable|in:in_stock,low_stock,out_of_stock',
        ]);

        // Get category_id from either category_id or category name
        $categoryId = $validated['category_id'] ?? null;
        if (!$categoryId && isset($validated['category'])) {
            $category = Category::firstOrCreate(
                ['name' => $validated['category']],
                ['description' => null, 'status' => 'active']
            );
            $categoryId = $category->id;
        }

        if (!$categoryId) {
            return response()->json([
                'message' => 'Category is required',
                'errors' => ['category_id' => ['The category field is required.']]
            ], 422);
        }

        // Handle both imageUrl and image_url
        $imageUrl = $validated['image_url'] ?? $validated['imageUrl'] ?? null;

        $product = Product::create([
            'category_id' => $categoryId,
            'name' => $validated['name'],
            'sku' => $validated['sku'],
            'description' => $validated['description'] ?? null,
            'image_url' => $imageUrl,
            'price' => (int) $validated['price'],
            'quantity' => (int) $validated['quantity'],
            // Status will be auto-set by model's saving event
        ]);

        // Record initial stock movement if quantity > 0
        if ($product->quantity > 0) {
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => 'in',
                'quantity' => $product->quantity,
                'previous_quantity' => 0,
                'new_quantity' => $product->quantity,
                'notes' => 'Initial stock',
            ]);
        }

        // Transform response to match frontend expectations
        $product->load('categoryRelation:id,name');
        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'category' => $product->category,
            'category_id' => $product->category_id,
            'description' => $product->description,
            'image_url' => $product->image_url,
            'price' => $product->price,
            'quantity' => $product->quantity,
            'status' => $product->status,
            'created_at' => $product->created_at->toISOString(),
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load('categoryRelation:id,name');

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'category' => $product->category,
            'description' => $product->description,
            'imageUrl' => $product->image_url,
            'price' => $product->price,
            'quantity' => $product->quantity,
            'status' => $product->status,
            'createdAt' => $product->created_at->toISOString(),
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],
            'category' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'image_url' => 'nullable|url|max:500',
            'imageUrl' => 'nullable|url|max:500',
            'price' => 'sometimes|required|numeric|min:0',
            'quantity' => 'sometimes|required|integer|min:0',
            'status' => 'nullable|in:in_stock,low_stock,out_of_stock',
        ]);

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }

        if (isset($validated['sku'])) {
            $updateData['sku'] = $validated['sku'];
        }

        if (isset($validated['category'])) {
            $category = Category::firstOrCreate(
                ['name' => $validated['category']],
                ['description' => null, 'status' => 'active']
            );
            $updateData['category_id'] = $category->id;
        }

        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description'];
        }

        // Handle both imageUrl and image_url
        if (isset($validated['image_url'])) {
            $updateData['image_url'] = $validated['image_url'];
        } elseif (isset($validated['imageUrl'])) {
            $updateData['image_url'] = $validated['imageUrl'];
        }

        if (isset($validated['price'])) {
            $updateData['price'] = (int) $validated['price'];
        }

        // Handle quantity changes with stock movement tracking
        if (isset($validated['quantity'])) {
            $oldQuantity = $product->quantity;
            $newQuantity = (int) $validated['quantity'];
            
            if ($oldQuantity !== $newQuantity) {
                $updateData['quantity'] = $newQuantity;
                
                // Record stock movement
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => $newQuantity > $oldQuantity ? 'in' : 'out',
                    'quantity' => abs($newQuantity - $oldQuantity),
                    'previous_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                    'notes' => 'Product update',
                ]);
            }
        }

        $product->update($updateData);
        $product->refresh();

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'category' => $product->category,
            'description' => $product->description,
            'imageUrl' => $product->image_url,
            'price' => $product->price,
            'quantity' => $product->quantity,
            'status' => $product->status,
            'createdAt' => $product->created_at->toISOString(),
        ]);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully',
            'data' => null,
        ]);
    }

    /**
     * Get product statistics for dashboard.
     */
    public function stats(): JsonResponse
    {
        $totalProducts = Product::count();
        $lowStockCount = Product::lowStock()->count();
        $outOfStockCount = Product::outOfStock()->count();
        $categoriesCount = Category::count();
        
        // Calculate total inventory value
        $totalValue = Product::sum(DB::raw('price * quantity'));

        // Get low stock products for alerts
        $lowStockProducts = Product::lowStock()
            ->with('categoryRelation:id,name')
            ->orderBy('quantity')
            ->limit(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category,
                    'quantity' => $product->quantity,
                ];
            });

        // Get category breakdown
        $categoryBreakdown = Category::withCount('products')
            ->orderByDesc('products_count')
            ->limit(5)
            ->get()
            ->map(function ($category) {
                return [
                    'name' => $category->name,
                    'value' => $category->products_count,
                ];
            });

        return response()->json([
            'totalProducts' => $totalProducts,
            'totalValue' => $totalValue,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'categoriesCount' => $categoriesCount,
            'lowStockProducts' => $lowStockProducts,
            'categoryBreakdown' => $categoryBreakdown,
        ]);
    }
}
