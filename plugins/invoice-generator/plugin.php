<?php
/**
 * Plugin Name: Invoice Generator
 * Description: Genera e imprime comprobantes fiscales / facturas comerciales PDF por pedido
 */

use ShopCMS\Core\Database;
use ShopCMS\Core\OrderWorkflow;
use ShopCMS\Core\PluginManager;
use ShopCMS\Core\Settings;

if (!function_exists('scms_render_invoice_button')) {
    function scms_render_invoice_button(array $order, ?array $items = null, string $variant = 'store'): void {
        if ($items === null && !empty($order['id'])) {
            $items = Database::fetchAll("SELECT * FROM " . Database::table('order_items') . " WHERE order_id = ?", [(int)$order['id']]);
        }
        $items = $items ?? [];

        $storeName = Settings::get('store_name', 'ShopCMS Store');
        $storeEmail = Settings::get('store_email', '');
        $storePhone = Settings::get('store_phone', '');
        $storeAddress = Settings::get('store_address', '');
        $sym = Settings::get('store_currency_symbol', '$');

        $payload = [
            'store_name' => $storeName,
            'store_email' => $storeEmail,
            'store_phone' => $storePhone,
            'store_address' => $storeAddress,
            'currency' => $sym,
            'order_number' => $order['order_number'] ?? '',
            'customer_name' => $order['customer_name'] ?? '',
            'customer_email' => $order['customer_email'] ?? '',
            'shipping_address' => $order['shipping_address'] ?? '',
            'subtotal' => (float)($order['subtotal'] ?? 0),
            'discount' => (float)($order['discount_amount'] ?? 0),
            'shipping' => (float)($order['shipping_amount'] ?? 0),
            'tax' => (float)($order['tax_amount'] ?? 0),
            'total' => (float)($order['total'] ?? 0),
            'payment_method' => OrderWorkflow::paymentMethodLabel((string)($order['payment_method'] ?? 'cod')),
            'payment_status' => OrderWorkflow::getPaymentMeta((string)($order['payment_status'] ?? 'unpaid'))['label'],
            'date' => date('d/m/Y H:i', strtotime($order['created_at'] ?? 'now')),
            'items' => array_map(function ($it) {
                return [
                    'name' => (string)($it['product_name'] ?? 'Artículo') . (!empty($it['variant_title']) ? ' (' . $it['variant_title'] . ')' : ''),
                    'qty' => (int)($it['quantity'] ?? 1),
                    'price' => (float)($it['price'] ?? 0),
                    'total' => (float)($it['total'] ?? 0),
                ];
            }, $items),
        ];

        $orderJson = htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

        if ($variant === 'admin') {
            $cls = 'btn btn-secondary btn-sm';
            $style = 'display: inline-flex; align-items: center; gap: 6px;';
            $label = 'Factura PDF';
        } elseif ($variant === 'compact') {
            $cls = 'btn-store btn-store-secondary';
            $style = 'font-size: 12px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px;';
            $label = 'Descargar Factura PDF';
        } else {
            $cls = 'btn-store btn-store-secondary';
            $style = 'display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 20px; font-size: 14px; font-weight: 600; width: 100%;';
            $label = 'Descargar / Imprimir Factura Electrónica (PDF)';
        }

        echo '<button type="button" onclick="scmsPrintInvoice(' . $orderJson . ')" class="' . $cls . '" style="' . $style . '">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            <span>' . $label . '</span>
        </button>';

        static $scriptPrinted = false;
        if (!$scriptPrinted) {
            $scriptPrinted = true;
            echo '
            <script>
            function scmsPrintInvoice(data) {
                function esc(v) {
                    return String(v === undefined || v === null ? "" : v).replace(/[&<>"\x27]/g, function(ch) {
                        return {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","\x27":"&#39;"}[ch];
                    });
                }
                var sym = esc(data.currency || "$");
                var itemsHtml = "";
                if (Array.isArray(data.items) && data.items.length) {
                    itemsHtml = data.items.map(function(it) {
                        return "<tr>"
                            + "<td>" + esc(it.name) + "</td>"
                            + "<td style=\'text-align:center;\'>" + (parseInt(it.qty, 10) || 1) + "</td>"
                            + "<td style=\'text-align:right;\'>" + sym + Number(it.price || 0).toFixed(2) + "</td>"
                            + "<td style=\'text-align:right;font-weight:600;\'>" + sym + Number(it.total || 0).toFixed(2) + "</td>"
                            + "</tr>";
                    }).join("");
                } else {
                    itemsHtml = "<tr><td colspan=\'3\'>Pedido " + esc(data.order_number) + "</td><td style=\'text-align:right;\'>" + sym + Number(data.subtotal || 0).toFixed(2) + "</td></tr>";
                }

                var win = window.open("", "_blank", "width=820,height=740");
                if (!win) return;
                win.document.write(`
                    <!DOCTYPE html>
                    <html lang="es">
                    <head>
                        <meta charset="UTF-8">
                        <title>Factura Comercial - ${esc(data.order_number)}</title>
                        <style>
                            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; padding: 36px; color: #1F2937; line-height: 1.5; }
                            .header { display: flex; justify-content: space-between; border-bottom: 2px solid #E5E7EB; padding-bottom: 18px; margin-bottom: 22px; gap: 20px; }
                            .title { font-size: 22px; font-weight: 800; color: #111827; }
                            .meta { text-align: right; font-size: 13px; color: #4B5563; }
                            .box { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 14px 16px; margin-bottom: 22px; font-size: 13.5px; }
                            table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
                            th, td { padding: 10px; border-bottom: 1px solid #E5E7EB; font-size: 13.5px; text-align: left; }
                            th { background: #F3F4F6; font-weight: 700; }
                            .total-box { margin-left: auto; width: 300px; font-size: 13.5px; }
                            .total-box div { display: flex; justify-content: space-between; padding: 5px 0; }
                            .total-grand { font-size: 17px; font-weight: 800; color: #008060; border-top: 2px solid #E5E7EB; margin-top: 6px; padding-top: 8px !important; }
                            .btn-print { background: #008060; color: #FFF; border: none; padding: 10px 18px; border-radius: 6px; font-weight: 700; cursor: pointer; margin-bottom: 20px; }
                            @media print { .btn-print { display: none; } body { padding: 0; } }
                        </style>
                    </head>
                    <body>
                        <button class="btn-print" onclick="window.print()">Imprimir / Guardar como PDF</button>
                        <div class="header">
                            <div>
                                <div class="title">${esc(data.store_name)}</div>
                                <div style="font-size: 12.5px; color: #6B7280; margin-top: 3px;">Comprobante Comercial Electrónico</div>
                                ${data.store_address ? `<div style="font-size:12px;color:#6B7280;">${esc(data.store_address)}</div>` : ``}
                                ${data.store_phone || data.store_email ? `<div style="font-size:12px;color:#6B7280;">${esc(data.store_phone)} ${esc(data.store_email)}</div>` : ``}
                            </div>
                            <div class="meta">
                                <div><strong>Pedido N°:</strong> ${esc(data.order_number)}</div>
                                <div><strong>Fecha:</strong> ${esc(data.date)}</div>
                                <div><strong>Pago:</strong> ${esc(data.payment_method)} (${esc(data.payment_status)})</div>
                            </div>
                        </div>
                        <div class="box">
                            <div style="font-weight: 700; margin-bottom: 4px;">Facturado a:</div>
                            <div><strong>Cliente:</strong> ${esc(data.customer_name)} (${esc(data.customer_email)})</div>
                            <div><strong>Dirección de entrega:</strong> ${esc(data.shipping_address)}</div>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th style="text-align: center;">Cant.</th>
                                    <th style="text-align: right;">Precio Unit.</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>${itemsHtml}</tbody>
                        </table>
                        <div class="total-box">
                            <div><span>Subtotal:</span> <span>${sym}${Number(data.subtotal || 0).toFixed(2)}</span></div>
                            ${Number(data.discount || 0) > 0 ? `<div style="color:#008060;"><span>Descuento:</span> <span>-${sym}${Number(data.discount || 0).toFixed(2)}</span></div>` : ``}
                            <div><span>Envío:</span> <span>${Number(data.shipping || 0) > 0 ? sym + Number(data.shipping || 0).toFixed(2) : "Gratis"}</span></div>
                            ${Number(data.tax || 0) > 0 ? `<div><span>Impuestos:</span> <span>${sym}${Number(data.tax || 0).toFixed(2)}</span></div>` : ``}
                            <div class="total-grand"><span>Total Pagado:</span> <span>${sym}${Number(data.total || 0).toFixed(2)}</span></div>
                        </div>
                        <div style="margin-top: 40px; text-align: center; font-size: 11.5px; color: #9CA3AF; border-top: 1px dashed #E5E7EB; padding-top: 14px;">
                            Comprobante generado por ${esc(data.store_name)}.
                        </div>
                    </body>
                    </html>
                `);
                win.document.close();
            }
            </script>';
        }
    }
}

PluginManager::addAction('order_success_actions', function ($order) {
    echo '<div style="margin: 12px 0;">';
    scms_render_invoice_button($order, null, 'store');
    echo '</div>';
});

PluginManager::addAction('account_order_actions', function ($order) {
    scms_render_invoice_button($order, $order['items'] ?? null, 'compact');
});

PluginManager::addAction('admin_order_actions', function ($order, $items = null) {
    scms_render_invoice_button($order, $items, 'admin');
});
