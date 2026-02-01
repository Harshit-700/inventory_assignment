<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(0, 100);
        
        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->unique()->bothify('???-###-???')),
            'description' => $this->faker->paragraph(2),
            'image_url' => $this->faker->optional(0.7)->imageUrl(640, 480, 'products'),
            'price' => $this->faker->numberBetween(100, 99999), // Price in cents
            'quantity' => $quantity,
            'status' => $this->determineStatus($quantity),
        ];
    }

    /**
     * Determine status based on quantity.
     */
    private function determineStatus(int $quantity): string
    {
        if ($quantity <= 0) {
            return 'out_of_stock';
        } elseif ($quantity < 10) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    /**
     * Indicate that the product is in stock.
     */
    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->numberBetween(10, 100),
            'status' => 'in_stock',
        ]);
    }

    /**
     * Indicate that the product has low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->numberBetween(1, 9),
            'status' => 'low_stock',
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'status' => 'out_of_stock',
        ]);
    }
}
