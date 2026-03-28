<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Puntodev\Payments\PayPal;
use Puntodev\PaymentsFake\PayPalFake;
use Puntodev\PaymentsFake\PayPalFakeApi;

class PayPalFakeTest extends TestCase
{
    #[Test]
    public function resolves_fake_from_container(): void
    {
        $paypal = app(PayPal::class);

        $this->assertInstanceOf(PayPalFake::class, $paypal);
    }

    #[Test]
    public function default_client_returns_fake_api(): void
    {
        $paypal = app(PayPal::class);
        $client = $paypal->defaultClient();

        $this->assertInstanceOf(PayPalFakeApi::class, $client);
    }

    #[Test]
    public function with_credentials_returns_fake_api(): void
    {
        $paypal = app(PayPal::class);
        $client = $paypal->withCredentials('any-id', 'any-secret');

        $this->assertInstanceOf(PayPalFakeApi::class, $client);
    }

    #[Test]
    public function reset_clears_state(): void
    {
        $client = app(PayPal::class)->defaultClient();
        $client->createOrder(['intent' => 'CAPTURE']);

        $this->assertNotEmpty(PayPalFake::getCalls());

        PayPalFake::reset();

        $this->assertEmpty(PayPalFake::getCalls());
    }
}
