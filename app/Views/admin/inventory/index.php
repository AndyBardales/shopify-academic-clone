<?php
use ShopCMS\Core\InventoryManager;

$currency = $currency ?? '$';
$alertFilter = $alertFilter ?? '';
$categoryFilter = $categoryFilter ?? 0;
$search = $search ?? '';
$counts = $counts ?? ['all' => count($products), 'low' => 0, 'out' => 0, 'ok' => 0, 'total_units' => 0, 'retail_value' => 0.0, 'cost_value' => 0.0];
$movements = $movements ?? [];
$categories = $categories ?? [];
$globalLimit = $globalLimit ?? 5;

$buildInvUrl = function(array $overrides = []) use ($baseUrl, $alertFilter, $categoryFilter, $search): string {
    $params = [
        'alert'    => array_key_exists('alert', $overrides) ? $overrides['alert'] : $alertFilter,
        'category' => array_key_exists('category', $overrides) ? $overrides['category'] : ($categoryFilter ?: ''),
        'q'        => array_key_exists('q', $overrides) ? $overrides['q'] : $search,
    ];
    $clean = array_filter($params, fn($v) => $v !== '' && $v !== null && $v !== 0);
    return $baseUrl . '/admin/inventory' . (!empty($clean) ? '?' . http_build_query($clean) : '');
};
?>

<style>
.inventory-layout-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 20px;
    align-items: start;
}
@media (max-width: 1020px) {
    .inventory-layout-grid {
        grid-template-columns: 1fr;
    }
}
.quick-stock-btn {
    background: #F9FAFB;
    border: 1px solid #D1D5DB;
    border-radius: 5px;
    padding: 4px 7px;
    font-size: 11px;
    font-weight: 700;
    color: #065F46;
    cursor: pointer;
    transition: all 0.15s ease;
}
.quick-stock-btn:hover {
    background: #ECFDF5;
    border-color: #059669;
}
.inv-variant-row td {
    background: #FAFBFB;
    padding-top: 9px !important;
    padding-bottom: 9px !important;
}
</style>

<!-- CABECERA -->
<div class="page-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 class="page-title">Inventario &amp; Alertas de Stock</h1>
        <p class="page-subtitle">Control centralizado de existencias por producto y variante, umbrales de alerta temprana y Kardex conectado a pedidos.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button type="button" onclick="toggleInventoryGuide()" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span>¿Cómo usar?</span>
        </button>
        <a href="<?= $baseUrl ?>/admin/settings" class="btn btn-secondary btn-sm" title="Configurar umbral global por defecto">
            Umbral global: <?= (int)$globalLimit ?> unid.
        </a>
    </div>
</div>

<!-- GUÍA RÁPIDA EN 3 PASOS -->
<div id="inventory-quick-guide" class="polaris-card" style="background: #F9FAFB; border-color: #E5E7EB; margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                <strong style="font-size: 13px; color: #111827;">¿Cómo funciona el control de inventario y el Kardex?</strong>
            </div>
            <button type="button" onclick="toggleInventoryGuide()" style="background: none; border: none; color: #6B7280; font-size: 12px; cursor: pointer; text-decoration: underline;">Ocultar</button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">1</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Ajuste en lote y por variante</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Modifica varias cantidades o umbrales a la vez (o usa <strong>+5 / +10</strong>) y confirma con el botón <strong>Guardar</strong> de la barra superior.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #FFF5EA; color: #B95000; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">2</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Semáforo de alertas tempranas</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Si un producto o cualquiera de sus variantes llega a su <strong>Umbral Mínimo</strong>, se activa la alerta en esta vista y en el Dashboard.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #EBF5FA; color: #006FAD; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">3</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Conectado a Carrito y Pedidos</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">El carrito impide comprar más del stock real; las ventas descuentan stock y las cancelaciones/devoluciones lo reponen en el <strong>Kardex</strong>.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MÉTRICAS DE INVENTARIO -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-title">Unidades Totales en Almacén</div>
        <div class="metric-value"><?= number_format((int)($counts['total_units'] ?? 0)) ?></div>
        <div class="metric-sub">En <?= (int)($counts['all'] ?? 0) ?> productos activos con control de stock</div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Valorización Comercial</div>
        <div class="metric-value" style="color: #008060;"><?= htmlspecialchars($currency) ?><?= number_format((float)($counts['retail_value'] ?? 0), 2) ?></div>
        <div class="metric-sub">
            <?php if ((float)($counts['cost_value'] ?? 0) > 0): ?>
                Costo estimado: <?= htmlspecialchars($currency) ?><?= number_format((float)$counts['cost_value'], 2) ?>
            <?php else: ?>
                Valor total a precio de venta
            <?php endif; ?>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Alertas de Stock Bajo</div>
        <div class="metric-value" style="color: <?= ($counts['low'] ?? 0) > 0 ? '#B95000' : '#111827' ?>;"><?= (int)($counts['low'] ?? 0) ?></div>
        <div class="metric-sub">
            <?php if (($counts['low'] ?? 0) > 0): ?>
                <a href="<?= $buildInvUrl(['alert' => 'low']) ?>" style="color: #B95000; font-weight: 600; text-decoration: underline;">Filtrar por reponer</a>
            <?php else: ?>
                Sin alertas de stock bajo
            <?php endif; ?>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Productos / Variantes Agotados</div>
        <div class="metric-value" style="color: <?= ($counts['out'] ?? 0) > 0 ? 'var(--color-error)' : '#008060' ?>;"><?= (int)($counts['out'] ?? 0) ?></div>
        <div class="metric-sub">
            <?php if (($counts['out'] ?? 0) > 0): ?>
                <a href="<?= $buildInvUrl(['alert' => 'out']) ?>" style="color: var(--color-error); font-weight: 600; text-decoration: underline;">Ver agotados ahora</a>
            <?php else: ?>
                0 artículos agotados
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="inventory-layout-grid">
    <!-- COLUMNA IZQUIERDA: TABLA DE EXISTENCIAS Y VARIANTES -->
    <div class="polaris-card">
        <div class="card-header" style="flex-direction: column; align-items: stretch; gap: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <a href="<?= $buildInvUrl(['alert' => '']) ?>" class="btn <?= empty($alertFilter) ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                        Todos (<?= (int)($counts['all'] ?? 0) ?>)
                    </a>
                    <a href="<?= $buildInvUrl(['alert' => 'low']) ?>" class="btn <?= $alertFilter === 'low' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                        Stock Bajo (<?= (int)($counts['low'] ?? 0) ?>)
                    </a>
                    <a href="<?= $buildInvUrl(['alert' => 'out']) ?>" class="btn <?= $alertFilter === 'out' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                        Agotados (<?= (int)($counts['out'] ?? 0) ?>)
                    </a>
                    <a href="<?= $buildInvUrl(['alert' => 'ok']) ?>" class="btn <?= $alertFilter === 'ok' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                        Disponibles (<?= (int)($counts['ok'] ?? 0) ?>)
                    </a>
                </div>
            </div>

            <!-- BUSCADOR Y FILTRO POR CATEGORÍA -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding-top: 8px; border-top: 1px solid #F3F4F6;">
                <form method="GET" action="<?= $baseUrl ?>/admin/inventory" style="display: flex; gap: 8px; flex: 1; min-width: 240px; max-width: 540px;">
                    <?php if (!empty($alertFilter)): ?>
                        <input type="hidden" name="alert" value="<?= htmlspecialchars($alertFilter) ?>">
                    <?php endif; ?>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por producto, variante o SKU..." class="form-control" style="padding: 6px 10px; font-size: 13px;">
                    <select name="category" class="form-control" style="width: 170px; padding: 6px 8px; font-size: 12.5px;" onchange="this.form.submit()">
                        <option value="">Categoría: Todas</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= $categoryFilter === (int)$cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
                    <?php if ($search !== '' || $categoryFilter > 0): ?>
                        <a href="<?= $buildInvUrl(['q' => '', 'category' => '']) ?>" class="btn btn-secondary btn-sm">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- FORMULARIO UNIFICADO DE INVENTARIO (INTEGRADO CON BARRA CONTEXTUAL SUPERIOR) -->
        <form id="inventory-batch-form" method="POST" action="<?= $baseUrl ?>/admin/inventory/update">
            <!-- BARRA DE MOTIVO PARA EL KARDEX -->
            <div style="background: #F6FBF9; border-bottom: 1px solid #E5E7EB; padding: 10px 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #065F46; font-weight: 600;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    <span>Registro en Kardex al guardar cambios:</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <select name="movement_type" class="form-control" style="width: 185px; padding: 5px 8px; font-size: 12px; font-weight: 600;">
                        <option value="restock">Reposición de Stock (+)</option>
                        <option value="adjustment">Ajuste por Conteo (=)</option>
                        <option value="loss">Merma / Daño / Salida (-)</option>
                    </select>
                    <input type="text" name="movement_note" class="form-control" placeholder="Nota opcional (ej: Ingreso lote proveedor)" style="width: 240px; padding: 5px 10px; font-size: 12px;">
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="polaris-table">
                    <thead>
                        <tr>
                            <th>Producto / Variante</th>
                            <th>SKU</th>
                            <th>Estado de Alerta</th>
                            <th style="text-align: center; width: 120px;">Umbral Mín.</th>
                            <th style="text-align: right; min-width: 195px;">Existencias y Ajuste Rápido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding: 48px 20px; color: var(--color-text-muted);">
                                    No se encontraron productos con seguimiento de inventario para este filtro.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $p): ?>
                                <?php
                                $pid = (int)$p['id'];
                                $pVariants = $p['variants'] ?? [];
                                $hasVars = !empty($pVariants);
                                $threshold = max(0, (int)$p['low_stock_threshold']);
                                $qty = max(0, (int)$p['quantity']);
                                $alertState = $p['alert_state'] ?? ($qty == 0 ? 'out' : ($qty <= $threshold ? 'low' : 'ok'));
                                ?>
                                <!-- FILA PRINCIPAL DEL PRODUCTO -->
                                <tr style="<?= $alertState === 'out' ? 'background-color: #FEF2F2;' : ($alertState === 'low' ? 'background-color: #FFFDF9;' : '') ?>">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 38px; height: 38px; border-radius: 6px; background: #F3F4F6; border: 1px solid #E5E7EB; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                <?php if (!empty($p['featured_image'])): ?>
                                                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($p['featured_image']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"></rect></svg>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <a href="<?= $baseUrl ?>/admin/products/edit/<?= $pid ?>" style="font-weight: 700; color: #111827; text-decoration: none; font-size: 13.5px;">
                                                    <?= htmlspecialchars($p['name']) ?>
                                                </a>
                                                <div style="font-size: 11.5px; color: var(--color-text-secondary);">
                                                    <?= htmlspecialchars($p['category_name'] ?? 'Sin categoría') ?>
                                                    <?php if ($hasVars): ?>
                                                        · <strong style="color: #006FAD;"><?= count($pVariants) ?> variantes</strong>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><code><?= htmlspecialchars($p['sku'] ?: '—') ?></code></td>
                                    <td>
                                        <?php if ($alertState === 'out'): ?>
                                            <span class="badge badge-danger"><?= $hasVars ? 'VARIANTE AGOTADA' : 'AGOTADO' ?></span>
                                        <?php elseif ($alertState === 'low'): ?>
                                            <span class="badge badge-warning">STOCK BAJO</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">DISPONIBLE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <input type="number"
                                               min="0"
                                               name="thresholds[<?= $pid ?>]"
                                               value="<?= $threshold ?>"
                                               class="form-control variant-num-input"
                                               style="width: 72px; margin: 0 auto; padding: 5px 8px; font-size: 12.5px;"
                                               title="Umbral para activar alerta de stock bajo">
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if ($hasVars): ?>
                                            <div style="display: inline-flex; align-items: center; gap: 6px; background: #EFF6FF; border: 1px solid #BFDBFE; padding: 4px 10px; border-radius: 6px;">
                                                <span style="font-size: 11.5px; color: #1E40AF;">Total variantes:</span>
                                                <strong id="parent-qty-sum-<?= $pid ?>" style="font-size: 14px; color: #1E3A8A;"><?= $qty ?></strong>
                                            </div>
                                        <?php else: ?>
                                            <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: flex-end;">
                                                <input type="number"
                                                       min="0"
                                                       id="prod-qty-<?= $pid ?>"
                                                       name="products_qty[<?= $pid ?>]"
                                                       value="<?= $qty ?>"
                                                       class="form-control variant-num-input"
                                                       style="width: 82px; padding: 5px 8px; font-size: 13px;">
                                                <button type="button" class="quick-stock-btn" onclick="bumpStockInput('prod-qty-<?= $pid ?>', 5, null)" title="Sumar 5 unidades">+5</button>
                                                <button type="button" class="quick-stock-btn" onclick="bumpStockInput('prod-qty-<?= $pid ?>', 10, null)" title="Sumar 10 unidades">+10</button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- FILAS DESGLOSADAS DE VARIANTES (SI TIENE VARIANTES) -->
                                <?php if ($hasVars): ?>
                                    <?php foreach ($pVariants as $v): ?>
                                        <?php
                                        $vid = (int)$v['id'];
                                        $vQty = max(0, (int)$v['quantity']);
                                        $vZero = ($vQty === 0);
                                        $vLow = ($vQty > 0 && $vQty <= $threshold);
                                        ?>
                                        <tr class="inv-variant-row">
                                            <td style="padding-left: 44px;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <span style="color: #9CA3AF; font-family: monospace; font-size: 14px;">└</span>
                                                    <span style="font-weight: 600; font-size: 12.5px; color: #374151;"><?= htmlspecialchars($v['title']) ?></span>
                                                    <span style="font-size: 11.5px; color: #6B7280;">(<?= htmlspecialchars($currency) ?><?= number_format((float)$v['price'], 2) ?>)</span>
                                                </div>
                                            </td>
                                            <td><code style="font-size: 11.5px;"><?= htmlspecialchars($v['sku'] ?: '—') ?></code></td>
                                            <td>
                                                <?php if ($vZero): ?>
                                                    <span class="badge badge-danger" style="font-size: 10.5px;">AGOTADO (0)</span>
                                                <?php elseif ($vLow): ?>
                                                    <span class="badge badge-warning" style="font-size: 10.5px;">STOCK BAJO</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success" style="font-size: 10.5px;">DISPONIBLE</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center; font-size: 11px; color: #9CA3AF;">
                                                ≤ <?= $threshold ?> unid.
                                            </td>
                                            <td style="text-align: right;">
                                                <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: flex-end;">
                                                    <input type="number"
                                                           min="0"
                                                           id="var-qty-<?= $vid ?>"
                                                           name="variants_qty[<?= $vid ?>]"
                                                           value="<?= $vQty ?>"
                                                           data-parent-id="<?= $pid ?>"
                                                           oninput="recalcParentVariantSum(<?= $pid ?>)"
                                                           class="form-control variant-num-input"
                                                           style="width: 82px; padding: 5px 8px; font-size: 13px;">
                                                    <button type="button" class="quick-stock-btn" onclick="bumpStockInput('var-qty-<?= $vid ?>', 5, <?= $pid ?>)" title="Sumar 5 unidades">+5</button>
                                                    <button type="button" class="quick-stock-btn" onclick="bumpStockInput('var-qty-<?= $vid ?>', 10, <?= $pid ?>)" title="Sumar 10 unidades">+10</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <button type="submit" style="display:none;" aria-hidden="true"></button>
        </form>
    </div>

    <!-- COLUMNA DERECHA: KARDEX / HISTORIAL DE MOVIMIENTOS DE INVENTARIO -->
    <div style="position: sticky; top: 20px;">
        <div class="polaris-card">
            <div class="card-header">
                <div>
                    <h2 class="card-title" style="margin: 0;">Kardex · Últimos Movimientos</h2>
                    <p style="font-size: 11.5px; color: var(--color-text-secondary); margin: 2px 0 0;">Conectado a compras, devoluciones y ajustes</p>
                </div>
            </div>
            <div class="card-body" style="padding: 12px 16px;">
                <?php if (empty($movements)): ?>
                    <div style="padding: 28px 12px; text-align: center; color: var(--color-text-muted); font-size: 12.5px;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5" style="margin: 0 auto 8px; display: block;">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        Aún no hay movimientos registrados en el Kardex. Se registrarán automáticamente al ajustar stock o recibir pedidos.
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 10px; max-height: 560px; overflow-y: auto;">
                        <?php foreach ($movements as $m): ?>
                            <?php
                            $mMeta = InventoryManager::getTypeMeta($m['type']);
                            $delta = (int)$m['change_amount'];
                            $deltaStr = ($delta > 0 ? '+' : '') . $delta;
                            ?>
                            <div style="border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px 12px; background: #FAFBFB;">
                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 6px; margin-bottom: 4px;">
                                    <span class="badge" style="background: <?= $mMeta['bg'] ?>; color: <?= $mMeta['color'] ?>; font-size: 10.5px; font-weight: 700;">
                                        <?= $mMeta['label'] ?>
                                    </span>
                                    <strong style="font-size: 13px; color: <?= $delta > 0 ? '#008060' : ($delta < 0 ? '#DC2626' : '#374151') ?>;">
                                        <?= $deltaStr ?> unid.
                                    </strong>
                                </div>
                                <div style="font-size: 12.5px; font-weight: 700; color: #111827;">
                                    <?= htmlspecialchars($m['product_name']) ?>
                                    <?php if (!empty($m['variant_title'])): ?>
                                        <span style="font-weight: 600; color: #006FAD;">(<?= htmlspecialchars($m['variant_title']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 11.5px; color: #4B5563; margin-top: 2px;">
                                    Stock: <strong><?= (int)$m['previous_qty'] ?> → <?= (int)$m['new_qty'] ?></strong>
                                    <?php if (!empty($m['reference'])): ?>
                                        · Ref: <code><?= htmlspecialchars($m['reference']) ?></code>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($m['note'])): ?>
                                    <div style="font-size: 11px; color: #6B7280; margin-top: 2px;"><?= htmlspecialchars($m['note']) ?></div>
                                <?php endif; ?>
                                <div style="font-size: 10.5px; color: #9CA3AF; margin-top: 4px;">
                                    <?= date('d/m/Y H:i', strtotime($m['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php \ShopCMS\Core\PluginManager::doAction('admin_inventory_bottom'); ?>

<script>
function toggleInventoryGuide() {
    const guide = document.getElementById('inventory-quick-guide');
    if (!guide) return;
    const isHidden = guide.style.display === 'none';
    guide.style.display = isHidden ? 'block' : 'none';
    try {
        localStorage.setItem('shopcms_hide_inv_guide', isHidden ? '0' : '1');
    } catch (e) {}
}

function recalcParentVariantSum(parentId) {
    const inputs = document.querySelectorAll('input[data-parent-id="' + parentId + '"]');
    let sum = 0;
    inputs.forEach(inp => {
        const val = parseInt(inp.value, 10);
        if (!isNaN(val) && val > 0) {
            sum += val;
        }
    });
    const badge = document.getElementById('parent-qty-sum-' + parentId);
    if (badge) {
        badge.textContent = sum.toString();
    }
}

function bumpStockInput(inputId, delta, parentId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const cur = parseInt(input.value, 10) || 0;
    input.value = Math.max(0, cur + delta).toString();
    if (parentId) {
        recalcParentVariantSum(parentId);
    }
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

window.onShopCMSDiscard = function(form) {
    if (form && form.id === 'inventory-batch-form') {
        const parents = new Set();
        form.querySelectorAll('input[data-parent-id]').forEach(inp => {
            parents.add(inp.getAttribute('data-parent-id'));
        });
        parents.forEach(pid => recalcParentVariantSum(pid));
    }
};

document.addEventListener('DOMContentLoaded', function() {
    try {
        if (localStorage.getItem('shopcms_hide_inv_guide') === '1') {
            const guide = document.getElementById('inventory-quick-guide');
            if (guide) guide.style.display = 'none';
        }
    } catch (e) {}
});
</script>
