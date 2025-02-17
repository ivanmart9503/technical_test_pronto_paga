<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class ScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'doctor_id' => User::factory(),
            'shift' => $this->faker->randomElement(['morning', 'afternoon']),
            'start_time' => $this->faker->randomElement(['07:00', '14:00']),
            'end_time' => $this->faker->randomElement(['12:00', '18:00']),
        ];
    }

    public function morning(User $doctor): static
    {
        return $this->state(fn(array $attributes) => [
            'doctor_id' => $doctor->id,
            'shift' => 'morning',
            'start_time' => '07:00',
            'end_time' => '12:00',
        ]);
    }

    public function afternoon(User $doctor): static
    {
        return $this->state(fn(array $attributes) => [
            'doctor_id' => $doctor->id,
            'shift' => 'afternoon',
            'start_time' => '14:00',
            'end_time' => '18:00',
        ]);
    }
}
