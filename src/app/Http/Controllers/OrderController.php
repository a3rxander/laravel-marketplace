<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {
        $this->middleware('auth:api');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);
        
        $orders = $this->orderService->getOrders($request->all());
        
        return response()->json([
            'success' => true,
            'data' => $orders,
            'message' => 'Orders retrieved successfully'
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('view', $order);
        
        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order retrieved successfully'
        ]);
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);
        
        $orderData = $request->validated();
        $orderData['user_id'] = auth()->id();
        
        $order = $this->orderService->createOrder($orderData);

        if ($order->payment_status === 'pending') 
        {
            // Simular referencia de pago (o usar la real de tu gateway)
            $paymentReference = 'PAY-' . strtoupper(Str::random(10));
            
            // Despachar job de procesamiento
            \App\Jobs\ProcessOrderPayment::dispatch($order, $paymentReference)
                ->onQueue('orders')
                ->delay(now()->addSeconds(5));
        }
        
        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order created successfully'
        ], 201);
    }

    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('update', $order);
        
        $updatedOrder = $this->orderService->updateOrder($id, $request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $updatedOrder,
            'message' => 'Order updated successfully'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('delete', $order);
        
        $this->orderService->deleteOrder($id);
        
        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    }

    public function myOrders(Request $request): JsonResponse
    {
        $orders = $this->orderService->getUserOrders(auth()->id(), $request->all());
        
        return response()->json([
            'success' => true,
            'data' => $orders,
            'message' => 'Your orders retrieved successfully'
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('updateStatus', $order);
        
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
            'notes' => 'nullable|string|max:1000'
        ]);
        
        $updatedOrder = $this->orderService->updateOrderStatus(
            $id,
            $request->status,
            $request->notes
        );
        
        return response()->json([
            'success' => true,
            'data' => $updatedOrder,
            'message' => 'Order status updated successfully'
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('cancel', $order);
        
        $request->validate([
            'cancellation_reason' => 'nullable|string|max:1000'
        ]);
        
        $cancelledOrder = $this->orderService->cancelOrder($id, $request->cancellation_reason);
        
        return response()->json([
            'success' => true,
            'data' => $cancelledOrder,
            'message' => 'Order cancelled successfully'
        ]);
    }

    public function confirm(int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('confirm', $order);
        
        $confirmedOrder = $this->orderService->confirmOrder($id);

        // Dispatch jobs for sending email and updating inventory
        \App\Jobs\SendOrderConfirmationEmail::dispatch($confirmedOrder)
            ->onQueue('emails')
            ->delay(now()->addSeconds(10));

        \App\Jobs\UpdateProductInventory::dispatch($confirmedOrder)
            ->onQueue('inventory')
            ->delay(now()->addSeconds(20));
        
        return response()->json([
            'success' => true,
            'data' => $confirmedOrder,
            'message' => 'Order confirmed successfully'
        ]);
    }

    public function ship(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('ship', $order);
        
        $request->validate([
            'tracking_number' => 'required|string|max:100',
            'shipping_method' => 'nullable|string|max:100'
        ]);
        
        $shippedOrder = $this->orderService->shipOrder(
            $id,
            $request->tracking_number,
            $request->shipping_method
        );
        
        return response()->json([
            'success' => true,
            'data' => $shippedOrder,
            'message' => 'Order shipped successfully'
        ]);
    }

    public function deliver(int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('deliver', $order);
        
        $deliveredOrder = $this->orderService->deliverOrder($id);
        
        return response()->json([
            'success' => true,
            'data' => $deliveredOrder,
            'message' => 'Order delivered successfully'
        ]);
    }

    public function refund(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('refund', $order);
        
        $request->validate([
            'refund_amount' => 'nullable|numeric|min:0',
            'refund_reason' => 'nullable|string|max:1000'
        ]);
        
        $refundedOrder = $this->orderService->refundOrder(
            $id,
            $request->refund_amount,
            $request->refund_reason
        );
        
        return response()->json([
            'success' => true,
            'data' => $refundedOrder,
            'message' => 'Order refunded successfully'
        ]);
    }

    public function sellerOrders(Request $request): JsonResponse
    {
        $seller = auth()->user()->seller;
        
        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => 'Seller profile not found'
            ], 404);
        }
        
        $orders = $this->orderService->getSellerOrders($seller->id, $request->all());
        
        return response()->json([
            'success' => true,
            'data' => $orders,
            'message' => 'Seller orders retrieved successfully'
        ]);
    }

    public function printInvoice(int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        $this->authorize('view', $order);
        
        $invoice = $this->orderService->generateInvoice($id);
        
        return response()->json([
            'success' => true,
            'data' => $invoice,
            'message' => 'Invoice generated successfully'
        ]);
    }

    public function handlePaymentWebhook(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_reference' => 'required|string',
            'status' => 'required|in:success,failed'
        ]);

        $order = Order::find($request->order_id);
        
        if ($request->status === 'success') {
            // Procesar pago exitoso usando nuestro job
            \App\Jobs\ProcessOrderPayment::dispatch($order, $request->payment_reference)
                ->onQueue('orders');
                
            return response()->json([
                'success' => true,
                'message' => 'Payment webhook processed successfully'
            ]);
        }
        
        // Manejar pago fallido
        $order->update([
            'payment_status' => 'failed',
            'status' => 'cancelled'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Payment failed, order cancelled'
        ]);
    }
}