<?php

namespace Database\Seeders;

use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Appointment;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $appointments = Appointment::where('status', 'pending')->get();

        foreach ($appointments as $appointment) {
            $payment = Payment::factory()->randomPayment($appointment)->create();
                
            // If payment is completed, update appointment status
            if ($payment->status === PaymentStatusEnum::Completed->value()) {
                $appointment->update([
                    'payment_id' => $payment->id,
                    'status' => 'paid'
                ]);
            }
        }
    }
}
