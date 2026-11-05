<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    // =========================
    // Public Products Tests
    // =========================

    public function test_guest_can_view_products(): void
    {
        Product::factory()->count(3)->create();

        $this->getJson('/api/products')
            ->assertOk();
    }

    public function test_guest_can_view_single_product(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->id}")
            ->assertOk();
    }

    public function test_guest_cannot_see_products_from_inactive_categories(): void
    {
        $activeCategory = Category::factory()->create([
            'is_active' => true,
        ]);

        $inactiveCategory = Category::factory()->create([
            'is_active' => false,
        ]);

        $activeProduct = Product::factory()->create([
            'category_id' => $activeCategory->id,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $inactiveCategory->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/products')
            ->assertOk();

        $this->assertSame(
            [$activeProduct->id],
            collect($response->json('data.data'))
                ->pluck('id')
                ->all()
        );
    }

    public function test_guest_cannot_view_product_from_inactive_category(): void
    {
        $category = Category::factory()->create([
            'is_active' => false,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $this->getJson("/api/products/{$product->id}")
            ->assertNotFound();
    }


    // =========================
    // Public Categories Tests
    // =========================

    public function test_guest_can_view_categories(): void
    {
        Category::factory()->count(3)->create();

        $this->getJson('/api/categories')
            ->assertOk();
    }

    public function test_guest_cannot_see_inactive_categories(): void
    {
        $activeCategory = Category::factory()->create([
            'is_active' => true,
        ]);

        Category::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/categories')
            ->assertOk();

        $this->assertSame(
            [$activeCategory->id],
            collect($response->json('data.data'))
                ->pluck('id')
                ->all()
        );
    }

    public function test_guest_cannot_view_inactive_category(): void
    {
        $category = Category::factory()->create([
            'is_active' => false,
        ]);

        $this->getJson("/api/categories/{$category->id}")
            ->assertNotFound();
    }

    public function test_category_shows_only_active_products(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $activeProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $response = $this->getJson("/api/categories/{$category->id}")
            ->assertOk();

        $this->assertSame(
            [$activeProduct->id],
            collect($response->json('data.products'))
                ->pluck('id')
                ->all()
        );
    }

    public function test_category_products_count_includes_only_active_products(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        Product::factory()->count(2)->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/categories')
            ->assertOk();

        $categoryData = collect($response->json('data.data'))
            ->firstWhere('id', $category->id);

        $this->assertEquals(2, $categoryData['products_count']);
    }

    // =========================
    // Authorization Tests
    // =========================

    public function test_guest_cannot_create_product(): void
    {
        $this->postJson('/api/admin/products', [])
            ->assertUnauthorized();
    }

    public function test_customer_cannot_create_product(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $this->actingAs($customer)
            ->postJson('/api/admin/products', [])
            ->assertForbidden();
    }

    // =========================
    // Admin Products Tests
    // =========================

    public function test_admin_can_create_product(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/products', [
                'category_id' => $category->id,
                'name' => 'Test Product',
                'slug' => 'test-product',
                'description' => 'Test Description',
                'sku' => 'TEST-001',
                'price' => 100,
                'stock_quantity' => 20,
                'is_active' => true,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-001',
        ]);
    }

    public function test_admin_can_update_product(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->putJson("/api/admin/products/{$product->id}", [
                'name' => 'Updated Product',
                'slug' => 'updated-product',
                'price' => 250,
            ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'price' => 250,
        ]);
    }
    // =========================
    // Product Validation Tests
    // =========================

    public function test_admin_can_create_product_with_decimal_price(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/products', [
                'category_id' => $category->id,
                'name' => 'Decimal Product',
                'slug' => 'decimal-product',
                'sku' => 'DEC-001',
                'price' => '19.99',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('products', [
            'slug' => 'decimal-product',
            'price' => '19.99',
        ]);
    }

    public function test_admin_cannot_create_product_with_more_than_two_decimal_places(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/products', [
                'category_id' => $category->id,
                'name' => 'Invalid Price Product',
                'slug' => 'invalid-price-product',
                'sku' => 'INVALID-001',
                'price' => '19.999',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price');

        $this->assertDatabaseMissing('products', [
            'slug' => 'invalid-price-product',
        ]);
    }

    public function test_admin_cannot_create_product_with_zero_or_negative_price(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create();

        foreach (['0', '-10'] as $index => $price) {
            $this->actingAs($admin)
                ->postJson('/api/admin/products', [
                    'category_id' => $category->id,
                    'name' => "Invalid Price {$index}",
                    'slug' => "invalid-price-{$index}",
                    'sku' => "INVALID-00{$index}",
                    'price' => $price,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('price');
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_admin_cannot_create_product_with_price_above_decimal_column_limit(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/products', [
                'category_id' => $category->id,
                'name' => 'Expensive Product',
                'slug' => 'expensive-product',
                'sku' => 'EXP-001',
                'price' => '100000000.00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price');

        $this->assertDatabaseMissing('products', [
            'slug' => 'expensive-product',
        ]);
    }

    public function test_admin_can_update_product_without_changing_its_slug_and_sku(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $product = Product::factory()->create([
            'slug' => 'existing-product',
            'sku' => 'EXISTING-001',
        ]);

        $this->actingAs($admin)
            ->putJson("/api/admin/products/{$product->id}", [
                'name' => 'Updated Product Name',
                'slug' => 'existing-product',
                'sku' => 'EXISTING-001',
            ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'slug' => 'existing-product',
            'sku' => 'EXISTING-001',
        ]);
    }
    public function test_admin_cannot_update_product_with_more_than_two_decimal_places(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $product = Product::factory()->create([
            'price' => 100,
        ]);

        $this->actingAs($admin)
            ->putJson("/api/admin/products/{$product->id}", [
                'price' => '19.999',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => 100,
        ]);
    }

    public function test_admin_cannot_update_product_with_price_above_decimal_column_limit(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $product = Product::factory()->create([
            'price' => 100,
        ]);

        $this->actingAs($admin)
            ->putJson("/api/admin/products/{$product->id}", [
                'price' => '100000000.00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => 100,
        ]);
    }
    public function test_admin_can_delete_product(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/products/{$product->id}")
            ->assertOk();

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_admin_cannot_create_product_in_inactive_category(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create([
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/products', [
                'category_id' => $category->id,
                'name' => 'Inactive Category Product',
                'slug' => 'inactive-category-product',
                'sku' => 'INACTIVE-001',
                'price' => 100,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');

        $this->assertDatabaseMissing('products', [
            'slug' => 'inactive-category-product',
        ]);
    }

    public function test_admin_cannot_move_product_to_inactive_category(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $activeCategory = Category::factory()->create([
            'is_active' => true,
        ]);

        $inactiveCategory = Category::factory()->create([
            'is_active' => false,
        ]);

        $product = Product::factory()->create([
            'category_id' => $activeCategory->id,
        ]);

        $this->actingAs($admin)
            ->putJson("/api/admin/products/{$product->id}", [
                'category_id' => $inactiveCategory->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => $activeCategory->id,
        ]);
    }

    // =========================
    // Admin Categories Tests
    // =========================

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/categories', [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic products',
                'is_active' => true,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('categories', [
            'slug' => 'electronics',
        ]);
    }

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->putJson("/api/admin/categories/{$category->id}", [
                'name' => 'Updated Category',
                'slug' => 'updated-category',
                'description' => 'Updated Description',
                'is_active' => true,
            ])
            ->assertOk();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
        ]);
    }

    public function test_admin_can_delete_category_without_products(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/categories/{$category->id}")
            ->assertOk();

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    // =========================
    // Additional Authorization Tests
    // =========================

    public function test_guest_cannot_update_or_delete_product(): void
    {
        $product = Product::factory()->create();

        $this->putJson("/api/admin/products/{$product->id}", [
            'name' => 'Unauthorized Update',
        ])->assertUnauthorized();

        $this->deleteJson("/api/admin/products/{$product->id}")
            ->assertUnauthorized();
    }

    public function test_customer_cannot_update_or_delete_product(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $product = Product::factory()->create();

        $this->actingAs($customer)
            ->putJson("/api/admin/products/{$product->id}", [
                'name' => 'Unauthorized Update',
            ])
            ->assertForbidden();

        $this->actingAs($customer)
            ->deleteJson("/api/admin/products/{$product->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }

    public function test_customer_cannot_create_update_or_delete_category(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $category = Category::factory()->create();

        $this->actingAs($customer)
            ->postJson('/api/admin/categories', [
                'name' => 'Unauthorized Category',
                'slug' => 'unauthorized-category',
            ])
            ->assertForbidden();

        $this->actingAs($customer)
            ->putJson("/api/admin/categories/{$category->id}", [
                'name' => 'Unauthorized Update',
            ])
            ->assertForbidden();

        $this->actingAs($customer)
            ->deleteJson("/api/admin/categories/{$category->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_guest_cannot_access_admin_category_routes(): void
    {
        $category = Category::factory()->create();

        $this->postJson('/api/admin/categories', [])
            ->assertUnauthorized();

        $this->putJson("/api/admin/categories/{$category->id}", [])
            ->assertUnauthorized();

        $this->deleteJson("/api/admin/categories/{$category->id}")
            ->assertUnauthorized();
    }
}
