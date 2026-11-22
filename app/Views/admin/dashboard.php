<div class="page-header">
    <div>
        <h1 class="page-title">Panel de Control</h1>
        <p class="page-subtitle">Resumen general del rendimiento de tu tienda en tiempo real.</p>
    </div>
    <div>
        <a href="<?= $baseUrl ?>/admin/products/create" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
        <div class="metric-title">Ventas Totales</div>
        <div class="metric-value"><?= $currency ?><?= $totalSales ?></div>
        <div class="metric-sub">Ingresos netos de pedidos activos</div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Total de Pedidos</div>
        <div class="metric-value"><?= $totalOrders ?></div>
        <div class="metric-sub">Órdenes registradas</div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Productos en Catálogo</div>
        <div class="metric-value"><?= $totalProducts ?></div>
        <div class="metric-sub">Artículos activos en tienda</div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Alertas de Inventario</div>
        <div class="metric-value" style="color: <?= $lowStockCount > 0 ? 'var(--color-error)' : 'var(--color-shopify-green)' ?>;">
            <?= $lowStockCount ?>
        </div>
        <div class="metric-sub">Productos con stock bajo o agotado</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
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
                                <td><?= $currency ?><?= number_format($order['total'], 2) ?></td>
                                <td>
                                    <?php $stMeta = \ShopCMS\Core\OrderWorkflow::getStatusMeta($order['status']); ?>
                                    <span class="badge" style="background: <?= $stMeta['bg'] ?>; color: <?= $stMeta['color'] ?>; font-weight: 600;">
                                        <?= $stMeta['short'] ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
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
                                        <?= $item['quantity'] ?> unidades
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
