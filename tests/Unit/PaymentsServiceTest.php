<?php

namespace Tests\Unit;

use App\Enums\PaymentStatusEnum;
use App\Services\PaymentsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentsService $paymentsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentsService = new PaymentsService();
    }

    /** @test */
    public function createPayment_returns_a_new_payment_with_pending_status()
    {
        $amount = 100.50;

        $payment = $this->paymentsService->createPayment($amount);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'amount' => $amount,
            'status' => PaymentStatusEnum::Pending->value(),
        ]);
    }

    /** @test */
    public function generateCheckoutSession_returns_a_valid_stripe_checkout_session()
    {
        $session = $this->paymentsService->generateCheckoutSession(
            'Test Product',
            100.0,
            'usd',
            1,
            route('appointments.payment-success', 1) . '/success',
            route('appointments.payment-failed', 1) . '/cancel',
            ['order_id' => 123]
        );

        // Verify that the link starts with the expected prefix (https://checkout.stripe.com/c/pay/)
        $this->assertStringStartsWith('https://checkout.stripe.com/c/pay/', $session->url);

        // Verify that the link contains the cs_test_ prefix (which indicates a test mode session)
        $this->assertStringContainsString('cs_test_', $session->url);
    }

    /** @test */
    public function getCheckoutSession_returns_a_valid_stripe_checkout_session()
    {
        // First generate a session
        $originalSession = $this->paymentsService->generateCheckoutSession(
            'Test product',
            100.0,
            'usd',
            1,
            route('appointments.payment-success', 1) . '/success',
            route('appointments.payment-failed', 1) . '/cancel',
            ['order_id' => 123]
        );

        // Use the session id to retrieve the session from Stripe
        $session = $this->paymentsService->getCheckoutSession($originalSession->id);

        // Verify that the retrieved session matches the original session and is unpaid
        $this->assertNotNull($session, 'Failed to retrieve the payment session from Stripe');
        $this->assertEquals($originalSession->id, $session->id, 'The session_id does not match');
        $this->assertEquals($session->payment_status, 'unpaid', 'The payment status is not as expected');
    }


    /** @test */
    public function getCheckoutSession_returns_null_when_the_session_is_not_found()
    {
        // Use the session id to retrieve the session from Stripe
        $session = $this->paymentsService->getCheckoutSession('invalid_session_id');

        // Verify that the retrieved session is null
        $this->assertNull($session, 'Failed to retrieve the payment session from Stripe');
    }
}
