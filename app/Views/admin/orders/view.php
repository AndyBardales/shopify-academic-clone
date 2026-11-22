<?php
use ShopCMS\Core\OrderWorkflow;

$currency = $currency ?? '$';
$currentStatus = $order['status'] === 'completed' ? 'delivered' : $order['status'];
$stMeta = OrderWorkflow::getStatusMeta($currentStatus);
$payMeta = OrderWorkflow::getPaymentMeta($order['payment_status']);
$steps = OrderWorkflow::trackingSteps();
$currentStep = (int)$stMeta['step'];
?>

<style>
.order-view-grid {
    display: grid;
    grid-template-columns: 1.55fr 1fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 960px) {
    .order-view-grid {
        grid-template-columns: 1fr;
    }
}
.admin-stepper {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 8px;
}
@media (max-width: 780px) {
    .admin-stepper {
        grid-template-columns: repeat(2, 1fr);
    }
}
.admin-step-box {
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    padding: 10px 12px;
    background: #FFFFFF;
    cursor: pointer;
    transition: all 0.15s ease;
    position: relative;
}
.admin-step-box:hover {
    border-color: #008060;
    background: #F6FBF9;
}
.admin-step-box.done {
    border-color: #A7F3D0;
    background: #ECFDF5;
}
.admin-step-box.current {
    border: 2px solid #008060;
    background: #ECFDF5;
    box-shadow: 0 2px 8px rgba(0, 128, 96, 0.12);
}
.carrier-chip {
    background: #F3F4F6;
    border: 1px solid #E5E7EB;
    border-radius: 5px;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
}
.carrier-chip:hover {
    background: #ECFDF5;
    border-color: #059669;
    color: #065F46;
}
</style>

<!-- CABECERA DEL PEDIDO -->
<div class="page-header" style="flex-wrap: wrap; gap: 14px;">
    <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
        <a href="<?= $baseUrl ?>/admin/orders" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <span>Volver a Pedidos</span>
        </a>
        <div>
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <h1 class="page-title" style="margin: 0;">Pedido <?= htmlspecialchars($order['order_number']) ?></h1>
                <span class="badge" style="background: <?= $stMeta['bg'] ?>; color: <?= $stMeta['color'] ?>; font-weight: 700; font-size: 12px; padding: 3px 10px;">
                    <?= $stMeta['label'] ?>
                </span>
                <span class="badge <?= $payMeta['badge'] ?>" style="font-size: 12px; padding: 3px 10px;">
                    Pago: <?= $payMeta['label'] ?>
                </span>
            </div>
            <p class="page-subtitle" style="margin: 3px 0 0;">
                Registrado el <?= date('d/m/Y \a \l\a\s H:i', strtotime($order['created_at'])) ?> · Método: <strong><?= htmlspecialchars(OrderWorkflow::paymentMethodLabel($order['payment_method'])) ?></strong>
            </p>
        </div>
    </div>

    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <?php \ShopCMS\Core\PluginManager::doAction('admin_order_actions', $order, $items); ?>
        <?php if (!empty($stMeta['next'])): ?>
            <button type="button" onclick="selectNextOrderStep('<?= $stMeta['next'] ?>')" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 7px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
                <span>Siguiente fase: <?= htmlspecialchars($stMeta['next_label']) ?></span>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- STEPPER VISUAL DE 6 HITOS (CLICKABLE PARA CAMBIAR DE FASE) -->
<div class="polaris-card" style="margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
            <strong style="font-size: 13px; color: #111827;">Progreso Logístico del Pedido (Haz clic en cualquier etapa para seleccionarla)</strong>
            <span style="font-size: 12px; color: var(--color-text-secondary);"><?= htmlspecialchars($stMeta['desc']) ?></span>
        </div>

        <?php if (in_array($currentStatus, ['cancelled', 'returned'], true)): ?>
            <div style="background: <?= $currentStatus === 'cancelled' ? '#FEF2F2' : '#F3F4F6' ?>; border: 1px solid <?= $currentStatus === 'cancelled' ? '#FCA5A5' : '#D1D5DB' ?>; color: <?= $currentStatus === 'cancelled' ? '#991B1B' : '#374151' ?>; border-radius: 8px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <strong>Estado terminal: <?= htmlspecialchars($stMeta['label']) ?></strong>
                    <?php if (!empty($order['cancel_reason'])): ?>
                        <div style="font-size: 12.5px; margin-top: 3px;">Motivo registrado: <?= htmlspecialchars($order['cancel_reason']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($order['stock_restored'])): ?>
                    <span class="badge badge-success" style="font-size: 11.5px;">Stock repuesto al inventario</span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="admin-stepper">
                <?php foreach ($steps as $idx => $step): ?>
                    <?php
                    $isDone = $currentStep > $idx;
                    $isCurr = $currentStep === $idx;
                    ?>
                    <div class="admin-step-box <?= $isDone ? 'done' : ($isCurr ? 'current' : '') ?>" onclick="selectNextOrderStep('<?= $step['key'] ?>')" title="Cambiar a: <?= htmlspecialchars($step['title']) ?>">
                        <div style="display: flex; align-items: center; gap: 7px; margin-bottom: 4px;">
                            <span style="width: 20px; height: 20px; border-radius: 50%; background: <?= ($isDone || $isCurr) ? '#008060' : '#E5E7EB' ?>; color: <?= ($isDone || $isCurr) ? '#FFFFFF' : '#6B7280' ?>; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <?php if ($isDone): ?>
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <?php else: ?>
                                    <?= $idx ?>
                                <?php endif; ?>
                            </span>
                            <strong style="font-size: 12px; color: <?= ($isDone || $isCurr) ? '#065F46' : '#374151' ?>;"><?= htmlspecialchars($step['title']) ?></strong>
                        </div>
                        <div style="font-size: 11px; color: #6B7280; padding-left: 27px;"><?= htmlspecialchars($step['sub']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="order-view-grid">
    <!-- COLUMNA IZQUIERDA: ARTÍCULOS Y LÍNEA DE TIEMPO -->
    <div>
        <!-- ARTÍCULOS DEL PEDIDO -->
        <div class="polaris-card">
            <div class="card-header">
                <h2 class="card-title">Artículos del Pedido (<?= count($items) ?>)</h2>
            </div>
            <div style="overflow-x: auto;">
                <table class="polaris-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th style="text-align: right;">Precio Unit.</th>
                            <th style="text-align: center;">Cant.</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 42px; height: 42px; border-radius: 6px; background: #F3F4F6; border: 1px solid #E5E7EB; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <?php if (!empty($item['featured_image'])): ?>
                                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($item['featured_image']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"></rect></svg>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                            <?php if (!empty($item['variant_title'])): ?>
                                                <div style="font-size: 12px; color: var(--color-text-secondary);"><?= htmlspecialchars($item['variant_title']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($item['sku'])): ?>
                                                <code style="font-size: 11px; color: #6B7280;">SKU: <?= htmlspecialchars($item['sku']) ?></code>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: right;"><?= htmlspecialchars($currency) ?><?= number_format((float)$item['price'], 2) ?></td>
                                <td style="text-align: center; font-weight: 600;"><?= (int)$item['quantity'] ?></td>
                                <td style="text-align: right;"><strong><?= htmlspecialchars($currency) ?><?= number_format((float)$item['total'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- RESUMEN FINANCIERO -->
            <div class="card-body" style="border-top: 1px solid var(--color-border); background: #FAFBFB;">
                <div style="max-width: 320px; margin-left: auto; font-size: 13.5px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--color-text-secondary);">Subtotal de artículos:</span>
                        <span><?= htmlspecialchars($currency) ?><?= number_format((float)$order['subtotal'], 2) ?></span>
                    </div>
                    <?php if ((float)$order['discount_amount'] > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; color: #008060; font-weight: 600;">
                            <span>Descuento (<?= htmlspecialchars($order['coupon_code'] ?: 'Cupón') ?>):</span>
                            <span>-<?= htmlspecialchars($currency) ?><?= number_format((float)$order['discount_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--color-text-secondary);">Envío:</span>
                        <span><?= (float)($order['shipping_amount'] ?? 0) > 0 ? htmlspecialchars($currency) . number_format((float)$order['shipping_amount'], 2) : 'Gratis' ?></span>
                    </div>
                    <?php if ((float)($order['tax_amount'] ?? 0) > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: var(--color-text-secondary);">Impuesto registrado:</span>
                            <span><?= htmlspecialchars($currency) . number_format((float)$order['tax_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div style="display: flex; justify-content: space-between; margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--color-border); font-size: 16px; font-weight: 700;">
                        <span>Total de la Orden:</span>
                        <span style="color: #008060;"><?= htmlspecialchars($currency) ?><?= number_format((float)$order['total'], 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- LÍNEA DE TIEMPO CRONOLÓGICA (TRACKING DE EVENTOS) -->
        <div class="polaris-card">
            <div class="card-header">
                <h2 class="card-title">Línea de Tiempo y Trazabilidad (Visible al Cliente)</h2>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <p style="color: var(--color-text-muted);">Sin eventos registrados.</p>
                <?php else: ?>
                    <div style="position: relative; padding-left: 24px;">
                        <?php foreach ($history as $idx => $h): ?>
                            <?php
                            $hMeta = OrderWorkflow::getStatusMeta($h['status']);
                            $actorLabel = ($h['actor'] ?? '') === 'customer' ? 'Cliente' : (($h['actor'] ?? '') === 'admin' ? ' Centro Logístico' : 'Sistema');
                            ?>
                            <div style="position: relative; padding-bottom: <?= $idx < count($history) - 1 ? '18px' : '4px' ?>; <?= $idx < count($history) - 1 ? 'border-left: 2px solid #E5E7EB;' : '' ?> padding-left: 16px; margin-left: -16px;">
                                <span style="position: absolute; left: -7px; top: 2px; width: 12px; height: 12px; border-radius: 50%; background: <?= $idx === 0 ? '#008060' : '#D1D5DB' ?>; border: 2px solid #FFFFFF; box-shadow: 0 0 0 2px <?= $idx === 0 ? '#A7F3D0' : '#F3F4F6' ?>;"></span>
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 4px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span class="badge" style="background: <?= $hMeta['bg'] ?>; color: <?= $hMeta['color'] ?>; font-size: 11px; font-weight: 700;">
                                            <?= $hMeta['short'] ?>
                                        </span>
                                        <span style="font-size: 11.5px; color: #6B7280; font-weight: 600;"><?= $actorLabel ?></span>
                                    </div>
                                    <span style="font-size: 11.5px; color: var(--color-text-muted);"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></span>
                                </div>
                                <div style="font-size: 13px; color: #374151; line-height: 1.45;">
                                    <?= htmlspecialchars($h['comment'] ?? '') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- COLUMNA DERECHA: PANEL DE CONTROL LOGÍSTICO, PAGO Y CLIENTE -->
    <div>
        <div class="polaris-card" id="order-logistics-card" style="border-color: #008060;">
            <div class="card-header" style="background: #F6FBF9;">
                <div>
                    <h2 class="card-title" style="margin: 0;">Control de Estado, Pago y Envío</h2>
                    <p style="font-size: 12px; color: var(--color-text-secondary); margin: 2px 0 0;">Modifica cualquier dato y guarda en la barra superior</p>
                </div>
            </div>
            <div class="card-body">
                <form id="order-logistics-form" method="POST" action="<?= $baseUrl ?>/admin/orders/update-status/<?= $order['id'] ?>">
                    <div class="form-group">
                        <label for="status">1. Fase Logística del Pedido *</label>
                        <select id="status" name="status" class="form-control" style="font-weight: 700;" onchange="onOrderStatusChange()">
                            <option value="pending" <?= $currentStatus === 'pending' ? 'selected' : '' ?>>1. Pendiente de confirmación</option>
                            <option value="processing" <?= $currentStatus === 'processing' ? 'selected' : '' ?>>2. Confirmado / En cola de almacén</option>
                            <option value="packing" <?= $currentStatus === 'packing' ? 'selected' : '' ?>>3. Preparando y empacando (Picking)</option>
                            <option value="shipped" <?= $currentStatus === 'shipped' ? 'selected' : '' ?>>4. Enviado / Despachado con Courier</option>
                            <option value="out_for_delivery" <?= $currentStatus === 'out_for_delivery' ? 'selected' : '' ?>>5. En camino / En reparto local</option>
                            <option value="delivered" <?= $currentStatus === 'delivered' ? 'selected' : '' ?>>6. Entregado en destino final</option>
                            <option value="cancelled" <?= $currentStatus === 'cancelled' ? 'selected' : '' ?>>— Cancelado (Repone stock)</option>
                            <option value="returned" <?= $currentStatus === 'returned' ? 'selected' : '' ?>>— Devuelto / Reembolsado (Repone stock)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="payment_status">2. Estado del Pago *</label>
                        <select id="payment_status" name="payment_status" class="form-control" style="font-weight: 600;">
                            <option value="unpaid" <?= $order['payment_status'] === 'unpaid' ? 'selected' : '' ?>>Pendiente de pago</option>
                            <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Pagado / Verificado</option>
                            <option value="refunded" <?= $order['payment_status'] === 'refunded' ? 'selected' : '' ?>>Reembolsado al cliente</option>
                        </select>
                        <?php if ($order['payment_method'] === 'cod' && $order['payment_status'] === 'unpaid'): ?>
                            <small style="font-size: 11px; color: #B95000; display: block; margin-top: 4px;">
                                Modalidad Contra Entrega: al marcar como <strong>Entregado</strong> pasará a <strong>Pagado</strong> automáticamente.
                            </small>
                        <?php endif; ?>
                    </div>

                    <!-- DATOS DE DESPACHO Y TRACKING -->
                    <div id="shipping-tracking-box" style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px;">
                        <div style="font-size: 12px; font-weight: 700; color: #111827; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#006FAD" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="3" width="15" height="13"></rect>
                                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                                <circle cx="18.5" cy="18.5" r="2.5"></circle>
                            </svg>
                            <span>Datos de Envío y Rastreo (Tracking)</span>
                        </div>

                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="carrier" style="font-size: 12px;">Empresa Courier / Transportista</label>
                            <input type="text" id="carrier" name="carrier" class="form-control" value="<?= htmlspecialchars($order['carrier'] ?? '') ?>" placeholder="Ej: Olva Courier, Shalom, DHL...">
                            <div style="display: flex; gap: 5px; flex-wrap: wrap; margin-top: 5px;">
                                <button type="button" class="carrier-chip" onclick="setCarrier('Olva Courier')">Olva Courier</button>
                                <button type="button" class="carrier-chip" onclick="setCarrier('Shalom')">Shalom</button>
                                <button type="button" class="carrier-chip" onclick="setCarrier('Urbano Express')">Urbano</button>
                                <button type="button" class="carrier-chip" onclick="setCarrier('Motorizado Propio')">Reparto Propio</button>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="tracking_number" style="font-size: 12px;">N° de Guía / Tracking</label>
                                <input type="text" id="tracking_number" name="tracking_number" class="form-control" value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>" placeholder="Ej: TRK-90812">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="estimated_delivery" style="font-size: 12px;">Entrega Estimada</label>
                                <input type="date" id="estimated_delivery" name="estimated_delivery" class="form-control" value="<?= htmlspecialchars($order['estimated_delivery'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- MOTIVO DE CANCELACIÓN O DEVOLUCIÓN -->
                    <div id="cancel-reason-box" class="form-group" style="display: none; background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: 8px; padding: 12px;">
                        <label for="cancel_reason" style="color: #991B1B; font-weight: 700;">Motivo de Cancelación / Devolución</label>
                        <input type="text" id="cancel_reason" name="cancel_reason" class="form-control" value="<?= htmlspecialchars($order['cancel_reason'] ?? '') ?>" placeholder="Ej: Solicitud del cliente / Pago no recibido">
                        <small style="font-size: 11px; color: #991B1B; display: block; margin-top: 4px;">
                            Al guardar este estado, las unidades del pedido se repondrán automáticamente al stock.
                        </small>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="comment">Nota para el historial de seguimiento (opcional)</label>
                        <input type="text" id="comment" name="comment" class="form-control" placeholder="Se autogenerará un detalle claro si lo dejas vacío">
                    </div>

                    <button type="submit" style="display:none;" aria-hidden="true"></button>
                </form>
            </div>
        </div>

        <!-- DATOS DEL CLIENTE -->
        <div class="polaris-card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2 class="card-title">Datos del Comprador</h2>
                <?php if (!empty($order['customer_id'])): ?>
                    <a href="<?= $baseUrl ?>/admin/customers/view/<?= (int)$order['customer_id'] ?>" class="btn btn-secondary btn-sm">Ver Ficha</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p style="font-weight: 700; font-size: 14.5px; margin-bottom: 6px; color: #111827;"><?= htmlspecialchars($order['customer_name']) ?></p>
                <p style="color: var(--color-text-secondary); margin-bottom: 6px; font-size: 13px;">
                    <?= htmlspecialchars($order['customer_email']) ?>
                </p>
                <?php if (!empty($order['customer_phone'])): ?>
                    <p style="color: var(--color-text-secondary); margin-bottom: 12px; font-size: 13px;">
                        Tel: <?= htmlspecialchars($order['customer_phone']) ?>
                    </p>
                <?php endif; ?>

                <div style="border-top: 1px solid #F3F4F6; padding-top: 12px; margin-top: 8px;">
                    <div style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--color-text-secondary); margin-bottom: 4px;">Dirección de Entrega</div>
                    <p style="font-size: 13px; line-height: 1.5; color: #374151; margin: 0;"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function onOrderStatusChange() {
    const status = document.getElementById('status').value;
    const cancelBox = document.getElementById('cancel-reason-box');
    const shipBox = document.getElementById('shipping-tracking-box');

    if (status === 'cancelled' || status === 'returned') {
        cancelBox.style.display = 'block';
    } else {
        cancelBox.style.display = 'none';
    }

    if (status === 'shipped' || status === 'out_for_delivery') {
        shipBox.style.borderColor = '#006FAD';
        shipBox.style.background = '#F4F9FD';
    } else {
        shipBox.style.borderColor = '#E5E7EB';
        shipBox.style.background = '#F9FAFB';
    }
}

function selectNextOrderStep(nextStatus) {
    const form = document.getElementById('order-logistics-form');
    const select = document.getElementById('status');
    select.value = nextStatus;
    onOrderStatusChange();
    if (window.ShopCMS && window.ShopCMS.markDirty) {
        window.ShopCMS.markDirty(form);
    }
    if (nextStatus === 'shipped' && !document.getElementById('carrier').value) {
        document.getElementById('carrier').focus();
    } else {
        select.focus();
    }
    document.getElementById('order-logistics-card').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function setCarrier(name) {
    const form = document.getElementById('order-logistics-form');
    const input = document.getElementById('carrier');
    input.value = name;
    if (window.ShopCMS && window.ShopCMS.markDirty) {
        window.ShopCMS.markDirty(form);
    }
    document.getElementById('tracking_number').focus();
}

document.addEventListener('DOMContentLoaded', function() {
    onOrderStatusChange();
});
</script>
