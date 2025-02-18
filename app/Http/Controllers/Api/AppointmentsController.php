<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppointmentsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AppointmentsController extends Controller
{

    public function __construct(protected AppointmentsService $appointmentsService) {}

    /**
     * Create or request a new appointment (only patients can access this endpoint)
     */
    public function create(Request $request)
    {
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

        $appointment = $this->appointmentsService->createAppointment($validatedData);

        return response()->json([
            'success' => true,
            'data' => $appointment,
            'message' => 'Cita creada correctamente'
        ], 200);
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
}
