<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\ProductFilterRequest;

class ProductController extends Controller
{
    /**
     * List active products.
     *
     * Retrieve a paginated list of active products with their categories.
     *
     * Available filters:
     * - search: Search by product name, description, or SKU.
     * - category_id: Filter products by category.
     * - min_price: Filter by minimum price.
     * - max_price: Filter by maximum price.
     *
     * Available sorting:
     * - name
     * - price
     * - created_at
     *
     * Sorting direction:
     * - asc
     * - desc
     *
     * Pagination:
     * - per_page: Number of products per page (1-100).
     *
     * Only active products are visible through this public endpoint.
     */
    public function index(ProductFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $query = Product::with('category')
            ->where('is_active', true)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            });

        // Search by name, description, or SKU.
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        });

        // Filter by category.
        $query->when($filters['category_id'] ?? null, function ($query, $categoryId) {
            $query->where('category_id', $categoryId);
        });

        // Filter by minimum price.
        $query->when(isset($filters['min_price']), function ($query) use ($filters) {
            $query->where('price', '>=', $filters['min_price']);
        });

        // Filter by maximum price.
        $query->when(isset($filters['max_price']), function ($query) use ($filters) {
            $query->where('price', '<=', $filters['max_price']);
        });

        // Sorting.
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        $query->orderBy($sortBy, $sortDirection)
            ->orderBy('id', 'desc');

        // Pagination.
        $products = $query
            ->paginate($filters['per_page'] ?? 10)
            ->appends($request->query());

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Get a single active product.
     *
     * Retrieve detailed information about an active product,
     * including its category.
     *
     * This endpoint is publicly accessible.
     *
     * @param Product $product The product to retrieve.
     */
    public function show(Product $product): JsonResponse
    {
        abort_unless($product->is_active, 404);
        abort_unless(
            $product->category?->is_active,
            404
        );
        $product->load('category');

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Create a new product.
     *
     * Create a product using the provided product information.
     *
     * Required fields and validation rules are defined
     * in StoreProductRequest.
     *
     * This endpoint requires Admin authentication.
     *
     * @param StoreProductRequest $request Validated product data.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        $product->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => $product,
        ], 201);
    }

    /**
     * Update an existing product.
     *
     * Update the information of an existing product.
     * Only validated fields are updated.
     *
     * Required fields and validation rules are defined
     * in UpdateProductRequest.
     *
     * This endpoint requires Admin authentication.
     *
     * @param UpdateProductRequest $request Validated product data.
     * @param Product $product The product to update.
     */
    public function update(
        UpdateProductRequest $request,
        Product $product
    ): JsonResponse {
        $product->update($request->validated());

        $product->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => $product,
        ]);
    }

    /**
     * Delete a product.
     *
     * Permanently delete the specified product.
     *
     * Products referenced by existing order items cannot be deleted
     * while the foreign key constraint is active.
     *
     * This endpoint requires Admin authentication.
     *
     * @param Product $product The product to delete.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }
}
