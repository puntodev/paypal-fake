# paypal-fake

A fake PayPal API server for testing Laravel applications. It replaces the real `puntodev/paypal` client with a mock implementation that simulates the full checkout flow without hitting the PayPal API.

## Installation

```bash
composer require --dev puntodev/paypal-fake
```

## Activation

Set the environment variable in your `.env` or `phpunit.xml`:

```
PAYPAL_USE_FAKE=true
```

The service provider auto-registers and swaps the `PayPal` binding in the container with `PayPalFake`.

## How It Works

The library has four main components:

### PayPalFake

Extends `PayPal` and manages global test state. Stores orders in a file-based cache, records method calls for assertions, and provides a `reset()` method to clean up between tests.

### PayPalFakeApi

Extends `PayPalApi` with mock implementations:

- `createOrder(array $order)` — Generates a fake order ID, stores it in cache, and returns the expected response structure.
- `findOrderById(string $id)` — Retrieves a stored order from cache.
- `captureOrder(string $orderId)` — Simulates payment capture. Throws a `RequestException` if the order was marked as declined.
- `verifyIpn(string $querystring)` — Always returns `'VERIFIED'`.

### FakeCheckoutController

Exposes HTTP routes that simulate PayPal's checkout UI:

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/paypal-fake/checkout/{orderId}` | Displays a styled checkout page with Approve, Decline, and Cancel buttons |
| POST | `/paypal-fake/checkout/{orderId}/approve` | Simulates approval and posts a webhook to your app |
| POST | `/paypal-fake/checkout/{orderId}/decline` | Marks the order as declined |
| GET | `/paypal-fake/checkout/{orderId}/cancel` | Redirects to the order's cancel URL |

### PayPalFakeServiceProvider

When `PAYPAL_USE_FAKE` is `true`, it binds `PayPalFake` as a singleton in place of `PayPal`, registers the checkout routes, and loads the Blade views.

## Checkout Flow

```
Your test creates an order via PayPalFakeApi::createOrder()
        ↓
Order is stored in file cache (300s TTL)
        ↓
Test or browser approves the order
        ↓
Webhook POSTed to /paypal/webhook/{providerId}
        ↓
Test captures payment via PayPalFakeApi::captureOrder()
        ↓
Assertions via PayPalFake::getCalls()
```

## Usage

### Automated Tests

```php
use Puntodev\PaymentsFake\PayPalFake;
use Puntodev\Payments\PayPal;
use Puntodev\Payments\OrderBuilder;

// Get the fake client
$client = app(PayPal::class)->defaultClient();

// Create an order
$order = (new OrderBuilder())
    ->currency('USD')
    ->amount(25.00)
    ->make();

$created = $client->createOrder($order);

// Capture the payment
$captured = $client->captureOrder($created['id']);

// Assert calls were made
$calls = PayPalFake::getCalls('createOrder');
$this->assertCount(1, $calls);

// Clean up
PayPalFake::reset();
```

### Manual Browser Testing

Start your Laravel app with `PAYPAL_USE_FAKE=true` and navigate to `/paypal-fake/checkout/{orderId}` after creating an order. The checkout page lets you click Approve, Decline, or Cancel to test the full flow, including webhooks hitting your application.

### Test Helpers

| Method | Description |
|--------|-------------|
| `PayPalFake::storeOrder($id, $order)` | Store an order in the fake cache |
| `PayPalFake::getStoredOrder($id)` | Retrieve a stored order |
| `PayPalFake::markOrderAsDeclined($id)` | Mark an order as declined |
| `PayPalFake::isOrderDeclined($id)` | Check if an order is declined |
| `PayPalFake::recordCall($method, $args)` | Record a method call |
| `PayPalFake::getCalls($method)` | Get recorded calls, optionally filtered by method |
| `PayPalFake::reset()` | Clear all stored state and recorded calls |

## Requirements

- PHP >= 8.4
- Laravel 12+
- `puntodev/paypal` ^4.1.3
