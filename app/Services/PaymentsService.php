<?php

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class PaymentsService
{
    public function __construct()
    {
        Stripe::setApiKey(config('app.stripe.secret'));
    }

    public function createPayment(float $amount): Payment
    {
        $payment = Payment::create([
            'amount' => $amount,
            'status' => PaymentStatusEnum::Pending->value(),
        ]);

        return $payment;
    }

    public function generateCheckoutSession(
        string $productName,
        float $amount,
        string $currency,
        int $quantity,
        string $successUrl,
        string $cancelUrl,
        array $metadata,

    ): null|Session {
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $productName,
                        ],
                        'unit_amount' => $amount * 100,
                    ],
                    'quantity' => $quantity,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $successUrl . "?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
        ]);

        return $session;
    }

    public function getCheckoutSession($sessionId): null|Session
    {
        try {
            $session = Session::retrieve($sessionId);

            return $session;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return null;
        }
    }
}
