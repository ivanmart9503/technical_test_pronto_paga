<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create 2 doctors, the index will be used to generate email like doctor1@test.com
        foreach (range(1, 2) as $i) {
            User::factory()->doctor($i)->create();
        }

        // Create 2 patients, the index will be used to generate email like patient1@test.com
        foreach (range(1, 2) as $i) {
            User::factory()->patient($i)->create();
        }
    }
}
