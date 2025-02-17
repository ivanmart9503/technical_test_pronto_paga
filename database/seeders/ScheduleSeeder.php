<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\User;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = User::where('role', 'doctor')->get();

        foreach ($doctors as $doctor) {
            // Create morning and afternoon blocks/shifts for each doctor
            Schedule::factory()->morning($doctor)->create();
            Schedule::factory()->afternoon($doctor)->create();
        }
    }
}
