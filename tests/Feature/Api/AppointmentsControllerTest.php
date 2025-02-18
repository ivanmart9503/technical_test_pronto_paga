<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Appointment;
use App\Services\AppointmentsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Tests\TestCase;

class AppointmentsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $appointmentsService;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear un mock de AppointmentsService
        $this->appointmentsService = Mockery::mock(AppointmentsService::class);
        $this->app->instance(AppointmentsService::class, $this->appointmentsService);
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

        // Simular que el Gate permite la creación de la cita
        Gate::shouldReceive('denies')->with('create-appointment')->andReturn(false);

        // Simular la validación de disponibilidad
        $this->appointmentsService->shouldReceive('createRules')
            ->once()
            ->andReturn([
                'doctor_id' => ['required', 'exists:users,id'],
                'patient_id' => ['required', 'exists:users,id'],
                'date_time' => ['required', 'date_format:Y-m-d H:i'],
            ]);

        $this->appointmentsService->shouldReceive('checkAvailability')
            ->once()
            ->with($doctor->id, '2025-02-18 10:00')
            ->andReturn(true);

        // Simular la creación de la cita
        $this->appointmentsService->shouldReceive('createAppointment')
            ->once()
            ->andReturn(Appointment::factory()->create([
                'id' => 1,
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'date_time' => '2025-02-18 10:00'
            ]));

        $response = $this->actingAs($patient)->postJson('/api/appointments', [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'date_time' => '2025-02-18 10:00',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => 1,
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'date_time' => '2025-02-18 10:00',
                ],
                'message' => 'Cita creada correctamente',
            ]);
    }

    public function test_doctor_cannot_create_appointment()
    {
        $doctor = $this->create_doctor();

        $this->actingAs($doctor);

        // Simular que el Gate bloquea la creación de la cita
        Gate::shouldReceive('denies')->with('create-appointment')->andReturn(true);

        $response = $this->postJson('/api/appointments', [
            'doctor_id' => 1,
            'patient_id' => 1,
            'date_time' => '2025-02-18 10:00',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Sólo los pacientes pueden crear citas',
            ]);
    }

    public function test_doctor_can_list_appointments()
    {
        $doctor = $this->create_doctor();

        // Simular que el Gate permite listar citas
        Gate::shouldReceive('denies')->with('list-appointments')->andReturn(false);

        // Simular la respuesta del servicio
        $this->appointmentsService->shouldReceive('getAppointments')
            ->once()
            ->with($doctor, null, null)
            ->andReturn([]);

        $response = $this->actingAs($doctor)->getJson('/api/appointments');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Citas listadas correctamente',
                'data' => [],
            ]);
    }

    public function test_patient_cannot_list_appointments()
    {
        $patient = $this->create_patient();

        $this->actingAs($patient);

        // Simular que el Gate bloquea la consulta
        Gate::shouldReceive('denies')->with('list-appointments')->andReturn(true);

        $response = $this->getJson('/api/appointments');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Sólo los doctores pueden ver sus citas',
            ]);
    }
}
