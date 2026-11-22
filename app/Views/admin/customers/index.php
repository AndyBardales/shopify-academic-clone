<?php $currency = $currency ?? '$'; ?>

<div class="page-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 class="page-title">Clientes</h1>
        <p class="page-subtitle">Consulta tus compradores, edita su información de contacto o deshabilita cuentas sin perder el historial de pedidos.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button type="button" id="btn-toggle-guide" onclick="toggleCustomerGuide()" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span>¿Cómo usar?</span>
        </button>
        <button type="button" onclick="toggleNewCustomerCard()" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Nuevo Cliente</span>
        </button>
    </div>
</div>

<!-- GUÍA RÁPIDA INTUITIVA (3 PASOS CLAROS) -->
<div id="customer-quick-guide" class="polaris-card" style="background: #F9FAFB; border-color: #E5E7EB; margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <strong style="font-size: 13px; color: #111827;">Guía rápida del módulo de Clientes</strong>
            </div>
            <button type="button" onclick="toggleCustomerGuide()" style="background: none; border: none; color: #6B7280; font-size: 12px; cursor: pointer; text-decoration: underline;">Ocultar</button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">1</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Registro automático o manual</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Quien compra o deja una reseña aparece aquí solo. También puedes añadir uno con <strong>Nuevo Cliente</strong>.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #EBF5FA; color: #006FAD; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">2</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Edita datos y revisa pedidos</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Pulsa <strong>Editar</strong> en cualquier fila para actualizar nombre, correo, teléfono, dirección y ver sus compras.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #FFF5EA; color: #B95000; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">3</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Deshabilitar (Borrado Lógico)</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Usa <strong>Deshabilitar</strong> para bloquear el acceso del cliente sin borrar sus pedidos ni historial contable.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FORMULARIO DESPLEGABLE: NUEVO CLIENTE -->
<div id="new-customer-card" class="polaris-card" style="display: none; border-color: #008060;">
    <div class="card-header" style="background: #F6FBF9;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 30px; height: 30px; border-radius: 8px; background: #E8F5E9; color: #008060; display: flex; align-items: center; justify-content: center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <div>
                <h2 class="card-title" style="margin: 0;">Registrar Nuevo Cliente</h2>
                <p style="font-size: 12px; color: var(--color-text-secondary); margin: 2px 0 0;">Al empezar a escribir aparecerá arriba la barra para Guardar o Descartar.</p>
            </div>
        </div>
        <button type="button" onclick="closeNewCustomerCard()" class="btn btn-secondary btn-sm" title="Cerrar formulario" style="padding: 5px 8px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <div class="card-body">
        <form id="new-customer-form" method="POST" action="<?= $baseUrl ?>/admin/customers/store">
            <div class="form-row">
                <div class="form-group">
                    <label for="new_cust_name">Nombre completo *</label>
                    <input type="text" id="new_cust_name" name="name" class="form-control" placeholder="Ej: María Fernanda López" required>
                </div>
                <div class="form-group">
                    <label for="new_cust_email">Correo electrónico *</label>
                    <input type="email" id="new_cust_email" name="email" class="form-control" placeholder="cliente@ejemplo.com" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="new_cust_phone">Teléfono / WhatsApp (opcional)</label>
                    <input type="text" id="new_cust_phone" name="phone" class="form-control" placeholder="Ej: +51 987 654 321">
                </div>
                <div class="form-group">
                    <label for="new_cust_password">Contraseña de acceso web (opcional)</label>
                    <input type="password" id="new_cust_password" name="password" class="form-control" placeholder="Déjalo vacío si el cliente la creará luego">
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="new_cust_address">Dirección de entrega predeterminada (opcional)</label>
                <input type="text" id="new_cust_address" name="address" class="form-control" placeholder="Ej: Av. Principal 450, Depto 3B, Lima">
            </div>
            <button type="submit" style="display:none;" aria-hidden="true"></button>
        </form>
    </div>
</div>

<!-- MÉTRICAS PRINCIPALES -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-title" style="display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <span>Clientes Registrados</span>
        </div>
        <div class="metric-value"><?= number_format($totalCustomers) ?></div>
        <div class="metric-sub"><?= (int)($totalActive ?? $totalCustomers) ?> activos · <?= (int)($totalDisabled ?? 0) ?> deshabilitados</div>
    </div>

    <div class="metric-card">
        <div class="metric-title" style="display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#006FAD" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <span>Pedidos Acumulados</span>
        </div>
        <div class="metric-value"><?= number_format($totalOrdersSum) ?></div>
        <div class="metric-sub">Compras registradas por clientes</div>
    </div>

    <div class="metric-card">
        <div class="metric-title" style="display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            <span>Ventas Totales de Clientes</span>
        </div>
        <div class="metric-value" style="color: #008060;"><?= htmlspecialchars($currency) ?><?= number_format($totalSpentSum, 2) ?></div>
        <div class="metric-sub">Valor acumulado histórico (LTV)</div>
    </div>
</div>

<!-- DIRECTORIO DE CLIENTES -->
<div class="polaris-card">
    <div class="card-header" style="flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="<?= $baseUrl ?>/admin/customers<?= !empty($search) ? '?q=' . urlencode($search) : '' ?>" class="btn <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Todos (<?= (int)($totalActive ?? 0) + (int)($totalDisabled ?? 0) ?>)
            </a>
            <a href="<?= $baseUrl ?>/admin/customers?status=active<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="btn <?= ($statusFilter ?? '') === 'active' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Activos (<?= (int)($totalActive ?? 0) ?>)
            </a>
            <a href="<?= $baseUrl ?>/admin/customers?status=disabled<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="btn <?= ($statusFilter ?? '') === 'disabled' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Deshabilitados (<?= (int)($totalDisabled ?? 0) ?>)
            </a>
        </div>

        <form method="GET" action="<?= $baseUrl ?>/admin/customers" style="display: flex; gap: 8px; width: 100%; max-width: 380px;">
            <?php if (!empty($statusFilter)): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Buscar por nombre, correo o teléfono..." class="form-control">
            <button type="submit" class="btn btn-secondary btn-sm" style="flex-shrink: 0;">Buscar</button>
            <?php if (!empty($search)): ?>
                <a href="<?= $baseUrl ?>/admin/customers<?= !empty($statusFilter) ? '?status=' . urlencode($statusFilter) : '' ?>" class="btn btn-secondary btn-sm" style="flex-shrink: 0;">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($customers)): ?>
        <div style="padding: 56px 20px; text-align: center; color: var(--color-text-secondary);">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 12px;">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <p style="font-size: 15px; margin: 0 0 6px; font-weight: 600; color: var(--color-text-primary);">No se encontraron clientes</p>
            <p style="font-size: 13px; margin: 0 0 16px;">Los clientes aparecen aquí cuando se registran o realizan un pedido, o puedes crear uno manualmente.</p>
            <?php if (!empty($search) || !empty($statusFilter)): ?>
                <a href="<?= $baseUrl ?>/admin/customers" class="btn btn-secondary btn-sm">Ver todos los clientes</a>
            <?php else: ?>
                <button type="button" onclick="toggleNewCustomerCard()" class="btn btn-primary btn-sm">Registrar primer cliente</button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="polaris-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th style="text-align: center;">Pedidos</th>
                        <th style="text-align: right;">Total Gastado</th>
                        <th>Estado</th>
                        <th style="text-align: right; min-width: 210px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                        <?php $isActive = !isset($c['is_active']) || !empty($c['is_active']); ?>
                        <tr style="<?= !$isActive ? 'background: #FAFBFB; opacity: 0.85;' : '' ?>">
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 38px; height: 38px; border-radius: 50%; background: <?= $isActive ? '#E8F5E9' : '#E5E7EB' ?>; color: <?= $isActive ? '#008060' : '#6B7280' ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0;">
                                        <?= strtoupper(substr(trim($c['name'] ?: 'C'), 0, 1)) ?>
                                    </div>
                                    <div>
                                        <a href="<?= $baseUrl ?>/admin/customers/view/<?= $c['id'] ?>" style="font-weight: 700; color: #111827; text-decoration: none; font-size: 13.5px; display: block;">
                                            <?= htmlspecialchars($c['name']) ?>
                                        </a>
                                        <div style="font-size: 12px; color: var(--color-text-secondary);"><?= htmlspecialchars($c['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 13px; color: #111827;">
                                    <?= $c['phone'] ? htmlspecialchars($c['phone']) : '<span style="color:#9CA3AF;">Sin teléfono</span>' ?>
                                </div>
                                <div style="font-size: 11.5px; color: var(--color-text-secondary); max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= $c['address'] ? htmlspecialchars($c['address']) : '<span style="color:#9CA3AF;">Sin dirección registrada</span>' ?>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge badge-info" style="font-size: 11.5px; padding: 3px 9px;">
                                    <?= (int)$c['total_orders'] ?> <?= (int)$c['total_orders'] === 1 ? 'pedido' : 'pedidos' ?>
                                </span>
                                <?php if (!empty($c['last_order_date'])): ?>
                                    <div style="font-size: 11px; color: var(--color-text-muted); margin-top: 3px;">
                                        Último: <?= date('d/m/Y', strtotime($c['last_order_date'])) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: <?= $isActive ? '#008060' : '#6B7280' ?>; font-size: 13.5px;">
                                <?= htmlspecialchars($currency) ?><?= number_format((float)$c['total_spent'], 2) ?>
                            </td>
                            <td>
                                <?php if ($isActive): ?>
                                    <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 5px;">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                                        Activo
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background: #E5E7EB; color: #4B5563; display: inline-flex; align-items: center; gap: 5px;">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                                        Deshabilitado
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <a href="<?= $baseUrl ?>/admin/customers/view/<?= $c['id'] ?>" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 5px; font-weight: 600;" title="Editar datos y ver historial del cliente">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        <span>Editar</span>
                                    </a>
                                    <?php if ($isActive): ?>
                                        <a href="<?= $baseUrl ?>/admin/customers/toggle/<?= $c['id'] ?>"
                                           onclick="return confirm('¿Deshabilitar la cuenta de <?= htmlspecialchars(addslashes($c['name'])) ?>?\n\nEl cliente no podrá iniciar sesión ni realizar compras en la tienda, pero se conservará todo su historial de pedidos (borrado lógico).');"
                                           class="btn btn-secondary btn-sm"
                                           style="display: inline-flex; align-items: center; gap: 5px; color: #B95000;"
                                           title="Deshabilitar cliente (borrado lógico)">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                                <line x1="1" y1="1" x2="23" y2="23"></line>
                                            </svg>
                                            <span>Deshabilitar</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= $baseUrl ?>/admin/customers/toggle/<?= $c['id'] ?>"
                                           class="btn btn-secondary btn-sm"
                                           style="display: inline-flex; align-items: center; gap: 5px; color: #008060; border-color: #008060;"
                                           title="Reactivar acceso del cliente">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                            <span>Activar</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleCustomerGuide() {
    const guide = document.getElementById('customer-quick-guide');
    if (!guide) return;
    const isHidden = guide.style.display === 'none';
    guide.style.display = isHidden ? 'block' : 'none';
    try {
        localStorage.setItem('shopcms_hide_cust_guide', isHidden ? '0' : '1');
    } catch (e) {}
}

function toggleNewCustomerCard() {
    const card = document.getElementById('new-customer-card');
    if (!card) return;
    if (card.style.display === 'none') {
        card.style.display = 'block';
        document.getElementById('new_cust_name').focus();
    } else {
        closeNewCustomerCard();
    }
}

function closeNewCustomerCard() {
    const card = document.getElementById('new-customer-card');
    const form = document.getElementById('new-customer-form');
    if (form) form.reset();
    if (card) card.style.display = 'none';
    if (window.ShopCMS && window.ShopCMS.markClean) {
        window.ShopCMS.markClean();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        if (localStorage.getItem('shopcms_hide_cust_guide') === '1') {
            const guide = document.getElementById('customer-quick-guide');
            if (guide) guide.style.display = 'none';
        }
    } catch (e) {}
});
</script>
