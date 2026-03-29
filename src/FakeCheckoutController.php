<?php

namespace Puntodev\PaymentsFake;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Uri;

class FakeCheckoutController extends Controller
{
    public function show(string $orderId): View
    {
        $order = PayPalFake::getStoredOrder($orderId);

        $brandName = $order['purchase_units'][0]['payee']['display_data']['brand_name']
            ?? $order['payment_source']['paypal']['experience_context']['brand_name']
            ?? '';

        return view('paypal-fake::checkout', [
            'orderId' => $orderId,
            'description' => $order['purchase_units'][0]['description'] ?? 'PayPal Order',
            'amount' => $order['purchase_units'][0]['amount']['value'] ?? '0.00',
            'currency' => $order['purchase_units'][0]['amount']['currency_code'] ?? 'USD',
            'brandName' => $brandName,
        ]);
    }

    public function approve(string $orderId): RedirectResponse
    {
        return $this->processCheckout($orderId);
    }

    public function decline(string $orderId): RedirectResponse
    {
        PayPalFake::markOrderAsDeclined($orderId);

        return $this->processCheckout($orderId);
    }

    public function cancel(string $orderId): RedirectResponse
    {
        $order = PayPalFake::getStoredOrder($orderId);

        $cancelUrl = $order['payment_source']['paypal']['experience_context']['cancel_url'] ?? null;

        $redirectUrl = Uri::of($cancelUrl)
            ->withQuery(['token' => $orderId]);

        return redirect($redirectUrl);
    }

    private function processCheckout(string $orderId): RedirectResponse
    {
        $order = PayPalFake::getStoredOrder($orderId);

        $returnUrl = $order['payment_source']['paypal']['experience_context']['return_url'] ?? null;

        $providerId = $this->extractProviderId($returnUrl);

        if ($providerId) {
            $webhookUrl = url("/paypal/webhook/$providerId");

            $webhookPayload = json_encode([
                'event_type' => 'CHECKOUT.ORDER.APPROVED',
                'resource' => [
                    'id' => $orderId,
                    'status' => 'APPROVED',
                    'intent' => 'CAPTURE',
                    'purchase_units' => $order['purchase_units'],
                    'payer' => [
                        'name' => [
                            'given_name' => 'Test',
                            'surname' => 'Buyer',
                        ],
                        'email_address' => 'buyer@example.com',
                        'payer_id' => 'FAKE_PAYER_ID',
                        'address' => [
                            'country_code' => 'US',
                        ],
                    ],
                    'create_time' => $order['create_time'] ?? now()->toIso8601String(),
                    'links' => [
                        [
                            'href' => "https://api.sandbox.paypal.com/v2/checkout/orders/$orderId",
                            'rel' => 'self',
                            'method' => 'GET',
                        ],
                        [
                            'href' => "https://api.sandbox.paypal.com/v2/checkout/orders/$orderId/capture",
                            'rel' => 'capture',
                            'method' => 'POST',
                        ],
                    ],
                ],
            ]);

            app()->handle(
                HttpRequest::create($webhookUrl, 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $webhookPayload)
            );
        }

        $redirectUrl = Uri::of($returnUrl)
            ->withQuery(['token' => $orderId, 'PayerID' => 'FAKE_PAYER_ID']);

        return redirect($redirectUrl);
    }

    private function extractProviderId(string $returnUrl): ?string
    {
        if (preg_match('#/paypal/return/(\d+)#', $returnUrl, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
