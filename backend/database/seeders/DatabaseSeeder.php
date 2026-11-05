<?php
namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@shopflow.test'],
            [
                'name' => 'ShopFlow Admin',
                'password' => Hash::make('password'),
            ]
        );

        $admin->forceFill(['role' => 'admin'])->save();

        // Demo Customer
        $customer = User::updateOrCreate(
            ['email' => 'customer@shopflow.test'],
            [
                'name' => 'Demo Customer',
                'password' => Hash::make('password'),
            ]
        );

        $customer->forceFill(['role' => 'customer'])->save();

        // Categories
        $categoryNames = [
            'Electronics',
            'Fashion',
            'Home & Kitchen',
            'Accessories',
        ];

        $categories = [];

        foreach ($categoryNames as $name) {
            $slug = Str::slug($name);

            $categories[$name] = Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => "Explore our {$name} collection.",
                    'is_active' => true,
                ]
            );
        }

        // Products
        $products = [
            [
                'sku' => 'ELEC-001',
                'category' => 'Electronics',
                'name' => 'Wireless Headphones',
                'price' => 89.99,
                'stock_quantity' => 25,
            ],
            [
                'sku' => 'ELEC-002',
                'category' => 'Electronics',
                'name' => 'Smart Watch',
                'price' => 129.99,
                'stock_quantity' => 18,
            ],
            [
                'sku' => 'ELEC-003',
                'category' => 'Electronics',
                'name' => 'Bluetooth Speaker',
                'price' => 49.99,
                'stock_quantity' => 30,
            ],
            [
                'sku' => 'FASH-001',
                'category' => 'Fashion',
                'name' => 'Classic T-Shirt',
                'price' => 24.99,
                'stock_quantity' => 50,
            ],
            [
                'sku' => 'FASH-002',
                'category' => 'Fashion',
                'name' => 'Casual Hoodie',
                'price' => 54.99,
                'stock_quantity' => 20,
            ],
            [
                'sku' => 'FASH-003',
                'category' => 'Fashion',
                'name' => 'Denim Jacket',
                'price' => 79.99,
                'stock_quantity' => 12,
            ],
            [
                'sku' => 'HOME-001',
                'category' => 'Home & Kitchen',
                'name' => 'Coffee Maker',
                'price' => 69.99,
                'stock_quantity' => 15,
            ],
            [
                'sku' => 'HOME-002',
                'category' => 'Home & Kitchen',
                'name' => 'Electric Kettle',
                'price' => 34.99,
                'stock_quantity' => 22,
            ],
            [
                'sku' => 'ACC-001',
                'category' => 'Accessories',
                'name' => 'Leather Wallet',
                'price' => 29.99,
                'stock_quantity' => 40,
            ],
            [
                'sku' => 'ACC-002',
                'category' => 'Accessories',
                'name' => 'Classic Sunglasses',
                'price' => 39.99,
                'stock_quantity' => 28,
            ],
        ];

        foreach ($products as $product) {
            $category = $categories[$product['category']];

            Product::updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'category_id' => $category->id,
                    'name' => $product['name'],
                    'slug' => Str::slug($product['name']),
                    'description' => 'Discover this quality product from ShopFlow.',
                    'price' => $product['price'],
                    'stock_quantity' => $product['stock_quantity'],
                    'is_active' => true,
                ]
            );
        }
    }
}
