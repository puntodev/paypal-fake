<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Puntodev\Payments\OrderBuilder;
use Puntodev\Payments\PayPal;
use Puntodev\PaymentsFake\PayPalFake;

class PayPalFakeApiTest extends TestCase
{
    private function createTestOrder(): array
    {
        $client = app(PayPal::class)->defaultClient();

        $order = (new OrderBuilder())
            ->externalId('test-123')
            ->currency('USD')
            ->amount(25.00)
            ->description('Test Product')
            ->brandName('Test Brand')
            ->returnUrl('https://example.com/return')
            ->cancelUrl('https://example.com/cancel')
            ->make();

        return $client->createOrder($order);
    }

    #[Test]
    public function create_order(): void
    {
        $result = $this->createTestOrder();

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('CREATED', $result['status']);
        $this->assertCount(2, $result['links']);

        $payerAction = collect($result['links'])->firstWhere('rel', 'payer-action');
        $this->assertNotNull($payerAction);
        $this->assertStringContainsString($result['id'], $payerAction['href']);

        $calls = PayPalFake::getCalls('createOrder');
        $this->assertCount(1, $calls);
    }

    #[Test]
    public function find_order_by_id(): void
    {
        $created = $this->createTestOrder();
        $client = app(PayPal::class)->defaultClient();

        $result = $client->findOrderById($created['id']);

        $this->assertNotNull($result);
        $this->assertEquals($created['id'], $result['id']);
    }

    #[Test]
    public function find_order_by_id_returns_null_for_unknown(): void
    {
        $client = app(PayPal::class)->defaultClient();

        $result = $client->findOrderById('NON_EXISTENT_ID');

        $this->assertNull($result);
    }

    #[Test]
    public function capture_order(): void
    {
        $created = $this->createTestOrder();
        $client = app(PayPal::class)->defaultClient();

        $result = $client->captureOrder($created['id']);

        $this->assertNotNull($result);
        $this->assertEquals($created['id'], $result['id']);
        $this->assertEquals('COMPLETED', $result['status']);
        $this->assertArrayHasKey('payer', $result);
        $this->assertArrayHasKey('purchase_units', $result);

        $capture = $result['purchase_units'][0]['payments']['captures'][0];
        $this->assertEquals('COMPLETED', $capture['status']);
        $this->assertTrue($capture['final_capture']);

        $calls = PayPalFake::getCalls('captureOrder');
        $this->assertCount(1, $calls);
    }

    #[Test]
    public function verify_ipn(): void
    {
        $client = app(PayPal::class)->defaultClient();

        $result = $client->verifyIpn('tx=fake_transaction');

        $this->assertEquals('VERIFIED', $result);

        $calls = PayPalFake::getCalls('verifyIpn');
        $this->assertCount(1, $calls);
        $this->assertEquals('tx=fake_transaction', $calls[0]['args']['querystring']);
    }

    #[Test]
    public function create_order_with_discount(): void
    {
        $client = app(PayPal::class)->defaultClient();

        $order = (new OrderBuilder())
            ->externalId('discount-test')
            ->currency('USD')
            ->amount(50.00)
            ->discount(10.00)
            ->description('Discounted Product')
            ->brandName('Test Brand')
            ->returnUrl('https://example.com/return')
            ->cancelUrl('https://example.com/cancel')
            ->make();

        $result = $client->createOrder($order);

        $this->assertEquals('CREATED', $result['status']);

        $stored = PayPalFake::getStoredOrder($result['id']);
        $this->assertNotNull($stored);
        $this->assertEquals(40.00, $stored['purchase_units'][0]['amount']['value']);
        $this->assertEquals(10.00, $stored['purchase_units'][0]['amount']['breakdown']['discount']['value']);
    }
}
