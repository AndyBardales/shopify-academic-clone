<?php
use ShopCMS\Core\OrderWorkflow;
use ShopCMS\Core\Settings;
use ShopCMS\Core\ThemeCustomizer;
use ShopCMS\Core\ThemeManager;

$baseUrl = defined('APP_URL') ? APP_URL : '';
$storeName = $storeName ?? Settings::get('store_name', 'ShopCMS Store');
$storeEmail = $storeEmail ?? Settings::get('store_email', '');
$storePhone = $storePhone ?? Settings::get('store_phone', '');
$storeAddress = $storeAddress ?? Settings::get('store_address', '');
$currencySymbol = $currencySymbol ?? Settings::get('store_currency_symbol', '$');
$backUrl = $backUrl ?? ($baseUrl . '/account');

$themeCfg = ThemeCustomizer::getConfig(ThemeManager::getActiveTheme());
$logoUrl = trim((string)($themeCfg['global']['logo_url'] ?? ''));
$resolvedLogo = $logoUrl !== ''
    ? ((preg_match('#^https?://#i', $logoUrl) || str_starts_with($logoUrl, '/')) ? $logoUrl : $baseUrl . '/' . ltrim($logoUrl, '/'))
    : '';

$stMeta = OrderWorkflow::getStatusMeta((string)($order['status'] ?? 'pending'));
$payMeta = OrderWorkflow::getPaymentMeta((string)($order['payment_status'] ?? 'unpaid'));
$payMethodLabel = OrderWorkflow::paymentMethodLabel((string)($order['payment_method'] ?? 'cod'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante <?= htmlspecialchars((string)$order['order_number']) ?> — <?= htmlspecialchars($storeName) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #F3F4F6;
            color: #111827;
            padding: 24px 16px 60px;
            line-height: 1.5;
        }
        .receipt-toolbar {
            max-width: 820px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .tb-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid #D1D5DB;
            background: #FFFFFF;
            color: #111827;
        }
        .tb-btn-primary {
            background: #008060;
            border-color: #008060;
            color: #FFFFFF;
        }
        .tb-btn-primary:hover { background: #006E52; }
        .receipt-sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 36px 40px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.06);
        }
        .receipt-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            padding-bottom: 22px;
            border-bottom: 2px solid #111827;
            flex-wrap: wrap;
        }
        .store-brand {
            font-size: 20px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 4px;
        }
        .store-meta {
            font-size: 12.5px;
            color: #4B5563;
        }
        .doc-box {
            text-align: right;
            border: 1.5px solid #111827;
            border-radius: 8px;
            padding: 12px 18px;
            min-width: 230px;
        }
        .doc-type {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
            color: #4B5563;
        }
        .doc-num {
            font-size: 20px;
            font-weight: 800;
            color: #111827;
            margin: 2px 0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin: 22px 0;
        }
        .info-box {
            background: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 14px 16px;
            font-size: 13px;
        }
        .info-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 700;
            color: #6B7280;
            margin-bottom: 6px;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 22px;
            font-size: 13.5px;
        }
        table.items-table th {
            text-align: left;
            padding: 10px 12px;
            background: #111827;
            color: #FFFFFF;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        table.items-table td {
            padding: 11px 12px;
            border-bottom: 1px solid #E5E7EB;
            vertical-align: top;
        }
        .totals-wrap {
            display: flex;
            justify-content: flex-end;
        }
        .totals-box {
            width: 100%;
            max-width: 320px;
            font-size: 13.5px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .total-final {
            border-top: 2px solid #111827;
            margin-top: 8px;
            padding-top: 10px;
            font-size: 17px;
            font-weight: 800;
        }
        .receipt-foot {
            margin-top: 28px;
            padding-top: 16px;
            border-top: 1px dashed #D1D5DB;
            font-size: 12px;
            color: #6B7280;
            text-align: center;
        }
        @media (max-width: 640px) {
            .receipt-sheet { padding: 22px 18px; }
            .info-grid { grid-template-columns: 1fr; }
            .doc-box { text-align: left; width: 100%; }
        }
        @media print {
            body { background: #FFFFFF; padding: 0; }
            .receipt-toolbar { display: none !important; }
            .receipt-sheet { border: none; box-shadow: none; padding: 10px 0; max-width: 100%; }
            table.items-table th { background: #111827 !important; color: #FFFFFF !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="receipt-toolbar">
    <a href="<?= htmlspecialchars($backUrl) ?>" class="tb-btn">Volver</a>
    <button type="button" onclick="window.print()" class="tb-btn tb-btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        <span>Imprimir / Guardar PDF</span>
    </button>
</div>

<div class="receipt-sheet">
    <div class="receipt-head">
        <div>
            <?php if ($resolvedLogo !== ''): ?>
                <img src="<?= htmlspecialchars($resolvedLogo) ?>" alt="<?= htmlspecialchars($storeName) ?>" style="max-height: 44px; margin-bottom: 8px; object-fit: contain;">
            <?php endif; ?>
            <div class="store-brand"><?= htmlspecialchars($storeName) ?></div>
            <div class="store-meta">
                <?php if ($storeAddress !== ''): ?><div><?= htmlspecialchars($storeAddress) ?></div><?php endif; ?>
                <?php if ($storeEmail !== '' || $storePhone !== ''): ?>
                    <div>
                        <?= $storeEmail !== '' ? htmlspecialchars($storeEmail) : '' ?>
                        <?= ($storeEmail !== '' && $storePhone !== '') ? ' · ' : '' ?>
                        <?= $storePhone !== '' ? 'Tel: ' . htmlspecialchars($storePhone) : '' ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="doc-box">
            <div class="doc-type">Comprobante de Pedido</div>
            <div class="doc-num"><?= htmlspecialchars((string)$order['order_number']) ?></div>
            <div style="font-size: 12px; color: #4B5563;">Fecha: <?= date('d/m/Y H:i', strtotime((string)$order['created_at'])) ?></div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="info-title">Datos del Cliente y Entrega</div>
            <div><strong><?= htmlspecialchars((string)$order['customer_name']) ?></strong></div>
            <div><?= htmlspecialchars((string)$order['customer_email']) ?></div>
            <?php if (!empty($order['customer_phone'])): ?>
                <div>Tel: <?= htmlspecialchars((string)$order['customer_phone']) ?></div>
            <?php endif; ?>
            <div style="margin-top: 6px; color: #374151;"><?= nl2br(htmlspecialchars((string)$order['shipping_address'])) ?></div>
        </div>

        <div class="info-box">
            <div class="info-title">Pago y Logística</div>
            <div><strong>Método de pago:</strong> <?= htmlspecialchars($payMethodLabel) ?></div>
            <div><strong>Estado del pago:</strong> <?= htmlspecialchars((string)$payMeta['label']) ?></div>
            <div><strong>Estado del pedido:</strong> <?= htmlspecialchars((string)$stMeta['label']) ?></div>
            <?php if (!empty($order['payment_reference'])): ?>
                <div><strong>Nro. Operación:</strong> <?= htmlspecialchars((string)$order['payment_reference']) ?></div>
            <?php endif; ?>
            <?php if (!empty($order['carrier']) || !empty($order['tracking_number'])): ?>
                <div style="margin-top: 6px;">
                    <strong>Envío:</strong> <?= htmlspecialchars((string)($order['carrier'] ?: 'Courier')) ?>
                    <?= !empty($order['tracking_number']) ? ' · Guía: ' . htmlspecialchars((string)$order['tracking_number']) : '' ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción</th>
                <th style="text-align: center;">Cant.</th>
                <th style="text-align: right;">Precio Unit.</th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars((string)$item['product_name']) ?></strong>
                        <?php if (!empty($item['variant_title'])): ?>
                            <div style="font-size: 12px; color: #6B7280;"><?= htmlspecialchars((string)$item['variant_title']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($item['sku'])): ?>
                            <div style="font-size: 11.5px; color: #6B7280;">SKU: <?= htmlspecialchars((string)$item['sku']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;"><?= (int)$item['quantity'] ?></td>
                    <td style="text-align: right;"><?= htmlspecialchars($currencySymbol) ?><?= number_format((float)$item['price'], 2) ?></td>
                    <td style="text-align: right;"><strong><?= htmlspecialchars($currencySymbol) ?><?= number_format((float)$item['total'], 2) ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals-wrap">
        <div class="totals-box">
            <div class="total-row">
                <span style="color: #4B5563;">Subtotal:</span>
                <span><?= htmlspecialchars($currencySymbol) ?><?= number_format((float)$order['subtotal'], 2) ?></span>
            </div>
            <?php if ((float)($order['discount_amount'] ?? 0) > 0): ?>
                <div class="total-row" style="color: #065F46; font-weight: 600;">
                    <span>Descuento<?= !empty($order['coupon_code']) ? ' (' . htmlspecialchars((string)$order['coupon_code']) . ')' : '' ?>:</span>
                    <span>-<?= htmlspecialchars($currencySymbol) ?><?= number_format((float)$order['discount_amount'], 2) ?></span>
                </div>
            <?php endif; ?>
            <div class="total-row">
                <span style="color: #4B5563;">Costo de Envío:</span>
                <span><?= (float)($order['shipping_amount'] ?? 0) > 0 ? htmlspecialchars($currencySymbol) . number_format((float)$order['shipping_amount'], 2) : 'Gratis' ?></span>
            </div>
            <?php if ((float)($order['tax_amount'] ?? 0) > 0): ?>
                <div class="total-row">
                    <span style="color: #4B5563;">Impuesto:</span>
                    <span><?= htmlspecialchars($currencySymbol) ?><?= number_format((float)$order['tax_amount'], 2) ?></span>
                </div>
            <?php endif; ?>
            <div class="total-row total-final">
                <span>Total:</span>
                <span><?= htmlspecialchars($currencySymbol) ?><?= number_format((float)$order['total'], 2) ?></span>
            </div>
        </div>
    </div>

    <div class="receipt-foot">
        Comprobante generado electrónicamente por <?= htmlspecialchars($storeName) ?>. Gracias por tu compra.
    </div>
</div>

</body>
</html>
