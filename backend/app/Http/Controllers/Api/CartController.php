<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    /**
     * Get the authenticated user's cart.
     *
     * Returns cart items with product details and calculated totals.
     *
     * @authenticated
     */
    public function show(Request $request): JsonResponse
    {
        $cart = $this->getCart($request);

        $cart->load('items.product');

        $totalCents = 0;

        $items = $cart->items->map(function ($item) use (&$totalCents) {
            $unitPriceCents = (int) round(
                (float) $item->product->price * 100
            );

            $subtotalCents = $unitPriceCents * $item->quantity;

            $totalCents += $subtotalCents;

            return [
                'id' => $item->id,
                'product' => $item->product,
                'quantity' => $item->quantity,
                'subtotal' => number_format(
                    $subtotalCents / 100,
                    2,
                    '.',
                    ''
                ),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'total' => number_format(
                    $totalCents / 100,
                    2,
                    '.',
                    ''
                ),
            ],
        ]);
    }

    /**
     * Add a product to the authenticated user's cart.
     *
     * Checks product availability and stock inside a transaction.
     * Locks the cart and product to protect concurrent updates.
     *
     * @authenticated
     */
    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $data = $request->validated();

        $cart = $this->getCart($request);

        $item = DB::transaction(function () use ($cart, $data) {

            $lockedCart = Cart::whereKey($cart->id)
                ->lockForUpdate()
                ->firstOrFail();

            $product = Product::whereKey($data['product_id'])
                ->lockForUpdate()
                ->first();

            if (!$product || !$product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => ['This product is not available.'],
                ]);
            }

            $item = $lockedCart->items()
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $newQuantity = ($item?->quantity ?? 0) + $data['quantity'];

            if ($newQuantity > $product->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        'Requested quantity exceeds available stock.',
                    ],
                ]);
            }

            if ($item) {
                $item->update([
                    'quantity' => $newQuantity,
                ]);
            } else {
                $item = $lockedCart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $newQuantity,
                ]);
            }

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart.',
            'data' => $item->load('product'),
        ], 201);
    }

    /**
     * Update the quantity of a cart item.
     *
     * Checks cart ownership, product availability and stock
     * inside a transaction.
     *
     * @authenticated
     */
    public function updateItem(
        UpdateCartItemRequest $request,
        CartItem $cartItem
    ): JsonResponse {
        $data = $request->validated();

        $cart = $this->getCart($request);

        $updatedItem = DB::transaction(function () use (
            $cart,
            $cartItem,
            $data
        ) {

            $lockedCart = Cart::whereKey($cart->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedItem = $lockedCart->items()
                ->whereKey($cartItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            $product = Product::whereKey($lockedItem->product_id)
                ->lockForUpdate()
                ->first();

            if (!$product || !$product->is_active) {
                throw ValidationException::withMessages([
                    'product' => ['This product is not available.'],
                ]);
            }

            $quantity = $data['quantity'];

            if ($quantity > $product->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        'Requested quantity exceeds available stock.',
                    ],
                ]);
            }

            $lockedItem->update([
                'quantity' => $quantity,
            ]);

            return $lockedItem;
        });

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated.',
            'data' => $updatedItem->load('product'),
        ]);
    }

    /**
     * Remove an item from the authenticated user's cart.
     *
     * @authenticated
     */
    public function removeItem(
        Request $request,
        CartItem $cartItem
    ): JsonResponse {
        $cart = $this->getCart($request);

        DB::transaction(function () use ($cart, $cartItem) {

            $lockedCart = Cart::whereKey($cart->id)
                ->lockForUpdate()
                ->firstOrFail();

            $item = $lockedCart->items()
                ->whereKey($cartItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            return $item->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.',
        ]);
    }

    /**
     * Remove all items from the authenticated user's cart.
     *
     * @authenticated
     */
    public function clear(Request $request): JsonResponse
    {
        $cart = $this->getCart($request);

        DB::transaction(function () use ($cart) {

            $lockedCart = Cart::whereKey($cart->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedCart->items()->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully.',
        ]);
    }

    /**
     * Get or create the authenticated user's cart.
     */
    private function getCart(Request $request): Cart
    {
        return $request->user()->cart()->firstOrCreate();
    }
}
