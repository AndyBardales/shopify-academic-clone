<div class="page-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 class="page-title">Productos</h1>
        <p class="page-subtitle">Gestiona tu catálogo de artículos, variantes, precios, inventario y visibilidad en tienda.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button type="button" onclick="toggleProductGuide()" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span>¿Cómo usar?</span>
        </button>
        <a href="<?= $baseUrl ?>/admin/products/create" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Agregar Producto</span>
        </a>
    </div>
</div>

<!-- GUÍA RÁPIDA EN 3 PASOS -->
<div id="product-quick-guide" class="polaris-card" style="background: #F9FAFB; border-color: #E5E7EB; margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <strong style="font-size: 13px; color: #111827;">Guía rápida para gestionar tus productos</strong>
            </div>
            <button type="button" onclick="toggleProductGuide()" style="background: none; border: none; color: #6B7280; font-size: 12px; cursor: pointer; text-decoration: underline;">Ocultar</button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">1</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Crea producto simple o con variantes</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Usa <strong>Agregar Producto</strong> para cargar nombre, categoría, imágenes, precio y variantes (talla o color) con SKU único.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">2</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Controla stock y alertas visuales</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Revisa el stock por producto o variante desde aquí; cuando necesites reponer lotes o ajustar Kardex, usa el módulo de Inventario.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">3</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Deshabilita sin perder historial</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;"><strong>Deshabilitar</strong> oculta el artículo en la tienda conservando sus pedidos y reseñas; usa <strong>Vista previa</strong> para verificarlo en vivo.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="polaris-card">
    <div class="card-header" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
        <div style="display: flex; gap: 8px;">
            <a href="<?= $baseUrl ?>/admin/products" class="btn <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Todos (<?= ($totalActive ?? 0) + ($totalDisabled ?? 0) ?>)
            </a>
            <a href="<?= $baseUrl ?>/admin/products?status=active" class="btn <?= $statusFilter === 'active' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Activos (<?= $totalActive ?? 0 ?>)
            </a>
            <a href="<?= $baseUrl ?>/admin/products?status=disabled" class="btn <?= $statusFilter === 'disabled' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                Deshabilitados (<?= $totalDisabled ?? 0 ?>)
            </a>
        </div>

        <form method="GET" action="<?= $baseUrl ?>/admin/products" style="display: flex; gap: 10px; width: 100%; max-width: 380px;">
            <input type="hidden" name="route" value="admin/products">
            <?php if (!empty($statusFilter)): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <?php endif; ?>
            <input type="text" name="q" class="form-control" placeholder="Buscar por nombre o SKU..." value="<?= htmlspecialchars($search ?? '') ?>">
            <button type="submit" class="btn btn-secondary">Buscar</button>
            <?php if (!empty($search)): ?>
                <a href="<?= $baseUrl ?>/admin/products<?= !empty($statusFilter) ? '?status=' . $statusFilter : '' ?>" class="btn btn-secondary">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <div style="overflow-x: auto;">
        <table class="polaris-table">
            <thead>
                <tr>
                    <th style="width: 55px;">Imagen</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>SKU</th>
                    <th>Precio</th>
                    <th>Inventario</th>
                    <th>Variantes</th>
                    <th>Estado</th>
                    <th style="text-align: right; min-width: 230px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px 20px; color: var(--color-text-muted);">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="8" y1="12" x2="16" y2="12"></line>
                                </svg>
                                <span>No se encontraron productos con los criterios especificados.</span>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr style="<?= empty($p['is_active']) ? 'background: #FAFBFB;' : '' ?>">
                            <td>
                                <div style="width: 44px; height: 44px; border-radius: 6px; background: #F3F4F6; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid #E5E7EB;">
                                    <?php if (!empty($p['featured_image'])): ?>
                                        <img src="<?= $baseUrl ?>/<?= htmlspecialchars($p['featured_image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                            <polyline points="21 15 16 10 5 21"></polyline>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <a href="<?= $baseUrl ?>/admin/products/edit/<?= $p['id'] ?>" style="font-weight: 700; color: #111827; text-decoration: none;">
                                    <?= htmlspecialchars($p['name']) ?>
                                </a>
                                <?php if (!empty($p['short_description'])): ?>
                                    <div style="font-size: 12px; color: var(--color-text-secondary); max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($p['short_description']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['category_name'] ?? 'Sin categoría') ?></td>
                            <td><code><?= htmlspecialchars($p['sku'] ?: '—') ?></code></td>
                            <td>
                                <strong><?= $currency ?><?= number_format($p['price'], 2) ?></strong>
                                <?php if ($p['compare_price']): ?>
                                    <div style="font-size: 11px; text-decoration: line-through; color: var(--color-text-muted);">
                                        <?= $currency ?><?= number_format($p['compare_price'], 2) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['quantity'] <= $p['low_stock_threshold']): ?>
                                    <span class="badge badge-danger"><?= $p['quantity'] ?> disp.</span>
                                <?php else: ?>
                                    <span class="badge badge-success"><?= $p['quantity'] ?> disp.</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($p['has_variants']) && $p['variants_count'] > 0): ?>
                                    <a href="<?= $baseUrl ?>/admin/products/edit/<?= $p['id'] ?>#seccion-variantes" class="badge badge-info" style="display: inline-flex; align-items: center; gap: 4px; text-decoration: none;" title="Ver y editar variantes">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="3" width="7" height="7"></rect>
                                            <rect x="14" y="3" width="7" height="7"></rect>
                                            <rect x="14" y="14" width="7" height="7"></rect>
                                            <rect x="3" y="14" width="7" height="7"></rect>
                                        </svg>
                                        <?= $p['variants_count'] ?> opciones
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: var(--color-text-muted);">Sin variantes</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($p['is_active'])): ?>
                                    <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 5px;">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor">
                                            <circle cx="12" cy="12" r="10"></circle>
                                        </svg>
                                        Activo
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background: #E5E7EB; color: #4B5563; display: inline-flex; align-items: center; gap: 5px;">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor">
                                            <circle cx="12" cy="12" r="10"></circle>
                                        </svg>
                                        Deshabilitado
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <!-- ACCIÓN PRINCIPAL: EDITAR -->
                                    <a href="<?= $baseUrl ?>/admin/products/edit/<?= $p['id'] ?>" class="btn btn-secondary btn-sm" title="Editar información y variantes" style="display: inline-flex; align-items: center; gap: 5px; font-weight: 600;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        <span>Editar</span>
                                    </a>

                                    <!-- BORRADO LÓGICO / ACTIVAR / DESHABILITAR -->
                                    <?php if (!empty($p['is_active'])): ?>
                                        <form method="POST" action="<?= $baseUrl ?>/admin/products/toggle/<?= $p['id'] ?>" style="display: inline;">
                                            <?= \ShopCMS\Core\Security::csrfField() ?>
                                            <button type="submit" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 5px; color: #B95000;" title="Deshabilitar producto (borrado lógico: oculta en tienda sin eliminar datos)">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                                </svg>
                                                <span>Deshabilitar</span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="<?= $baseUrl ?>/admin/products/toggle/<?= $p['id'] ?>" style="display: inline;">
                                            <?= \ShopCMS\Core\Security::csrfField() ?>
                                            <button type="submit" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 5px; color: #008060; border-color: #008060;" title="Activar y mostrar en tienda web">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                                <span>Activar</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- VER EN TIENDA (VISTA PREVIA SECUNDARIA) -->
                                    <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($p['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 4px; color: #6B7280;" title="Vista previa en tienda web">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                            <polyline points="15 3 21 3 21 9"></polyline>
                                            <line x1="10" y1="14" x2="21" y2="3"></line>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleProductGuide() {
    const guide = document.getElementById('product-quick-guide');
    if (!guide) return;
    const isHidden = guide.style.display === 'none';
    guide.style.display = isHidden ? 'block' : 'none';
    try {
        localStorage.setItem('shopcms_hide_product_guide', isHidden ? '0' : '1');
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        if (localStorage.getItem('shopcms_hide_product_guide') === '1') {
            const guide = document.getElementById('product-quick-guide');
            if (guide) guide.style.display = 'none';
        }
    } catch (e) {}
});
</script>
