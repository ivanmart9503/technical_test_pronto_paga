<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Appointment;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'amount' => $this->faker->randomFloat(2, 50, 100),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'payment_gateway' => 'Stripe',
            'transaction_id' => 'txn_' . uniqid(),
        ];
    }

    public function randomPayment(Appointment $appointment): static
    {
        // Random value to determine if payment is paid or not
        $paid = $this->faker->boolean();

        if ($paid) {
            return $this->paid($appointment);
        }

        return $this->failed($appointment);
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'amount' => $this->faker->randomFloat(2, 50, 100),
                'status' => 'completed',
                'payment_gateway' => 'Stripe',
                'transaction_id' => 'txn_' . uniqid(),
            ];
        });
    }

    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'amount' => $this->faker->randomFloat(2, 50, 100),
                'status' => 'failed',
                'payment_gateway' => 'Stripe',
                'transaction_id' => 'txn_' . uniqid(),
            ];
        });
    }
}
