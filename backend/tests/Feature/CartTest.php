<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    // =========================
    // Cart Display
    // =========================

    public function test_authenticated_user_can_view_cart(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items',
                    'total',
                ],
            ]);
    }

    public function test_guest_cannot_view_cart(): void
    {
        $this->getJson('/api/cart')
            ->assertUnauthorized();
    }

    // =========================
    // Add Cart Item
    // =========================

    public function test_user_can_add_product_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.quantity', 2);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_user_cannot_add_inactive_product(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertUnprocessable();
    }

    public function test_user_cannot_add_quantity_exceeding_stock(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 3,
        ]);

        $this->actingAs($user)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 5,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_adding_same_product_increases_quantity(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 10,
        ]);

        $this->actingAs($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated();

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ])->assertCreated();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $this->assertDatabaseCount('cart_items', 1);
    }
    // =========================
    // Cart Request Validation Tests
    // =========================

    public function test_user_cannot_add_nonexistent_product(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/cart/items', [
                'product_id' => 999999,
                'quantity' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_user_cannot_add_invalid_quantity(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        foreach ([0, -1, 2.5] as $quantity) {
            $this->actingAs($user)
                ->postJson('/api/cart/items', [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('quantity');
        }

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_user_cannot_add_product_without_required_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/cart/items', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'product_id',
                'quantity',
            ]);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_user_cannot_update_cart_item_with_invalid_or_missing_quantity(): void
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

        foreach (
            [
                ['quantity' => 0],
                ['quantity' => -1],
                ['quantity' => 2.5],
                [],
            ] as $payload
        ) {
            $this->actingAs($user)
                ->patchJson(
                    "/api/cart/items/{$cartItem->id}",
                    $payload
                )
                ->assertUnprocessable()
                ->assertJsonValidationErrors('quantity');
        }

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);
    }
    // =========================
    // Update Cart Item
    // =========================

    public function test_user_can_update_cart_item_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 10,
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
            ->patchJson("/api/cart/items/{$cartItem->id}", [
                'quantity' => 5,
            ])
            ->assertOk()
            ->assertJsonPath('data.quantity', 5);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 5,
        ]);
    }

    public function test_user_cannot_update_cart_item_beyond_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 3,
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
            ->patchJson("/api/cart/items/{$cartItem->id}", [
                'quantity' => 5,
            ])
            ->assertUnprocessable();
    }

    // =========================
    // Additional Cart Validation Tests
    // =========================

    public function test_user_cannot_update_inactive_product_cart_item(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 10,
            'is_active' => false,
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
            ->patchJson("/api/cart/items/{$cartItem->id}", [
                'quantity' => 5,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);
    }

    public function test_user_cannot_add_inactive_product_already_in_cart(): void
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

        $product->update([
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity' => 3,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);
    }

    public function test_failed_cart_update_preserves_existing_quantity(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock_quantity' => 3,
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
            ->patchJson("/api/cart/items/{$cartItem->id}", [
                'quantity' => 5,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);
    }

    public function test_cart_total_is_calculated_correctly_with_decimal_prices(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'price' => 19.99,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->actingAs($user)
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('data.items.0.subtotal', '59.97')
            ->assertJsonPath('data.total', '59.97');
    }

    // =========================
    // Remove & Clear Cart
    // =========================

    public function test_user_can_remove_cart_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
        ]);

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/cart/items/{$cartItem->id}")
            ->assertOk();

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    public function test_user_can_clear_cart(): void
    {
        $user = User::factory()->create();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $cart = Cart::factory()->create([
            'user_id' => $user->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product1->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product2->id,
        ]);

        $this->actingAs($user)
            ->deleteJson('/api/cart')
            ->assertOk();

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_user_cannot_access_another_users_cart_item(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $product = Product::factory()->create();

        $cart = Cart::factory()->create([
            'user_id' => $owner->id,
        ]);

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($otherUser)
            ->deleteJson("/api/cart/items/{$cartItem->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    public function test_user_cannot_clear_another_users_cart(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $product = Product::factory()->create();

        $cart = Cart::factory()->create([
            'user_id' => $owner->id,
        ]);

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($otherUser)
            ->deleteJson('/api/cart')
            ->assertOk();

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'quantity' => 2,
        ]);
    }
}
