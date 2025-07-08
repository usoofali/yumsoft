<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'barcode' => $this->faker->unique()->ean13,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now')
        ];
    }

    public function withPrice(float $min, float $max)
    {
        return $this->state([
            'price' => $this->faker->randomFloat(2, $min, $max)
        ]);
    }
}