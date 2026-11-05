<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * List all categories.
     *
     * Retrieve a paginated list of categories.
     * Each category includes the total number of related products.
     *
     * This endpoint is publicly accessible.
     *
     * Pagination:
     * - 10 categories per page.
     */

    public function index(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->withCount([
                'products' => function ($query) {
                    $query->where('is_active', true);
                },
            ])
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }


    /**
     * Create a new category.
     *
     * Create a category using the provided category information.
     *
     * Required fields and validation rules are defined
     * in StoreCategoryRequest.
     *
     * This endpoint requires Admin authentication.
     *
     * @param StoreCategoryRequest $request Validated category data.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => $category,
        ], 201);
    }

    /**
     * Get a single category.
     *
     * Retrieve detailed information about a category,
     * including its related products.
     *
     * This endpoint is publicly accessible.
     *
     * @param Category $category The category to retrieve.
     */

    public function show(Category $category): JsonResponse
    {
        abort_unless($category->is_active, 404);

        $category->load([
            'products' => function ($query) {
                $query->where('is_active', true);
            },
        ]);

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }


    /**
     * Update an existing category.
     *
     * Update the information of an existing category.
     * Only validated fields are updated.
     *
     * Required fields and validation rules are defined
     * in UpdateCategoryRequest.
     *
     * This endpoint requires Admin authentication.
     *
     * @param UpdateCategoryRequest $request Validated category data.
     * @param Category $category The category to update.
     */
    public function update(
        UpdateCategoryRequest $request,
        Category $category
    ): JsonResponse {
        $category->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => $category,
        ]);
    }

    /**
     * Delete a category.
     *
     * Permanently delete the specified category.
     *
     * Categories containing products cannot be deleted
     * because of the database foreign key constraint.
     *
     * This endpoint requires Admin authentication.
     *
     * @param Category $category The category to delete.
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }
}
