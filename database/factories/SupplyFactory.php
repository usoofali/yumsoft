<?php

namespace Database\Factories;

use App\Models\Supply;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplyFactory extends Factory
{
    protected $model = Supply::class;

    public function definition()
    {
        return [
            'quantity' => $this->faker->numberBetween(10, 100),
            'cost_price' => $this->faker->randomFloat(2, 5, 500),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now')
        ];
    }

    public function withCostPrice(float $price)
    {
        return $this->state([
            'cost_price' => $price
        ]);
    }
}