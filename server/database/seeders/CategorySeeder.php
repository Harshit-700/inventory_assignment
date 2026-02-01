<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Electronic devices and gadgets including smartphones, laptops, tablets, and accessories.',
                'status' => 'active',
            ],
            [
                'name' => 'Clothing',
                'description' => 'Apparel and fashion items for men, women, and children.',
                'status' => 'active',
            ],
            [
                'name' => 'Home',
                'description' => 'Home and garden products including furniture, decor, and outdoor equipment.',
                'status' => 'active',
            ],
            [
                'name' => 'Accessories',
                'description' => 'Fashion accessories, jewelry, watches, and bags.',
                'status' => 'active',
            ],
            [
                'name' => 'Office',
                'description' => 'Office supplies, stationery, and business equipment.',
                'status' => 'active',
            ],
            [
                'name' => 'Sports',
                'description' => 'Sports equipment, fitness gear, and outdoor recreation products.',
                'status' => 'active',
            ],
            [
                'name' => 'Books',
                'description' => 'Books, magazines, and educational materials.',
                'status' => 'active',
            ],
            [
                'name' => 'Health & Beauty',
                'description' => 'Health, beauty, and personal care products.',
                'status' => 'active',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
