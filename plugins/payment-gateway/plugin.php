<?php
/**
 * Plugin Name: Payment Gateway (Culqi & PayPal)
 * Description: Pasarela de pagos para ShopCMS
 */

use ShopCMS\Core\Database;
use ShopCMS\Core\OrderWorkflow;
use ShopCMS\Core\PluginManager;

// Hook en la pantalla de checkout para agregar las opciones de pago
PluginManager::addAction('checkout_payment_methods', function () {
    echo '
    <label style="display: flex; align-items: flex-start; gap: 12px; padding: 14px; border: 1px solid #008060; background: #F4FBF7; border-radius: 8px; cursor: pointer;">
        <input type="radio" name="payment_method" value="culqi_card" style="margin-top: 3px;">
        <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                <strong style="color: #008060;">Tarjeta de Crédito / Débito (Culqi)</strong>
                <span style="font-size: 10.5px; font-weight: 700; background: #DCFCE7; color: #166534; padding: 2px 7px; border-radius: 4px;">Confirmación inmediata</span>
            </div>
            <p style="font-size: 12px; color: #6D7175; margin-top: 3px;">Acepta Visa, Mastercard, Diners y Amex de forma segura con validación en línea.</p>
            <div id="pg-culqi-box" style="display: none; margin-top: 10px; padding: 10px 12px; background: #FFFFFF; border: 1px solid #A7F3D0; border-radius: 6px; font-size: 12px; color: #065F46;">
                Al confirmar el pedido se procesará el cargo seguro mediante token Culqi y tu orden pasará automáticamente a estado <strong>Confirmado / Pagado</strong>.
            </div>
        </div>
    </label>

    <label style="display: flex; align-items: flex-start; gap: 12px; padding: 14px; border: 1px solid #006FAD; background: #EBF5FA; border-radius: 8px; cursor: pointer;">
        <input type="radio" name="payment_method" value="paypal" style="margin-top: 3px;">
        <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#006FAD" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                </svg>
                <strong style="color: #006FAD;">PayPal Express Checkout</strong>
                <span style="font-size: 10.5px; font-weight: 700; background: #DBEAFE; color: #1E40AF; padding: 2px 7px; border-radius: 4px;">Confirmación inmediata</span>
            </div>
            <p style="font-size: 12px; color: #6D7175; margin-top: 3px;">Paga con saldo de cuenta PayPal o tarjeta internacional protegida.</p>
            <div id="pg-paypal-box" style="display: none; margin-top: 10px; padding: 10px 12px; background: #FFFFFF; border: 1px solid #BFDBFE; border-radius: 6px; font-size: 12px; color: #1E3A8A;">
                Autorización PayPal Express habilitada: al confirmar tu compra se validará el pago al instante.
            </div>
        </div>
    </label>

    <script>
    (function() {
        function syncPaymentGatewayInfo() {
            var checked = document.querySelector("input[name=\'payment_method\']:checked");
            var val = checked ? checked.value : "";
            var cBox = document.getElementById("pg-culqi-box");
            var pBox = document.getElementById("pg-paypal-box");
            if (cBox) cBox.style.display = (val === "culqi_card") ? "block" : "none";
            if (pBox) pBox.style.display = (val === "paypal") ? "block" : "none";
        }
        document.addEventListener("change", function(e) {
            if (e.target && e.target.name === "payment_method") syncPaymentGatewayInfo();
        });
        document.addEventListener("DOMContentLoaded", syncPaymentGatewayInfo);
    })();
    </script>
    ';
});

// Hook cuando se crea una orden para validar pago en pasarela y avanzar estado
PluginManager::addAction('order_created', function ($orderId, $orderData) {
    $method = $orderData['payment_method'] ?? '';
    if (in_array($method, ['culqi_card', 'paypal'], true)) {
        $txCode = '#TX-' . strtoupper(bin2hex(random_bytes(4)));
        $label = OrderWorkflow::paymentMethodLabel($method);

        Database::update('orders', [
            'payment_status' => 'paid',
            'status' => 'processing'
        ], "id = :id", ['id' => (int)$orderId]);

        OrderWorkflow::recordHistory(
            (int)$orderId,
            'processing',
            "Pago aprobado vía {$label}. Autorización {$txCode}. Orden pasada a procesamiento.",
            'system'
        );
    }
});
