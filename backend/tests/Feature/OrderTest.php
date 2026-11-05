<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    // =========================
    // Checkout Tests
    // =========================

    public function test_user_can_checkout_successfully(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'price' => 100,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user)
            ->postJson('/api/checkout', [
                'customer_name' => 'Test Customer',
                'customer_phone' => '01012345678',
                'shipping_city' => 'Cairo',
                'shipping_address' => 'Test Address',
                'notes' => 'Test Order',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_status', 'unpaid')
            ->assertJsonPath('data.subtotal', '200.00');

        $this->assertDatabaseCount('orders', 1);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'total' => 200,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 8,
        ]);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_guest_cannot_checkout(): void
    {
        $this->postJson('/api/checkout', [
            'customer_name' => 'Test Customer',
            'customer_phone' => '01012345678',
            'shipping_city' => 'Cairo',
            'shipping_address' => 'Test Address',
        ])->assertUnauthorized();
    }

    public function test_user_cannot_checkout_with_empty_cart(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/checkout', [
                'customer_name' => 'Test Customer',
                'customer_phone' => '01012345678',
                'shipping_city' => 'Cairo',
                'shipping_address' => 'Test Address',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_fails_when_stock_is_insufficient(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 2,
            'is_active' => true,
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $this->actingAs($user)
            ->postJson('/api/checkout', [
                'customer_name' => 'Test Customer',
                'customer_phone' => '01012345678',
                'shipping_city' => 'Cairo',
                'shipping_address' => 'Test Address',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 2,
        ]);

        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_checkout_rolls_back_all_changes_when_one_product_has_insufficient_stock(): void
    {
        $user = User::factory()->create();

        $firstProduct = Product::factory()->create([
            'price' => 100,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $secondProduct = Product::factory()->create([
            'price' => 200,
            'stock_quantity' => 1,
            'is_active' => true,
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $firstProduct->id,
            'quantity' => 2,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $secondProduct->id,
            'quantity' => 5,
        ]);

        $this->actingAs($user)
            ->postJson('/api/checkout', [
                'customer_name' => 'Test Customer',
                'customer_phone' => '01012345678',
                'shipping_city' => 'Cairo',
                'shipping_address' => 'Test Address',
            ])
            ->assertUnprocessable();

        // No order should be created.
        $this->assertDatabaseCount('orders', 0);

        // First product stock must be restored after rollback.
        $this->assertDatabaseHas('products', [
            'id' => $firstProduct->id,
            'stock_quantity' => 10,
        ]);

        // Second product stock must remain unchanged.
        $this->assertDatabaseHas('products', [
            'id' => $secondProduct->id,
            'stock_quantity' => 1,
        ]);

        // Both cart items must remain.
        $this->assertDatabaseCount('cart_items', 2);
    }

    public function test_checkout_fails_when_product_is_inactive(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => false,
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->postJson('/api/checkout', [
                'customer_name' => 'Test Customer',
                'customer_phone' => '01012345678',
                'shipping_city' => 'Cairo',
                'shipping_address' => 'Test Address',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 10,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }



    public function test_checkout_requires_shipping_information(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/checkout', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'customer_name',
                'customer_phone',
                'shipping_city',
                'shipping_address',
            ]);

        $this->assertDatabaseCount('orders', 0);
    }

    // =========================
    // Checkout Validation Tests
    // =========================

    public function test_checkout_rejects_shipping_fields_that_are_too_short(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
        ]);

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user)
            ->postJson('/api/checkout', [
                'customer_name' => 'A',
                'customer_phone' => '123456',
                'shipping_city' => 'C',
                'shipping_address' => '1234',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'customer_name',
                'customer_phone',
                'shipping_city',
                'shipping_address',
            ]);

        // Failed validation must not affect the cart or stock.
        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 10,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);
    }

    public function test_checkout_rejects_shipping_fields_that_exceed_maximum_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/checkout', [
                'customer_name' => str_repeat('A', 256),
                'customer_phone' => str_repeat('1', 31),
                'shipping_city' => str_repeat('A', 256),
                'shipping_address' => str_repeat('A', 1001),
                'notes' => str_repeat('A', 2001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'customer_name',
                'customer_phone',
                'shipping_city',
                'shipping_address',
                'notes',
            ]);

        $this->assertDatabaseCount('orders', 0);
    }

    // =========================
    // Customer Orders Tests
    // =========================

    public function test_user_can_view_own_orders(): void
    {
        $user = User::factory()->create();

        $this->createOrder($user);

        $this->actingAs($user)
            ->getJson('/api/orders')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_user_can_view_single_order(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrder($user);

        $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $order = $this->createOrder($owner);

        $this->actingAs($otherUser)
            ->getJson("/api/orders/{$order->id}")
            ->assertNotFound();
    }

    public function test_guest_cannot_view_orders(): void
    {
        $this->getJson('/api/orders')
            ->assertUnauthorized();
    }

    // =========================
    // Admin Order Management
    // =========================

    public function test_admin_can_view_all_orders(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $this->createOrder($user);

        $this->actingAs($admin)
            ->getJson('/api/admin/orders')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_customer_cannot_view_admin_orders(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $this->actingAs($customer)
            ->getJson('/api/admin/orders')
            ->assertForbidden();
    }
    // =========================
    // Admin Order Filters Tests
    // =========================

    public function test_admin_can_filter_orders_by_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $pendingOrder = $this->createOrder($user, [
            'status' => 'pending',
        ]);

        $this->createOrder($user, [
            'status' => 'delivered',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/orders?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath(
                'data.data.0.id',
                $pendingOrder->id
            );
    }

    public function test_admin_can_filter_orders_by_payment_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $paidOrder = $this->createOrder($user, [
            'payment_status' => 'paid',
        ]);

        $this->createOrder($user, [
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/orders?payment_status=paid')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath(
                'data.data.0.id',
                $paidOrder->id
            );
    }

    public function test_admin_cannot_filter_orders_with_invalid_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/orders?status=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_admin_cannot_filter_orders_with_invalid_payment_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/orders?payment_status=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment_status');
    }
    public function test_admin_can_confirm_order(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder($user);

        $this->actingAs($admin)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'confirmed',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_admin_cannot_make_invalid_order_status_transition(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder($user, [
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'delivered',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_restore_stock_when_cancelling_order(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 8,
        ]);

        $order = $this->createOrder($user, [
            'status' => 'pending',
        ], [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'cancelled',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 10,
        ]);
    }

    // =========================
    // Payment Tests
    // =========================

    public function test_admin_can_mark_delivered_order_as_paid(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder($user, [
            'status' => 'delivered',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/orders/{$order->id}/payment-status", [
                'payment_status' => 'paid',
            ])
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'paid');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);
    }

    public function test_admin_cannot_mark_undelivered_order_as_paid(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder($user, [
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/orders/{$order->id}/payment-status", [
                'payment_status' => 'paid',
            ])
            ->assertUnprocessable();
    }

    // =========================
    // Helper
    // =========================

    public function test_admin_can_return_delivered_order_and_restore_stock(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 8,
        ]);

        $order = $this->createOrder(
            $user,
            ['status' => 'delivered'],
            [
                'product_id' => $product->id,
                'quantity' => 2,
            ]
        );

        $this->actingAs($admin)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'returned',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'returned');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 10,
        ]);
    }

    public function test_admin_can_refund_paid_returned_order(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder($user, [
            'status' => 'delivered',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($admin);

        // First mark the delivered order as paid.
        $this->patchJson(
            "/api/admin/orders/{$order->id}/payment-status",
            ['payment_status' => 'paid']
        )->assertOk();

        // Then accept the return.
        $this->patchJson(
            "/api/admin/orders/{$order->id}/status",
            ['status' => 'returned']
        )->assertOk();

        // Finally refund the payment.
        $this->patchJson(
            "/api/admin/orders/{$order->id}/payment-status",
            ['payment_status' => 'refunded']
        )
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'refunded');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'returned',
            'payment_status' => 'refunded',
        ]);
    }

    // =========================
    // Additional Authorization Tests
    // =========================

    public function test_guest_cannot_access_admin_order_routes(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);

        $this->getJson('/api/admin/orders')
            ->assertUnauthorized();

        $this->getJson("/api/admin/orders/{$order->id}")
            ->assertUnauthorized();

        $this->patchJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'confirmed',
        ])->assertUnauthorized();

        $this->patchJson("/api/admin/orders/{$order->id}/payment-status", [
            'payment_status' => 'paid',
        ])->assertUnauthorized();
    }

    public function test_customer_cannot_update_order_status(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $order = $this->createOrder($customer, [
            'status' => 'pending',
        ]);

        $this->actingAs($customer)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'confirmed',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_customer_cannot_update_payment_status(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $order = $this->createOrder($customer, [
            'status' => 'delivered',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($customer)
            ->patchJson("/api/admin/orders/{$order->id}/payment-status", [
                'payment_status' => 'paid',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'unpaid',
        ]);
    }

    // =========================
    // Stock Restoration Tests
    // =========================

    public function test_order_cannot_restore_stock_twice_when_cancelled(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 8,
        ]);

        $order = $this->createOrder($user, [
            'status' => 'pending',
        ], [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'cancelled',
            ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 10,
        ]);

        // Attempt to cancel the same order again.
        $this->patchJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'cancelled',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 10,
        ]);
    }

    public function test_order_cannot_restore_stock_twice_when_returned(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 8,
        ]);

        $order = $this->createOrder($user, [
            'status' => 'delivered',
        ], [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'returned',
            ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 10,
        ]);

        // Attempt to return the same order again.
        $this->patchJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'returned',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 10,
        ]);
    }

    // =========================
    // Payment Validation Tests
    // =========================

    public function test_admin_cannot_refund_unpaid_order(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder($user, [
            'status' => 'returned',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/orders/{$order->id}/payment-status", [
                'payment_status' => 'refunded',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_admin_cannot_refund_order_before_return(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $order = $this->createOrder($user, [
            'status' => 'delivered',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/orders/{$order->id}/payment-status", [
                'payment_status' => 'refunded',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);
    }

    private function createOrder(
        User $user,
        array $attributes = [],
        array $itemAttributes = []
    ): Order {
        $product = isset($itemAttributes['product_id'])
            ? Product::findOrFail($itemAttributes['product_id'])
            : Product::factory()->create();

        $quantity = $itemAttributes['quantity'] ?? 1;
        $unitPrice = (float) $product->price;

        $order = Order::create(array_merge([
            'user_id' => $user->id,
            'order_number' => 'SF-' . strtoupper(fake()->unique()->bothify('##########')),
            'status' => 'pending',
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'unpaid',
            'subtotal' => round($unitPrice * $quantity, 2),
            'shipping_fee' => 0,
            'total' => round($unitPrice * $quantity, 2),
            'customer_name' => 'Test Customer',
            'customer_phone' => '01012345678',
            'shipping_city' => 'Cairo',
            'shipping_address' => 'Test Address',
            'notes' => null,
        ], $attributes));

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'total' => round($unitPrice * $quantity, 2),
        ]);

        return $order;
    }
}
