<?php

namespace App\Http\Controllers\Api;

use App\Enums\AppointmentStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\PaymentsService;
use Illuminate\Support\Facades\Gate;

class PaymentsController extends Controller
{
    public function __construct(protected PaymentsService $paymentsService) {}

    /**
     * Generate a payment link for patient to pay an appointment
     */
    public function pay(Appointment $appointment)
    {
        if (Gate::denies('pay-appointment', $appointment)) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Solo los pacientes pueden generar el enlace de pago, o bien, no tienes permisos para realizar esta acción'
            ], 403);
        }

        if($appointment->status === AppointmentStatusEnum::Cancelled->value()) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'No se puede generar el enlace de pago para una cita cancelada'
            ], 400);
        }

        if ($appointment->payment->status === PaymentStatusEnum::Completed->value()) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'La cita ya está pagada'
            ], 400);
        }

        try {
            $session = $this->paymentsService->generateCheckoutSession(
                productName: 'Cita médica con el Doctor ' . $appointment->doctor->name,
                amount: $appointment->payment->amount,
                currency: 'mxn',
                quantity: 1,
                successUrl: route('appointments.payment-success', $appointment->id),
                cancelUrl: route('appointments.payment-failed', $appointment->id),
                metadata: [
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                    'patient_id' => $appointment->patient_id,
                ],
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $session->id,
                    'payment_url' => $session->url,
                ],
                'message' => 'Enlace de pago generado correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Error al generar el enlace de pago: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Handle payment success callback from payment gateway
     */
    public function paymentSuccess(Appointment $appointment)
    {
        if ($appointment->payment->status === PaymentStatusEnum::Completed->value()) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'La cita ya está pagada'
            ], 400);
        }

        $sessionId = request('session_id');

        if (!$sessionId) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Ocurrió un error al procesar el pago en nuestro sistema'
            ], 400);
        }

        // Validate session and payment status to update appointment and payment info on database
        $session = $this->paymentsService->getCheckoutSession($sessionId);

        if ($session->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'El pago no se ha completado correctamente, inténtalo de nuevo'
            ], 400);
        }

        $appointment->payment->status = PaymentStatusEnum::Completed->value();
        $appointment->payment->payment_gateway = 'Stripe';
        $appointment->payment->transaction_id = $session->payment_intent;
        $appointment->status = AppointmentStatusEnum::Paid->value();

        $appointment->payment->save();
        $appointment->save();

        return response()->json([
            'success' => true,
            'data' => $appointment->with(['payment', 'doctor']),
            'message' => 'Cita pagada correctamente'
        ]);
    }

    /**
     * Handle payment failure callback from payment gateway
     */
    public function paymentFailure(Appointment $appointment)
    {
        return response()->json([
            'success' => false,
            'data' => [],
            'message' => 'El pago no se ha completado correctamente, inténtalo de nuevo'
        ]);
    }
}
