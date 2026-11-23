<?php \ShopCMS\Core\ThemeManager::renderHeader(get_defined_vars()); ?>
<?php
$subtotal = $subtotal ?? 0;
$discount = $discount ?? 0;
$total = $total ?? 0;
?>

<main class="container" style="padding-top: 40px; padding-bottom: 80px;">
    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 24px;">Finalizar Compra</h1>

    <?php if (!empty($_SESSION['cart_error'])): ?>
        <div style="background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13.5px; display: flex; align-items: center; gap: 10px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#B45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span><?= htmlspecialchars($_SESSION['cart_error']) ?></span>
        </div>
        <?php unset($_SESSION['cart_error']); ?>
    <?php endif; ?>

    <form method="POST" action="<?= $baseUrl ?>/checkout/process">
        <?= \ShopCMS\Core\Security::csrfField() ?>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 36px;">
            <!-- DATOS DEL CLIENTE Y ENVÍO -->
            <div>
                <div style="background: #FFF; padding: 28px; border-radius: 12px; border: 1px solid var(--color-border); margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="font-size: 18px; font-weight: 700; margin: 0;">1. Información de Contacto y Envío</h2>
                        <?php if (!empty($customer)): ?>
                            <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #166534; background: #DCFCE7; padding: 4px 10px; border-radius: 6px; font-weight: 600;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                Sesión: <?= htmlspecialchars($customer['name']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; gap: 16px; margin-bottom: 16px;">
                        <div style="flex: 1;">
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nombre Completo *</label>
                            <input type="text" name="customer_name" required value="<?= htmlspecialchars($customer['name'] ?? '') ?>" placeholder="Juan Pérez" style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Teléfono / WhatsApp *</label>
                            <input type="tel" name="customer_phone" required value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" placeholder="+51 987 654 321" style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Correo Electrónico (Para envío de comprobante) *</label>
                        <input type="email" name="customer_email" required value="<?= htmlspecialchars($customer['email'] ?? '') ?>" placeholder="tu-correo@ejemplo.com" style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Dirección Completa de Envío (Calle, Número, Distrito, Ciudad) *</label>
                        <textarea name="shipping_address" required rows="3" placeholder="Av. Principal 123, Dpto 402, Lima..." style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px; font-family: inherit;"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- MÉTODO DE PAGO -->
                <?php
                $enablePaymentCod = $enablePaymentCod ?? true;
                $enablePaymentBank = $enablePaymentBank ?? true;
                $bankInstructions = trim((string)($bankInstructions ?? ''));
                $shippingAmount = (float)($shippingAmount ?? 0);
                $taxAmount = (float)($taxAmount ?? 0);
                $taxRate = (float)($taxRate ?? 0);
                $taxIncluded = $taxIncluded ?? true;
                $estimatedDays = $estimatedDays ?? '2 a 4 días hábiles';
                ?>
                <div style="background: #FFF; padding: 28px; border-radius: 12px; border: 1px solid var(--color-border);">
                    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;">2. Método de Pago</h2>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php if ($enablePaymentCod): ?>
                            <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid var(--color-border); border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="payment_method" value="cod" checked onchange="toggleBankInstructionsBox()">
                                <div>
                                    <strong>Pago Contra Entrega / Efectivo</strong>
                                    <p style="font-size: 12px; color: var(--color-text-light);">Paga al momento de recibir tus artículos en tu domicilio.</p>
                                </div>
                            </label>
                        <?php endif; ?>

                        <?php if ($enablePaymentBank): ?>
                            <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid var(--color-border); border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="payment_method" value="bank_transfer" <?= !$enablePaymentCod ? 'checked' : '' ?> onchange="toggleBankInstructionsBox()">
                                <div>
                                    <strong>Transferencia Bancaria / BCP / Yape / Plin</strong>
                                    <p style="font-size: 12px; color: var(--color-text-light);">Realiza el pago directo a nuestras cuentas y envía el comprobante.</p>
                                </div>
                            </label>
                            <?php if ($bankInstructions !== ''): ?>
                                <div id="bank-instructions-box" style="display: <?= !$enablePaymentCod ? 'block' : 'none' ?>; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; font-size: 12.5px; color: #374151; line-height: 1.5;">
                                    <strong style="display: block; margin-bottom: 4px; color: #111827;">Datos para tu depósito o transferencia:</strong>
                                    <?= nl2br(htmlspecialchars($bankInstructions)) ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- HOOK PARA PASARELAS DE PAGO DE PLUGINS (Culqi, PayPal, Stripe) -->
                        <?php \ShopCMS\Core\PluginManager::doAction('checkout_payment_methods'); ?>
                    </div>
                </div>
            </div>

            <!-- RESUMEN LATERAL -->
            <div>
                <div style="background: var(--color-surface); padding: 24px; border-radius: 12px; border: 1px solid var(--color-border); position: sticky; top: 90px;">
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 16px;">Resumen del Pedido</h3>

                    <div style="max-height: 240px; overflow-y: auto; margin-bottom: 16px; padding-right: 8px;">
                        <?php foreach ($cart as $item): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 13px;">
                                <div>
                                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                                    <div style="color: var(--color-text-light);">Cant: <?= $item['quantity'] ?> &times; <?= $currency['symbol'] ?><?= number_format($item['price'], 2) ?></div>
                                </div>
                                <div>
                                    <strong><?= $currency['symbol'] ?><?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr style="border: 0; border-top: 1px solid var(--color-border); margin: 16px 0;">

                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
                        <span>Subtotal:</span>
                        <span><?= $currency['symbol'] ?><?= number_format($subtotal, 2) ?></span>
                    </div>

                    <?php if ($discount > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #008060; font-weight: 600;">
                            <span>Descuento<?= !empty($couponCode) ? ' (' . htmlspecialchars($couponCode) . ')' : '' ?>:</span>
                            <span>-<?= $currency['symbol'] ?><?= number_format($discount, 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: var(--color-text-light);">
                        <span>Costo de Envío (<?= htmlspecialchars($estimatedDays) ?>):</span>
                        <span style="<?= $shippingAmount == 0.0 ? 'color: #008060; font-weight: 600;' : 'font-weight: 600; color: var(--color-text);' ?>">
                            <?= $shippingAmount > 0 ? $currency['symbol'] . number_format($shippingAmount, 2) : 'Gratis' ?>
                        </span>
                    </div>

                    <?php if ($taxRate > 0 && $taxAmount > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 13.5px; color: var(--color-text-light);">
                            <span><?= $taxIncluded ? 'Impuesto (' . number_format($taxRate, 2) . '% incluido):' : 'Impuesto (' . number_format($taxRate, 2) . '%):' ?></span>
                            <span><?= $currency['symbol'] ?><?= number_format($taxAmount, 2) ?></span>
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 10px;"></div>
                    <?php endif; ?>

                    <div style="display: flex; justify-content: space-between; padding-top: 14px; border-top: 1px solid var(--color-border); font-size: 20px; font-weight: 700;">
                        <span>Total a Pagar:</span>
                        <span><?= $currency['symbol'] ?><?= number_format($total, 2) ?></span>
                    </div>

                    <button type="submit" class="btn-store btn-store-primary" style="width: 100%; margin-top: 24px; padding: 14px; font-size: 16px;">
                        Confirmar y Realizar Pedido
                    </button>
                </div>
            </div>
        </div>
    </form>
</main>

<script>
function toggleBankInstructionsBox() {
    const box = document.getElementById('bank-instructions-box');
    if (!box) return;
    const selected = document.querySelector('input[name="payment_method"]:checked');
    box.style.display = (selected && selected.value === 'bank_transfer') ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="payment_method"]').forEach(function(radio) {
        radio.addEventListener('change', toggleBankInstructionsBox);
    });
    const checked = document.querySelector('input[name="payment_method"]:checked');
    if (!checked) {
        const first = document.querySelector('input[name="payment_method"]');
        if (first) first.checked = true;
    }
    toggleBankInstructionsBox();
});
</script>

<?php \ShopCMS\Core\ThemeManager::renderFooter(get_defined_vars()); ?>

