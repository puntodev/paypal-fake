<?php

namespace Puntodev\PaymentsFake;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Puntodev\Payments\PayPal;

class PayPalFakeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/paypal-fake.php', 'paypal-fake');

        if (config('paypal-fake.enabled')) {
            $this->app->singleton(PayPal::class, function () {
                return new PayPalFake();
            });
            $this->app->alias(PayPal::class, 'paypal');
        }
    }

    public function boot(): void
    {
        if (config('paypal-fake.enabled')) {
            $this->loadViewsFrom(__DIR__ . '/../resources/views', 'paypal-fake');

            Route::middleware('web')
                ->withoutMiddleware(VerifyCsrfToken::class)
                ->group(function () {
                    Route::get('/paypal-fake/checkout/{orderId}', [FakeCheckoutController::class, 'show']);
                    Route::post('/paypal-fake/checkout/{orderId}/approve', [FakeCheckoutController::class, 'approve']);
                    Route::post('/paypal-fake/checkout/{orderId}/decline', [FakeCheckoutController::class, 'decline']);
                    Route::get('/paypal-fake/checkout/{orderId}/cancel', [FakeCheckoutController::class, 'cancel']);
                });
        }
    }
}
