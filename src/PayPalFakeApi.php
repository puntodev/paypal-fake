<?php

namespace Puntodev\PaymentsFake;

use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Puntodev\Payments\PayPalApi;

class PayPalFakeApi implements PayPalApi
{
    public function createOrder(array $order): array
    {
        $orderId = strtoupper(Str::random(17));

        $checkoutUrl = url("/paypal-fake/checkout/$orderId");

        $purchaseUnits = [];
        foreach ($order['purchase_units'] ?? [] as $unit) {
            $purchaseUnits[] = [
                'reference_id' => 'default',
                'amount' => $unit['amount'] ?? [],
                'payee' => [
                    'email_address' => 'sb-fake@business.example.com',
                    'merchant_id' => 'FAKE_MERCHANT_ID',
                    'display_data' => [
                        'brand_name' => Arr::get($order, 'payment_source.paypal.experience_context.brand_name', ''),
                    ],
                ],
                'description' => $unit['description'] ?? '',
                'custom_id' => $unit['custom_id'] ?? '',
            ];
        }

        $response = [
            'id' => $orderId,
            'intent' => 'CAPTURE',
            'purchase_units' => $purchaseUnits,
            'create_time' => now()->toIso8601String(),
            'links' => [
                [
                    'href' => "https://api.sandbox.paypal.com/v2/checkout/orders/$orderId",
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                [
                    'href' => $checkoutUrl,
                    'rel' => 'payer-action',
                    'method' => 'GET',
                ],
            ],
            'status' => 'CREATED',
        ];

        // Store the full order input alongside the response so we can access
        // payment_source.paypal.experience_context (return_url, cancel_url) later
        PayPalFake::storeOrder($orderId, array_merge($response, [
            'payment_source' => $order['payment_source'] ?? [],
        ]));
        PayPalFake::recordCall('createOrder', ['order' => $order, 'response' => $response]);

        return $response;
    }

    public function findOrderById(string $id): ?array
    {
        PayPalFake::recordCall('findOrderById', ['id' => $id]);

        return PayPalFake::getStoredOrder($id);
    }

    /**
     * @throws RequestException
     */
    public function captureOrder(string $orderId): ?array
    {
        PayPalFake::recordCall('captureOrder', ['orderId' => $orderId]);

        if (PayPalFake::isOrderDeclined($orderId)) {
            $this->throwDeclinedRequestException($orderId);
        }

        $stored = PayPalFake::getStoredOrder($orderId);

        $captureId = strtoupper(Str::random(17));

        $purchaseUnits = $stored['purchase_units'] ?? [];
        foreach ($purchaseUnits as &$unit) {
            $unit['payments'] = [
                'captures' => [
                    [
                        'id' => $captureId,
                        'status' => 'COMPLETED',
                        'amount' => $unit['amount'] ?? [
                            'currency_code' => 'USD',
                            'value' => '0.00',
                        ],
                        'final_capture' => true,
                    ],
                ],
            ];
        }

        $response = [
            'id' => $orderId,
            'intent' => 'CAPTURE',
            'status' => 'COMPLETED',
            'purchase_units' => $purchaseUnits,
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
            'create_time' => $stored['create_time'] ?? now()->toIso8601String(),
            'links' => [
                [
                    'href' => "https://api.sandbox.paypal.com/v2/checkout/orders/$orderId",
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ];

        PayPalFake::storeCapturedOrder($orderId, $response);

        return $response;
    }

    /**
     * @throws RequestException
     */
    private function throwDeclinedRequestException(string $orderId): never
    {
        $body = json_encode([
            'name' => 'UNPROCESSABLE_ENTITY',
            'details' => [
                [
                    'issue' => 'INSTRUMENT_DECLINED',
                    'description' => 'The instrument presented was either declined by the processor or bank, or it can\'t be used for this payment.',
                ],
            ],
            'message' => 'The requested action could not be performed, semantically incorrect, or failed business validation.',
            'debug_id' => strtoupper(Str::random(13)),
            'links' => [
                [
                    'href' => "https://api.sandbox.paypal.com/v2/checkout/orders/$orderId",
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ]);

        $psrResponse = new Response(422, ['Content-Type' => 'application/json'], $body);
        $clientResponse = new ClientResponse($psrResponse);

        throw new RequestException($clientResponse);
    }

    public function verifyIpn(string $querystring): string
    {
        PayPalFake::recordCall('verifyIpn', ['querystring' => $querystring]);

        return 'VERIFIED';
    }
}
