<?php

namespace Puntodev\PaymentsFake;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Puntodev\Payments\PayPal;
use Puntodev\Payments\PayPalApi;

class PayPalFake implements PayPal
{
    private static array $calls = [];

    public function defaultClient(): PayPalApi
    {
        return new PayPalFakeApi();
    }

    public function withCredentials(string $clientId, string $clientSecret): PayPalApi
    {
        return new PayPalFakeApi();
    }

    public static function storeOrder(string $id, array $order): void
    {
        static::cache()->put("fake-paypal-order-$id", $order, 300);
    }

    public static function getStoredOrder(string $id): ?array
    {
        return static::cache()->get("fake-paypal-order-$id");
    }

    public static function storeCapturedOrder(string $id, array $capture): void
    {
        static::cache()->put("fake-paypal-captured-$id", $capture, 300);
    }

    public static function getCapturedOrder(string $id): ?array
    {
        return static::cache()->get("fake-paypal-captured-$id");
    }

    public static function markOrderAsDeclined(string $id): void
    {
        static::cache()->put("fake-paypal-declined-$id", true, 300);
    }

    public static function isOrderDeclined(string $id): bool
    {
        return static::cache()->get("fake-paypal-declined-$id", false);
    }

    public static function recordCall(string $method, array $args = []): void
    {
        static::$calls[] = ['method' => $method, 'args' => $args];
    }

    public static function getCalls(?string $method = null): array
    {
        if ($method === null) {
            return static::$calls;
        }

        return array_values(array_filter(static::$calls, fn (array $call) => $call['method'] === $method));
    }

    public static function reset(): void
    {
        static::$calls = [];
    }

    private static function cache(): Repository
    {
        return Cache::store('file');
    }
}
