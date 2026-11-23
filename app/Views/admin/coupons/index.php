<?php
$currency = $currency ?? '$';
$today = $today ?? date('Y-m-d');
$totalAll = $totalAll ?? count($coupons);
$totalActive = $totalActive ?? 0;
$totalExpired = $totalExpired ?? 0;
$totalDisabled = $totalDisabled ?? 0;
$totalUses = $totalUses ?? 0;
$totalDiscounted = $totalDiscounted ?? 0.0;
$ordersWithCoupon = $ordersWithCoupon ?? 0;
?>

<style>
.coupons-layout-grid {
    display: grid;
    grid-template-columns: 1fr 390px;
    gap: 20px;
    align-items: start;
}
@media (max-width: 980px) {
    .coupons-layout-grid {
        grid-template-columns: 1fr;
    }
}
.coupon-ticket-preview {
    background: linear-gradient(135deg, #F6FBF9 0%, #ECFDF5 100%);
    border: 1.5px dashed #059669;
    border-radius: 10px;
    padding: 14px 16px;
    margin-bottom: 16px;
    transition: all 0.2s ease;
}
.coupon-preset-chip {
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
    border-radius: 6px;
    padding: 4px 9px;
    font-size: 11.5px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    transition: all 0.15s ease;
}
.coupon-preset-chip:hover {
    background: #ECFDF5;
    border-color: #059669;
    color: #059669;
}
</style>

<!-- CABECERA -->
<div class="page-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 class="page-title">Cupones y Descuentos</h1>
        <p class="page-subtitle">Crea códigos promocionales en porcentaje o monto fijo, controla su límite de usos y edítalos o deshabilítalos cuando quieras.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button type="button" onclick="toggleCouponGuide()" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span>¿Cómo usar?</span>
        </button>
        <button type="button" onclick="openNewCoupon()" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Nuevo Cupón</span>
        </button>
    </div>
</div>

<!-- GUÍA RÁPIDA EN 3 PASOS -->
<div id="coupon-quick-guide" class="polaris-card" style="background: #F9FAFB; border-color: #E5E7EB; margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                    <line x1="7" y1="7" x2="7.01" y2="7"></line>
                </svg>
                <strong style="font-size: 13px; color: #111827;">¿Cómo funcionan los cupones y descuentos?</strong>
            </div>
            <button type="button" onclick="toggleCouponGuide()" style="background: none; border: none; color: #6B7280; font-size: 12px; cursor: pointer; text-decoration: underline;">Ocultar</button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">1</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Crea o usa una plantilla rápida</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">En el panel derecho escribe un código, pulsa <strong>Generar</strong> o elige una plantilla sugerida para autocompletar.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #EBF5FA; color: #006FAD; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">2</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Define condiciones claras</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Elige porcentaje (%) o monto fijo (<?= htmlspecialchars($currency) ?>), compra mínima opcional, tope de usos y fecha límite.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #FFF5EA; color: #B95000; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">3</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Edita, copia o deshabilita</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Pulsa <strong>Editar</strong> para ajustar condiciones o <strong>Deshabilitar</strong> para pausar el cupón sin perder su historial (borrado lógico).</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MÉTRICAS DE CAMPAÑAS -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-title" style="display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                <line x1="7" y1="7" x2="7.01" y2="7"></line>
            </svg>
            <span>Cupones Vigentes</span>
        </div>
        <div class="metric-value"><?= (int)$totalActive ?></div>
        <div class="metric-sub"><?= (int)$totalExpired ?> vencidos/agotados · <?= (int)$totalDisabled ?> deshabilitados</div>
    </div>

    <div class="metric-card">
        <div class="metric-title" style="display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#006FAD" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                <polyline points="17 6 23 6 23 12"></polyline>
            </svg>
            <span>Canjes Acumulados</span>
        </div>
        <div class="metric-value"><?= number_format((int)$totalUses) ?></div>
        <div class="metric-sub">Veces aplicados por clientes en compras</div>
    </div>

    <div class="metric-card">
        <div class="metric-title" style="display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            <span>Ahorro Otorgado en Pedidos</span>
        </div>
        <div class="metric-value" style="color: #008060;"><?= htmlspecialchars($currency) ?><?= number_format((float)$totalDiscounted, 2) ?></div>
        <div class="metric-sub">En <?= (int)$ordersWithCoupon ?> <?= (int)$ordersWithCoupon === 1 ? 'pedido' : 'pedidos' ?> con descuento</div>
    </div>
</div>

<!-- LAYOUT PRINCIPAL: TABLA + PANEL LATERAL CREAR/EDITAR -->
<div class="coupons-layout-grid">
    <!-- COLUMNA IZQUIERDA: LISTADO DE CUPONES -->
    <div class="polaris-card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                <a href="<?= $baseUrl ?>/admin/coupons<?= !empty($search) ? '?q=' . urlencode($search) : '' ?>" class="btn <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                    Todos (<?= (int)$totalAll ?>)
                </a>
                <a href="<?= $baseUrl ?>/admin/coupons?status=active<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="btn <?= ($statusFilter ?? '') === 'active' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                    Vigentes (<?= (int)$totalActive ?>)
                </a>
                <a href="<?= $baseUrl ?>/admin/coupons?status=expired<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="btn <?= ($statusFilter ?? '') === 'expired' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                    Vencidos / Agotados (<?= (int)$totalExpired ?>)
                </a>
                <a href="<?= $baseUrl ?>/admin/coupons?status=disabled<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="btn <?= ($statusFilter ?? '') === 'disabled' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                    Deshabilitados (<?= (int)$totalDisabled ?>)
                </a>
            </div>

            <form method="GET" action="<?= $baseUrl ?>/admin/coupons" style="display: flex; gap: 6px;">
                <?php if (!empty($statusFilter)): ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                <?php endif; ?>
                <input type="text" name="q" class="form-control" placeholder="Buscar código o campaña..." value="<?= htmlspecialchars($search ?? '') ?>" style="padding: 6px 10px; font-size: 13px; width: 190px;">
                <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
                <?php if (!empty($search)): ?>
                    <a href="<?= $baseUrl ?>/admin/coupons<?= !empty($statusFilter) ? '?status=' . urlencode($statusFilter) : '' ?>" class="btn btn-secondary btn-sm">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table class="polaris-table">
                <thead>
                    <tr>
                        <th>Código / Campaña</th>
                        <th>Beneficio</th>
                        <th>Usos</th>
                        <th>Vigencia</th>
                        <th>Estado</th>
                        <th style="text-align: right; min-width: 215px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($coupons)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 48px 20px; color: var(--color-text-muted);">
                                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 10px; display: block;">
                                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                                    <line x1="7" y1="7" x2="7.01" y2="7"></line>
                                </svg>
                                <div style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 4px;">No se encontraron cupones</div>
                                <div style="font-size: 12.5px; color: #6B7280;">Crea un nuevo cupón con el formulario de la derecha o limpia los filtros.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($coupons as $c): ?>
                            <?php
                            $isActive = !empty($c['is_active']);
                            $isExpired = !empty($c['expires_at']) && $c['expires_at'] < $today;
                            $isExhausted = !empty($c['usage_limit']) && (int)$c['usage_count'] >= (int)$c['usage_limit'];
                            $daysLeft = null;
                            if (!empty($c['expires_at']) && !$isExpired) {
                                $daysLeft = (int)floor((strtotime($c['expires_at']) - strtotime($today)) / 86400);
                            }
                            $usagePct = (!empty($c['usage_limit']) && (int)$c['usage_limit'] > 0)
                                ? min(100, round(((int)$c['usage_count'] / (int)$c['usage_limit']) * 100))
                                : null;
                            ?>
                            <tr style="<?= (!$isActive || $isExpired || $isExhausted) ? 'background: #FAFBFB;' : '' ?>">
                                <!-- CÓDIGO Y CAMPAÑA -->
                                <td>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <code style="background: <?= ($isActive && !$isExpired && !$isExhausted) ? '#ECFDF5' : '#F3F4F6' ?>; border: 1px solid <?= ($isActive && !$isExpired && !$isExhausted) ? '#A7F3D0' : '#E5E7EB' ?>; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 13px; color: <?= ($isActive && !$isExpired && !$isExhausted) ? '#047857' : '#6B7280' ?>;">
                                            <?= htmlspecialchars($c['code']) ?>
                                        </code>
                                        <button type="button"
                                            onclick="copyCouponCode('<?= htmlspecialchars(addslashes($c['code'])) ?>', this)"
                                            title="Copiar código al portapapeles"
                                            style="background: none; border: 1px solid #E5E7EB; border-radius: 5px; padding: 3px 6px; cursor: pointer; color: #6B7280; display: inline-flex; align-items: center; gap: 3px; font-size: 11px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                            </svg>
                                            <span class="copy-label">Copiar</span>
                                        </button>
                                    </div>
                                    <?php if (!empty($c['description'])): ?>
                                        <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">
                                            <?= htmlspecialchars($c['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- BENEFICIO Y CONDICIÓN DE MONTO -->
                                <td>
                                    <div style="font-size: 14px; font-weight: 700; color: #111827;">
                                        <?php if ($c['type'] === 'percent'): ?>
                                            <?= rtrim(rtrim(number_format((float)$c['value'], 2), '0'), '.') ?>% DCTO
                                        <?php else: ?>
                                            -<?= htmlspecialchars($currency) ?><?= number_format((float)$c['value'], 2) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--color-text-secondary);">
                                        <?php
                                        $spendRule = $c['spend_rule'] ?? 'min';
                                        $minSp = (float)($c['min_spend'] ?? 0);
                                        $maxSp = isset($c['max_spend']) && $c['max_spend'] !== null ? (float)$c['max_spend'] : 0;
                                        if ($spendRule === 'exact') {
                                            echo 'Monto exacto: <strong>' . htmlspecialchars($currency) . number_format($minSp, 2) . '</strong>';
                                        } elseif ($spendRule === 'range') {
                                            echo 'Rango: <strong>' . htmlspecialchars($currency) . number_format($minSp, 2) . ' - ' . htmlspecialchars($currency) . number_format($maxSp, 2) . '</strong>';
                                        } elseif ($minSp > 0) {
                                            echo 'Compra mín.: ' . htmlspecialchars($currency) . number_format($minSp, 2);
                                        } else {
                                            echo 'Sin compra mínima';
                                        }
                                        ?>
                                    </div>
                                    <?php if (!isset($c['single_use_per_user']) || !empty($c['single_use_per_user'])): ?>
                                        <span style="display: inline-block; margin-top: 3px; font-size: 10.5px; background: #EFF6FF; color: #1D4ED8; padding: 1px 6px; border-radius: 4px; font-weight: 600;">1 canje por usuario</span>
                                    <?php else: ?>
                                        <span style="display: inline-block; margin-top: 3px; font-size: 10.5px; background: #F3F4F6; color: #4B5563; padding: 1px 6px; border-radius: 4px; font-weight: 500;">Multiuso por usuario</span>
                                    <?php endif; ?>
                                </td>

                                <!-- USOS Y PROGRESO -->
                                <td>
                                    <div style="font-size: 13px; font-weight: 600; color: #111827;">
                                        <?= (int)$c['usage_count'] ?> <?= !empty($c['usage_limit']) ? '/ ' . (int)$c['usage_limit'] : '<span style="font-weight:400; color:#6B7280; font-size:12px;">(ilimitado)</span>' ?>
                                    </div>
                                    <?php if ($usagePct !== null): ?>
                                        <div style="width: 90px; height: 5px; background: #E5E7EB; border-radius: 4px; overflow: hidden; margin-top: 4px;">
                                            <div style="width: <?= $usagePct ?>%; height: 100%; background: <?= $usagePct >= 100 ? '#DC2626' : ($usagePct >= 80 ? '#F59E0B' : '#008060') ?>;"></div>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- VENCIMIENTO -->
                                <td>
                                    <?php if (!empty($c['expires_at'])): ?>
                                        <div style="font-size: 13px; color: <?= $isExpired ? '#DC2626' : '#111827' ?>; font-weight: <?= $isExpired ? '600' : '400' ?>;">
                                            <?= date('d/m/Y', strtotime($c['expires_at'])) ?>
                                        </div>
                                        <?php if ($isExpired): ?>
                                            <span style="font-size: 11px; color: #DC2626; font-weight: 600;">Caducado</span>
                                        <?php elseif ($daysLeft !== null && $daysLeft <= 7): ?>
                                            <span style="font-size: 11px; color: #B95000; font-weight: 600;">
                                                <?= $daysLeft === 0 ? 'Vence hoy' : "Vence en {$daysLeft}d" ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="font-size: 12.5px; color: var(--color-text-secondary);">Sin fecha límite</span>
                                    <?php endif; ?>
                                </td>

                                <!-- ESTADO -->
                                <td>
                                    <?php if (!$isActive): ?>
                                        <span class="badge" style="background: #E5E7EB; color: #4B5563; display: inline-flex; align-items: center; gap: 5px;">
                                            <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                                            Deshabilitado
                                        </span>
                                    <?php elseif ($isExpired): ?>
                                        <span class="badge badge-danger" style="display: inline-flex; align-items: center; gap: 5px;">
                                            <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                                            Vencido
                                        </span>
                                    <?php elseif ($isExhausted): ?>
                                        <span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 5px;">
                                            <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                                            Agotado
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 5px;">
                                            <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                                            Activo
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- ACCIONES -->
                                <td style="text-align: right; white-space: nowrap;">
                                    <div style="display: inline-flex; align-items: center; gap: 5px;">
                                        <button type="button"
                                            onclick='editCoupon(<?= json_encode($c) ?>)'
                                            class="btn btn-secondary btn-sm"
                                            style="display: inline-flex; align-items: center; gap: 4px; font-weight: 600;"
                                            title="Editar código, descuento o vigencia">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                            <span>Editar</span>
                                        </button>

                                        <button type="button"
                                            onclick='duplicateCoupon(<?= json_encode($c) ?>)'
                                            class="btn btn-secondary btn-sm"
                                            style="padding: 5px 8px;"
                                            title="Duplicar como nuevo cupón">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                            </svg>
                                        </button>

                                        <?php if ($isActive): ?>
                                            <form method="POST" action="<?= $baseUrl ?>/admin/coupons/toggle/<?= $c['id'] ?>" style="display: inline;" onsubmit="return confirm('¿Deshabilitar el cupón <?= htmlspecialchars(addslashes($c['code'])) ?>?\n\nYa no podrá aplicarse en el carrito, pero se conservará su historial de usos (borrado lógico).');">
                                                <?= \ShopCMS\Core\Security::csrfField() ?>
                                                <button type="submit" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 4px; color: #B95000;" title="Deshabilitar cupón (borrado lógico)">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                                    </svg>
                                                    <span>Deshabilitar</span>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="<?= $baseUrl ?>/admin/coupons/toggle/<?= $c['id'] ?>" style="display: inline;">
                                                <?= \ShopCMS\Core\Security::csrfField() ?>
                                                <button type="submit" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 4px; color: #008060; border-color: #008060;" title="Reactivar cupón">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                    <span>Activar</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- COLUMNA DERECHA: FORMULARIO CREAR / EDITAR CUPÓN CON VISTA PREVIA EN VIVO -->
    <div class="polaris-card" id="coupon-form-card" style="position: sticky; top: 20px;">
        <div class="card-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div id="coupon-mode-icon" style="width: 32px; height: 32px; border-radius: 8px; background: #ECFDF5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg id="coupon-mode-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                        <line x1="7" y1="7" x2="7.01" y2="7"></line>
                    </svg>
                </div>
                <div>
                    <h2 class="card-title" id="coupon-form-title" style="margin: 0;">Nuevo Cupón</h2>
                    <p id="coupon-form-subtitle" style="font-size: 12px; color: var(--color-text-secondary); margin: 2px 0 0;">Configura tu promoción y guarda arriba</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- VISTA PREVIA EN VIVO DEL CUPÓN -->
            <div class="coupon-ticket-preview" id="coupon-live-ticket">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 6px;">
                    <span style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #047857;">Vista previa del cupón</span>
                    <span id="preview-badge-type" style="font-size: 11px; font-weight: 600; background: #FFFFFF; color: #059669; border: 1px solid #A7F3D0; padding: 1px 7px; border-radius: 12px;">Porcentaje</span>
                </div>
                <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                    <code id="preview-code" style="font-size: 16px; font-weight: 800; color: #065F46; letter-spacing: 0.5px;">PROMO10</code>
                    <span id="preview-benefit" style="font-size: 16px; font-weight: 800; color: #059669;">10% DCTO</span>
                </div>
                <div id="preview-conditions" style="font-size: 11.5px; color: #374151; margin-top: 6px;">
                    Sin compra mínima · Usos ilimitados · Sin fecha de vencimiento
                </div>
            </div>

            <!-- PLANTILLAS RÁPIDAS (SOLO EN MODO CREAR) -->
            <div id="coupon-presets-wrap" style="margin-bottom: 14px;">
                <div style="font-size: 11.5px; color: var(--color-text-secondary); margin-bottom: 6px; font-weight: 500;">Plantillas rápidas (1 clic):</div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <button type="button" class="coupon-preset-chip" onclick="applyCouponPreset('BIENVENIDO10', 'Descuento de primera compra', 'percent', '10', 'min', '0.00', '')">10% Bienvenida</button>
                    <button type="button" class="coupon-preset-chip" onclick="applyCouponPreset('FLASH20', 'Oferta relámpago 20%', 'percent', '20', 'min', '50.00', '')">20% Flash</button>
                    <button type="button" class="coupon-preset-chip" onclick="applyCouponPreset('EXACTO100', 'Bono al comprar exactamente <?= htmlspecialchars($currency) ?>100', 'fixed', '15.00', 'exact', '100.00', '')">Monto exacto <?= htmlspecialchars($currency) ?>100</button>
                </div>
            </div>

            <form id="coupon-form" method="POST" action="<?= $baseUrl ?>/admin/coupons/store">
                <input type="hidden" id="coupon_id" name="coupon_id" value="">

                <div class="form-group">
                    <label for="code" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Código promocional *</span>
                        <button type="button" onclick="generateCouponCode()" style="background: none; border: none; color: #008060; font-size: 11.5px; font-weight: 600; cursor: pointer; padding: 0; text-decoration: underline;">
                            Generar aleatorio
                        </button>
                    </label>
                    <input type="text" id="code" name="code" class="form-control" placeholder="Ej: VERANO2026" required style="text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9_-]/g, ''); updateCouponPreview();">
                </div>

                <div class="form-group">
                    <label for="description">Nombre o nota de campaña (opcional)</label>
                    <input type="text" id="description" name="description" class="form-control" placeholder="Ej: Campaña Cyber / Solo clientes nuevos" oninput="updateCouponPreview()">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="type">Tipo *</label>
                        <select id="type" name="type" class="form-control" onchange="onCouponTypeChange()">
                            <option value="percent">Porcentaje (%)</option>
                            <option value="fixed">Monto Fijo (<?= htmlspecialchars($currency) ?>)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="value" id="value-label">Valor (%) *</label>
                        <input type="number" step="0.01" min="0.01" max="100" id="value" name="value" class="form-control" placeholder="Ej: 15" required oninput="updateCouponPreview()">
                    </div>
                </div>

                <div class="form-group">
                    <label for="spend_rule">Condición de monto en carrito</label>
                    <select id="spend_rule" name="spend_rule" class="form-control" onchange="onSpendRuleChange()">
                        <option value="min">Compra mínima (>=) o cualquier monto</option>
                        <option value="exact">Monto exacto de compra (=)</option>
                        <option value="range">Rango de monto específico (Mín. a Máx.)</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="min_spend" id="min-spend-label">Compra mínima (<?= htmlspecialchars($currency) ?>)</label>
                        <input type="number" step="0.01" id="min_spend" name="min_spend" class="form-control" value="0.00" oninput="updateCouponPreview()">
                        <small id="min-spend-help" style="font-size: 11px; color: var(--color-text-secondary);">0.00 = sin mínimo</small>
                    </div>

                    <div class="form-group" id="max-spend-wrap" style="display: none;">
                        <label for="max_spend">Compra máxima (<?= htmlspecialchars($currency) ?>) *</label>
                        <input type="number" step="0.01" id="max_spend" name="max_spend" class="form-control" placeholder="Ej: 150.00" oninput="updateCouponPreview()">
                        <small style="font-size: 11px; color: var(--color-text-secondary);">Tope del rango</small>
                    </div>

                    <div class="form-group">
                        <label for="usage_limit">Límite global de usos</label>
                        <input type="number" id="usage_limit" name="usage_limit" class="form-control" placeholder="Ilimitado" oninput="updateCouponPreview()">
                        <small style="font-size: 11px; color: var(--color-text-secondary);">Vacío = sin límite</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="expires_at">Fecha de caducidad (opcional)</label>
                    <input type="date" id="expires_at" name="expires_at" class="form-control" oninput="updateCouponPreview()" onchange="updateCouponPreview()">
                    <small style="font-size: 11px; color: var(--color-text-secondary);">Deja vacío si el cupón nunca vence.</small>
                </div>

                <div class="form-group" style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px 12px;">
                    <label style="display: flex; align-items: flex-start; gap: 9px; margin: 0; cursor: pointer; font-weight: 600;">
                        <input type="checkbox" id="single_use_per_user" name="single_use_per_user" value="1" checked onchange="updateCouponPreview()" style="margin-top: 2px; width: 15px; height: 15px; accent-color: #008060;">
                        <span>
                            Limitar a 1 solo canje por usuario
                            <span style="display: block; font-size: 11px; font-weight: 400; color: var(--color-text-secondary); margin-top: 2px;">
                                Impide que el mismo cliente vuelva a canjear este código en pedidos distintos.
                            </span>
                        </span>
                    </label>
                </div>

                <div class="form-group" id="coupon-status-wrap" style="display: none;">
                    <label for="is_active">Estado del Cupón</label>
                    <select id="is_active" name="is_active" class="form-control" style="font-weight: 600;">
                        <option value="1">Activo (Disponible en tienda)</option>
                        <option value="0">Deshabilitado (Borrado lógico)</option>
                    </select>
                </div>

                <button type="submit" style="display:none;" aria-hidden="true"></button>
            </form>

            <!-- BOTÓN CANCELAR EDICIÓN (SOLO EN MODO EDITAR) -->
            <div id="cancel-coupon-edit-wrap" style="display: none; margin-top: 12px; padding-top: 12px; border-top: 1px solid #F3F4F6;">
                <button type="button" onclick="resetCouponForm()" class="btn btn-secondary" style="width: 100%; font-size: 13px;">
                    Cancelar edición y crear nuevo
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const couponBaseUrl = '<?= $baseUrl ?>';
const couponCurrency = '<?= htmlspecialchars(addslashes($currency)) ?>';

function toggleCouponGuide() {
    const guide = document.getElementById('coupon-quick-guide');
    if (!guide) return;
    const isHidden = guide.style.display === 'none';
    guide.style.display = isHidden ? 'block' : 'none';
    try {
        localStorage.setItem('shopcms_hide_coupon_guide', isHidden ? '0' : '1');
    } catch (e) {}
}

function onCouponTypeChange() {
    const typeEl = document.getElementById('type');
    const valInput = document.getElementById('value');
    const valLabel = document.getElementById('value-label');
    if (typeEl.value === 'percent') {
        valLabel.textContent = 'Valor (%) *';
        valInput.setAttribute('max', '100');
        valInput.placeholder = 'Ej: 15';
        if (parseFloat(valInput.value) > 100) {
            valInput.value = '100';
        }
    } else {
        valLabel.textContent = 'Monto (' + couponCurrency + ') *';
        valInput.removeAttribute('max');
        valInput.placeholder = 'Ej: 20.00';
    }
    updateCouponPreview();
}

function onSpendRuleChange() {
    const rule = document.getElementById('spend_rule').value;
    const minLabel = document.getElementById('min-spend-label');
    const minHelp = document.getElementById('min-spend-help');
    const maxWrap = document.getElementById('max-spend-wrap');
    const maxInput = document.getElementById('max_spend');
    const minInput = document.getElementById('min_spend');

    if (rule === 'exact') {
        minLabel.textContent = 'Monto exacto (' + couponCurrency + ') *';
        minHelp.textContent = 'Debe coincidir exactamente con este total';
        minInput.required = true;
        maxWrap.style.display = 'none';
        maxInput.required = false;
    } else if (rule === 'range') {
        minLabel.textContent = 'Desde (' + couponCurrency + ') *';
        minHelp.textContent = 'Mínimo del rango';
        minInput.required = true;
        maxWrap.style.display = 'block';
        maxInput.required = true;
    } else {
        minLabel.textContent = 'Compra mínima (' + couponCurrency + ')';
        minHelp.textContent = '0.00 = sin mínimo';
        minInput.required = false;
        maxWrap.style.display = 'none';
        maxInput.required = false;
    }
    updateCouponPreview();
}

function updateCouponPreview() {
    const code = (document.getElementById('code').value || '').trim() || 'CODIGO10';
    const desc = (document.getElementById('description').value || '').trim();
    const type = document.getElementById('type').value;
    const val = parseFloat(document.getElementById('value').value);
    const spendRule = document.getElementById('spend_rule').value;
    const minSpend = parseFloat(document.getElementById('min_spend').value);
    const maxSpend = parseFloat(document.getElementById('max_spend').value);
    const usageLimit = (document.getElementById('usage_limit').value || '').trim();
    const singleUse = document.getElementById('single_use_per_user').checked;
    const expiresAt = (document.getElementById('expires_at').value || '').trim();

    document.getElementById('preview-code').textContent = code;
    document.getElementById('preview-badge-type').textContent = type === 'percent' ? 'Porcentaje' : 'Monto Fijo';

    if (!isNaN(val) && val > 0) {
        document.getElementById('preview-benefit').textContent = type === 'percent'
            ? (val % 1 === 0 ? val.toFixed(0) : val.toFixed(2)) + '% DCTO'
            : '-' + couponCurrency + val.toFixed(2);
    } else {
        document.getElementById('preview-benefit').textContent = type === 'percent' ? '0% DCTO' : '-' + couponCurrency + '0.00';
    }

    const parts = [];
    if (desc) parts.push(desc);
    if (spendRule === 'exact') {
        parts.push('Solo monto exacto ' + couponCurrency + (!isNaN(minSpend) ? minSpend.toFixed(2) : '0.00'));
    } else if (spendRule === 'range') {
        const minStr = !isNaN(minSpend) ? minSpend.toFixed(2) : '0.00';
        const maxStr = !isNaN(maxSpend) ? maxSpend.toFixed(2) : '0.00';
        parts.push('Compras entre ' + couponCurrency + minStr + ' y ' + couponCurrency + maxStr);
    } else {
        parts.push((!isNaN(minSpend) && minSpend > 0) ? 'Compra mín. ' + couponCurrency + minSpend.toFixed(2) : 'Sin compra mínima');
    }
    parts.push(singleUse ? '1 canje por usuario' : 'Reutilizable por usuario');
    parts.push(usageLimit && parseInt(usageLimit, 10) > 0 ? 'Máx. ' + parseInt(usageLimit, 10) + ' usos' : 'Usos ilimitados');
    if (expiresAt) {
        const dParts = expiresAt.split('-');
        parts.push(dParts.length === 3 ? 'Vence ' + dParts[2] + '/' + dParts[1] + '/' + dParts[0] : 'Vence ' + expiresAt);
    } else {
        parts.push('Sin vencimiento');
    }
    document.getElementById('preview-conditions').textContent = parts.join(' · ');
}

function generateCouponCode() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let suffix = '';
    for (let i = 0; i < 4; i++) {
        suffix += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    const val = parseFloat(document.getElementById('value').value);
    const prefix = (!isNaN(val) && val > 0) ? 'PROMO' + Math.round(val) + '-' : 'PROMO-';
    const codeInput = document.getElementById('code');
    codeInput.value = prefix + suffix;
    updateCouponPreview();
    codeInput.dispatchEvent(new Event('input', { bubbles: true }));
}

function applyCouponPreset(code, desc, type, value, spendRule, minSpend, maxSpend) {
    resetCouponForm();
    document.getElementById('code').value = code;
    document.getElementById('description').value = desc;
    document.getElementById('type').value = type;
    document.getElementById('value').value = value;
    document.getElementById('spend_rule').value = spendRule || 'min';
    document.getElementById('min_spend').value = minSpend;
    document.getElementById('max_spend').value = maxSpend || '';
    document.getElementById('single_use_per_user').checked = true;
    onCouponTypeChange();
    onSpendRuleChange();
    const form = document.getElementById('coupon-form');
    if (window.ShopCMS && window.ShopCMS.markDirty) {
        window.ShopCMS.markDirty(form);
    }
    document.getElementById('code').focus();
}

function populateCouponFields(c, isEdit) {
    document.getElementById('code').value = isEdit ? (c.code || '') : ((c.code || 'PROMO') + '-2');
    document.getElementById('description').value = c.description || '';
    document.getElementById('type').value = c.type || 'percent';
    document.getElementById('value').value = c.value || '';
    document.getElementById('spend_rule').value = c.spend_rule || 'min';
    document.getElementById('min_spend').value = c.min_spend !== null && c.min_spend !== undefined ? parseFloat(c.min_spend).toFixed(2) : '0.00';
    document.getElementById('max_spend').value = c.max_spend ? parseFloat(c.max_spend).toFixed(2) : '';
    document.getElementById('usage_limit').value = c.usage_limit ? c.usage_limit : '';
    document.getElementById('expires_at').value = c.expires_at ? c.expires_at : '';
    document.getElementById('single_use_per_user').checked = (c.single_use_per_user === undefined || parseInt(c.single_use_per_user, 10) !== 0);
}

function openNewCoupon() {
    resetCouponForm();
    document.getElementById('code').focus();
    document.getElementById('coupon-form-card').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function editCoupon(c) {
    const form = document.getElementById('coupon-form');
    document.getElementById('coupon-form-title').textContent = 'Editar Cupón';
    document.getElementById('coupon-form-subtitle').textContent = 'Editando código: ' + c.code;
    document.getElementById('coupon-mode-icon').style.background = '#EFF6FF';
    document.getElementById('coupon-mode-svg').setAttribute('stroke', '#2563EB');

    document.getElementById('coupon_id').value = c.id;
    populateCouponFields(c, true);
    document.getElementById('is_active').value = (c.is_active !== undefined && parseInt(c.is_active, 10) === 0) ? '0' : '1';

    form.action = couponBaseUrl + '/admin/coupons/update/' + c.id;
    document.getElementById('coupon-presets-wrap').style.display = 'none';
    document.getElementById('coupon-status-wrap').style.display = 'block';
    document.getElementById('cancel-coupon-edit-wrap').style.display = 'block';

    onCouponTypeChange();
    onSpendRuleChange();
    if (window.ShopCMS && window.ShopCMS.syncFormInitialState) {
        window.ShopCMS.syncFormInitialState(form);
    }
    document.getElementById('code').focus();
    document.getElementById('coupon-form-card').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function duplicateCoupon(c) {
    resetCouponForm();
    populateCouponFields(c, false);
    onCouponTypeChange();
    onSpendRuleChange();
    const form = document.getElementById('coupon-form');
    if (window.ShopCMS && window.ShopCMS.markDirty) {
        window.ShopCMS.markDirty(form);
    }
    document.getElementById('code').focus();
    document.getElementById('coupon-form-card').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function resetCouponForm() {
    const form = document.getElementById('coupon-form');
    document.getElementById('coupon-form-title').textContent = 'Nuevo Cupón';
    document.getElementById('coupon-form-subtitle').textContent = 'Configura tu promoción y guarda arriba';
    document.getElementById('coupon-mode-icon').style.background = '#ECFDF5';
    document.getElementById('coupon-mode-svg').setAttribute('stroke', '#059669');

    document.getElementById('coupon_id').value = '';
    form.reset();
    document.getElementById('spend_rule').value = 'min';
    document.getElementById('min_spend').value = '0.00';
    document.getElementById('max_spend').value = '';
    document.getElementById('single_use_per_user').checked = true;
    document.getElementById('is_active').value = '1';
    form.action = couponBaseUrl + '/admin/coupons/store';

    document.getElementById('coupon-presets-wrap').style.display = 'block';
    document.getElementById('coupon-status-wrap').style.display = 'none';
    document.getElementById('cancel-coupon-edit-wrap').style.display = 'none';

    onCouponTypeChange();
    onSpendRuleChange();
    if (window.ShopCMS && window.ShopCMS.syncFormInitialState) {
        window.ShopCMS.syncFormInitialState(form);
    } else if (window.ShopCMS && window.ShopCMS.markClean) {
        window.ShopCMS.markClean();
    }
}

function copyCouponCode(code, btn) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code);
    } else {
        const temp = document.createElement('input');
        temp.value = code;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
    }
    const label = btn.querySelector('.copy-label');
    if (label) {
        const prev = label.textContent;
        label.textContent = 'Copiado';
        btn.style.color = '#008060';
        btn.style.borderColor = '#008060';
        setTimeout(function() {
            label.textContent = prev;
            btn.style.color = '#6B7280';
            btn.style.borderColor = '#E5E7EB';
        }, 1400);
    }
}

window.onShopCMSDiscard = function(form) {
    if (form && form.id === 'coupon-form') {
        onCouponTypeChange();
        onSpendRuleChange();
    }
};

document.addEventListener('DOMContentLoaded', function() {
    try {
        if (localStorage.getItem('shopcms_hide_coupon_guide') === '1') {
            const guide = document.getElementById('coupon-quick-guide');
            if (guide) guide.style.display = 'none';
        }
    } catch (e) {}
    updateCouponPreview();
});
</script>
