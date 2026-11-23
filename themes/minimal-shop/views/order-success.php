<?php
use ShopCMS\Core\OrderWorkflow;
\ShopCMS\Core\ThemeManager::renderHeader(get_defined_vars());
$steps = OrderWorkflow::trackingSteps();
?>

<main class="container" style="padding-top: 48px; padding-bottom: 90px; text-align: center;">
    <div style="max-width: 680px; margin: 0 auto; background: #FFF; padding: 36px; border-radius: 16px; border: 1px solid var(--color-border); box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
        <div style="width: 68px; height: 68px; background: #AEE9D1; color: #008060; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
        </div>

        <h1 style="font-size: 25px; font-weight: 700; margin-bottom: 6px;">¡Pedido Confirmado y Reservado!</h1>
        <p style="color: var(--color-text-light); font-size: 14.5px; margin-bottom: 20px;">
            Tu orden <strong><?= htmlspecialchars($order['order_number']) ?></strong> ya inició su proceso logístico y tu stock fue apartado.
        </p>

        <!-- MINI STEPPER INICIAL -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 22px; text-align: left;">
            <div style="background: #ECFDF5; border: 1.5px solid #008060; border-radius: 8px; padding: 10px 12px;">
                <div style="font-size: 11px; font-weight: 700; color: #008060;">PASO 1 · ACTUAL</div>
                <div style="font-size: 13px; font-weight: 700; color: #065F46;">Pedido Recibido</div>
                <div style="font-size: 11px; color: #374151;">Stock reservado</div>
            </div>
            <div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px 12px;">
                <div style="font-size: 11px; font-weight: 700; color: #6B7280;">PASO 2 · SIGUIENTE</div>
                <div style="font-size: 13px; font-weight: 600; color: #374151;">Empaque &amp; Guía</div>
                <div style="font-size: 11px; color: #6B7280;">Preparación en almacén</div>
            </div>
            <div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px 12px;">
                <div style="font-size: 11px; font-weight: 700; color: #6B7280;">PASO 3 · FINAL</div>
                <div style="font-size: 13px; font-weight: 600; color: #374151;">Reparto y Entrega</div>
                <div style="font-size: 11px; color: #6B7280;">En tu dirección</div>
            </div>
        </div>

        <div style="background: var(--color-surface); padding: 18px; border-radius: 10px; text-align: left; margin-bottom: 24px; font-size: 13.5px;">
            <div style="margin-bottom: 6px;"><strong>Cliente:</strong> <?= htmlspecialchars($order['customer_name']) ?> (<?= htmlspecialchars($order['customer_email']) ?>)</div>
            <div style="margin-bottom: 6px;"><strong>Dirección de entrega:</strong> <?= htmlspecialchars($order['shipping_address']) ?></div>
            <div style="margin-bottom: 6px;"><strong>Método de pago:</strong> <?= htmlspecialchars(OrderWorkflow::paymentMethodLabel($order['payment_method'])) ?></div>
            <div style="margin-bottom: 6px;"><strong>Envío:</strong> <?= (float)($order['shipping_amount'] ?? 0) > 0 ? $currency['symbol'] . number_format((float)$order['shipping_amount'], 2) : 'Gratis' ?></div>
            <?php if ((float)($order['tax_amount'] ?? 0) > 0): ?>
                <div style="margin-bottom: 6px;"><strong>Impuesto:</strong> <?= $currency['symbol'] . number_format((float)$order['tax_amount'], 2) ?></div>
            <?php endif; ?>
            <div style="padding-top: 8px; margin-top: 8px; border-top: 1px solid var(--color-border); font-size: 16px; font-weight: 700; display: flex; justify-content: space-between;">
                <span>Total del Pedido:</span>
                <span style="color: #008060;"><?= $currency['symbol'] ?><?= number_format((float)$order['total'], 2) ?></span>
            </div>
        </div>

        <?php if (($order['payment_method'] ?? '') === 'bank_transfer' && !empty($bankInstructions)): ?>
            <div style="background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 10px; padding: 14px 16px; text-align: left; margin-bottom: 18px; font-size: 13px; color: #78350F; line-height: 1.5;">
                <strong style="display: block; margin-bottom: 4px; color: #92400E;">Instrucciones para completar tu transferencia:</strong>
                <?= nl2br(htmlspecialchars($bankInstructions)) ?>
            </div>
        <?php endif; ?>

        <?php if (($order['payment_method'] ?? '') === 'bank_transfer' && !in_array($order['status'] ?? '', ['cancelled', 'returned'], true)): ?>
            <div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 10px; padding: 14px 16px; text-align: left; margin-bottom: 22px; font-size: 13px;">
                <strong style="display: block; color: #111827; margin-bottom: 6px;">Comprobante / Voucher de Transferencia o Yape</strong>
                <?php if (!empty($order['payment_proof']) || !empty($order['payment_reference'])): ?>
                    <div style="color: #065F46; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 6px; padding: 8px 12px; margin-bottom: 8px;">
                        Comprobante registrado correctamente<?= !empty($order['payment_reference']) ? ' (Operación: ' . htmlspecialchars((string)$order['payment_reference']) . ')' : '' ?>.
                        <?php if (!empty($order['payment_proof'])): ?>
                            <a href="<?= $baseUrl ?>/<?= htmlspecialchars(ltrim((string)$order['payment_proof'], '/')) ?>" target="_blank" style="color: #008060; font-weight: 700; margin-left: 6px;">Ver archivo</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <form method="POST" action="<?= $baseUrl ?>/account/order-voucher/<?= (int)$order['id'] ?>" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 8px;">
                        <?= \ShopCMS\Core\Security::csrfField() ?>
                        <input type="hidden" name="return_to" value="/order/success/<?= htmlspecialchars(ltrim((string)$order['order_number'], '#')) ?>">
                        <input type="text" name="payment_reference" maxlength="120" placeholder="Número de operación (opcional)" style="padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px;">
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                            <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" style="flex: 1; font-size: 12.5px; background: #FFF; padding: 5px; border: 1px solid #D1D5DB; border-radius: 6px;">
                            <button type="submit" class="btn-store btn-store-primary" style="padding: 8px 14px; font-size: 12.5px;">Enviar Voucher</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- HOOK PARA ACCIONES POST-COMPRA (Invoice Generator, etc.) -->
        <div style="margin-bottom: 22px;">
            <?php \ShopCMS\Core\PluginManager::doAction('order_success_actions', $order); ?>
        </div>

        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <a href="<?= $baseUrl ?>/order/receipt/<?= htmlspecialchars(ltrim((string)$order['order_number'], '#')) ?>" target="_blank" class="btn-store btn-store-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span>Imprimir / Ver Boleta</span>
            </a>
            <a href="<?= $baseUrl ?>/account" class="btn-store btn-store-primary">Ver Seguimiento en Vivo en Mi Cuenta</a>
            <a href="<?= $baseUrl ?>/catalog" class="btn-store btn-store-secondary">Seguir Comprando</a>
        </div>
    </div>
</main>

<?php \ShopCMS\Core\ThemeManager::renderFooter(get_defined_vars()); ?>
