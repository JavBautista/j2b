<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo {{ $recibo_numero }}</title>
    <style>
        @page {
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            background: #fff;
        }

        /* Header */
        .header {
            background: #1a1a2e;
            color: #fff;
            padding: 30px 40px;
            border-bottom: 4px solid #00f5a0;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
        }

        .header-right {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
            text-align: right;
        }

        .logo-area {
            display: table;
        }

        .logo-img {
            display: table-cell;
            vertical-align: middle;
            padding-right: 12px;
        }

        .logo-img img {
            height: 45px;
        }

        .logo-text {
            display: table-cell;
            vertical-align: middle;
        }

        .brand-name {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
            letter-spacing: -0.5px;
        }

        .brand-tagline {
            font-size: 10px;
            color: #00f5a0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .receipt-badge {
            background: #00f5a0;
            border: 2px solid #00f5a0;
            border-radius: 8px;
            padding: 12px 20px;
            display: inline-block;
        }

        .receipt-label {
            font-size: 10px;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .receipt-number {
            font-size: 18px;
            font-weight: bold;
            color: #1a1a2e;
            margin-top: 2px;
        }

        /* Main Content */
        .main {
            padding: 30px 40px;
        }

        /* Status Bar */
        .status-bar {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px 20px;
            margin-bottom: 25px;
        }

        .status-bar-content {
            display: table;
            width: 100%;
        }

        .status-bar-left {
            display: table-cell;
            vertical-align: middle;
        }

        .status-bar-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }

        .status-text {
            color: #155724;
            font-weight: bold;
            font-size: 14px;
        }

        .status-date {
            color: #155724;
            font-size: 12px;
        }

        /* Info Cards */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }

        .info-card {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .info-card:last-child {
            padding-right: 0;
            padding-left: 15px;
        }

        .info-card-inner {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 18px;
            border: 1px solid #e9ecef;
        }

        .info-card-title {
            font-size: 10px;
            font-weight: bold;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #00f5a0;
            display: inline-block;
        }

        .info-card-name {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 4px;
        }

        .info-card-detail {
            font-size: 12px;
            color: #666;
        }

        /* Concept Box */
        .concept-box {
            background: #1a1a2e;
            padding: 20px;
            margin-bottom: 25px;
            color: #fff;
        }

        .concept-header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .concept-title {
            display: table-cell;
            vertical-align: middle;
        }

        .concept-badge {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }

        .concept-name {
            font-size: 16px;
            font-weight: bold;
            color: #fff;
        }

        .concept-plan {
            font-size: 12px;
            color: #fff;
            margin-top: 4px;
            opacity: 0.85;
        }

        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-green {
            background: #00f5a0;
            color: #1a1a2e;
        }

        .badge-blue {
            background: #00d9f5;
            color: #1a1a2e;
        }

        .concept-details {
            display: table;
            width: 100%;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 15px;
        }

        .concept-detail-item {
            display: table-cell;
            width: 50%;
        }

        .concept-detail-label {
            font-size: 10px;
            color: #00f5a0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
        }

        .concept-detail-value {
            font-size: 14px;
            color: #fff;
            margin-top: 4px;
        }

        /* Totals */
        .totals-box {
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 25px;
        }

        .totals-row {
            display: table;
            width: 100%;
            padding: 12px 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .totals-row:last-child {
            border-bottom: none;
        }

        .totals-label {
            display: table-cell;
            width: 60%;
            font-size: 13px;
            color: #666;
        }

        .totals-value {
            display: table-cell;
            width: 40%;
            text-align: right;
            font-size: 13px;
            color: #333;
        }

        .totals-row.total {
            background: #1a1a2e;
        }

        .totals-row.total .totals-label {
            color: #fff;
            font-weight: bold;
            font-size: 14px;
        }

        .totals-row.total .totals-value {
            color: #00f5a0;
            font-weight: bold;
            font-size: 22px;
        }

        /* Payment Method */
        .payment-method {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }

        .payment-method-item {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 0 10px;
        }

        .payment-method-inner {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            border: 1px solid #e9ecef;
        }

        .payment-method-label {
            font-size: 10px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .payment-method-value {
            font-size: 13px;
            font-weight: bold;
            color: #1a1a2e;
        }

        /* Notes */
        .notes {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 12px 16px;
            margin-bottom: 25px;
            border-radius: 0 8px 8px 0;
            font-size: 12px;
            color: #856404;
        }

        /* Footer */
        .footer {
            background: #f8f9fa;
            padding: 20px 40px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .footer-brand {
            font-size: 14px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 4px;
        }

        .footer-contact {
            font-size: 11px;
            color: #666;
        }

        .footer-thanks {
            margin-top: 10px;
            font-size: 12px;
            color: #00f5a0;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <div class="logo-area">
                    <div class="logo-img">
                        <img src="{{ public_path('img/j2b_60px.png') }}" alt="J2Biznes">
                    </div>
                    <div class="logo-text">
                        <div class="brand-name">J2Biznes</div>
                        <div class="brand-tagline">Sistema de Gestion</div>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="receipt-badge">
                    <div class="receipt-label">Recibo de Pago</div>
                    <div class="receipt-number">{{ $recibo_numero }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">

        <!-- Status Bar -->
        <div class="status-bar">
            <div class="status-bar-content">
                <div class="status-bar-left">
                    <div class="status-text">✓ Pago Confirmado</div>
                </div>
                <div class="status-bar-right">
                    <div class="status-date">Fecha: {{ $fecha_pago }}</div>
                </div>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-inner">
                    <div class="info-card-title">Cliente</div>
                    <div class="info-card-name">{{ $cliente_nombre }}</div>
                    @if($cliente_email)
                    <div class="info-card-detail">{{ $cliente_email }}</div>
                    @endif
                </div>
            </div>
            <div class="info-card">
                <div class="info-card-inner">
                    <div class="info-card-title">Emitido por</div>
                    <div class="info-card-name">J2Biznes</div>
                    <div class="info-card-detail">j2biznes.com</div>
                </div>
            </div>
        </div>

        <!-- Concept Box -->
        <div class="concept-box">
            <div class="concept-header">
                <div class="concept-title">
                    <div class="concept-name">{{ $concepto }}</div>
                    <div class="concept-plan">Plan: {{ $plan_nombre }}</div>
                </div>
                <div class="concept-badge">
                    <span class="badge {{ $ciclo == 'yearly' ? 'badge-blue' : 'badge-green' }}">
                        {{ $ciclo == 'yearly' ? 'Anual' : 'Mensual' }}
                    </span>
                </div>
            </div>
            <div class="concept-details">
                <div class="concept-detail-item">
                    <div class="concept-detail-label">Periodo de Vigencia</div>
                    <div class="concept-detail-value">{{ $periodo }}</div>
                </div>
                <div class="concept-detail-item" style="text-align: right;">
                    <div class="concept-detail-label">Metodo de Pago</div>
                    <div class="concept-detail-value">{{ $metodo_pago }}</div>
                </div>
            </div>
        </div>

        <!-- Totals -->
        <div class="totals-box">
            <div class="totals-row">
                <div class="totals-label">Subtotal</div>
                <div class="totals-value">{{ $moneda }} ${{ number_format($subtotal, 2) }}</div>
            </div>
            @if($iva > 0)
            <div class="totals-row">
                <div class="totals-label">IVA (16%)</div>
                <div class="totals-value">{{ $moneda }} ${{ number_format($iva, 2) }}</div>
            </div>
            @endif
            <div class="totals-row total">
                <div class="totals-label">Total Pagado</div>
                <div class="totals-value">{{ $moneda }} ${{ number_format($total, 2) }}</div>
            </div>
        </div>

        @if($referencia && !str_starts_with($referencia, 'MANUAL-'))
        <!-- Reference -->
        <div class="payment-method">
            <div class="payment-method-item" style="width: 100%;">
                <div class="payment-method-inner">
                    <div class="payment-method-label">Referencia de Pago</div>
                    <div class="payment-method-value">{{ $referencia }}</div>
                </div>
            </div>
        </div>
        @endif

        @if($notas && !str_contains($notas, 'Pago registrado por'))
        <div class="notes">
            <strong>Nota:</strong> {{ $notas }}
        </div>
        @endif

    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-brand">J2Biznes</div>
        <div class="footer-contact">j2biznes.com • contacto@j2biznes.com</div>
        <div class="footer-thanks">¡Gracias por tu confianza!</div>
    </div>

</body>
</html>
