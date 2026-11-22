<?php
use ShopCMS\Core\OrderWorkflow;

$currency = $currency ?? '$';
$currentFilter = $currentFilter ?? '';
$paymentFilter = $paymentFilter ?? '';
$search = $search ?? '';
$counts = $counts ?? ['all' => count($orders), 'pending' => 0, 'processing' => 0, 'packing' => 0, 'shipped' => 0, 'out_for_delivery' => 0, 'delivered' => 0, 'cancelled' => 0, 'returned' => 0];
$inProgressCount = $inProgressCount ?? 0;
$totalRevenue = $totalRevenue ?? 0.0;

$buildOrderUrl = function(array $overrides = []) use ($baseUrl, $currentFilter, $paymentFilter, $search): string {
    $params = [
        'status'  => array_key_exists('status', $overrides) ? $overrides['status'] : $currentFilter,
        'payment' => array_key_exists('payment', $overrides) ? $overrides['payment'] : $paymentFilter,
        'q'       => array_key_exists('q', $overrides) ? $overrides['q'] : $search,
    ];
    $clean = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return $baseUrl . '/admin/orders' . (!empty($clean) ? '?' . http_build_query($clean) : '');
};
?>

<!-- CABECERA -->
<div class="page-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 class="page-title">Pedidos y Centro Logístico</h1>
        <p class="page-subtitle">Controla el ciclo completo de cada compra: validación de pago, picking/empaque, despacho con guía y entrega final.</p>
    </div>
    <div>
        <button type="button" onclick="toggleOrderGuide()" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span>¿Cómo funciona el flujo?</span>
        </button>
    </div>
</div>

<!-- GUÍA RÁPIDA DEL CICLO LOGÍSTICO -->
<div id="order-quick-guide" class="polaris-card" style="background: #F9FAFB; border-color: #E5E7EB; margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="3" width="15" height="13"></rect>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                </svg>
                <strong style="font-size: 13px; color: #111827;">Flujo operativo de pedidos (6 fases + control de inventario)</strong>
            </div>
            <button type="button" onclick="toggleOrderGuide()" style="background: none; border: none; color: #6B7280; font-size: 12px; cursor: pointer; text-decoration: underline;">Ocultar</button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">1</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Validación y Empaque</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">El stock se reserva al comprar. Confirma el pedido y pásalo a <strong>Empacando</strong> mientras almacén arma la caja.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #EBF5FA; color: #006FAD; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">2</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Despacho con Guía y Reparto</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Al marcar <strong>Enviado</strong> o <strong>En reparto</strong>, registra el courier y código de tracking para que el cliente lo rastree en su cuenta.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #FFF5EA; color: #B95000; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">3</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Entrega o Devolución Segura</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Si un pedido pasa a <strong>Cancelado</strong> o <strong>Devuelto</strong>, el sistema repone automáticamente el stock al catálogo.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MÉTRICAS DE PEDIDOS -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-title">Total de Pedidos</div>
        <div class="metric-value"><?= (int)($counts['all'] ?? 0) ?></div>
        <div class="metric-sub"><?= (int)($counts['delivered'] ?? 0) ?> entregados · <?= (int)($counts['cancelled'] ?? 0) + (int)($counts['returned'] ?? 0) ?> cancelados/devueltos</div>
    </div>
    <div class="metric-card">
        <div class="metric-title">En Curso / Por Atender</div>
        <div class="metric-value" style="color: <?= $inProgressCount > 0 ? '#006FAD' : '#111827' ?>;"><?= (int)$inProgressCount ?></div>
        <div class="metric-sub"><?= (int)($counts['pending'] ?? 0) ?> pendientes · <?= (int)($counts['packing'] ?? 0) ?> empacando · <?= (int)($counts['shipped'] ?? 0) + (int)($counts['out_for_delivery'] ?? 0) ?> en tránsito</div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Ingresos Confirmados</div>
        <div class="metric-value" style="color: #008060;"><?= htmlspecialchars($currency) ?><?= number_format((float)$totalRevenue, 2) ?></div>
        <div class="metric-sub">Excluye pedidos cancelados o devueltos</div>
    </div>
</div>

<!-- TABLA DE PEDIDOS -->
<div class="polaris-card">
    <div class="card-header" style="flex-direction: column; align-items: stretch; gap: 12px;">
        <!-- PESTAÑAS POR FASE LOGÍSTICA -->
        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
            <a href="<?= $buildOrderUrl(['status' => '']) ?>" class="btn <?= empty($currentFilter) ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Todos (<?= (int)($counts['all'] ?? 0) ?>)
            </a>
            <a href="<?= $buildOrderUrl(['status' => 'pending']) ?>" class="btn <?= $currentFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Pendientes (<?= (int)($counts['pending'] ?? 0) ?>)
            </a>
            <a href="<?= $buildOrderUrl(['status' => 'processing']) ?>" class="btn <?= $currentFilter === 'processing' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Confirmados (<?= (int)($counts['processing'] ?? 0) ?>)
            </a>
            <a href="<?= $buildOrderUrl(['status' => 'packing']) ?>" class="btn <?= $currentFilter === 'packing' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Empacando (<?= (int)($counts['packing'] ?? 0) ?>)
            </a>
            <a href="<?= $buildOrderUrl(['status' => 'shipped']) ?>" class="btn <?= $currentFilter === 'shipped' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Enviados (<?= (int)($counts['shipped'] ?? 0) ?>)
            </a>
            <a href="<?= $buildOrderUrl(['status' => 'out_for_delivery']) ?>" class="btn <?= $currentFilter === 'out_for_delivery' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                En reparto (<?= (int)($counts['out_for_delivery'] ?? 0) ?>)
            </a>
            <a href="<?= $buildOrderUrl(['status' => 'delivered']) ?>" class="btn <?= $currentFilter === 'delivered' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Entregados (<?= (int)($counts['delivered'] ?? 0) ?>)
            </a>
            <a href="<?= $buildOrderUrl(['status' => 'cancelled']) ?>" class="btn <?= $currentFilter === 'cancelled' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Cancelados (<?= (int)($counts['cancelled'] ?? 0) ?>)
            </a>
            <a href="<?= $buildOrderUrl(['status' => 'returned']) ?>" class="btn <?= $currentFilter === 'returned' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Devueltos (<?= (int)($counts['returned'] ?? 0) ?>)
            </a>
        </div>

        <!-- BUSCADOR Y FILTRO DE PAGO -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding-top: 8px; border-top: 1px solid #F3F4F6;">
            <form method="GET" action="<?= $baseUrl ?>/admin/orders" style="display: flex; gap: 8px; flex: 1; min-width: 260px; max-width: 520px;">
                <?php if (!empty($currentFilter)): ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($currentFilter) ?>">
                <?php endif; ?>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por #ORD, cliente, correo o guía de tracking..." class="form-control" style="padding: 6px 10px; font-size: 13px;">
                <select name="payment" class="form-control" style="width: 165px; padding: 6px 8px; font-size: 12.5px;" onchange="this.form.submit()">
                    <option value="">Pago: Todos</option>
                    <option value="unpaid" <?= $paymentFilter === 'unpaid' ? 'selected' : '' ?>>Pendiente de pago</option>
                    <option value="paid" <?= $paymentFilter === 'paid' ? 'selected' : '' ?>>Pagado</option>
                    <option value="refunded" <?= $paymentFilter === 'refunded' ? 'selected' : '' ?>>Reembolsado</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
                <?php if ($search !== '' || $paymentFilter !== ''): ?>
                    <a href="<?= $buildOrderUrl(['q' => '', 'payment' => '']) ?>" class="btn btn-secondary btn-sm">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="polaris-table">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Pago</th>
                    <th>Fase Logística</th>
                    <th>Tracking / Courier</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: right;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding: 48px 20px; color: var(--color-text-muted);">
                            No se encontraron pedidos con los criterios seleccionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <?php
                        $stMeta = OrderWorkflow::getStatusMeta($o['status']);
                        $payMeta = OrderWorkflow::getPaymentMeta($o['payment_status']);
                        ?>
                        <tr>
                            <td>
                                <a href="<?= $baseUrl ?>/admin/orders/view/<?= $o['id'] ?>" style="font-weight: 700; color: #008060; text-decoration: none; font-size: 13.5px;">
                                    <?= htmlspecialchars($o['order_number']) ?>
                                </a>
                                <?php if (!empty($o['total_items'])): ?>
                                    <div style="font-size: 11px; color: var(--color-text-secondary);"><?= (int)$o['total_items'] ?> art.</div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 12.5px; color: var(--color-text-secondary);">
                                <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #111827;"><?= htmlspecialchars($o['customer_name']) ?></div>
                                <div style="font-size: 11.5px; color: var(--color-text-secondary);"><?= htmlspecialchars($o['customer_email']) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $payMeta['badge'] ?>" style="font-size: 11px;">
                                    <?= $payMeta['label'] ?>
                                </span>
                                <div style="font-size: 11px; color: var(--color-text-secondary); margin-top: 2px;">
                                    <?= htmlspecialchars(OrderWorkflow::paymentMethodLabel($o['payment_method'])) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background: <?= $stMeta['bg'] ?>; color: <?= $stMeta['color'] ?>; font-weight: 600;">
                                    <?= $stMeta['short'] ?>
                                </span>
                                <?php if ($stMeta['step'] > 0): ?>
                                    <div style="font-size: 11px; color: var(--color-text-muted); margin-top: 2px;">Paso <?= $stMeta['step'] ?> de 6</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($o['carrier']) || !empty($o['tracking_number'])): ?>
                                    <div style="font-size: 12px; font-weight: 600; color: #111827;"><?= htmlspecialchars($o['carrier'] ?: 'Courier') ?></div>
                                    <?php if (!empty($o['tracking_number'])): ?>
                                        <code style="font-size: 11px; color: #006FAD;"><?= htmlspecialchars($o['tracking_number']) ?></code>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #9CA3AF;">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; font-weight: 700; font-size: 13.5px;">
                                <?= htmlspecialchars($currency) ?><?= number_format((float)$o['total'], 2) ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= $baseUrl ?>/admin/orders/view/<?= $o['id'] ?>" class="btn btn-secondary btn-sm" style="font-weight: 600;">
                                    Gestionar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleOrderGuide() {
    const guide = document.getElementById('order-quick-guide');
    if (!guide) return;
    const isHidden = guide.style.display === 'none';
    guide.style.display = isHidden ? 'block' : 'none';
    try {
        localStorage.setItem('shopcms_hide_order_guide', isHidden ? '0' : '1');
    } catch (e) {}
}
document.addEventListener('DOMContentLoaded', function() {
    try {
        if (localStorage.getItem('shopcms_hide_order_guide') === '1') {
            const guide = document.getElementById('order-quick-guide');
            if (guide) guide.style.display = 'none';
        }
    } catch (e) {}
});
</script>
