<?php

namespace App\Http\Controllers\Api;

use App\Enums\AppointmentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentsService;
use App\Services\PaymentsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AppointmentsController extends Controller
{

    public function __construct(
        protected AppointmentsService $appointmentsService,
        protected PaymentsService $paymentsService,
    ) {}

    /**
     * Create or request a new appointment (only patients can access this endpoint)
     */
    public function create(Request $request)
    {
        try {
            if (Gate::denies('create-appointment')) {
                return response()->json([
                    'success' => false,
                    'data' => [],
                    'message' => 'Sólo los pacientes pueden crear citas'
                ], 403);
            }
    
            $validationRules = $this->appointmentsService->createRules();
            $validatedData = $request->validate($validationRules);
    
            $canCreateAppointment = $this->appointmentsService->checkAvailability(
                $validatedData['doctor_id'],
                $validatedData['date_time']
            );
    
            if (!$canCreateAppointment) {
                return response()->json([
                    'success' => false,
                    'data' => [],
                    'message' => 'No hay disponibilidad para la cita en la fecha y hora seleccionada'
                ], 400);
            }
    
            $payment = $this->paymentsService->createPayment(1500);
            $appointment = $this->appointmentsService->createAppointment(
                [
                    ...$validatedData,
                    'patient_id' => $request->user()->id,
                    'payment_id' => $payment->id,
                ],
            );
    
            return response()->json([
                'success' => true,
                'data' => $appointment,
                'message' => 'Cita creada correctamente'
            ], 200);
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    /**
     * List appointments (only doctors can access this endpoint)
     */
    public function index()
    {
        if (Gate::denies('list-appointments')) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Sólo los doctores pueden ver sus citas'
            ], 403);
        }

        $appointments = $this->appointmentsService->getAppointments(
            request()->user(),
            request('start_date'),
            request('end_date')
        );

        return response()->json([
            'success' => true,
            'data' => $appointments,
            'message' => 'Citas listadas correctamente'
        ], 200);
    }

    public function getAppointmentsForToday()
    {
        if (Gate::denies('list-appointments')) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Sólo los doctores pueden ver sus citas'
            ], 403);
        }

        $startOfDay = Carbon::now()->startOfDay();
        $endOfDay = Carbon::now()->endOfDay();

        $appointments = $this->appointmentsService->getAppointments(
            request()->user(),
            $startOfDay->toDateTimeString(),
            $endOfDay->toDateTimeString(),
        );

        return response()->json([
            'success' => true,
            'data' => $appointments,
            'message' => 'Citas listadas correctamente'
        ], 200);
    }

    /**
     * Confirm an appointment (only doctors can access this endpoint)
     */
    public function confirm(Appointment $appointment)
    {
        if (Gate::denies('confirm-cancel-appointment', $appointment)) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Sólo los doctores pueden confirmar citas'
            ], 403);
        }

        // If the appointment is already confirmed, return success with an explanation message
        if ($appointment->confirmed) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'La cita ya fue confirmada con anterioridad'
            ], 200);
        }

        // If the appointment can not be confirmed, return an error message
        if (!$appointment->can_confirm) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'La cita no puede ser confirmada debido a que ya pasó o el paciente no ha pagado',
            ], 400);
        }

        $updatedAppointment = $this->appointmentsService->updateAppointment(
            $appointment,
            ['status' => AppointmentStatusEnum::Confirmed->value()],
        );

        return response()->json([
            'success' => true,
            'data' => $updatedAppointment,
            'message' => 'Cita confirmada correctamente'
        ], 200);
    }

    /**
     * Confirm an appointment (only doctors can access this endpoint)
     */
    public function cancel(Appointment $appointment)
    {
        if (Gate::denies('confirm-cancel-appointment', $appointment)) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Sólo los doctores pueden rechazar citas'
            ], 403);
        }

        // If the appointment is already cancelled, return success with an explanation message
        if ($appointment->cancelled) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'La cita ya fue rechazada con anterioridad'
            ], 200);
        }

        // If the appointment can not be confirmed, return an error message
        if (!$appointment->can_cancel) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'La cita no puede ser cancelada debido a que ya pasó o el paciente ya ha pagado'
            ], 400);
        }

        $updatedAppointment = $this->appointmentsService->updateAppointment(
            $appointment,
            ['status' => AppointmentStatusEnum::Cancelled->value()],
        );

        return response()->json([
            'success' => true,
            'data' => $updatedAppointment,
            'message' => 'Cita cancelada correctamente'
        ], 200);
    }
}
