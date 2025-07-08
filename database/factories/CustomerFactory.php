<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'credit_limit' => $this->faker->randomFloat(2, 1000, 10000),
            'payment_terms' => $this->faker->randomElement(['net 15', 'net 30', 'net 60']),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now')
        ];
    }

    public function withCreditLimit(float $amount)
    {
        return $this->state([
            'credit_limit' => $amount
        ]);
    }
}