<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Appointment;
use App\Models\Payment;
use App\Services\AppointmentsService;
use App\Services\PaymentsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Tests\TestCase;

class AppointmentsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $appointmentsService;
    protected $paymentsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appointmentsService = Mockery::mock(AppointmentsService::class);
        $this->paymentsService = Mockery::mock(PaymentsService::class);
        $this->app->instance(AppointmentsService::class, $this->appointmentsService);
        $this->app->instance(PaymentsService::class, $this->paymentsService);
    }

    public function create_doctor(): User
    {
        return User::factory()->create([
            'email' => 'doctor@example.com',
            'role' => 'doctor',
        ]);
    }

    public function create_patient(): User
    {
        return User::factory()->create([
            'email' => 'patient@example.com',
            'role' => 'patient'
        ]);
    }

    public function test_patient_can_create_appointment()
    {
        $doctor = $this->create_doctor();
        $patient = $this->create_patient();

        Gate::shouldReceive('denies')->with('create-appointment')->andReturn(false);

        $this->appointmentsService->shouldReceive('createRules')
            ->once()
            ->andReturn([
                'doctor_id' => ['required', 'exists:users,id'],
                'date_time' => ['required', 'date_format:Y-m-d H:i'],
            ]);

        $this->appointmentsService->shouldReceive('checkAvailability')
            ->once()
            ->with($doctor->id, '2025-02-18 10:00')
            ->andReturn(true);

        // Simulate the creation of the payment and the appointment
        $this->paymentsService->shouldReceive('createPayment')
            ->once()
            ->with(1500)
            ->andReturn(Payment::factory()->create([
                'id' => 1,
                'amount' => 1500,
                'status' => 'pending',
            ]));

        $this->appointmentsService->shouldReceive('createAppointment')
            ->once()
            ->andReturn(Appointment::factory()->create([
                'id' => 1,
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'payment_id' => 1,
                'date_time' => '2025-02-18 10:00',
                'status' => 'pending',
            ]));

        $response = $this->actingAs($patient)->postJson(
            route('appointments.create'),
            [
                'doctor_id' => $doctor->id,
                'date_time' => '2025-02-18 10:00',
            ]
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => 1,
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'payment_id' => 1,
                    'date_time' => '2025-02-18 10:00',
                    'status' => 'pending',
                ],
                'message' => 'Cita creada correctamente',
            ]);
    }

    public function test_doctor_cannot_create_appointment()
    {
        $doctor = $this->create_doctor();

        $this->actingAs($doctor);

        Gate::shouldReceive('denies')->with('create-appointment')->andReturn(true);

        $response = $this->postJson(
            route('appointments.create'),
            [
                'doctor_id' => 1,
                'patient_id' => 1,
                'date_time' => '2025-02-18 10:00',
            ]
        );

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Sólo los pacientes pueden crear citas',
            ]);
    }

    public function test_doctor_can_list_appointments()
    {
        $doctor = $this->create_doctor();
        $patient = $this->create_patient();
        $startDate = '2025-02-18';
        $endDate = '2025-02-28';

        Gate::shouldReceive('denies')->with('list-appointments')->andReturn(false);

        $this->appointmentsService->shouldReceive('getAppointments')
            ->once()
            ->with($doctor, $startDate, $endDate)
            ->andReturn([
                Appointment::factory()->create([
                    'id' => 1,
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'date_time' => '2025-02-18 10:00',
                    'status' => 'pending',
                ]),
                Appointment::factory()->create([
                    'id' => 2,
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'date_time' => '2025-02-20 10:00',
                    'status' => 'pending',
                ]),
                Appointment::factory()->create([
                    'id' => 3,
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'date_time' => '2025-02-27 10:00',
                    'status' => 'pending',
                ]),
            ]);

        $response = $this->actingAs($doctor)
            ->getJson(
                route(
                    'appointments.index',
                    [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                )
            );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Citas listadas correctamente',
                'data' => [
                    [
                        'id' => 1,
                        'doctor_id' => $doctor->id,
                        'patient_id' => $patient->id,
                        'date_time' => '2025-02-18 10:00',
                        'status' => 'pending',
                    ],
                    [
                        'id' => 2,
                        'doctor_id' => $doctor->id,
                        'patient_id' => $patient->id,
                        'date_time' => '2025-02-20 10:00',
                        'status' => 'pending',
                    ],
                    [
                        'id' => 3,
                        'doctor_id' => $doctor->id,
                        'patient_id' => $patient->id,
                        'date_time' => '2025-02-27 10:00',
                        'status' => 'pending',
                    ],
                ],
            ]);
    }

    public function test_doctor_can_list_empty_appointments()
    {
        $doctor = $this->create_doctor();

        Gate::shouldReceive('denies')->with('list-appointments')->andReturn(false);

        $this->appointmentsService->shouldReceive('getAppointments')
            ->once()
            ->with($doctor, null, null)
            ->andReturn([]);

        $response = $this->actingAs($doctor)->getJson(route('appointments.index'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Citas listadas correctamente',
                'data' => [],
            ]);
    }

    public function test_doctor_can_list_appointments_for_today()
    {
        $doctor = $this->create_doctor();
        $patient = $this->create_patient();
        $startOfDay = Carbon::now()->startOfDay();
        $endOfDay = Carbon::now()->endOfDay();

        Gate::shouldReceive('denies')->with('list-appointments')->andReturn(false);

        $this->appointmentsService->shouldReceive('getAppointments')
            ->once()
            ->with(
                $doctor,
                $startOfDay->toDateTimeString(),
                $endOfDay->toDateTimeString()
            )
            ->andReturn([
                Appointment::factory()->create([
                    'id' => 1,
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'date_time' => '2025-02-19 10:00',
                    'status' => 'pending',
                ]),
                Appointment::factory()->create([
                    'id' => 2,
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'date_time' => '2025-02-19 12:00',
                    'status' => 'pending',
                ]),
                Appointment::factory()->create([
                    'id' => 3,
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'date_time' => '2025-02-19 15:00',
                    'status' => 'pending',
                ]),
            ]);

        $response = $this->actingAs($doctor)->getJson(route('appointments.today'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Citas listadas correctamente',
                'data' => [
                    [
                        'id' => 1,
                        'doctor_id' => $doctor->id,
                        'patient_id' => $patient->id,
                        'date_time' => '2025-02-19 10:00',
                        'status' => 'pending',
                    ],
                    [
                        'id' => 2,
                        'doctor_id' => $doctor->id,
                        'patient_id' => $patient->id,
                        'date_time' => '2025-02-19 12:00',
                        'status' => 'pending',
                    ],
                    [
                        'id' => 3,
                        'doctor_id' => $doctor->id,
                        'patient_id' => $patient->id,
                        'date_time' => '2025-02-19 15:00',
                        'status' => 'pending',
                    ],
                ],
            ]);
    }

    public function test_patient_cannot_list_appointments()
    {
        $patient = $this->create_patient();

        Gate::shouldReceive('denies')->with('list-appointments')->andReturn(true);

        $response = $this->actingAs($patient)->getJson(route('appointments.index'));

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Sólo los doctores pueden ver sus citas',
            ]);
    }

    public function test_doctor_can_confirm_appointment()
    {
        $patient = $this->create_patient();
        $doctor = $this->create_doctor();

        $payment = Payment::factory()->paid()->create();

        $appointment = Appointment::factory()->create([
            'id' => 1,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'payment_id' => $payment->id,
            'date_time' => '2025-03-31 10:00',
            'status' => 'pending',
        ]);

        $updatedAppointment = $appointment->fill(['status' => 'confirmed']);

        Gate::shouldReceive('denies')
            ->with('confirm-cancel-appointment', Mockery::type(Appointment::class))
            ->andReturn(false);

        $this->appointmentsService->shouldReceive('updateAppointment')
            ->once()
            ->with(Mockery::type(Appointment::class), ['status' => 'confirmed'])
            ->andReturn($updatedAppointment);

        $response = $this->actingAs($doctor)->putJson(
            route('appointments.confirm', ['appointment' => 1]),
            []
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => $updatedAppointment->toArray(),
                'message' => 'Cita confirmada correctamente',
            ]);
    }

    public function test_doctor_can_cancel_appointment()
    {
        $patient = $this->create_patient();
        $doctor = $this->create_doctor();

        $appointment = Appointment::factory()->create([
            'id' => 1,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'date_time' => '2025-03-31 10:00',
            'status' => 'pending',
        ]);

        $updatedAppointment = $appointment->fill(['status' => 'cancelled']);

        Gate::shouldReceive('denies')
            ->with('confirm-cancel-appointment', Mockery::type(Appointment::class))
            ->andReturn(false);

        $this->appointmentsService->shouldReceive('updateAppointment')
            ->once()
            ->with(Mockery::type(Appointment::class), ['status' => 'cancelled'])
            ->andReturn($updatedAppointment);

        $response = $this->actingAs($doctor)->putJson(
            route('appointments.cancel', ['appointment' => 1]),
            []
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => $updatedAppointment->toArray(),
                'message' => 'Cita cancelada correctamente',
            ]);
    }

    public function test_doctor_cannot_confirm_appointment_because_is_not_paid()
    {
        $patient = $this->create_patient();
        $doctor = $this->create_doctor();

        $payment = Payment::factory()->failed()->create();

        Appointment::factory()->create([
            'id' => 1,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'payment_id' => $payment->id,
            'date_time' => '2025-02-19 10:00',
            'status' => 'pending',
        ]);

        Gate::shouldReceive('denies')
            ->with('confirm-cancel-appointment', Mockery::type(Appointment::class))
            ->andReturn(false);

        $response = $this->actingAs($doctor)->putJson(
            route('appointments.confirm', ['appointment' => 1]),
            []
        );

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'data' => [],
                'message' => 'La cita no puede ser confirmada debido a que ya pasó o el paciente no ha pagado',
            ]);
    }

    public function test_doctor_cannot_cancel_appointment_because_is_paid()
    {
        $patient = $this->create_patient();
        $doctor = $this->create_doctor();

        $payment = Payment::factory()->paid()->create();

        Appointment::factory()->create([
            'id' => 1,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'payment_id' => $payment->id,
            'date_time' => '2025-02-19 10:00',
            'status' => 'paid',
        ]);

        Gate::shouldReceive('denies')
            ->with('confirm-cancel-appointment', Mockery::type(Appointment::class))
            ->andReturn(false);

        $response = $this->actingAs($doctor)->putJson(
            route('appointments.cancel', ['appointment' => 1]),
            []
        );

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'data' => [],
                'message' => 'La cita no puede ser cancelada debido a que ya pasó o el paciente ya ha pagado',
            ]);
    }

    public function test_doctor_cannot_confirm_a_past_appointment()
    {
        $patient = $this->create_patient();
        $doctor = $this->create_doctor();

        Appointment::factory()->create([
            'id' => 1,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'date_time' => '2025-02-17 10:00',
            'status' => 'pending',
        ]);

        Gate::shouldReceive('denies')
            ->with('confirm-cancel-appointment', Mockery::type(Appointment::class))
            ->andReturn(false);

        $response = $this->actingAs($doctor)->putJson(
            route('appointments.confirm', ['appointment' => 1]),
            []
        );

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'data' => [],
                'message' => 'La cita no puede ser confirmada debido a que ya pasó o el paciente no ha pagado'
            ]);
    }

    public function test_doctor_cannot_cancel_a_past_appointment()
    {
        $patient = $this->create_patient();
        $doctor = $this->create_doctor();

        Appointment::factory()->create([
            'id' => 1,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'date_time' => '2025-02-17 10:00',
            'status' => 'pending',
        ]);

        Gate::shouldReceive('denies')
            ->with('confirm-cancel-appointment', Mockery::type(Appointment::class))
            ->andReturn(false);

        $response = $this->actingAs($doctor)->putJson(
            route('appointments.cancel', ['appointment' => 1]),
            []
        );

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'data' => [],
                'message' => 'La cita no puede ser cancelada debido a que ya pasó o el paciente ya ha pagado'
            ]);
    }

    public function test_patient_cannot_confirm_an_appointment()
    {
        $patient = $this->create_patient();
        $doctor = $this->create_doctor();

        Appointment::factory()->create([
            'id' => 1,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'date_time' => '2025-02-17 10:00',
            'status' => 'pending',
        ]);

        Gate::shouldReceive('denies')
            ->with('confirm-cancel-appointment', Mockery::type(Appointment::class))
            ->andReturn(true);

        $response = $this->actingAs($doctor)->putJson(
            route('appointments.confirm', ['appointment' => 1]),
            []
        );

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'data' => [],
                'message' => 'Sólo los doctores pueden confirmar citas'
            ]);
    }
}
