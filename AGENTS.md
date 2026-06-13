# AGENTS.md

Guidance for AI agents working in this repository.

## What this project is

`puntodev/paypal-fake` is a **Laravel package** (Composer library) that provides a
**fake/in-memory implementation of the real PayPal client** it fakes. It
lets Laravel apps exercise the full Checkout flow — orders, capture and IPN/webhook
notifications — **without hitting the real PayPal API**. It is not an application: it is
published to Packagist and pulled in as a `--dev` dependency from Laravel apps and test
suites.

- **Namespace:** `Puntodev\PaymentsFake\` (PSR-4, mapped to `src/`)
- **PHP:** `>=8.4 <9.0`
- **Main dependencies:** `illuminate/support` (`^13.0`, Laravel 13+) and the real PayPal
  client package this one fakes (its public interfaces live under the `Puntodev\Payments\`
  namespace).
- **License:** MIT

## Architecture

The package mirrors the two public interfaces of the real PayPal client (`PayPal` and
`PayPalApi`) with fake implementations, plus an HTTP controller that renders a stand-in
Checkout UI.

| File | Role |
|------|------|
| `src/PayPalFake.php` | Implements `Puntodev\Payments\PayPal` (the factory). `defaultClient()`/`withCredentials()` return a `PayPalFakeApi`. Also the global **test state store**: static `storeOrder`/`storeCapturedOrder` (+ getters) backed by the **file cache** (300s TTL), `markOrderAsDeclined`/`isOrderDeclined`, and a static `$calls` log driven by `recordCall`/`getCalls`/`reset`. |
| `src/PayPalFakeApi.php` | Implements `Puntodev\Payments\PayPalApi`. `createOrder` mints a random 17-char order id, builds a PayPal-shaped response with a `payer-action` link pointing at the fake checkout route, stores it and records the call. `findOrderById` reads from the store; `captureOrder` simulates capture and throws a `RequestException` when the order was marked as declined; `verifyIpn` always returns `'VERIFIED'`. |
| `src/FakeCheckoutController.php` | Renders and drives the fake Checkout UI: `show`, `approve`, `decline`, `cancel`. Approving/declining posts a webhook (notification) to your app and redirects to the order's `experience_context` return/cancel URLs with PayPal-style query params. |
| `src/PayPalFakeServiceProvider.php` | When `paypal-fake.enabled` is true, binds `PayPal::class` as a singleton to `PayPalFake` (aliased `paypal`), loads the Blade views and registers the checkout routes (under the `web` group, **without** `VerifyCsrfToken`). |
| `resources/views/checkout.blade.php` | The styled fake checkout page with Approve / Decline / Cancel actions. |
| `config/paypal-fake.php` | Single `enabled` flag, read from `PAYPAL_USE_FAKE` (default `false`). |

### Important behavior details

- **Activation:** everything is gated by `config('paypal-fake.enabled')`, i.e. the
  `PAYPAL_USE_FAKE` env var. When off, the package registers nothing and the real
  PayPal client binding stays in place — so it is safe to keep installed.
- **State lives in the file cache, not memory.** Orders/captures are persisted via
  `Cache::store('file')` with a 300s TTL so they survive the redirect → webhook →
  return-url round trip across separate HTTP requests. The `$calls` log is static
  (per-process) and is what tests assert against; call `PayPalFake::reset()` between
  tests.
- **Routes bypass CSRF** (`withoutMiddleware(VerifyCsrfToken::class)`) because the fake
  checkout posts back from a plain HTML form.
- **Declined captures throw `RequestException`**, matching how the real client surfaces
  PayPal errors.

## Laravel auto-registration (package discovery)

Defined in `composer.json` → `extra.laravel`:
- Provider: `Puntodev\PaymentsFake\PayPalFakeServiceProvider`

There is no facade: the package overrides the `PayPal` container binding, so app code
keeps resolving `Puntodev\Payments\PayPal` exactly as in production.

## How to run and test

```bash
composer install
composer test            # vendor/bin/phpunit
composer test-coverage   # generates an HTML coverage report under ./coverage
composer lint            # vendor/bin/pint --test (style check, no changes)
composer format          # vendor/bin/pint (fix style)
```

- Tests use **Orchestra Testbench** (`tests/TestCase.php` extends
  `Orchestra\Testbench\TestCase` and registers the service provider).
- `phpunit.xml.dist` forces `SANDBOX_GATEWAYS=true` and `PAYPAL_USE_FAKE=true`.
- ✅ Unlike the real PayPal client, these tests are **fully isolated** — they never reach
  the network, so no credentials are needed in CI.
- CI: `.github/workflows/php.yml` runs on PHP 8.4 on every push/PR to `main`, including a
  Pint code-style check.

## Conventions

- Code style is enforced by **Laravel Pint** (`pint.json`, `laravel` preset). Run
  `composer format` before committing; `composer lint` is what CI runs.
- The fakes must stay drop-in compatible with the real PayPal client interfaces
  (`PayPal`, `PayPalApi`): when that package adds an interface method, mirror it here and
  cover it with a test.
- API methods return `array`/`?array` (matching the real client); keep that convention.

## Workflow rules (inherited from the user's global config)

- **Do not commit on `main`.** Always work on a branch or worktree.
- PRs are always opened as **Draft**.
- Run `git pull` before starting to make sure you have the latest version.
