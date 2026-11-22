<?php
$currency = $currency ?? '$';
$isActive = !isset($customer['is_active']) || !empty($customer['is_active']);
$hasWebPassword = !empty($customer['password']);
$lastOrderDate = !empty($orders) ? $orders[0]['created_at'] : null;

$orderStatusLabels = [
    'pending'    => ['label' => 'Pendiente',  'badge' => 'badge-warning'],
    'processing' => ['label' => 'En proceso', 'badge' => 'badge-info'],
    'shipped'    => ['label' => 'Enviado',    'badge' => 'badge-info'],
    'completed'  => ['label' => 'Completado', 'badge' => 'badge-success'],
    'cancelled'  => ['label' => 'Cancelado',  'badge' => 'badge-danger'],
];

$reviewStatusLabels = [
    'approved' => ['label' => 'Aprobada',      'badge' => 'badge-success'],
    'pending'  => ['label' => 'Pendiente',     'badge' => 'badge-warning'],
    'rejected' => ['label' => 'Rechazada',     'badge' => 'badge-danger'],
    'hidden'   => ['label' => 'Deshabilitada', 'badge' => 'badge-draft'],
];
?>

<style>
.customer-split-grid {
    display: grid;
    grid-template-columns: 1.55fr 1fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 920px) {
    .customer-split-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- CABECERA DEL PERFIL DE CLIENTE -->
<div class="page-header" style="flex-wrap: wrap; gap: 14px;">
    <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
        <a href="<?= $baseUrl ?>/admin/customers" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <span>Volver a Clientes</span>
        </a>

        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 50%; background: <?= $isActive ? '#E8F5E9' : '#E5E7EB' ?>; color: <?= $isActive ? '#008060' : '#6B7280' ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; flex-shrink: 0;">
                <?= strtoupper(substr(trim($customer['name'] ?: 'C'), 0, 1)) ?>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <h1 class="page-title" style="margin: 0;"><?= htmlspecialchars($customer['name']) ?></h1>
                    <?php if ($isActive): ?>
                        <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 5px;">
                            <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                            Cuenta Activa
                        </span>
                    <?php else: ?>
                        <span class="badge" style="background: #E5E7EB; color: #4B5563; display: inline-flex; align-items: center; gap: 5px;">
                            <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                            Cuenta Deshabilitada
                        </span>
                    <?php endif; ?>
                </div>
                <p class="page-subtitle" style="margin: 2px 0 0;">
                    <?= htmlspecialchars($customer['email']) ?> · Registrado el <?= date('d/m/Y', strtotime($customer['created_at'])) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- ACCIÓN RÁPIDA DE ESTADO -->
    <div>
        <?php if ($isActive): ?>
            <a href="<?= $baseUrl ?>/admin/customers/toggle/<?= $customer['id'] ?>?redirect=view"
               onclick="return confirm('¿Deshabilitar la cuenta de <?= htmlspecialchars(addslashes($customer['name'])) ?>?\n\nNo podrá iniciar sesión ni comprar en la tienda, pero todo su historial de pedidos permanecerá intacto (borrado lógico).');"
               class="btn btn-secondary btn-sm"
               style="display: inline-flex; align-items: center; gap: 6px; color: #B95000;"
               title="Deshabilitar acceso del cliente">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>
                <span>Deshabilitar Cliente</span>
            </a>
        <?php else: ?>
            <a href="<?= $baseUrl ?>/admin/customers/toggle/<?= $customer['id'] ?>?redirect=view"
               class="btn btn-primary btn-sm"
               style="display: inline-flex; align-items: center; gap: 6px;"
               title="Reactivar acceso del cliente">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Reactivar Cliente</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$isActive): ?>
    <div style="background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 10px; font-size: 13px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#B45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span><strong>Cliente deshabilitado (borrado lógico):</strong> su acceso e inicio de sesión están bloqueados en la tienda, pero su historial de compras sigue disponible.</span>
        </div>
        <a href="<?= $baseUrl ?>/admin/customers/toggle/<?= $customer['id'] ?>?redirect=view" class="btn btn-secondary btn-sm" style="color: #008060; border-color: #008060; flex-shrink: 0;">
            Activar ahora
        </a>
    </div>
<?php endif; ?>

<!-- MÉTRICAS DEL CLIENTE -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-title">Total de Pedidos</div>
        <div class="metric-value"><?= count($orders) ?></div>
        <div class="metric-sub">Órdenes asociadas a este cliente</div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Total Gastado</div>
        <div class="metric-value" style="color: #008060;"><?= htmlspecialchars($currency) ?><?= number_format((float)$totalSpent, 2) ?></div>
        <div class="metric-sub">Suma histórica de compras</div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Ticket Promedio</div>
        <div class="metric-value"><?= htmlspecialchars($currency) ?><?= count($orders) > 0 ? number_format((float)$totalSpent / count($orders), 2) : '0.00' ?></div>
        <div class="metric-sub">Gasto medio por pedido</div>
    </div>
</div>

<!-- FORMULARIO EDITABLE: DATOS DE CONTACTO + ESTADO DE CUENTA -->
<form id="customer-edit-form" method="POST" action="<?= $baseUrl ?>/admin/customers/update/<?= $customer['id'] ?>">
    <div class="customer-split-grid" style="margin-bottom: 20px;">
        <!-- IZQUIERDA: DATOS EDITABLES -->
        <div class="polaris-card" id="datos-cliente" style="margin-bottom: 0;">
            <div class="card-header" style="flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    <h2 class="card-title">Información de Contacto (Editable)</h2>
                </div>
                <span style="font-size: 11.5px; color: var(--color-text-secondary);">Edita cualquier campo y guarda arriba</span>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="cust_name">Nombre completo *</label>
                        <input type="text" id="cust_name" name="name" class="form-control" value="<?= htmlspecialchars($customer['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="cust_email">Correo electrónico *</label>
                        <input type="email" id="cust_email" name="email" class="form-control" value="<?= htmlspecialchars($customer['email']) ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cust_phone">Teléfono / WhatsApp</label>
                        <input type="text" id="cust_phone" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" placeholder="Ej: +51 987 654 321">
                    </div>
                    <div class="form-group">
                        <label for="cust_address">Dirección predeterminada</label>
                        <input type="text" id="cust_address" name="address" class="form-control" value="<?= htmlspecialchars($customer['address'] ?? '') ?>" placeholder="Calle, número, distrito / ciudad">
                    </div>
                </div>

                <div style="font-size: 12px; color: var(--color-text-secondary); background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 6px; padding: 10px 12px;">
                    Al modificar un dato se activará automáticamente la barra superior con los botones <strong>Descartar</strong> y <strong>Guardar</strong>. Si cambias el correo, sus pedidos y reseñas se mantendrán vinculados.
                </div>
            </div>
        </div>

        <!-- DERECHA: ESTADO DE LA CUENTA (BORRADO LÓGICO) -->
        <div class="polaris-card" style="margin-bottom: 0;">
            <div class="card-header">
                <h2 class="card-title">Estado de la Cuenta</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="is_active">Acceso en Tienda Web</label>
                    <select id="is_active" name="is_active" class="form-control" style="font-weight: 600;">
                        <option value="1" <?= $isActive ? 'selected' : '' ?>>Activo (Puede iniciar sesión y comprar)</option>
                        <option value="0" <?= !$isActive ? 'selected' : '' ?>>Deshabilitado (Borrado lógico)</option>
                    </select>
                </div>

                <div style="font-size: 12px; color: var(--color-text-secondary); line-height: 1.5; background: #F9FAFB; padding: 12px; border-radius: 6px; border: 1px solid #E5E7EB;">
                    <strong>Borrado Lógico:</strong> deshabilitar un cliente impide que acceda a su cuenta o realice nuevos pedidos, pero conserva íntegramente sus compras, facturas y reseñas pasadas. Puedes reactivarlo aquí en cualquier momento.
                </div>
            </div>
        </div>
    </div>

    <button type="submit" style="display:none;" aria-hidden="true"></button>
</form>

<!-- SECCIÓN INFERIOR: HISTORIAL DE PEDIDOS, RESEÑAS Y GUÍA DE USO -->
<div class="customer-split-grid">
    <!-- IZQUIERDA: PEDIDOS Y RESEÑAS -->
    <div>
        <!-- PEDIDOS DEL CLIENTE -->
        <div class="polaris-card">
            <div class="card-header">
                <h2 class="card-title">Historial de Pedidos (<?= count($orders) ?>)</h2>
                <span style="font-size: 13px; font-weight: 700; color: #008060;">Total: <?= htmlspecialchars($currency) ?><?= number_format((float)$totalSpent, 2) ?></span>
            </div>

            <?php if (empty($orders)): ?>
                <div class="card-body" style="padding: 36px 20px; text-align: center; color: var(--color-text-secondary); font-size: 13px;">
                    Este cliente aún no registra pedidos en la tienda.
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="polaris-table">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Pago</th>
                                <th style="text-align: right;">Total</th>
                                <th style="text-align: right;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $ord): ?>
                                <?php
                                $stInfo = $orderStatusLabels[$ord['status']] ?? ['label' => ucfirst($ord['status']), 'badge' => 'badge-draft'];
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?= $baseUrl ?>/admin/orders/view/<?= $ord['id'] ?>" style="font-weight: 700; color: #008060; text-decoration: none;">
                                            <?= htmlspecialchars($ord['order_number']) ?>
                                        </a>
                                    </td>
                                    <td style="font-size: 12px; color: var(--color-text-secondary);">
                                        <?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $stInfo['badge'] ?>" style="font-size: 11px;">
                                            <?= $stInfo['label'] ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 12px;">
                                        <?= strtoupper(htmlspecialchars($ord['payment_method'])) ?>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; font-size: 13px;">
                                        <?= htmlspecialchars($currency) ?><?= number_format((float)$ord['total'], 2) ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="<?= $baseUrl ?>/admin/orders/view/<?= $ord['id'] ?>" class="btn btn-secondary btn-sm" style="font-size: 11.5px; padding: 4px 10px;">
                                            Ver Orden
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- RESEÑAS HECHAS POR EL CLIENTE -->
        <div class="polaris-card">
            <div class="card-header">
                <h2 class="card-title">Reseñas y Valoraciones (<?= count($reviews) ?>)</h2>
            </div>

            <?php if (empty($reviews)): ?>
                <div class="card-body" style="padding: 30px 20px; text-align: center; color: var(--color-text-secondary); font-size: 13px;">
                    El cliente no ha publicado reseñas todavía.
                </div>
            <?php else: ?>
                <div class="card-body">
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($reviews as $rev): ?>
                            <?php
                            $revInfo = $reviewStatusLabels[$rev['status']] ?? ['label' => ucfirst($rev['status']), 'badge' => 'badge-draft'];
                            ?>
                            <div style="border: 1px solid var(--color-border); border-radius: 8px; padding: 14px; background: #FAFBFB;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; gap: 8px;">
                                    <div>
                                        <strong style="font-size: 13px; color: var(--color-text-primary);"><?= htmlspecialchars($rev['product_name']) ?></strong>
                                        <span style="font-size: 11px; color: var(--color-text-secondary); margin-left: 8px;"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></span>
                                    </div>
                                    <span class="badge <?= $revInfo['badge'] ?>" style="font-size: 11px;">
                                        <?= $revInfo['label'] ?>
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 2px; margin-bottom: 6px;">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="<?= $s <= $rev['rating'] ? '#F59E0B' : '#E5E7EB' ?>" stroke="<?= $s <= $rev['rating'] ? '#F59E0B' : '#D1D5DB' ?>" stroke-width="1.5">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                        </svg>
                                    <?php endfor; ?>
                                    <span style="font-size: 12px; font-weight: 600; margin-left: 6px;"><?= htmlspecialchars($rev['title'] ?: 'Opinión') ?></span>
                                </div>
                                <p style="font-size: 13px; color: var(--color-text-secondary); margin: 0; line-height: 1.5;"><?= htmlspecialchars($rev['comment']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- DERECHA: RESUMEN DE CUENTA Y GUÍA RÁPIDA -->
    <div>
        <div class="polaris-card">
            <div class="card-header">
                <h2 class="card-title">Resumen de Cuenta</h2>
            </div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; margin-bottom: 10px; border-bottom: 1px solid #F3F4F6; font-size: 13px;">
                    <span style="color: var(--color-text-secondary);">ID de Cliente</span>
                    <code>#<?= (int)$customer['id'] ?></code>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; margin-bottom: 10px; border-bottom: 1px solid #F3F4F6; font-size: 13px;">
                    <span style="color: var(--color-text-secondary);">Tipo de Registro</span>
                    <?php if ($hasWebPassword): ?>
                        <span class="badge badge-info" style="font-size: 11px;">Cuenta Web Activa</span>
                    <?php else: ?>
                        <span class="badge badge-draft" style="font-size: 11px;">Sin Contraseña Web</span>
                    <?php endif; ?>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; margin-bottom: 10px; border-bottom: 1px solid #F3F4F6; font-size: 13px;">
                    <span style="color: var(--color-text-secondary);">Fecha de Registro</span>
                    <strong><?= date('d/m/Y', strtotime($customer['created_at'])) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                    <span style="color: var(--color-text-secondary);">Última Compra</span>
                    <strong><?= $lastOrderDate ? date('d/m/Y', strtotime($lastOrderDate)) : 'Sin pedidos' ?></strong>
                </div>
            </div>
        </div>

        <div class="polaris-card" style="background: #F9FAFB;">
            <div class="card-header">
                <h2 class="card-title">¿Cómo gestionar esta ficha?</h2>
            </div>
            <div class="card-body" style="font-size: 12.5px; color: #4B5563; line-height: 1.55;">
                <div style="display: flex; gap: 10px; margin-bottom: 12px; align-items: flex-start;">
                    <span style="width: 20px; height: 20px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 11px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">1</span>
                    <div><strong>Editar datos:</strong> cambia el nombre, correo, teléfono o dirección en el formulario superior y confirma en el botón <strong>Guardar</strong> de la barra flotante.</div>
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 12px; align-items: flex-start;">
                    <span style="width: 20px; height: 20px; border-radius: 50%; background: #FFF5EA; color: #B95000; font-weight: 700; font-size: 11px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">2</span>
                    <div><strong>Cambiar estado:</strong> elige <em>Deshabilitado</em> o usa el botón superior para suspender el acceso del cliente sin perder pedidos.</div>
                </div>
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <span style="width: 20px; height: 20px; border-radius: 50%; background: #EBF5FA; color: #006FAD; font-weight: 700; font-size: 11px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">3</span>
                    <div><strong>Ver compras:</strong> pulsa el código de cualquier pedido (ej. <code>#ORD-1001</code>) para ver sus artículos o cambiar el estado de envío.</div>
                </div>
            </div>
        </div>
    </div>
</div>
