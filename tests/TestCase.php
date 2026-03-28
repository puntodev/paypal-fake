<?php

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Puntodev\Payments\PayPalServiceProvider;
use Puntodev\PaymentsFake\PayPalFake;
use Puntodev\PaymentsFake\PayPalFakeServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        PayPalFake::reset();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('paypal.client_id', 'fake-client-id');
        $app['config']->set('paypal.client_secret', 'fake-client-secret');
        $app['config']->set('paypal.use_sandbox', true);
    }

    protected function getPackageProviders($app): array
    {
        return [
            PayPalServiceProvider::class,
            PayPalFakeServiceProvider::class,
        ];
    }
}
