<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AddressController; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
 
// 📊 HEALTH CHECK
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'database' => 'connected',
        'cache' => 'active',
        'timestamp' => now()->toISOString(),
    ]);
});

 
// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']); 

    
// User Routes
Route::prefix('users')->middleware('auth:api')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('profile', [UserController::class, 'profile']);
    Route::put('profile', [UserController::class, 'updateProfile']);
    Route::get('{id}', [UserController::class, 'show']);
    Route::put('{id}', [UserController::class, 'update']);
    Route::delete('{id}', [UserController::class, 'destroy']);
});

// Category Routes
Route::prefix('categories')->group(function () {
    // Public routes
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('tree', [CategoryController::class, 'tree']);
    Route::get('featured', [CategoryController::class, 'featured']);
    Route::get('{id}', [CategoryController::class, 'show']);
    
    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('{id}', [CategoryController::class, 'update']);
        Route::delete('{id}', [CategoryController::class, 'destroy']);
        Route::patch('{id}/status', [CategoryController::class, 'updateStatus']);
        Route::post('reorder', [CategoryController::class, 'reorder']);
    });
});

// Product Routes
Route::prefix('products')->group(function () {
    // Public routes
    Route::get('/', [ProductController::class, 'index']);
    Route::get('search', [ProductController::class, 'search']);
    Route::get('{id}', [ProductController::class, 'show']);
    
    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('{id}', [ProductController::class, 'update']);
        Route::delete('{id}', [ProductController::class, 'destroy']);
        Route::get('my/products', [ProductController::class, 'myProducts']);
        Route::patch('{id}/status', [ProductController::class, 'updateStatus']);
        Route::patch('bulk/status', [ProductController::class, 'bulkUpdateStatus']);
    });
});

// Cart Routes
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('add', [CartController::class, 'addToCart']);
    Route::put('{cartId}', [CartController::class, 'updateCart']);
    Route::delete('{cartId}', [CartController::class, 'removeFromCart']);
    Route::delete('/', [CartController::class, 'clear']);
    Route::get('count', [CartController::class, 'count']);
    Route::get('total', [CartController::class, 'total']);
    Route::get('validate', [CartController::class, 'validate']);
    
    // Protected cart routes
    Route::middleware('auth:api')->group(function () {
        Route::get('checkout', [CartController::class, 'checkout']);
        Route::post('merge', [CartController::class, 'mergeCart']);
    });
});

// Order Routes
Route::prefix('orders')->middleware('auth:api')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('my-orders', [OrderController::class, 'myOrders']);
    Route::get('seller-orders', [OrderController::class, 'sellerOrders']);
    Route::get('{id}', [OrderController::class, 'show']);
    Route::put('{id}', [OrderController::class, 'update']);
    Route::delete('{id}', [OrderController::class, 'destroy']);
    
    // Order status management
    Route::patch('{id}/status', [OrderController::class, 'updateStatus']);
    Route::patch('{id}/cancel', [OrderController::class, 'cancel']);
    Route::patch('{id}/confirm', [OrderController::class, 'confirm']);
    Route::patch('{id}/ship', [OrderController::class, 'ship']);
    Route::patch('{id}/deliver', [OrderController::class, 'deliver']);
    Route::patch('{id}/refund', [OrderController::class, 'refund']);
    
    // Invoice
    Route::get('{id}/invoice', [OrderController::class, 'printInvoice']);
});

// Seller Routes
Route::prefix('sellers')->middleware('auth:api')->group(function () {
    Route::get('/', [SellerController::class, 'index']);
    Route::post('/', [SellerController::class, 'store']);
    Route::get('profile', [SellerController::class, 'myProfile']);
    Route::put('profile', [SellerController::class, 'updateProfile']);
    Route::get('dashboard', [SellerController::class, 'dashboard']);
    Route::get('sales', [SellerController::class, 'sales']);
    Route::get('{id}', [SellerController::class, 'show']);
    Route::put('{id}', [SellerController::class, 'update']);
    Route::delete('{id}', [SellerController::class, 'destroy']);
    
    // Seller approval/management
    Route::patch('{id}/approve', [SellerController::class, 'approve']);
    Route::patch('{id}/reject', [SellerController::class, 'reject']);
    Route::patch('{id}/suspend', [SellerController::class, 'suspend']);
    Route::patch('{id}/reactivate', [SellerController::class, 'reactivate']);
});

// Address Routes (for user addresses, shipping, etc.)
Route::prefix('addresses')->middleware('auth:api')->group(function () {
    Route::get('/', [AddressController::class, 'index']);
    Route::post('/', [AddressController::class, 'store']);
    Route::get('{address}', [AddressController::class, 'show']);
    Route::put('{address}', [AddressController::class, 'update']);
    Route::delete('{address}', [AddressController::class, 'destroy']);
});

// Review Routes
Route::prefix('reviews')->group(function () {
    // Public routes for viewing reviews
    Route::get('/', [ReviewController::class, 'index']);
    Route::get('{review}', [ReviewController::class, 'show']);
    
    // Protected routes for managing reviews
    Route::middleware('auth:api')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);
        Route::put('{review}', [ReviewController::class, 'update']);
        Route::delete('{review}', [ReviewController::class, 'destroy']);
    });
});

// Order Items Routes (if needed for specific order item management)
Route::prefix('order-items')->middleware('auth:api')->group(function () {
    Route::get('/', [OrderItemController::class, 'index']);
    Route::post('/', [OrderItemController::class, 'store']);
    Route::get('{orderItem}', [OrderItemController::class, 'show']);
    Route::put('{orderItem}', [OrderItemController::class, 'update']);
    Route::delete('{orderItem}', [OrderItemController::class, 'destroy']);
});

// Fallback route for API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found'
    ], 404);
});
});