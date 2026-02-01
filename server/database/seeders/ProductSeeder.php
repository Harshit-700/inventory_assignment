<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all categories
        $categories = Category::all();

        if ($categories->isEmpty()) {
            $this->call(CategorySeeder::class);
            $categories = Category::all();
        }

        $products = [
            // Electronics
            [
                'name' => 'Wireless Bluetooth Headphones',
                'sku' => 'ELC-001-BLK',
                'description' => 'Premium noise-canceling wireless headphones with 30-hour battery life.',
                'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400',
                'price' => 14999,
                'quantity' => 45,
                'category' => 'Electronics',
            ],
            [
                'name' => 'USB-C Fast Charging Cable',
                'sku' => 'ELC-002-WHT',
                'description' => '2-meter braided USB-C cable with 100W power delivery.',
                'image_url' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400',
                'price' => 1999,
                'quantity' => 150,
                'category' => 'Electronics',
            ],
            [
                'name' => 'Portable Power Bank 20000mAh',
                'sku' => 'ELC-003-SLV',
                'description' => 'High-capacity power bank with dual USB ports and fast charging.',
                'image_url' => 'https://images.unsplash.com/photo-1609091839311-d5365f9ff1c5?w=400',
                'price' => 4999,
                'quantity' => 8,
                'category' => 'Electronics',
            ],
            [
                'name' => 'Mechanical Gaming Keyboard',
                'sku' => 'ELC-004-RGB',
                'description' => 'RGB mechanical keyboard with Cherry MX switches.',
                'image_url' => 'https://images.unsplash.com/photo-1511467687858-23d96c32e4ae?w=400',
                'price' => 12999,
                'quantity' => 25,
                'category' => 'Electronics',
            ],

            // Clothing
            [
                'name' => 'Premium Cotton T-Shirt',
                'sku' => 'CLT-001-BLU',
                'description' => '100% organic cotton crew neck t-shirt.',
                'image_url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400',
                'price' => 2499,
                'quantity' => 75,
                'category' => 'Clothing',
            ],
            [
                'name' => 'Slim Fit Denim Jeans',
                'sku' => 'CLT-002-DRK',
                'description' => 'Classic dark wash slim fit jeans with stretch comfort.',
                'image_url' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=400',
                'price' => 5999,
                'quantity' => 5,
                'category' => 'Clothing',
            ],
            [
                'name' => 'Winter Puffer Jacket',
                'sku' => 'CLT-003-BLK',
                'description' => 'Warm insulated puffer jacket with water-resistant coating.',
                'image_url' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=400',
                'price' => 8999,
                'quantity' => 0,
                'category' => 'Clothing',
            ],

            // Home & Garden
            [
                'name' => 'Ceramic Plant Pot Set',
                'sku' => 'HOM-001-WHT',
                'description' => 'Set of 3 modern ceramic pots with drainage holes.',
                'image_url' => 'https://images.unsplash.com/photo-1485955900006-10f4d324d411?w=400',
                'price' => 3499,
                'quantity' => 40,
                'category' => 'Home',
            ],
            [
                'name' => 'LED Desk Lamp',
                'sku' => 'HOM-002-SLV',
                'description' => 'Adjustable LED desk lamp with touch dimmer and USB charging port.',
                'image_url' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=400',
                'price' => 4499,
                'quantity' => 30,
                'category' => 'Home',
            ],
            [
                'name' => 'Bamboo Cutting Board',
                'sku' => 'HOM-003-NAT',
                'description' => 'Eco-friendly bamboo cutting board with juice groove.',
                'image_url' => 'https://images.unsplash.com/photo-1594226801341-41427b4e5c22?w=400',
                'price' => 2999,
                'quantity' => 60,
                'category' => 'Home',
            ],

            // Accessories
            [
                'name' => 'Premium Leather Wallet',
                'sku' => 'ACC-001-BRN',
                'description' => 'Genuine leather bifold wallet with RFID protection.',
                'image_url' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=400',
                'price' => 4999,
                'quantity' => 35,
                'category' => 'Accessories',
            ],
            [
                'name' => 'Aviator Sunglasses',
                'sku' => 'ACC-002-GLD',
                'description' => 'Classic aviator sunglasses with UV400 protection.',
                'image_url' => 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=400',
                'price' => 7999,
                'quantity' => 3,
                'category' => 'Accessories',
            ],
            [
                'name' => 'Canvas Backpack',
                'sku' => 'ACC-003-GRY',
                'description' => 'Durable canvas backpack with laptop compartment.',
                'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=400',
                'price' => 5999,
                'quantity' => 20,
                'category' => 'Accessories',
            ],

            // Office
            [
                'name' => 'Ergonomic Office Chair',
                'sku' => 'OFF-001-BLK',
                'description' => 'Adjustable ergonomic chair with lumbar support.',
                'image_url' => 'https://images.unsplash.com/photo-1580480055273-228ff5388ef8?w=400',
                'price' => 29999,
                'quantity' => 12,
                'category' => 'Office',
            ],
            [
                'name' => 'Wireless Mouse',
                'sku' => 'OFF-002-SLV',
                'description' => 'Silent click wireless mouse with ergonomic design.',
                'image_url' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=400',
                'price' => 2999,
                'quantity' => 80,
                'category' => 'Office',
            ],
            [
                'name' => 'Notebook Set',
                'sku' => 'OFF-003-AST',
                'description' => 'Set of 5 premium hardcover notebooks.',
                'image_url' => 'https://images.unsplash.com/photo-1531346878377-a5be20888e57?w=400',
                'price' => 1999,
                'quantity' => 100,
                'category' => 'Office',
            ],
        ];

        foreach ($products as $productData) {
            $category = $categories->firstWhere('name', $productData['category']);
            
            if (!$category) {
                $category = Category::firstOrCreate(
                    ['name' => $productData['category']],
                    ['description' => $productData['category'] . ' products', 'status' => 'active']
                );
            }

            $product = Product::firstOrCreate(
                ['sku' => $productData['sku']],
                [
                    'category_id' => $category->id,
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'image_url' => $productData['image_url'],
                    'price' => $productData['price'],
                    'quantity' => $productData['quantity'],
                ]
            );

            // Create initial stock movement
            if ($product->wasRecentlyCreated && $product->quantity > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => null,
                    'type' => 'in',
                    'quantity' => $product->quantity,
                    'previous_quantity' => 0,
                    'new_quantity' => $product->quantity,
                    'notes' => 'Initial stock from seeder',
                ]);
            }
        }
    }
}
