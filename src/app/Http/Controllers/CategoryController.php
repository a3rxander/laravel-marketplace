<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService
    ) {
        $this->middleware('auth:api')->except(['index', 'show', 'tree']);
    }

    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->getCategories($request->all());
        
        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Categories retrieved successfully'
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->getCategoryById($id);
        
        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Category retrieved successfully'
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);
        
        $category = $this->categoryService->createCategory($request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Category created successfully'
        ], 201);
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = $this->categoryService->getCategoryById($id);
        $this->authorize('update', $category);
        
        $updatedCategory = $this->categoryService->updateCategory($id, $request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $updatedCategory,
            'message' => 'Category updated successfully'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = $this->categoryService->getCategoryById($id);
        $this->authorize('delete', $category);
        
        $this->categoryService->deleteCategory($id);
        
        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    public function tree(): JsonResponse
    {
        $tree = $this->categoryService->getCategoryTree();
        
        return response()->json([
            'success' => true,
            'data' => $tree,
            'message' => 'Category tree retrieved successfully'
        ]);
    }

    public function featured(): JsonResponse
    {
        $featuredCategories = $this->categoryService->getFeaturedCategories();
        
        return response()->json([
            'success' => true,
            'data' => $featuredCategories,
            'message' => 'Featured categories retrieved successfully'
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $category = $this->categoryService->getCategoryById($id);
        $this->authorize('updateStatus', $category);
        
        $request->validate([
            'is_active' => 'required|boolean'
        ]);
        
        $updatedCategory = $this->categoryService->updateCategoryStatus($id, $request->is_active);
        
        return response()->json([
            'success' => true,
            'data' => $updatedCategory,
            'message' => 'Category status updated successfully'
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('reorder', Category::class);
        
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|integer|exists:categories,id',
            'categories.*.sort_order' => 'required|integer|min:0'
        ]);
        
        $this->categoryService->reorderCategories($request->categories);
        
        return response()->json([
            'success' => true,
            'message' => 'Categories reordered successfully'
        ]);
    }
}