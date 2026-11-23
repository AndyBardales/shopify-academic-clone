<?php
use ShopCMS\Core\DashboardAnalytics;
use ShopCMS\Core\OrderWorkflow;

$baseUrl = defined('APP_URL') ? APP_URL : '';
$currency = $currency ?? '$';
$analytics = $analytics ?? ['period' => '30d', 'periodLabel' => 'Últimos 30 días', 'series' => [], 'topProducts' => [], 'statusBreakdown' => [], 'summary' => []];
$period = $analytics['period'] ?? '30d';
$periods = DashboardAnalytics::periods();
$series = $analytics['series'] ?? [];
$topProducts = $analytics['topProducts'] ?? [];
$statusBreakdown = $analytics['statusBreakdown'] ?? [];

$paidSales = $paidSales ?? '0.00';
$pendingSales = $pendingSales ?? '0.00';
$avgTicket = $avgTicket ?? '0.00';
$activeOrders = (int)($activeOrders ?? 0);
$cancelledOrders = (int)($cancelledOrders ?? 0);

$maxRev = 0.0;
foreach ($series as $pt) {
    if ((float)$pt['revenue'] > $maxRev) {
        $maxRev = (float)$pt['revenue'];
    }
}
$chartMax = $maxRev > 0 ? ($maxRev * 1.15) : 100.0;
?>

<style>
.dash-period-bar {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    align-items: center;
}
.dash-period-pill {
    padding: 6px 12px;
    border-radius: 7px;
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid #D1D5DB;
    background: #FFFFFF;
    color: #374151;
}
.dash-period-pill.active {
    background: #008060;
    border-color: #008060;
    color: #FFFFFF;
}
.dash-analytics-grid {
    display: grid;
    grid-template-columns: 1.7fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
.dash-bottom-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}
@media (max-width: 980px) {
    .dash-analytics-grid, .dash-bottom-grid {
        grid-template-columns: 1fr;
    }
}
.scms-svg-bar {
    transition: opacity 0.15s ease;
    cursor: pointer;
}
.scms-svg-bar:hover {
    opacity: 0.8;
}
</style>

<div class="page-header" style="flex-wrap: wrap; gap: 14px;">
    <div>
        <h1 class="page-title">Panel de Control y Analítica</h1>
        <p class="page-subtitle">Rendimiento de ventas, pedidos y alertas para: <strong><?= htmlspecialchars((string)$analytics['periodLabel']) ?></strong>.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <div class="dash-period-bar">
            <?php foreach ($periods as $pKey => $pMeta): ?>
                <a href="<?= $baseUrl ?>/admin?period=<?= urlencode($pKey) ?>" class="dash-period-pill <?= $period === $pKey ? 'active' : '' ?>">
                    <?= htmlspecialchars($pMeta['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <a href="<?= $baseUrl ?>/admin/reports/sales-export?period=<?= urlencode($period) ?>" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            <span>Exportar Reporte CSV</span>
        </a>
        <a href="<?= $baseUrl ?>/admin/products/create" class="btn btn-primary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Agregar Producto
        </a>
    </div>
</div>

<!-- MÉTRICAS PRINCIPALES POLARIS -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-title">Ventas Netas (<?= htmlspecialchars((string)$analytics['periodLabel']) ?>)</div>
        <div class="metric-value"><?= htmlspecialchars($currency) ?><?= $totalSales ?></div>
        <div class="metric-sub">
            Pagado: <strong><?= htmlspecialchars($currency) ?><?= $paidSales ?></strong> &middot; Por cobrar: <?= htmlspecialchars($currency) ?><?= $pendingSales ?>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Pedidos y Ticket Promedio</div>
        <div class="metric-value"><?= (int)$totalOrders ?></div>
        <div class="metric-sub">
            Activos: <?= $activeOrders ?> &middot; Ticket prom.: <strong><?= htmlspecialchars($currency) ?><?= $avgTicket ?></strong>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Productos en Catálogo</div>
        <div class="metric-value"><?= (int)$totalProducts ?></div>
        <div class="metric-sub">Artículos activos publicados en tienda</div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Alertas de Inventario</div>
        <div class="metric-value" style="color: <?= $lowStockCount > 0 ? 'var(--color-error)' : 'var(--color-shopify-green)' ?>;">
            <?= (int)$lowStockCount ?>
        </div>
        <div class="metric-sub">Productos con stock bajo o agotado</div>
    </div>
</div>

<!-- GRÁFICO DE VENTAS + TOP PRODUCTOS / ESTADOS -->
<div class="dash-analytics-grid">
    <div class="polaris-card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <div>
                <h2 class="card-title" style="margin: 0;">Tendencia de Ingresos</h2>
                <div style="font-size: 12px; color: var(--color-text-secondary);">Pedidos válidos (excluye cancelados y devueltos)</div>
            </div>
            <div id="chart-hover-readout" style="font-size: 12px; font-weight: 600; color: #065F46; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 6px; padding: 4px 10px;">
                Pasa el cursor sobre una barra
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($series)): ?>
                <p style="color: var(--color-text-muted); text-align: center; padding: 30px 0;">Sin datos suficientes para graficar este período.</p>
            <?php else: ?>
                <?php
                $svgW = 680;
                $svgH = 220;
                $padL = 52;
                $padR = 14;
                $padT = 16;
                $padB = 30;
                $plotW = $svgW - $padL - $padR;
                $plotH = $svgH - $padT - $padB;
                $nBars = count($series);
                $slotW = $plotW / max(1, $nBars);
                $barW = max(6, min(34, $slotW * 0.62));
                $labelEvery = $nBars > 15 ? (int)ceil($nBars / 8) : ($nBars > 8 ? 2 : 1);
                ?>
                <div style="width: 100%; overflow-x: auto;">
                    <svg viewBox="0 0 <?= $svgW ?> <?= $svgH ?>" style="width: 100%; height: auto; min-width: 420px; display: block;" role="img" aria-label="Gráfico de ventas">
                        <?php for ($g = 0; $g <= 4; $g++): ?>
                            <?php
                            $gy = $padT + ($plotH * ($g / 4));
                            $gVal = $chartMax * (1 - ($g / 4));
                            ?>
                            <line x1="<?= $padL ?>" y1="<?= round($gy, 1) ?>" x2="<?= $svgW - $padR ?>" y2="<?= round($gy, 1) ?>" stroke="#E5E7EB" stroke-dasharray="<?= $g === 4 ? '0' : '3,3' ?>" />
                            <text x="<?= $padL - 6 ?>" y="<?= round($gy + 4, 1) ?>" text-anchor="end" font-size="10" fill="#6B7280"><?= htmlspecialchars($currency) ?><?= number_format($gVal, 0) ?></text>
                        <?php endfor; ?>

                        <?php foreach ($series as $idx => $pt): ?>
                            <?php
                            $val = (float)$pt['revenue'];
                            $barH = ($val > 0 && $chartMax > 0) ? max(3, ($val / $chartMax) * $plotH) : 2;
                            $bx = $padL + ($idx * $slotW) + (($slotW - $barW) / 2);
                            $by = $padT + ($plotH - $barH);
                            $tipText = $pt['label'] . ': ' . $currency . number_format($val, 2) . ' (' . (int)$pt['orders'] . ' ped.)';
                            ?>
                            <rect
                                class="scms-svg-bar"
                                x="<?= round($bx, 1) ?>"
                                y="<?= round($by, 1) ?>"
                                width="<?= round($barW, 1) ?>"
                                height="<?= round($barH, 1) ?>"
                                rx="3"
                                fill="<?= $val > 0 ? '#008060' : '#E5E7EB' ?>"
                                data-tip="<?= htmlspecialchars($tipText) ?>"
                                onmouseenter="document.getElementById('chart-hover-readout').textContent = this.dataset.tip"
                            >
                                <title><?= htmlspecialchars($tipText) ?></title>
                            </rect>
                            <?php if ($idx % $labelEvery === 0 || $idx === $nBars - 1): ?>
                                <text x="<?= round($bx + ($barW / 2), 1) ?>" y="<?= $svgH - 8 ?>" text-anchor="middle" font-size="10" fill="#6B7280"><?= htmlspecialchars((string)$pt['label']) ?></text>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </svg>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- TOP PRODUCTOS VENDIDOS -->
        <div class="polaris-card" style="margin-bottom: 0;">
            <div class="card-header">
                <h2 class="card-title">Top Productos Más Vendidos</h2>
            </div>
            <div class="card-body" style="padding: 12px 16px;">
                <?php if (empty($topProducts)): ?>
                    <p style="font-size: 13px; color: var(--color-text-muted); margin: 8px 0;">Aún no hay ventas registradas en este período.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($topProducts as $tp): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; font-size: 13px; border-bottom: 1px dashed #F3F4F6; padding-bottom: 8px;">
                                <div style="min-width: 0;">
                                    <div style="font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars((string)$tp['product_name']) ?></div>
                                    <div style="font-size: 11.5px; color: var(--color-text-secondary);"><?= (int)$tp['units_sold'] ?> unid. vendidas</div>
                                </div>
                                <strong style="color: #008060; white-space: nowrap;"><?= htmlspecialchars($currency) ?><?= number_format((float)$tp['revenue'], 2) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- DISTRIBUCIÓN POR ESTADO LOGÍSTICO -->
        <div class="polaris-card" style="margin-bottom: 0;">
            <div class="card-header">
                <h2 class="card-title">Pedidos por Estado</h2>
            </div>
            <div class="card-body" style="padding: 12px 16px;">
                <?php if (empty($statusBreakdown)): ?>
                    <p style="font-size: 13px; color: var(--color-text-muted); margin: 8px 0;">Sin pedidos en este rango.</p>
                <?php else: ?>
                    <?php $denom = max(1, (int)$totalOrders); ?>
                    <div style="display: flex; flex-direction: column; gap: 9px;">
                        <?php foreach ($statusBreakdown as $sb): ?>
                            <?php
                            $sMeta = OrderWorkflow::getStatusMeta((string)$sb['status']);
                            $cnt = (int)$sb['cnt'];
                            $pct = round(($cnt / $denom) * 100);
                            ?>
                            <div>
                                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 3px;">
                                    <span style="font-weight: 600; color: #374151;"><?= htmlspecialchars((string)$sMeta['short']) ?></span>
                                    <span style="color: var(--color-text-secondary);"><?= $cnt ?> (<?= $pct ?>%)</span>
                                </div>
                                <div style="height: 6px; background: #F3F4F6; border-radius: 999px; overflow: hidden;">
                                    <div style="height: 100%; width: <?= $pct ?>%; background: <?= htmlspecialchars((string)$sMeta['color']) ?>;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="dash-bottom-grid">
    <!-- ÚLTIMOS PEDIDOS -->
    <div class="polaris-card">
        <div class="card-header">
            <h2 class="card-title">Últimos Pedidos</h2>
            <a href="<?= $baseUrl ?>/admin/orders" class="btn btn-secondary btn-sm">Ver todos</a>
        </div>
        <div style="overflow-x: auto;">
            <table class="polaris-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="6" style="text-align:center; color: var(--color-text-muted);">No hay pedidos registrados aún.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td><?= htmlspecialchars($currency) ?><?= number_format((float)$order['total'], 2) ?></td>
                                <td>
                                    <?php $stMeta = OrderWorkflow::getStatusMeta((string)$order['status']); ?>
                                    <span class="badge" style="background: <?= $stMeta['bg'] ?>; color: <?= $stMeta['color'] ?>; font-weight: 600;">
                                        <?= htmlspecialchars($stMeta['short']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime((string)$order['created_at'])) ?></td>
                                <td>
                                    <a href="<?= $baseUrl ?>/admin/orders/view/<?= $order['id'] ?>" class="btn btn-secondary btn-sm">Detalles</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ALERTAS DE STOCK BAJO -->
    <div class="polaris-card">
        <div class="card-header">
            <h2 class="card-title">Stock Crítico</h2>
            <a href="<?= $baseUrl ?>/admin/inventory" class="btn btn-secondary btn-sm">Ver todo</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($lowStockItems)): ?>
                <div style="padding: 20px; text-align: center; color: var(--color-shopify-green); font-size: 13px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px;">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Todos los productos cuentan con inventario suficiente.
                </div>
            <?php else: ?>
                <table class="polaris-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th style="text-align: right;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStockItems as $item): ?>
                            <tr>
                                <td>
                                    <a href="<?= $baseUrl ?>/admin/products/edit/<?= $item['id'] ?>" style="font-weight: 700; color: #111827; text-decoration: none;" title="Editar producto y variantes">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </a><br>
                                    <small style="color: var(--color-text-secondary);"><?= htmlspecialchars($item['sku'] ?: 'Sin SKU') ?></small>
                                </td>
                                <td style="text-align: right;">
                                    <span class="badge <?= $item['quantity'] == 0 ? 'badge-danger' : 'badge-warning' ?>">
                                        <?= (int)$item['quantity'] ?> unidades
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
