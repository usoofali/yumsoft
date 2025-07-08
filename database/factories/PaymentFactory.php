<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        return [
            'amount' => $this->faker->randomFloat(2, 50, 2000),
            'payment_method' => $this->faker->randomElement(['cash', 'credit_card', 'bank_transfer', 'check']),
            'reference' => $this->faker->isbn13,
            'payment_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'notes' => $this->faker->sentence,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now')
        ];
    }

    public function withMethod(string $method)
    {
        return $this->state([
            'method' => $method
        ]);
    }
}