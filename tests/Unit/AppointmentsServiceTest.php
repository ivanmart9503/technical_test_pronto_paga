<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\Schedule;
use App\Models\User;
use App\Services\AppointmentsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentsServiceTest extends TestCase
{
    use RefreshDatabase; // Limpia la base de datos entre pruebas

    protected AppointmentsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AppointmentsService();
    }

    public function create_doctor(): User
    {
        return User::factory()->create([
            'email' => 'doctor@example.com',
            'role' => 'doctor',
        ]);
    }

    public function create_patient( ): User
    {
        return User::factory()->create([
            'email' => 'patient@example.com',
            'role' => 'patient'
        ]);
    }

    /** @test */
    public function checkScheduleAvailability_returns_true_if_doctor_is_available()
    {
        $doctor = $this->create_doctor();

        Schedule::factory()->morning($doctor)->create();
        Schedule::factory()->afternoon($doctor)->create();

        // Valid date and time because doctor is available at that time
        $dateTime = Carbon::parse('2025-02-19 09:00:00');

        $result = $this->service->checkScheduleAvailability($doctor, $dateTime);

        $this->assertTrue($result);
    }

    /** @test */
    public function checkScheduleAvailability_returns_false_if_doctor_is_not_available()
    {
        $doctor = $this->create_doctor();

        Schedule::factory()->morning($doctor)->create();
        Schedule::factory()->afternoon($doctor)->create();

        // Invalid date and time because doctor is not available at that time
        $dateTime = Carbon::parse('2025-02-19 13:00:00');

        $result = $this->service->checkScheduleAvailability($doctor, $dateTime);

        $this->assertFalse($result);
    }

    /** @test */
    public function checkOverlaps_returns_true_if_appointment_exists()
    {
        $doctor = $this->create_doctor();

        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'date_time' => '2025-02-19 10:00:00',
        ]);

        // Valid date and time but there is already an appointment at that time
        $dateTime = Carbon::parse('2025-02-19 10:00:00');

        $result = $this->service->checkOverlaps($doctor, $dateTime);

        $this->assertTrue($result);
    }

    /** @test */
    public function checkOverlaps_returns_false_if_no_appointment_exists()
    {
        $doctor = $this->create_doctor();

        // Valid date and time and there is no appointment at that time
        $dateTime = Carbon::parse('2025-02-19 10:00:00');

        $result = $this->service->checkOverlaps($doctor, $dateTime);

        $this->assertFalse($result);
    }

    /** @test */
    public function checkAvailability_returns_true_if_doctor_is_available_and_no_overlaps()
    {
        $doctor = $this->create_doctor();

        Schedule::factory()->morning($doctor)->create();
        Schedule::factory()->afternoon($doctor)->create();

        // Valid date and time because doctor is available at that time and there is no overlaps with other appointments
        $dateTime = '2025-02-19 09:00';

        $result = $this->service->checkAvailability($doctor->id, $dateTime);

        $this->assertTrue($result);
    }

    /** @test */
    public function checkAvailability_returns_false_if_doctor_is_not_available_or_has_conflict()
    {
        $doctor = $this->create_doctor();

        Schedule::factory()->morning($doctor)->create();
        Schedule::factory()->afternoon($doctor)->create();

        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'date_time' => '2025-02-19 09:00:00',
        ]);

        // Valid date and time but there is already an appointment at that time
        $dateTime = '2025-02-19 09:00';

        $result = $this->service->checkAvailability($doctor->id, $dateTime);

        $this->assertFalse($result);
    }

    /** @test */
    public function createAppointment_creates_an_appointment_successfully()
    {
        $doctor = $this->create_doctor();
        $patient = $this->create_patient();

        $data = [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'date_time' => '2025-02-19 09:00:00',
            'status' => 'pending',
        ];

        $appointment = $this->service->createAppointment($data);

        $this->assertDatabaseHas('appointments', [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'date_time' => '2025-02-19 09:00:00',
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Appointment::class, $appointment);
    }

    /** @test */
    public function getAppointments_returns_appointments_between_start_and_end_dates()
    {
        $doctor = $this->create_doctor();
        $patient = $this->create_patient();

        for ($i = 0; $i < 5; $i++) {
            $date = Carbon::parse('2025-02-19 09:00:00')->addDays($i);

            Appointment::factory()->create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'date_time' => $date,
            ]);
        }

        $startDate = '2025-02-19 00:00:00';
        $endDate = '2025-02-28 00:00:00';

        $appointments = $this->service->getAppointments($doctor, $startDate, $endDate);

        $this->assertCount(5, $appointments);
    }

    /** @test */
    public function getAppointments_returns_empty_collection_if_no_appointments_found()
    {
        $doctor = $this->create_doctor();

        $startDate = '2025-02-19 00:00:00';
        $endDate = '2025-02-28 00:00:00';

        $appointments = $this->service->getAppointments($doctor, $startDate, $endDate);

        $this->assertCount(0, $appointments);
    }
}
