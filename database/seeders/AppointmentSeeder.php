<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\User;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $patients = User::where('role', 'patient')->get();
        $doctors = User::where('role', 'doctor')->get();

        foreach ($patients as $patient) {
            Appointment::factory()->randomAppointment(
                $patient,
                $doctors->random(),
            )->create();
        }
    }
}
