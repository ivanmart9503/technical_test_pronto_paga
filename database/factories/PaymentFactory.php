<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Appointment;
use App\Models\Payment;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'amount' => 50.00,
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'payment_gateway' => 'Stripe',
            'transaction_id' => 'txn_' . uniqid(),
        ];
    }

    public function randomPayment(Appointment $appointment): static
    {
        // Random value to determine if payment is paid or not
        $paid = $this->faker->boolean();

        if($paid){
            return $this->paid($appointment);
        }

        return $this->failed($appointment);
    }

    public function paid(Appointment $appointment): static
    {
        return $this->state(function (array $attributes) use ($appointment) {
            return [
                'appointment_id' => $appointment->id,
                'status' => 'completed',
                'payment_gateway' => 'Stripe',
                'transaction_id' => 'txn_' . uniqid(),
            ];
        });
    }

    public function failed(Appointment $appointment): static
    {
        return $this->state(function (array $attributes) use ($appointment) {
            return [
                'appointment_id' => $appointment->id,
                'amount' => 50.00,
                'status' => 'failed',
                'payment_gateway' => 'Stripe',
                'transaction_id' => 'txn_' . uniqid(),
            ];
        });
    }
}
