<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Requests\UpdatePaymentStatusRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\AdminOrderFilterRequest;

class OrderController extends Controller
{
    /**
     * Create a new order from the authenticated user's cart.
     *
     * Validates product availability and stock, calculates order totals,
     * decreases product stock, creates order items, and clears the cart.
     *
     * @authenticated
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Order placed successfully.",
     *   "data": {
     *     "id": 1,
     *     "order_number": "SF-ABC123456789",
     *     "status": "pending",
     *     "payment_method": "cash_on_delivery",
     *     "payment_status": "unpaid",
     *     "subtotal": "500.00",
     *     "shipping_fee": "0.00",
     *     "total": "500.00"
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "cart": ["Your cart is empty."]
     *   }
     * }
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $user = $request->user();

        $order = DB::transaction(function () use ($request, $user) {

            $cart = $user->cart()
                ->lockForUpdate()
                ->first();

            if (!$cart) {
                throw ValidationException::withMessages([
                    'cart' => ['Your cart is empty.'],
                ]);
            }

            $cartItems = $cart->items()
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => ['Your cart is empty.'],
                ]);
            }

            $subtotalCents = 0;
            $orderItems = [];

            foreach ($cartItems as $cartItem) {

                $product = Product::whereKey($cartItem->product_id)
                    ->lockForUpdate()
                    ->first();

                if (
                    !$product ||
                    !$product->is_active ||
                    $product->stock_quantity < $cartItem->quantity
                ) {
                    throw ValidationException::withMessages([
                        'cart' => [
                            "Product ID {$cartItem->product_id} is unavailable or has insufficient stock."
                        ],
                    ]);
                }

                // Convert price to cents to avoid float arithmetic.
                $unitPriceCents = (int) round(
                    (float) $product->price * 100
                );

                $lineTotalCents = $unitPriceCents * $cartItem->quantity;

                $subtotalCents += $lineTotalCents;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'unit_price' => number_format(
                        $unitPriceCents / 100,
                        2,
                        '.',
                        ''
                    ),
                    'quantity' => $cartItem->quantity,
                    'total' => number_format(
                        $lineTotalCents / 100,
                        2,
                        '.',
                        ''
                    ),
                ];

                // Decrease available stock.
                $product->decrement(
                    'stock_quantity',
                    $cartItem->quantity
                );
            }

            $subtotal = number_format(
                $subtotalCents / 100,
                2,
                '.',
                ''
            );

            $shippingFee = '0.00';

            $total = number_format(
                $subtotalCents / 100,
                2,
                '.',
                ''
            );

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'SF-' . Str::upper(Str::random(12)),
                'status' => 'pending',
                'payment_method' => 'cash_on_delivery',
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'shipping_city' => $request->shipping_city,
                'shipping_address' => $request->shipping_address,
                'notes' => $request->notes,
            ]);

            $order->items()->createMany($orderItems);

            // Empty the cart after successful checkout.
            $cart->items()->delete();

            return $order;
        });

        $order->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'data' => $order,
        ], 201);
    }

    /**
     * Get the authenticated user's orders.
     *
     * Returns a paginated list of orders belonging to the current user.
     *
     * @authenticated
     *
     * @queryParam page integer The pagination page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "current_page": 1,
     *     "data": []
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with('items')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get a single order belonging to the authenticated user.
     *
     * Users can only access their own orders.
     *
     * @authenticated
     *
     * @urlParam order integer required The order ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "order_number": "SF-ABC123456789",
     *     "status": "pending"
     *   }
     * }
     *
     * @response 404 {
     *   "message": "Not Found"
     * }
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless(
            $order->user_id === $request->user()->id,
            404
        );

        $order->load('items');

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Admin: Get all orders with optional filters.
     *
     * Returns a paginated list of all orders, including customer information
     * and order items.
     *
     * @authenticated
     *
     * @queryParam status string Filter orders by their status. Example: pending
     * @queryParam payment_status string Filter orders by payment status. Example: unpaid
     * @queryParam page integer The pagination page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "current_page": 1,
     *     "data": []
     *   }
     * }
     */
    /**
     * Admin: Get all orders with optional filters.
     *
     * @authenticated
     */
    public function adminIndex(AdminOrderFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $query = Order::with([
            'user:id,name,email',
            'items',
        ]);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        $orders = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Admin: Get complete order details.
     *
     * Includes customer information and all order items.
     *
     * @authenticated
     *
     * @urlParam order integer required The order ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "order_number": "SF-ABC123456789",
     *     "status": "pending"
     *   }
     * }
     */
    public function adminShow(Order $order): JsonResponse
    {
        $order->load([
            'user:id,name,email',
            'items',
        ]);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Admin: Update an order's status.
     *
     * Validates the requested status transition.
     * Restores product stock when an order is cancelled or returned.
     *
     * Allowed transitions:
     * - pending: confirmed, cancelled
     * - confirmed: processing, cancelled
     * - processing: shipped, cancelled
     * - shipped: delivered
     * - delivered: returned
     *
     * @authenticated
     *
     * @urlParam order integer required The order ID. Example: 1
     * @bodyParam status string required The new order status. Example: confirmed
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Order status updated successfully.",
     *   "data": {
     *     "id": 1,
     *     "status": "confirmed"
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "status": ["This status transition is not allowed."]
     *   }
     * }
     */
    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order
    ): JsonResponse {

        $updatedOrder = DB::transaction(function () use ($request, $order) {

            $lockedOrder = Order::with('items')
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newStatus = $request->validated()['status'];

            $allowedTransitions = [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['processing', 'cancelled'],
                'processing' => ['shipped', 'cancelled'],
                'shipped' => ['delivered'],
                'delivered' => ['returned'],
                'returned' => [],
                'cancelled' => [],
            ];

            if (!in_array(
                $newStatus,
                $allowedTransitions[$lockedOrder->status] ?? [],
                true
            )) {
                throw ValidationException::withMessages([
                    'status' => [
                        'This status transition is not allowed.',
                    ],
                ]);
            }

            // Restore stock after cancellation or an accepted return.
            if (in_array($newStatus, ['cancelled', 'returned'], true)) {

                foreach ($lockedOrder->items->sortBy('product_id') as $item) {

                    // Product may have been deleted.
                    if (!$item->product_id) {
                        continue;
                    }

                    $product = Product::whereKey($item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($product) {
                        $product->increment(
                            'stock_quantity',
                            $item->quantity
                        );
                    }
                }
            }

            $lockedOrder->update([
                'status' => $newStatus,
            ]);

            return $lockedOrder->fresh('items');
        });

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => $updatedOrder,
        ]);
    }

    /**
     * Admin: Update an order's payment status.
     *
     * Payment can only be marked as paid after delivery.
     * Refunds are allowed only for paid orders that have been returned.
     *
     * @authenticated
     *
     * @urlParam order integer required The order ID. Example: 1
     * @bodyParam payment_status string required The new payment status: paid or refunded. Example: paid
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Payment status updated successfully.",
     *   "data": {
     *     "id": 1,
     *     "payment_status": "paid"
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "payment_status": ["Payment can only be marked as paid after delivery."]
     *   }
     * }
     */
    public function updatePaymentStatus(
        UpdatePaymentStatusRequest $request,
        Order $order
    ): JsonResponse {

        $updatedOrder = DB::transaction(function () use ($request, $order) {

            $lockedOrder = Order::whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newStatus = $request->validated()['payment_status'];

            // Payment can only be marked as paid after delivery.
            if (
                $newStatus === 'paid' &&
                $lockedOrder->status !== 'delivered'
            ) {
                throw ValidationException::withMessages([
                    'payment_status' => [
                        'Payment can only be marked as paid after delivery.',
                    ],
                ]);
            }

            // Only paid orders can be refunded.
            if (
                $newStatus === 'refunded' &&
                $lockedOrder->payment_status !== 'paid'
            ) {
                throw ValidationException::withMessages([
                    'payment_status' => [
                        'Only paid orders can be refunded.',
                    ],
                ]);
            }

            // Refund is allowed only after the order is returned.
            if (
                $newStatus === 'refunded' &&
                $lockedOrder->status !== 'returned'
            ) {
                throw ValidationException::withMessages([
                    'payment_status' => [
                        'Only returned orders can be refunded.',
                    ],
                ]);
            }

            $lockedOrder->update([
                'payment_status' => $newStatus,
            ]);

            return $lockedOrder->fresh('items');
        });

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully.',
            'data' => $updatedOrder,
        ]);
    }
}
