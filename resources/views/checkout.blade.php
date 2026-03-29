<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayPal Checkout</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            background-color: #f5f7fa;
            color: #2c2e2f;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header {
            width: 100%;
            background-color: #f5f7fa;
            border-bottom: 3px solid #0070ba;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #003087;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
        }

        .header-logo {
            font-size: 26px;
            font-weight: 700;
            color: #003087;
            letter-spacing: -0.5px;
        }

        .header-logo span {
            color: #0070ba;
        }

        .header-amount {
            font-size: 18px;
            font-weight: 600;
            color: #0070ba;
        }

        .container {
            width: 100%;
            max-width: 500px;
            background-color: #ffffff;
            margin-top: 0;
            flex: 1;
            padding: 32px 24px;
        }

        .section-title {
            font-size: 22px;
            font-weight: 300;
            color: #2c2e2f;
            margin-bottom: 24px;
        }

        .payment-method {
            display: flex;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #eaeced;
        }

        .payment-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            flex-shrink: 0;
        }

        .payment-icon svg {
            width: 28px;
            height: 28px;
        }

        .payment-info {
            flex: 1;
        }

        .payment-info .label {
            font-size: 16px;
            font-weight: 500;
            color: #2c2e2f;
        }

        .payment-info .badge {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 10px;
            font-size: 12px;
            color: #6c7378;
            background-color: #f0f0f0;
            border-radius: 12px;
        }

        .payment-amount {
            font-size: 16px;
            font-weight: 500;
            color: #2c2e2f;
        }

        .description {
            padding: 16px 0;
            font-size: 14px;
            color: #6c7378;
            border-bottom: 1px solid #eaeced;
        }

        .actions {
            padding-top: 32px;
        }

        .btn-pay {
            width: 100%;
            padding: 16px;
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            background-color: #003087;
            border: none;
            border-radius: 28px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-pay:hover {
            background-color: #001f5b;
        }

        .btn-decline {
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            font-size: 14px;
            font-weight: 500;
            color: #ffffff;
            background-color: #c9302c;
            border: none;
            border-radius: 28px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-decline:hover {
            background-color: #a02622;
        }

        .cancel-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            font-size: 15px;
            color: #0070ba;
            text-decoration: none;
            font-weight: 500;
        }

        .cancel-link:hover {
            text-decoration: underline;
        }

        .fake-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #fef3cd;
            border-top: 1px solid #ffc107;
            padding: 8px;
            text-align: center;
            font-size: 12px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-avatar">TB</div>
        <div class="header-logo">Pay<span>Pal</span></div>
        <div class="header-amount">${{ number_format((float)$amount, 2, ',', '.') }}</div>
    </div>

    <div class="container">
        <div class="section-title">Pagar con</div>

        <div class="payment-method">
            <div class="payment-icon">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 2.23A.773.773 0 0 1 5.706 1.6h6.174c2.05 0 3.474.434 4.229 1.29.354.4.584.826.702 1.3.124.498.126 1.093.006 1.818l-.01.057v.498l.39.22a2.62 2.62 0 0 1 .79.607c.332.389.547.868.638 1.423.093.57.063 1.249-.091 2.016-.177.88-.467 1.647-.86 2.28a4.778 4.778 0 0 1-1.343 1.452 5.235 5.235 0 0 1-1.738.808c-.644.18-1.372.27-2.164.27h-.514a1.546 1.546 0 0 0-1.529 1.308l-.039.206-.652 4.134-.03.148a.093.093 0 0 1-.092.08H7.076Z" fill="#253B80"/>
                    <path d="M19.439 6.352c-.013.087-.028.176-.046.268-.593 3.05-2.623 4.104-5.215 4.104H12.86a.643.643 0 0 0-.635.544l-.674 4.272-.19 1.21a.338.338 0 0 0 .334.39h2.35c.278 0 .514-.202.558-.477l.023-.12.442-2.806.029-.155a.564.564 0 0 1 .557-.477h.351c2.27 0 4.047-.922 4.566-3.588.217-.113.366-2.024-.13-3.165Z" fill="#179BD7"/>
                    <path d="M18.517 5.98a4.647 4.647 0 0 0-.573-.127 7.266 7.266 0 0 0-1.156-.084H12.86a.56.56 0 0 0-.538.399l-.793 5.024-.023.147a.643.643 0 0 1 .635-.544h1.318c2.592 0 4.622-1.053 5.215-4.104.018-.09.033-.179.046-.268a3.013 3.013 0 0 0-.464-.197l-.023-.008-.716-.238Z" fill="#222D65"/>
                </svg>
            </div>
            <div class="payment-info">
                <div class="label">Saldo de PayPal</div>
                <div class="badge">Opción preferida</div>
            </div>
            <div class="payment-amount">${{ number_format((float)$amount, 2, ',', '.') }}</div>
        </div>

        @if($description)
            <div class="description">{{ $description }}</div>
        @endif

        <div class="actions">
            <form method="POST" action="{{ url("/paypal-fake/checkout/$orderId/approve") }}">
                <button type="submit" class="btn-pay" dusk="fake-pay">Completar compra</button>
            </form>

            <form method="POST" action="{{ url("/paypal-fake/checkout/$orderId/decline") }}">
                <button type="submit" class="btn-decline" dusk="fake-decline">Rechazar pago</button>
            </form>

            <a href="{{ url("/paypal-fake/checkout/$orderId/cancel") }}" class="cancel-link" dusk="fake-cancel">
                Cancelar y volver al {{ $brandName }}
            </a>
        </div>
    </div>

    <div class="fake-banner">Fake Checkout (testing)</div>
</body>
</html>
