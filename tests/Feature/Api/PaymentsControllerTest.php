<?php

namespace Tests\Api\Feature;

use App\Enums\AppointmentStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Stripe\Checkout\Session;
use Tests\TestCase;

class PaymentsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentsService = Mockery::mock(PaymentsService::class);
        $this->app->instance(PaymentsService::class, $this->paymentsService);
    }

    public function create_patient(): User
    {
        return User::factory()->patient(1)->create();
    }

    public function create_doctor(): User
    {
        return User::factory()->doctor(1)->create();
    }

    /** @test */
    public function test_patient_can_generate_a_payment_link()
    {
        $patient = $this->create_patient();
        $doctor = $this->create_doctor();

        $payment = Payment::factory()->create([
            'amount' => 100,
            'status' => PaymentStatusEnum::Pending->value(),
        ]);

        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'payment_id' => $payment->id,
            'status' => 'pending',
        ]);

        Gate::shouldReceive('denies')
            ->with('pay-appointment', Mockery::type(Appointment::class))
            ->andReturn(false);

        $this->paymentsService
            ->shouldReceive('generateCheckoutSession')
            ->once()
            ->andReturn(
                Session::constructFrom([
                    'id' => 'test_session',
                    'url' => 'https://checkout.stripe.com/test',
                ]),
            );

        $response = $this->actingAs($patient)->getJson(route('appointments.pay', $appointment));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'session_id' => 'test_session',
                    'payment_url' => 'https://checkout.stripe.com/test',
                ],
            ]);
    }

    /** @test */
    public function test_patient_cannot_generate_a_payment_link_if_appointment_is_paid()
    {
        $patient = $this->create_patient();
        $doctor = $this->create_doctor();

        $payment = Payment::factory()->create([
            'id' => 1,
            'amount' => 100,
            'status' => PaymentStatusEnum::Completed->value(),
        ]);

        $appointment = Appointment::factory()->create([
            'id' => 1,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'payment_id' => $payment->id,
            'status' => AppointmentStatusEnum::Paid->value(),
        ]);

        Gate::shouldReceive('denies')
            ->with('pay-appointment', Mockery::type(Appointment::class))
            ->once();

        $response = $this->actingAs($patient)->getJson(route('appointments.pay', $appointment->id));

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'La cita ya está pagada',
            ]);
    }

    /** @test */
    public function test_appointment_payment_success()
    {
        $patient = $this->create_patient();
        $doctor = $this->create_doctor();

        $payment = Payment::factory()->create([
            'id' => 1,
            'amount' => 100,
            'status' => PaymentStatusEnum::Pending->value(),
        ]);

        $appointment = Appointment::factory()->create([
            'id' => 1,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'payment_id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->paymentsService
            ->shouldReceive('getCheckoutSession')
            ->once()
            ->with('test_session')
            ->andReturn(
                Session::constructFrom([
                    'id' => 'test_session',
                    'url' => 'https://checkout.stripe.com/test',
                    'payment_status' => 'paid',
                    'payment_intent' => 'test_transaction_id',
                ]),
            );

        $response = $this->getJson(route('appointments.payment-success', [
            'appointment' => $appointment->id,
            'session_id' => 'test_session'
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cita pagada correctamente',
            ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatusEnum::Completed->value(),
            'payment_gateway' => 'Stripe',
            'transaction_id' => 'test_transaction_id',
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'payment_id' => $payment->id,
            'status' => AppointmentStatusEnum::Paid->value(),
        ]);
    }

    /** @test */
    public function test_appointment_payment_failed()
    {
        $patient = $this->create_patient();
        $doctor = $this->create_doctor();

        $payment = Payment::factory()->create([
            'amount' => 100,
            'status' => PaymentStatusEnum::Pending->value(),
        ]);

        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'payment_id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->paymentsService
            ->shouldReceive('getCheckoutSession')
            ->once()
            ->with('test_session')
            ->andReturn(Session::constructFrom([
                'id' => 'test_session',
                'url' => 'https://checkout.stripe.com/test',
                'payment_status' => 'unpaid',
                'payment_intent' => 'test_transaction_id',
            ]),);

        $response = $this->getJson(route('appointments.payment-success', [
            'appointment' => $appointment->id,
            'session_id' => 'test_session'
        ]));

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'El pago no se ha completado correctamente, inténtalo de nuevo',
            ]);
    }
}
