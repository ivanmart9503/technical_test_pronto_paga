<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;

class AppointmentsService
{

    public function createRules()
    {
        return [
            'doctor_id' => ['required', 'exists:users,id'],
            'patient_id' => ['required', 'exists:users,id'],
            'date_time' => ['required', 'date_format:Y-m-d H:i'],
        ];
    }

    /**
     * Gets all appointments for the user within the specified date range.
     */
    public function getAppointments(User $user, $startDate, $endDate)
    {
        $appointments = $user->appointments()
            ->when(!empty($startDate) && !empty($endDate), function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date_time', [$startDate, $endDate]);
            })
            ->with('doctor', 'patient', 'payment')
            ->get();

        return $appointments;
    }

    /**
     * Checks if the appointment time is available in the doctor's schedules.
     */
    public function checkScheduleAvailability(User $doctor, Carbon $dateTime)
    {
        $schedules = $doctor->schedules;

        foreach ($schedules as $schedule) {
            $startTime = Carbon::parse($schedule->start_time)->format('H:i');
            $endTime = Carbon::parse($schedule->end_time)->format('H:i');
            $appointmentTime = $dateTime->format('H:i');

            if ($appointmentTime >= $startTime && $appointmentTime <= $endTime) {
                return true;
            }
        }

        return false;
    }


    /**
     * Checks if the appointment time overlaps with any existing appointments.
     */
    public function checkOverlaps(User $doctor, Carbon $dateTime)
    {
        $overlaps = $doctor->appointments()->where('date_time', $dateTime)->exists();

        return $overlaps;
    }

    /**
     * Checks if the appointment time is available and there are no overlaps with existing appointments.
     * This method is a combination of the checkScheduleAvailability and checkOverlaps methods, for convenience.
     */
    public function checkAvailability($doctorId, $dateTime): bool
    {
        $doctor = User::find($doctorId);
        $dateTime = Carbon::createFromFormat('Y-m-d H:i', $dateTime);

        $validDateTime = $this->checkScheduleAvailability($doctor, $dateTime);
        $noOverlaps = $this->checkOverlaps($doctor, $dateTime);

        return $validDateTime && !$noOverlaps;
    }

    /**
     * Creates a new appointment after checking availability and overlaps.
     */
    public function createAppointment(array $data)
    {
        $appointment = Appointment::create($data);

        return $appointment;
    }

    /**
     * Update an existing appointment.
     */
    public function updateAppointment(Appointment $appointment, array $data)
    {
        $appointment->update($data);

        return $appointment;
    }
}
