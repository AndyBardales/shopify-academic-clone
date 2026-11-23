<?php
use ShopCMS\Core\OrderWorkflow;
\ShopCMS\Core\ThemeManager::renderHeader(get_defined_vars());
$steps = OrderWorkflow::trackingSteps();
?>

<style>
.customer-stepper {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 6px;
    margin: 14px 0;
}
@media (max-width: 720px) {
    .customer-stepper {
        grid-template-columns: repeat(2, 1fr);
    }
}
.cust-step-node {
    padding: 10px 8px;
    border-radius: 8px;
    border: 1px solid #E5E7EB;
    background: #FFFFFF;
    text-align: center;
}
.cust-step-node.done {
    background: #ECFDF5;
    border-color: #A7F3D0;
}
.cust-step-node.active {
    background: #ECFDF5;
    border: 2px solid #008060;
    box-shadow: 0 2px 6px rgba(0, 128, 96, 0.12);
}
</style>

<main class="container" style="padding-top: 40px; padding-bottom: 80px;">
    <!-- CABECERA MI CUENTA -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 1px solid var(--color-border); flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: #E3F1DF; color: #008060; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 22px;">
                <?= strtoupper(substr($customer['name'] ?? 'C', 0, 1)) ?>
            </div>
            <div>
                <h1 style="font-size: 24px; font-weight: 700; color: var(--color-text); margin: 0 0 4px;">Mi Cuenta y Seguimiento de Pedidos</h1>
                <p style="font-size: 14px; color: var(--color-text-light); margin: 0;">
                    Hola, <strong><?= htmlspecialchars($customer['name'] ?? '') ?></strong> (<?= htmlspecialchars($customer['email'] ?? '') ?>) · Rastrea tus paquetes en tiempo real
                </p>
            </div>
        </div>

        <a href="<?= $baseUrl ?>/logout" class="btn-store btn-store-secondary" style="font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Cerrar Sesión</span>
        </a>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div style="background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; padding: 14px 18px; border-radius: 8px; font-size: 14px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <span><?= htmlspecialchars($flashSuccess) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
        <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 14px 18px; border-radius: 8px; font-size: 14px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span><?= htmlspecialchars($flashError) ?></span>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 2.1fr 1fr; gap: 28px; align-items: start;">
        <!-- COLUMNA PRINCIPAL: MIS PEDIDOS Y RASTREO ESTILO TEMU / ALIEXPRESS -->
        <div>
            <div style="background: #FFF; border-radius: 12px; border: 1px solid var(--color-border); padding: 24px; margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h2 style="font-size: 18px; font-weight: 700; margin: 0;">Mis Pedidos y Seguimiento en Vivo (<?= count($orders) ?>)</h2>
                        <p style="font-size: 12.5px; color: var(--color-text-light); margin: 3px 0 0;">Revisa en qué etapa se encuentra tu paquete, tu código de guía y el detalle de movimientos.</p>
                    </div>
                    <a href="<?= $baseUrl ?>/catalog" class="btn-store btn-store-primary" style="font-size: 13px; padding: 8px 14px;">Seguir Comprando</a>
                </div>

                <?php if (empty($orders)): ?>
                    <div style="text-align: center; padding: 40px 20px; color: var(--color-text-light);">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 12px; opacity: 0.6;">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        <p style="font-size: 15px; margin: 0 0 10px;">Aún no has realizado ningún pedido en la tienda.</p>
                        <a href="<?= $baseUrl ?>/catalog" style="color: #008060; font-weight: 600; text-decoration: none; font-size: 14px;">Explorar productos disponibles</a>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 22px;">
                        <?php foreach ($orders as $ord): ?>
                            <?php
                            $stKey = $ord['status'] === 'completed' ? 'delivered' : $ord['status'];
                            $stMeta = OrderWorkflow::getStatusMeta($stKey);
                            $payMeta = OrderWorkflow::getPaymentMeta($ord['payment_status']);
                            $currStep = (int)$stMeta['step'];
                            $historyList = $ord['history'] ?? [];
                            ?>
                            <div style="border: 1px solid var(--color-border); border-radius: 12px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
                                <!-- CABECERA DEL PEDIDO -->
                                <div style="background: var(--color-surface); padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; border-bottom: 1px solid var(--color-border);">
                                    <div>
                                        <strong style="font-size: 16px; color: var(--color-text);">Pedido <?= htmlspecialchars($ord['order_number']) ?></strong>
                                        <span style="font-size: 12px; color: var(--color-text-light); margin-left: 8px;">Realizado el <?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span style="background: <?= $payMeta['bg'] ?>; color: <?= $payMeta['color'] ?>; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px;">
                                            <?= $payMeta['label'] ?>
                                        </span>
                                        <span style="background: <?= $stMeta['bg'] ?>; color: <?= $stMeta['color'] ?>; font-size: 11.5px; font-weight: 700; padding: 3px 10px; border-radius: 4px;">
                                            <?= $stMeta['label'] ?>
                                        </span>
                                        <span style="font-size: 15px; font-weight: 800; color: #008060; margin-left: 4px;">
                                            <?= $currency['symbol'] ?><?= number_format((float)$ord['total'], 2) ?>
                                        </span>
                                    </div>
                                </div>

                                <div style="padding: 18px;">
                                    <!-- BARRA VISUAL DE FASES (ESTILO TEMU / ALIEXPRESS) -->
                                    <?php if (in_array($stKey, ['cancelled', 'returned'], true)): ?>
                                        <div style="background: <?= $stKey === 'cancelled' ? '#FEF2F2' : '#F3F4F6' ?>; border: 1px solid <?= $stKey === 'cancelled' ? '#FCA5A5' : '#D1D5DB' ?>; color: <?= $stKey === 'cancelled' ? '#991B1B' : '#374151' ?>; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; font-size: 13px;">
                                            <strong><?= htmlspecialchars($stMeta['label']) ?>:</strong> <?= htmlspecialchars($stMeta['desc']) ?>
                                            <?php if (!empty($ord['cancel_reason'])): ?>
                                                <div style="margin-top: 4px; font-size: 12px;">Detalle: <?= htmlspecialchars($ord['cancel_reason']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="margin-bottom: 14px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12.5px; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                                                <span style="color: #065F46; font-weight: 700;"><?= htmlspecialchars($stMeta['desc']) ?></span>
                                                <span style="color: var(--color-text-light); font-size: 11.5px;">Etapa <?= $currStep ?> de 6</span>
                                            </div>
                                            <div class="customer-stepper">
                                                <?php foreach ($steps as $idx => $sNode): ?>
                                                    <?php
                                                    $done = $currStep > $idx;
                                                    $active = $currStep === $idx;
                                                    ?>
                                                    <div class="cust-step-node <?= $done ? 'done' : ($active ? 'active' : '') ?>">
                                                        <div style="width: 22px; height: 22px; border-radius: 50%; margin: 0 auto 5px; background: <?= ($done || $active) ? '#008060' : '#E5E7EB' ?>; color: <?= ($done || $active) ? '#FFFFFF' : '#6B7280' ?>; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center;">
                                                            <?php if ($done): ?>
                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                            <?php else: ?>
                                                                <?= $idx ?>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div style="font-size: 11.5px; font-weight: 700; color: <?= ($done || $active) ? '#065F46' : '#4B5563' ?>;"><?= htmlspecialchars($sNode['title']) ?></div>
                                                        <div style="font-size: 10px; color: #6B7280;"><?= htmlspecialchars($sNode['sub']) ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- TARJETA DE TRACKING / COURIER SI YA FUE ASIGNADO -->
                                    <?php if (!empty($ord['carrier']) || !empty($ord['tracking_number']) || !empty($ord['estimated_delivery'])): ?>
                                        <div style="background: #F4F9FD; border: 1px solid #BFDBFE; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1D4ED8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="1" y="3" width="15" height="13"></rect>
                                                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                                                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                                                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                                                </svg>
                                                <div style="font-size: 13px; color: #1E3A8A;">
                                                    <strong>Transportista:</strong> <?= htmlspecialchars($ord['carrier'] ?: 'Courier asignado') ?>
                                                    <?php if (!empty($ord['tracking_number'])): ?>
                                                        · <strong>Guía:</strong> <code style="background: #FFFFFF; padding: 2px 6px; border-radius: 4px; border: 1px solid #93C5FD; font-weight: 700;"><?= htmlspecialchars($ord['tracking_number']) ?></code>
                                                        <button type="button" onclick="copyTrackingCode('<?= htmlspecialchars(addslashes($ord['tracking_number'])) ?>', this)" style="background: #FFFFFF; border: 1px solid #93C5FD; border-radius: 4px; padding: 2px 7px; font-size: 11px; color: #1D4ED8; cursor: pointer; margin-left: 4px;">Copiar guía</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if (!empty($ord['estimated_delivery'])): ?>
                                                <span style="font-size: 12px; font-weight: 700; color: #1E40AF; background: #DBEAFE; padding: 3px 9px; border-radius: 20px;">
                                                    Entrega estimada: <?= date('d/m/Y', strtotime($ord['estimated_delivery'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- LISTA DE ARTÍCULOS -->
                                    <div style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-text-light); margin-bottom: 10px;">
                                        Artículos del paquete:
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 14px;">
                                        <?php foreach ($ord['items'] as $item): ?>
                                            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px dashed var(--color-border); gap: 10px; flex-wrap: wrap;">
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    <div style="width: 44px; height: 44px; border-radius: 6px; background: #F3F4F6; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                        <?php if (!empty($item['featured_image'])): ?>
                                                            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($item['featured_image']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                                        <?php else: ?>
                                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <div style="font-weight: 600; font-size: 13.5px; color: var(--color-text);">
                                                            <?= htmlspecialchars($item['product_name']) ?>
                                                        </div>
                                                        <div style="font-size: 12px; color: var(--color-text-light);">
                                                            Cant: <?= (int)$item['quantity'] ?> × <?= $currency['symbol'] ?><?= number_format((float)$item['price'], 2) ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 6px;">
                                                    <?php if (!empty($item['can_review'])): ?>
                                                        <button type="button"
                                                                class="btn-store btn-store-primary"
                                                                style="font-size: 12px; padding: 5px 11px; display: inline-flex; align-items: center; gap: 5px;"
                                                                data-mode="create"
                                                                data-product-id="<?= (int)($item['product_id'] ?? 0) ?>"
                                                                data-order-id="<?= (int)$ord['id'] ?>"
                                                                data-product-name="<?= htmlspecialchars((string)($item['product_name'] ?? ''), ENT_QUOTES) ?>"
                                                                data-order-number="<?= htmlspecialchars((string)($ord['order_number'] ?? ''), ENT_QUOTES) ?>"
                                                                onclick="openAccountReviewModal(this)">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                                            </svg>
                                                            <span>Calificar Producto</span>
                                                        </button>
                                                    <?php elseif (!empty($item['review'])):
                                                        $ir = $item['review'];
                                                    ?>
                                                        <span style="font-size: 11.5px; color: #166534; background: #DCFCE7; padding: 3px 8px; border-radius: 999px; font-weight: 600;">
                                                            Calificado (<?= (int)$ir['rating'] ?>/5)
                                                        </span>
                                                        <?php if (!empty($ir['can_edit'])): ?>
                                                            <button type="button"
                                                                    class="btn-store btn-store-secondary"
                                                                    style="font-size: 11.5px; padding: 4px 10px;"
                                                                    data-mode="edit"
                                                                    data-review-id="<?= (int)$ir['id'] ?>"
                                                                    data-rating="<?= (int)$ir['rating'] ?>"
                                                                    data-comment="<?= htmlspecialchars((string)($ir['comment'] ?? ''), ENT_QUOTES) ?>"
                                                                    data-product-name="<?= htmlspecialchars((string)($item['product_name'] ?? ''), ENT_QUOTES) ?>"
                                                                    data-order-number="<?= htmlspecialchars((string)($ord['order_number'] ?? ''), ENT_QUOTES) ?>"
                                                                    onclick="openAccountReviewModal(this)">
                                                                Editar reseña
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if (!empty($ir['can_followup'])): ?>
                                                            <button type="button"
                                                                    class="btn-store btn-store-secondary"
                                                                    style="font-size: 11.5px; padding: 4px 10px;"
                                                                    data-mode="followup"
                                                                    data-review-id="<?= (int)$ir['id'] ?>"
                                                                    data-product-name="<?= htmlspecialchars((string)($item['product_name'] ?? ''), ENT_QUOTES) ?>"
                                                                    data-order-number="<?= htmlspecialchars((string)($ord['order_number'] ?? ''), ENT_QUOTES) ?>"
                                                                    onclick="openAccountReviewModal(this)">
                                                                Añadir comentario adicional
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php elseif (!empty($item['review_window_expired'])): ?>
                                                        <span style="font-size: 11.5px; color: var(--color-text-light); background: var(--color-surface); border: 1px solid var(--color-border); padding: 4px 9px; border-radius: 6px;">
                                                            Plazo de reseña cerrado
                                                        </span>
                                                    <?php elseif (!in_array((string)$ord['status'], ['cancelled', 'returned', 'delivered', 'completed'], true)): ?>
                                                        <span style="font-size: 11.5px; color: var(--color-text-light); background: var(--color-surface); border: 1px solid var(--color-border); padding: 4px 9px; border-radius: 6px;">
                                                            Podrás opinar al entregarse
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- PIE DEL PEDIDO: BOTÓN DE DETALLE TIMELINE + AUTOGESTIÓN SEGÚN FASE -->
                                    <?php if (($ord['payment_method'] ?? '') === 'bank_transfer' && ($ord['payment_status'] ?? 'unpaid') !== 'paid' && !in_array($stKey, ['cancelled', 'returned'], true)): ?>
                                        <div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px 12px; margin-bottom: 12px; font-size: 12.5px;">
                                            <?php if (!empty($ord['payment_proof'])): ?>
                                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 6px;">
                                                    <span style="color: #065F46; font-weight: 600;">Voucher enviado<?= !empty($ord['payment_reference']) ? ' (Op: ' . htmlspecialchars((string)$ord['payment_reference']) . ')' : '' ?> — en verificación</span>
                                                    <a href="<?= $baseUrl ?>/<?= htmlspecialchars(ltrim((string)$ord['payment_proof'], '/')) ?>" target="_blank" style="color: #008060; font-weight: 700; text-decoration: none;">Ver comprobante</a>
                                                </div>
                                            <?php else: ?>
                                                <div style="font-weight: 700; color: #111827; margin-bottom: 6px;">Adjuntar Voucher de Transferencia / Yape / Plin:</div>
                                                <form method="POST" action="<?= $baseUrl ?>/account/order-voucher/<?= (int)$ord['id'] ?>" enctype="multipart/form-data" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                                                    <?= \ShopCMS\Core\Security::csrfField() ?>
                                                    <input type="text" name="payment_reference" maxlength="120" placeholder="Nro. operación" style="padding: 6px 9px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 12px; min-width: 120px;">
                                                    <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" style="flex: 1; font-size: 12px; background: #FFF; padding: 4px; border: 1px solid #D1D5DB; border-radius: 6px;">
                                                    <button type="submit" class="btn-store btn-store-primary" style="padding: 6px 12px; font-size: 12px;">Subir Voucher</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding-top: 6px;">
                                        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                                            <button type="button" onclick="toggleOrderTimeline(<?= (int)$ord['id'] ?>)" class="btn-store btn-store-secondary" style="font-size: 12px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px;">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <polyline points="12 6 12 12 16 14"></polyline>
                                                </svg>
                                                <span>Ver bitácora de seguimiento (<?= count($historyList) ?>)</span>
                                            </button>
                                            <a href="<?= $baseUrl ?>/order/receipt/<?= htmlspecialchars(ltrim((string)$ord['order_number'], '#')) ?>" target="_blank" class="btn-store btn-store-secondary" style="font-size: 12px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                                    <rect x="6" y="14" width="12" height="8"></rect>
                                                </svg>
                                                <span>Boleta Imprimible</span>
                                            </a>
                                        </div>

                                        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                                            <?php \ShopCMS\Core\PluginManager::doAction('account_order_actions', $ord); ?>
                                            <?php if (in_array($stKey, ['pending', 'processing'], true)): ?>
                                                <form method="POST" action="<?= $baseUrl ?>/account/order-action/<?= (int)$ord['id'] ?>" style="display: inline;">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="reason" value="Cancelado por el comprador desde Mi Cuenta">
                                                    <button type="submit" onclick="return confirm('¿Deseas cancelar tu pedido <?= htmlspecialchars(addslashes($ord['order_number'])) ?>? Solo puedes hacerlo antes de que entre a empaque.');" class="btn-store btn-store-secondary" style="font-size: 12px; padding: 6px 12px; color: #991B1B; border-color: #FCA5A5;">
                                                        Cancelar Pedido
                                                    </button>
                                                </form>
                                            <?php elseif (in_array($stKey, ['shipped', 'out_for_delivery'], true)): ?>
                                                <form method="POST" action="<?= $baseUrl ?>/account/order-action/<?= (int)$ord['id'] ?>" style="display: inline;">
                                                    <input type="hidden" name="action" value="confirm_delivery">
                                                    <button type="submit" onclick="return confirm('¿Confirmas que ya recibiste tu paquete conforme?');" class="btn-store btn-store-primary" style="font-size: 12px; padding: 6px 14px;">
                                                        Confirmar que recibí el paquete
                                                    </button>
                                                </form>
                                            <?php elseif ($stKey === 'delivered'): ?>
                                                <form method="POST" action="<?= $baseUrl ?>/account/order-action/<?= (int)$ord['id'] ?>" style="display: inline;">
                                                    <input type="hidden" name="action" value="request_return">
                                                    <input type="hidden" name="reason" value="Devolución solicitada por el cliente desde Mi Cuenta">
                                                    <button type="submit" onclick="return confirm('¿Solicitar devolución o cambio de este pedido?');" class="btn-store btn-store-secondary" style="font-size: 12px; padding: 6px 12px;">
                                                        Solicitar Devolución
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- LÍNEA DE TIEMPO DESPLEGABLE (HISTORIAL DETALLADO) -->
                                    <div id="order-timeline-<?= (int)$ord['id'] ?>" style="display: none; margin-top: 14px; padding: 14px 16px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px;">
                                        <div style="font-size: 12px; font-weight: 700; color: #111827; margin-bottom: 10px;">Historial detallado de movimientos:</div>
                                        <div style="padding-left: 16px;">
                                            <?php foreach ($historyList as $hIdx => $hItem): ?>
                                                <?php $hStMeta = OrderWorkflow::getStatusMeta($hItem['status']); ?>
                                                <div style="position: relative; padding-left: 14px; padding-bottom: <?= $hIdx < count($historyList) - 1 ? '12px' : '2px' ?>; <?= $hIdx < count($historyList) - 1 ? 'border-left: 2px solid #D1D5DB;' : '' ?>">
                                                    <span style="position: absolute; left: -6px; top: 3px; width: 10px; height: 10px; border-radius: 50%; background: <?= $hIdx === 0 ? '#008060' : '#9CA3AF' ?>;"></span>
                                                    <div style="font-size: 11.5px; color: #6B7280;">
                                                        <strong><?= htmlspecialchars($hStMeta['short']) ?></strong> · <?= date('d/m/Y H:i', strtotime($hItem['created_at'])) ?>
                                                    </div>
                                                    <div style="font-size: 12.5px; color: #111827; margin-top: 2px;">
                                                        <?= htmlspecialchars($hItem['comment'] ?? '') ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- MIS RESEÑAS PUBLICADAS -->
            <div style="background: #FFF; border-radius: 12px; border: 1px solid var(--color-border); padding: 24px;">
                <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 18px;">Mis Reseñas Publicadas (<?= count($reviews) ?>)</h2>
                <?php if (empty($reviews)): ?>
                    <p style="font-size: 14px; color: var(--color-text-light); margin: 0;">Todavía no has calificado ningún producto entregado.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <?php foreach ($reviews as $rev):
                            $isRevEdited = !empty($rev['updated_at']);
                            $revFollowup = trim((string)($rev['followup_comment'] ?? ''));
                        ?>
                            <div style="border: 1px solid var(--color-border); border-radius: 8px; padding: 14px 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 6px;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars((string)$rev['product_slug']) ?>" style="font-weight: 700; font-size: 13px; color: var(--color-primary, #008060); text-decoration: none;">
                                            <?= htmlspecialchars((string)$rev['product_name']) ?>
                                        </a>
                                        <?php if (!empty($rev['order_number'])): ?>
                                            <span style="font-size: 11px; color: var(--color-text-light); background: var(--color-surface); border: 1px solid var(--color-border); padding: 1px 6px; border-radius: 4px;">
                                                Pedido <?= htmlspecialchars((string)$rev['order_number']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($isRevEdited): ?>
                                            <span style="font-size: 10.5px; color: var(--color-text-light); background: var(--color-surface); border: 1px solid var(--color-border); padding: 1px 6px; border-radius: 4px; font-weight: 600;">
                                                Editada
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <span style="font-size: 11px; color: var(--color-text-light);"><?= date('d/m/Y', strtotime((string)$rev['created_at'])) ?></span>
                                </div>
                                <div style="display: flex; gap: 2px; margin-bottom: 8px;">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="<?= $s <= (int)$rev['rating'] ? '#F59E0B' : '#E5E7EB' ?>" stroke="<?= $s <= (int)$rev['rating'] ? '#F59E0B' : '#D1D5DB' ?>" stroke-width="1.5">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                                <p style="font-size: 13px; color: var(--color-text); margin: 0; line-height: 1.5;"><?= nl2br(htmlspecialchars((string)$rev['comment'])) ?></p>

                                <?php if ($revFollowup !== ''): ?>
                                    <div style="margin-top: 8px; padding: 8px 10px; background: var(--color-surface, #F8FAFC); border-left: 3px solid var(--color-primary, #008060); border-radius: 4px; font-size: 12.5px;">
                                        <strong style="color: var(--color-primary, #008060); display: block; font-size: 11px; margin-bottom: 2px;">
                                            Comentario adicional<?= !empty($rev['followup_at']) ? ' · ' . date('d/m/Y', strtotime((string)$rev['followup_at'])) : '' ?>
                                        </strong>
                                        <?= nl2br(htmlspecialchars($revFollowup)) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($rev['admin_reply'])): ?>
                                    <div style="margin-top: 8px; padding: 8px 10px; background: #F3F4F6; border-left: 3px solid #008060; border-radius: 4px; font-size: 12px;">
                                        <strong style="color: #008060; display: block;">Respuesta de la tienda:</strong>
                                        <?= nl2br(htmlspecialchars((string)$rev['admin_reply'])) ?>
                                    </div>
                                <?php endif; ?>

                                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 10px;">
                                    <?php if (!empty($rev['can_edit'])): ?>
                                        <button type="button"
                                                class="btn-store btn-store-secondary"
                                                style="font-size: 11.5px; padding: 4px 10px;"
                                                data-mode="edit"
                                                data-review-id="<?= (int)$rev['id'] ?>"
                                                data-rating="<?= (int)$rev['rating'] ?>"
                                                data-comment="<?= htmlspecialchars((string)$rev['comment'], ENT_QUOTES) ?>"
                                                data-product-name="<?= htmlspecialchars((string)$rev['product_name'], ENT_QUOTES) ?>"
                                                data-order-number="<?= htmlspecialchars((string)($rev['order_number'] ?? ''), ENT_QUOTES) ?>"
                                                onclick="openAccountReviewModal(this)">
                                            Editar reseña
                                        </button>
                                    <?php endif; ?>
                                    <?php if (!empty($rev['can_followup'])): ?>
                                        <button type="button"
                                                class="btn-store btn-store-secondary"
                                                style="font-size: 11.5px; padding: 4px 10px;"
                                                data-mode="followup"
                                                data-review-id="<?= (int)$rev['id'] ?>"
                                                data-product-name="<?= htmlspecialchars((string)$rev['product_name'], ENT_QUOTES) ?>"
                                                data-order-number="<?= htmlspecialchars((string)($rev['order_number'] ?? ''), ENT_QUOTES) ?>"
                                                onclick="openAccountReviewModal(this)">
                                            Añadir comentario adicional
                                        </button>
                                    <?php endif; ?>
                                    <?php if (empty($rev['can_edit'])): ?>
                                        <span style="font-size: 11px; color: var(--color-text-light);">Plazo de edición finalizado</span>
                                    <?php elseif (!empty($rev['days_left'])): ?>
                                        <span style="font-size: 11px; color: var(--color-text-light);">Quedan <?= (int)$rev['days_left'] ?> días para editar</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- COLUMNA LATERAL: DATOS DE PERFIL CON EDICIÓN -->
        <div>
            <div style="background: #FFF; border-radius: 12px; border: 1px solid var(--color-border); padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 16px;">
                    <h3 style="font-size: 16px; font-weight: 700; margin: 0;">Datos de Contacto y Envío</h3>
                    <button type="button" id="btn-toggle-profile-edit" onclick="toggleProfileEditMode()" class="btn-store btn-store-secondary" style="font-size: 12px; padding: 5px 10px;">
                        Editar Datos
                    </button>
                </div>

                <!-- VISTA LECTURA -->
                <div id="profile-read-view">
                    <div style="margin-bottom: 14px;">
                        <div style="font-size: 12px; color: var(--color-text-light); font-weight: 600; text-transform: uppercase;">Nombre Completo</div>
                        <div style="font-size: 14px; font-weight: 600; color: var(--color-text); margin-top: 2px;"><?= htmlspecialchars($customer['name'] ?? 'No especificado') ?></div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <div style="font-size: 12px; color: var(--color-text-light); font-weight: 600; text-transform: uppercase;">Correo Electrónico</div>
                        <div style="font-size: 14px; color: var(--color-text); margin-top: 2px;"><?= htmlspecialchars($customer['email'] ?? '') ?></div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <div style="font-size: 12px; color: var(--color-text-light); font-weight: 600; text-transform: uppercase;">Teléfono</div>
                        <div style="font-size: 14px; color: var(--color-text); margin-top: 2px;"><?= htmlspecialchars($customer['phone'] ?: 'No registrado') ?></div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: var(--color-text-light); font-weight: 600; text-transform: uppercase;">Dirección Predeterminada</div>
                        <div style="font-size: 14px; color: var(--color-text); margin-top: 2px;"><?= nl2br(htmlspecialchars($customer['address'] ?: 'No registrada')) ?></div>
                    </div>
                </div>

                <!-- FORMULARIO DE EDICIÓN -->
                <form id="profile-edit-form" method="POST" action="<?= $baseUrl ?>/account/profile/update" style="display: none;">
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Nombre Completo *</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($customer['name'] ?? '') ?>" style="width: 100%; padding: 8px 10px; border: 1px solid var(--color-border); border-radius: 7px; font-size: 13.5px;">
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Correo (Identificador)</label>
                        <input type="email" disabled value="<?= htmlspecialchars($customer['email'] ?? '') ?>" style="width: 100%; padding: 8px 10px; border: 1px solid var(--color-border); border-radius: 7px; font-size: 13px; background: #F3F4F6; color: #6B7280;">
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Teléfono / WhatsApp</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" placeholder="+51 987 654 321" style="width: 100%; padding: 8px 10px; border: 1px solid var(--color-border); border-radius: 7px; font-size: 13.5px;">
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Dirección de Entrega</label>
                        <textarea name="address" rows="2" placeholder="Calle, número, distrito, ciudad..." style="width: 100%; padding: 8px 10px; border: 1px solid var(--color-border); border-radius: 7px; font-size: 13.5px; font-family: inherit;"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                    </div>

                    <details style="margin-bottom: 16px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px 12px;">
                        <summary style="font-size: 12.5px; font-weight: 700; cursor: pointer; color: #374151;">Cambiar Contraseña (opcional)</summary>
                        <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 10px;">
                            <div>
                                <label style="display: block; font-size: 11.5px; font-weight: 600; margin-bottom: 3px;">Contraseña Actual</label>
                                <div style="position: relative;">
                                    <input type="password" id="prof-cur-pass" name="current_password" style="width: 100%; padding: 7px 36px 7px 10px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px;">
                                    <button type="button" onclick="toggleProfileEye('prof-cur-pass', this)" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6B7280; cursor: pointer; display: inline-flex; padding: 2px;">
                                        <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label style="display: block; font-size: 11.5px; font-weight: 600; margin-bottom: 3px;">Nueva Contraseña (8+ caract., mayús, minús, núm, símbolo)</label>
                                <div style="position: relative;">
                                    <input type="password" id="prof-new-pass" name="new_password" style="width: 100%; padding: 7px 36px 7px 10px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px;">
                                    <button type="button" onclick="toggleProfileEye('prof-new-pass', this)" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6B7280; cursor: pointer; display: inline-flex; padding: 2px;">
                                        <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label style="display: block; font-size: 11.5px; font-weight: 600; margin-bottom: 3px;">Confirmar Nueva Contraseña</label>
                                <div style="position: relative;">
                                    <input type="password" id="prof-conf-pass" name="new_password_confirm" style="width: 100%; padding: 7px 36px 7px 10px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px;">
                                    <button type="button" onclick="toggleProfileEye('prof-conf-pass', this)" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6B7280; cursor: pointer; display: inline-flex; padding: 2px;">
                                        <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </details>

                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn-store btn-store-primary" style="flex: 1; padding: 9px 12px; font-size: 13px;">Guardar Datos</button>
                        <button type="button" onclick="toggleProfileEditMode()" class="btn-store btn-store-secondary" style="padding: 9px 12px; font-size: 13px;">Cancelar</button>
                    </div>
                </form>

                <div style="margin-top: 14px; padding: 12px; background: var(--color-surface); border-radius: 8px; font-size: 12px; color: var(--color-text-light); line-height: 1.4;">
                    Mantén tu teléfono y dirección actualizados para agilizar tus próximos envíos.
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function toggleProfileEditMode() {
    const readView = document.getElementById('profile-read-view');
    const editForm = document.getElementById('profile-edit-form');
    const btn = document.getElementById('btn-toggle-profile-edit');
    if (!readView || !editForm) return;
    const isEditing = editForm.style.display !== 'none';
    editForm.style.display = isEditing ? 'none' : 'block';
    readView.style.display = isEditing ? 'block' : 'none';
    if (btn) btn.textContent = isEditing ? 'Editar Datos' : 'Ver Perfil';
}

function toggleProfileEye(id, btn) {
    const inp = document.getElementById(id);
    if (!inp) return;
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    const o = btn.querySelector('.eye-open');
    const c = btn.querySelector('.eye-closed');
    if (o) o.style.display = show ? 'none' : '';
    if (c) c.style.display = show ? '' : 'none';
}

function toggleOrderTimeline(orderId) {
    const el = document.getElementById('order-timeline-' + orderId);
    if (!el) return;
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function copyTrackingCode(code, btn) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code);
    }
    const prev = btn.textContent;
    btn.textContent = 'Copiado';
    setTimeout(function() { btn.textContent = prev; }, 1400);
}

function openAccountReviewModal(btn) {
    const modal = document.getElementById('scms_account_review_modal');
    const form = document.getElementById('scms_account_review_form');
    if (!modal || !form || !btn) return;
    const mode = btn.getAttribute('data-mode') || 'create';
    const base = <?= json_encode($baseUrl) ?>;
    const prodName = btn.getAttribute('data-product-name') || 'Producto';
    const ordNum = btn.getAttribute('data-order-number') || '';
    const titleEl = document.getElementById('scms_arm_title');
    const subEl = document.getElementById('scms_arm_sub');
    const ratingGroup = document.getElementById('scms_arm_rating_group');
    const commentGroup = document.getElementById('scms_arm_comment_group');
    const followupGroup = document.getElementById('scms_arm_followup_group');
    const prodInput = document.getElementById('scms_arm_product_id');
    const orderInput = document.getElementById('scms_arm_order_id');
    const ratingEl = document.getElementById('scms_arm_rating');
    const commentEl = document.getElementById('scms_arm_comment');
    const followupEl = document.getElementById('scms_arm_followup');
    const submitBtn = document.getElementById('scms_arm_submit');

    subEl.textContent = prodName + (ordNum ? ' · Pedido ' + ordNum : '');

    if (mode === 'followup') {
        const revId = btn.getAttribute('data-review-id');
        form.action = base + '/account/review/followup/' + revId;
        titleEl.textContent = 'Añadir comentario adicional';
        ratingGroup.style.display = 'none';
        commentGroup.style.display = 'none';
        followupGroup.style.display = 'block';
        commentEl.required = false;
        followupEl.required = true;
        followupEl.value = '';
        submitBtn.textContent = 'Publicar Comentario Adicional';
    } else if (mode === 'edit') {
        const revId = btn.getAttribute('data-review-id');
        form.action = base + '/account/review/update/' + revId;
        titleEl.textContent = 'Editar tu reseña';
        ratingGroup.style.display = 'block';
        commentGroup.style.display = 'block';
        followupGroup.style.display = 'none';
        ratingEl.value = btn.getAttribute('data-rating') || '5';
        commentEl.value = btn.getAttribute('data-comment') || '';
        commentEl.required = true;
        followupEl.required = false;
        submitBtn.textContent = 'Guardar Cambios';
    } else {
        form.action = base + '/account/review';
        titleEl.textContent = 'Calificar producto entregado';
        ratingGroup.style.display = 'block';
        commentGroup.style.display = 'block';
        followupGroup.style.display = 'none';
        prodInput.value = btn.getAttribute('data-product-id') || '0';
        orderInput.value = btn.getAttribute('data-order-id') || '0';
        ratingEl.value = '5';
        commentEl.value = '';
        commentEl.required = true;
        followupEl.required = false;
        submitBtn.textContent = 'Publicar Opinión';
    }

    modal.style.display = 'flex';
}

function closeAccountReviewModal() {
    const modal = document.getElementById('scms_account_review_modal');
    if (modal) modal.style.display = 'none';
}
</script>

<div id="scms_account_review_modal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.55); align-items: center; justify-content: center; padding: 16px;" onclick="if(event.target===this) closeAccountReviewModal();">
    <div style="background: var(--color-bg, #FFF); border: 1px solid var(--color-border); border-radius: 12px; width: 100%; max-width: 460px; padding: 22px; box-shadow: 0 20px 45px rgba(0,0,0,0.18);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px;">
            <div>
                <h3 id="scms_arm_title" style="margin: 0 0 4px; font-size: 17px; font-weight: 700; color: var(--color-text);">Calificar producto</h3>
                <div id="scms_arm_sub" style="font-size: 12.5px; color: var(--color-text-light);"></div>
            </div>
            <button type="button" onclick="closeAccountReviewModal()" style="background: none; border: none; font-size: 20px; line-height: 1; color: var(--color-text-light); cursor: pointer;" aria-label="Cerrar">&times;</button>
        </div>

        <form id="scms_account_review_form" method="POST" action="<?= $baseUrl ?>/account/review">
            <?= \ShopCMS\Core\Security::csrfField() ?>
            <input type="hidden" name="return_to" value="/account">
            <input type="hidden" id="scms_arm_product_id" name="product_id" value="0">
            <input type="hidden" id="scms_arm_order_id" name="order_id" value="0">

            <div id="scms_arm_rating_group" style="margin-bottom: 12px;">
                <label for="scms_arm_rating" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Calificación *</label>
                <select id="scms_arm_rating" name="rating" style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--color-border); font-size: 13px;">
                    <option value="5">5 de 5 estrellas - Excelente</option>
                    <option value="4">4 de 5 estrellas - Bueno</option>
                    <option value="3">3 de 5 estrellas - Regular</option>
                    <option value="2">2 de 5 estrellas - Regular bajo</option>
                    <option value="1">1 de 5 estrellas - Insatisfecho</option>
                </select>
            </div>

            <div id="scms_arm_comment_group" style="margin-bottom: 16px;">
                <label for="scms_arm_comment" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Descripción / Comentario *</label>
                <textarea id="scms_arm_comment" name="comment" rows="4" placeholder="Cuéntale a otros compradores qué te pareció el producto..." style="width: 100%; padding: 9px 10px; border-radius: 6px; border: 1px solid var(--color-border); font-family: inherit; font-size: 13px;"></textarea>
            </div>

            <div id="scms_arm_followup_group" style="display: none; margin-bottom: 16px;">
                <label for="scms_arm_followup" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Comentario adicional después del uso *</label>
                <textarea id="scms_arm_followup" name="followup_comment" rows="4" placeholder="Agrega detalles de durabilidad o experiencia tras recibirlo sin borrar tu reseña inicial..." style="width: 100%; padding: 9px 10px; border-radius: 6px; border: 1px solid var(--color-border); font-family: inherit; font-size: 13px;"></textarea>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" id="scms_arm_submit" class="btn-store btn-store-primary" style="flex: 1; padding: 9px 14px; font-size: 13.5px;">Publicar Opinión</button>
                <button type="button" onclick="closeAccountReviewModal()" class="btn-store btn-store-secondary" style="padding: 9px 14px; font-size: 13.5px;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<?php \ShopCMS\Core\ThemeManager::renderFooter(get_defined_vars()); ?>
