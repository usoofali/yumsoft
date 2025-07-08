<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition()
    {
        return [
            'invoice_number' => 'INV-' . $this->faker->unique()->numberBetween(1000, 9999),
            'issue_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'due_date' => $this->faker->dateTimeBetween('now', '+60 days'),
            'total_amount' => $this->faker->randomFloat(2, 100, 10000),
            'amount_paid' => 0,
            'status' => 'unpaid',
            'notes' => $this->faker->sentence,
            'synced' => $this->faker->boolean(80),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now')
        ];
    }

    public function paid()
    {
        return $this->state(function (array $attributes) {
            return [
                'amount_paid' => $attributes['total_amount'],
                'status' => 'paid'
            ];
        });
    }
}