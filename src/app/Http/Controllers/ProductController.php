<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\CreateProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;
    
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService; 
    }

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->getProducts($request->all());
        
        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => 'Products retrieved successfully'
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);
        
        // Increment view count for public access
        $this->productService->incrementViewCount($id);
        
        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Product retrieved successfully'
        ]);
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);
        
        $productData = $request->validated();
        
        // For sellers, automatically set seller_id
        if (auth()->user()->hasRole('seller')) {
            $productData['seller_id'] = auth()->user()->seller->id;
        }
        
        $product = $this->productService->createProduct($productData);
        
        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Product created successfully'
        ], 201);
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);
        $this->authorize('update', $product);
        
        $updatedProduct = $this->productService->updateProduct($id, $request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $updatedProduct,
            'message' => 'Product updated successfully'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);
        $this->authorize('delete', $product);
        
        $this->productService->deleteProduct($id);
        
        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    public function myProducts(Request $request): JsonResponse
    {
        $this->authorize('viewOwn', Product::class);
        
        $sellerId = auth()->user()->seller->id;
        $products = $this->productService->getSellerProducts($sellerId, $request->all());
        
        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => 'Your products retrieved successfully'
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);
        $this->authorize('updateStatus', $product);
        
        $request->validate([
            'status' => 'required|in:draft,active,inactive,archived'
        ]);
        
        $updatedProduct = $this->productService->updateProductStatus($id, $request->status);
        
        return response()->json([
            'success' => true,
            'data' => $updatedProduct,
            'message' => 'Product status updated successfully'
        ]);
    }

    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $this->authorize('bulkUpdate', Product::class);
        
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
            'status' => 'required|in:draft,active,inactive,archived'
        ]);
        
        $updatedCount = $this->productService->bulkUpdateStatus(
            $request->product_ids,
            $request->status
        );
        
        return response()->json([
            'success' => true,
            'data' => ['updated_count' => $updatedCount],
            'message' => "Updated {$updatedCount} products successfully"
        ]);
    }

    /**
     * Búsqueda básica con Scout/Elasticsearch
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|min:1', // Cambiado para permitir búsquedas vacías
            'category_id' => 'nullable|exists:categories,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort_by' => 'nullable|in:name,price,rating,created_at',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);
        
        $results = $this->productService->searchProducts($request->all());
        
        return response()->json([
            'success' => true,
            'data' => $results,
            'search_query' => $request->input('q'),
            'filters_applied' => $request->except(['q', 'page', 'per_page']),
            'message' => 'Search results retrieved successfully'
        ]);
    }

    /**
     * Búsqueda avanzada con facetas y agregaciones
     */
    public function advancedSearch(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|min:1',
            'category_id' => 'nullable|exists:categories,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort_by' => 'nullable|in:name,price,rating,created_at',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'include_facets' => 'nullable|boolean'
        ]);
        
        $results = $this->productService->advancedSearchProducts($request->all());
        
        return response()->json([
            'success' => true,
            'data' => $results,
            'message' => 'Advanced search results retrieved successfully'
        ]);
    }

    /**
     * Autocomplete para búsqueda
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:50'
        ]);
        
        $suggestions = $this->productService->getSearchSuggestions($request->input('q'));
        
        return response()->json([
            'success' => true,
            'data' => $suggestions,
            'message' => 'Autocomplete suggestions retrieved successfully'
        ]);
    }

    /**
     * Productos similares usando Scout
     */
    public function similar(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:20'
        ]);
        
        $limit = $request->input('limit', 6);
        $similarProducts = $this->productService->getSimilarProducts($id, $limit);
        
        return response()->json([
            'success' => true,
            'data' => $similarProducts,
            'message' => 'Similar products retrieved successfully'
        ]);
    }

    /**
     * Endpoint para debug de Scout
     */
    public function searchDebug(Request $request): JsonResponse
    {
        if (!app()->environment(['local', 'testing'])) {
            abort(404);
        }
        
        $request->validate([
            'q' => 'required|string|min:1'
        ]);
        
        $debugInfo = $this->productService->getSearchDebugInfo($request->input('q'));
        
        return response()->json([
            'success' => true,
            'data' => $debugInfo,
            'message' => 'Search debug information retrieved'
        ]);
    }

    /**
     * Reindexar productos en Elasticsearch
     */
    public function reindex(Request $request): JsonResponse
    {
        if (!auth()->user()->can('admin.products.reindex')) {
            abort(403);
        }
        
        $request->validate([
            'batch_size' => 'nullable|integer|min:10|max:1000'
        ]);
        
        $batchSize = $request->input('batch_size', 100);
        $result = $this->productService->reindexProducts($batchSize);
        
        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Products reindexed successfully'
        ]);
    }
}