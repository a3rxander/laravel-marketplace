<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {
        $this->middleware('auth:api')->except(['index', 'addToCart', 'updateCart', 'removeFromCart', 'clear']);
    }

    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();
        
        $cart = $this->cartService->getCart($userId, $sessionId);
        
        return response()->json([
            'success' => true,
            'data' => $cart,
            'message' => 'Cart retrieved successfully'
        ]);
    }

    public function addToCart(AddToCartRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();
        
        $cartData = $request->validated();
        $cartData['user_id'] = $userId;
        $cartData['session_id'] = $sessionId;
        
        $cartItem = $this->cartService->addToCart($cartData);
        
        return response()->json([
            'success' => true,
            'data' => $cartItem,
            'message' => 'Product added to cart successfully'
        ], 201);
    }

    public function updateCart(UpdateCartRequest $request, int $cartId): JsonResponse
    {
        $userId = auth()->id();
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();
        
        $cartItem = $this->cartService->updateCartItem(
            $cartId,
            $request->validated(),
            $userId,
            $sessionId
        );
        
        return response()->json([
            'success' => true,
            'data' => $cartItem,
            'message' => 'Cart updated successfully'
        ]);
    }

    public function removeFromCart(int $cartId, Request $request): JsonResponse
    {
        $userId = auth()->id();
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();
        
        $this->cartService->removeFromCart($cartId, $userId, $sessionId);
        
        return response()->json([
            'success' => true,
            'message' => 'Product removed from cart successfully'
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();
        
        $this->cartService->clearCart($userId, $sessionId);
        
        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully'
        ]);
    }

    public function count(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();
        
        $count = $this->cartService->getCartCount($userId, $sessionId);
        
        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
            'message' => 'Cart count retrieved successfully'
        ]);
    }

    public function total(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();
        
        $total = $this->cartService->getCartTotal($userId, $sessionId);
        
        return response()->json([
            'success' => true,
            'data' => $total,
            'message' => 'Cart total retrieved successfully'
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required for checkout'
            ], 401);
        }
        
        $userId = auth()->id();
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();
        
        $checkoutData = $this->cartService->prepareCheckout($userId, $sessionId);
        
        return response()->json([
            'success' => true,
            'data' => $checkoutData,
            'message' => 'Checkout data prepared successfully'
        ]);
    }

    public function mergeCart(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }
        
        $userId = auth()->id();
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();
        
        $mergedCart = $this->cartService->mergeGuestCart($userId, $sessionId);
        
        return response()->json([
            'success' => true,
            'data' => $mergedCart,
            'message' => 'Cart merged successfully'
        ]);
    }

    public function validate(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();
        
        $validation = $this->cartService->validateCart($userId, $sessionId);
        
        return response()->json([
            'success' => true,
            'data' => $validation,
            'message' => 'Cart validation completed'
        ]);
    }
}