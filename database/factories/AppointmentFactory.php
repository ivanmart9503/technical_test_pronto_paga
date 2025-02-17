<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Appointment;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => User::factory(),
            'doctor_id' => User::factory(),
            'date_time' => $this->faker->unique()->dateTimeBetween('today 7:00', 'today 12:00'),
            'status' => 'pending',
        ];
    }

    public function randomAppointment(User $patient, User $doctor): static
    {
        // Random boolean value to determine morning or afternoon appointment
        $morning = $this->faker->boolean();

        if ($morning) {
            return $this->morningAppointment($patient, $doctor);
        }

        return $this->afternoonAppointment($patient, $doctor);
    }

    public function morningAppointment(User $patient, User $doctor): static
    {
        return $this->state(fn(array $attributes) => [
            'date_time' => $this->faker->unique()->dateTimeBetween('today 7:00', 'today 12:00'),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);
    }

    public function afternoonAppointment(User $patient, User $doctor): static
    {
        return $this->state(fn(array $attributes) => [
            'date_time' => $this->faker->unique()->dateTimeBetween('today 14:00', 'today 18:00'),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);
    }
}
