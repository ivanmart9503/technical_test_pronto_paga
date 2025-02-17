<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => 'default@test.com', // Default value, it will be replaced when seeding
            'password' => bcrypt('a123456'),
            'role' => 'patient', // Default value, it will be replaced when seeding
        ];
    }

    public function doctor(int $index): static
    {
        $index = $index + 1;

        return $this->state(fn(array $attributes) => [
            'email' => "doctor{$index}@test.com",
            'role' => 'doctor',
        ]);
    }

    public function patient(int $index): static
    {
        $index = $index + 1;

        return $this->state(fn(array $attributes) => [
            'email' => "patient{$index}@test.com",
            'role' => 'patient',
        ]);
    }
}
