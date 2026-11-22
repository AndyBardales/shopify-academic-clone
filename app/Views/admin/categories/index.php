<?php
// Organizar categorías en árbol: principales con sus hijos
$tree = [];
$childrenMap = [];
foreach ($categories as $cat) {
    if (empty($cat['parent_id'])) {
        $tree[] = $cat;
    } else {
        $childrenMap[$cat['parent_id']][] = $cat;
    }
}
?>

<div class="page-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 class="page-title">Categorías del Catálogo</h1>
        <p class="page-subtitle">Organiza tus productos en categorías principales y subcategorías. Las subcategorías aparecen debajo de su categoría padre.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <button type="button" onclick="toggleCategoryGuide()" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span>¿Cómo usar?</span>
        </button>
        <button type="button" class="btn btn-primary" onclick="openNewCategory(null)" style="display: inline-flex; align-items: center; gap: 7px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Nueva Categoría
        </button>
    </div>
</div>

<!-- GUÍA RÁPIDA EN 3 PASOS -->
<div id="category-quick-guide" class="polaris-card" style="background: #F9FAFB; border-color: #E5E7EB; margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <strong style="font-size: 13px; color: #111827;">Guía rápida para estructurar tus categorías</strong>
            </div>
            <button type="button" onclick="toggleCategoryGuide()" style="background: none; border: none; color: #6B7280; font-size: 12px; cursor: pointer; text-decoration: underline;">Ocultar</button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">1</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Carga un rubro o crea manualmente</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Aplica una plantilla predefinida según tu negocio o usa <strong>Nueva Categoría</strong> para construir tu propio menú.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">2</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Organiza en 2 niveles</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Crea categorías padre y agrégales subcategorías (por ejemplo: <em>Ropa &rarr; Camisetas</em>) para mejorar la navegación en la tienda.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">3</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Deshabilita de forma reversible</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Oculta una categoría sin borrar productos ni subcategorías asociadas; podrás reactivarla cuando la vuelvas a necesitar.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PLANTILLAS POR RUBRO -->
<div class="polaris-card" style="margin-bottom: 20px;">
    <div class="card-body" style="padding: 14px 20px;">
        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <span style="font-size: 13px; font-weight: 600; color: #374151;">Cargar plantilla de categorías:</span>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="<?= $baseUrl ?>/admin/categories/seed-preset?industry=fashion"
                   class="btn btn-secondary btn-sm"
                   onclick="return confirm('¿Cargar estructura de categorías para Moda y Ropa? No se eliminarán tus productos ni categorías existentes.');"
                   title="Categorías recomendadas para tiendas de ropa y moda">
                    Moda &amp; Ropa
                </a>
                <a href="<?= $baseUrl ?>/admin/categories/seed-preset?industry=electronics"
                   class="btn btn-secondary btn-sm"
                   onclick="return confirm('¿Cargar estructura de categorías para Tecnología? No se eliminarán tus productos ni categorías existentes.');"
                   title="Categorías recomendadas para tiendas de tecnología">
                    Tecnología
                </a>
                <a href="<?= $baseUrl ?>/admin/categories/seed-preset?industry=food"
                   class="btn btn-secondary btn-sm"
                   onclick="return confirm('¿Cargar estructura de categorías para Alimentos? No se eliminarán tus productos ni categorías existentes.');"
                   title="Categorías recomendadas para tiendas de alimentos">
                    Alimentos
                </a>
            </div>
            <span style="font-size: 12px; color: #9CA3AF; margin-left: 4px;">Las plantillas agregan categorías sugeridas sin borrar las que ya tienes.</span>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 380px; gap: 20px; align-items: start;">

    <!-- COLUMNA IZQUIERDA: ÁRBOL JERÁRQUICO VISUAL -->
    <div class="polaris-card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 class="card-title">Estructura de Categorías</h2>
                <p style="font-size: 12px; color: var(--color-text-secondary); margin: 2px 0 0 0;">
                    <?= count($categories) ?> categoría<?= count($categories) !== 1 ? 's' : '' ?> mostrada<?= count($categories) !== 1 ? 's' : '' ?>
                </p>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <div style="display: flex; gap: 6px;">
                    <a href="<?= $baseUrl ?>/admin/categories<?= !empty($search) ? '?q=' . urlencode($search) : '' ?>" class="btn <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                        Todas (<?= ($totalActive ?? 0) + ($totalDisabled ?? 0) ?>)
                    </a>
                    <a href="<?= $baseUrl ?>/admin/categories?status=active<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="btn <?= ($statusFilter ?? '') === 'active' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                        Activas (<?= $totalActive ?? 0 ?>)
                    </a>
                    <a href="<?= $baseUrl ?>/admin/categories?status=disabled<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="btn <?= ($statusFilter ?? '') === 'disabled' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                        Deshabilitadas (<?= $totalDisabled ?? 0 ?>)
                    </a>
                </div>
                <form method="GET" action="<?= $baseUrl ?>/admin/categories" style="display: flex; gap: 6px;">
                    <?php if (!empty($statusFilter)): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                    <?php endif; ?>
                    <input type="text" name="q" class="form-control" placeholder="Buscar..." value="<?= htmlspecialchars($search ?? '') ?>" style="padding: 6px 10px; font-size: 13px; width: 160px;">
                    <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
                    <?php if (!empty($search)): ?>
                        <a href="<?= $baseUrl ?>/admin/categories<?= !empty($statusFilter) ? '?status=' . urlencode($statusFilter) : '' ?>" class="btn btn-secondary btn-sm">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <div class="card-body" style="padding: 0;">

            <?php if (empty($categories)): ?>
                <!-- ESTADO VACÍO -->
                <div style="text-align: center; padding: 48px 24px; color: var(--color-text-muted);">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 16px auto; display: block;">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <?php if (!empty($search) || !empty($statusFilter)): ?>
                        <p style="font-size: 15px; font-weight: 600; color: #374151; margin: 0 0 6px 0;">No hay categorías que coincidan con el filtro</p>
                        <p style="font-size: 13px; color: #6B7280; margin: 0 0 20px 0;">Prueba cambiar el filtro de estado o limpiar la búsqueda.</p>
                        <a href="<?= $baseUrl ?>/admin/categories" class="btn btn-secondary">Ver todas las categorías</a>
                    <?php else: ?>
                        <p style="font-size: 15px; font-weight: 600; color: #374151; margin: 0 0 6px 0;">Sin categorías todavía</p>
                        <p style="font-size: 13px; color: #6B7280; margin: 0 0 20px 0;">Crea tu primera categoría con el formulario de la derecha, o carga una plantilla de arriba.</p>
                        <button type="button" class="btn btn-primary" onclick="openNewCategory(null)">Crear primera categoría</button>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- ÁRBOL DE CATEGORÍAS -->
                <div style="padding: 8px 0;">
                    <?php foreach ($tree as $parent): ?>
                        <?php
                        $children = $childrenMap[$parent['id']] ?? [];
                        $parentActive = !isset($parent['is_active']) || !empty($parent['is_active']);
                        ?>

                        <!-- CATEGORÍA PRINCIPAL -->
                        <div class="cat-parent-row <?= !$parentActive ? 'cat-disabled' : '' ?>" data-id="<?= $parent['id'] ?>" style="border-bottom: 1px solid #F3F4F6; <?= !$parentActive ? 'background: #FAFBFB;' : '' ?>">
                            <div style="display: flex; align-items: center; padding: 12px 20px; gap: 12px; cursor: default;" class="cat-row-inner">
                                <!-- Icono y nombre -->
                                <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: <?= $parentActive ? '#ECFDF5' : '#F3F4F6' ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="<?= $parentActive ? '#059669' : '#9CA3AF' ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                        </svg>
                                    </div>
                                    <div style="min-width: 0;">
                                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                            <span style="font-size: 14px; font-weight: 700; color: <?= $parentActive ? '#111827' : '#6B7280' ?>; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?= htmlspecialchars($parent['name']) ?>
                                            </span>
                                            <?php if (!$parentActive): ?>
                                                <span class="badge" style="background: #E5E7EB; color: #4B5563; font-size: 11px; padding: 2px 7px;">Deshabilitada</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: 11px; color: #9CA3AF; font-family: monospace; margin-top: 1px;">
                                            <?= htmlspecialchars($parent['slug']) ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Badge de subcategorías -->
                                <div style="flex-shrink: 0; display: flex; align-items: center; gap: 8px;">
                                    <?php if (!empty($children)): ?>
                                        <span style="background: #EFF6FF; color: #1D4ED8; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; white-space: nowrap;">
                                            <?= count($children) ?> subcategoría<?= count($children) > 1 ? 's' : '' ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($parent['products_count'] > 0): ?>
                                        <span style="background: #F9FAFB; color: #6B7280; font-size: 11px; padding: 3px 8px; border-radius: 20px; border: 1px solid #E5E7EB; white-space: nowrap;">
                                            <?= $parent['products_count'] ?> producto<?= $parent['products_count'] > 1 ? 's' : '' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Acciones -->
                                <div style="display: flex; gap: 4px; flex-shrink: 0;" class="cat-actions">
                                    <?php if ($parentActive): ?>
                                        <button type="button"
                                            onclick='openNewCategory(<?= $parent['id'] ?>, <?= json_encode(htmlspecialchars($parent['name'])) ?>)'
                                            title="Agregar subcategoría dentro de <?= htmlspecialchars(addslashes($parent['name'])) ?>"
                                            class="btn btn-secondary btn-sm"
                                            style="display: inline-flex; align-items: center; gap: 5px; font-size: 12px; padding: 5px 10px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                            </svg>
                                            Sub
                                        </button>
                                    <?php endif; ?>
                                    <button type="button"
                                        onclick='editCategory(<?= json_encode($parent) ?>)'
                                        title="Editar categoría"
                                        class="btn btn-secondary btn-sm"
                                        style="padding: 5px 8px;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>
                                    <?php if ($parentActive): ?>
                                        <a href="<?= $baseUrl ?>/admin/categories/toggle/<?= $parent['id'] ?>"
                                           onclick="return confirmDisable('<?= htmlspecialchars(addslashes($parent['name'])) ?>', <?= count($children) ?>, <?= (int)$parent['products_count'] ?>);"
                                           title="Deshabilitar categoría (borrado lógico)"
                                           class="btn btn-secondary btn-sm"
                                           style="display: inline-flex; align-items: center; gap: 4px; color: #B95000; padding: 5px 8px; font-size: 12px;">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                                <line x1="1" y1="1" x2="23" y2="23"></line>
                                            </svg>
                                            <span>Deshabilitar</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= $baseUrl ?>/admin/categories/toggle/<?= $parent['id'] ?>"
                                           title="Activar categoría y mostrar en tienda"
                                           class="btn btn-secondary btn-sm"
                                           style="display: inline-flex; align-items: center; gap: 4px; color: #008060; border-color: #008060; padding: 5px 9px; font-size: 12px;">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                            <span>Activar</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- SUBCATEGORÍAS -->
                            <?php if (!empty($children)): ?>
                                <div class="cat-children" style="border-top: 1px solid #F3F4F6; background: #FAFAFA;">
                                    <?php foreach ($children as $idx => $child): ?>
                                        <?php $childActive = !isset($child['is_active']) || !empty($child['is_active']); ?>
                                        <div class="cat-child-row <?= !$childActive ? 'cat-disabled' : '' ?>" style="display: flex; align-items: center; padding: 10px 20px 10px 56px; gap: 12px; <?= $idx < count($children)-1 ? 'border-bottom: 1px solid #F3F4F6;' : '' ?> <?= !$childActive ? 'background: #F4F5F7;' : '' ?>">
                                            <!-- Conector visual de árbol -->
                                            <div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0;">
                                                <span style="color: #D1D5DB; font-size: 16px; font-family: monospace; flex-shrink: 0; line-height: 1;">└</span>
                                                <div style="width: 26px; height: 26px; border-radius: 6px; background: <?= $childActive ? '#EFF6FF' : '#E5E7EB' ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="<?= $childActive ? '#3B82F6' : '#9CA3AF' ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                                    </svg>
                                                </div>
                                                <div style="min-width: 0;">
                                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                        <span style="font-size: 13px; font-weight: 600; color: <?= $childActive ? '#374151' : '#6B7280' ?>; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                            <?= htmlspecialchars($child['name']) ?>
                                                        </span>
                                                        <?php if (!$childActive): ?>
                                                            <span class="badge" style="background: #E5E7EB; color: #4B5563; font-size: 10px; padding: 1px 6px;">Deshabilitada</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div style="font-size: 11px; color: #9CA3AF; font-family: monospace; margin-top: 1px;">
                                                        <?= htmlspecialchars($child['slug']) ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Productos en subcategoría -->
                                            <?php if ($child['products_count'] > 0): ?>
                                                <span style="background: #F9FAFB; color: #6B7280; font-size: 11px; padding: 2px 8px; border-radius: 20px; border: 1px solid #E5E7EB; white-space: nowrap; flex-shrink: 0;">
                                                    <?= $child['products_count'] ?> prod.
                                                </span>
                                            <?php endif; ?>

                                            <!-- Acciones subcategoría -->
                                            <div style="display: flex; gap: 4px; flex-shrink: 0;" class="cat-actions">
                                                <button type="button"
                                                    onclick='editCategory(<?= json_encode($child) ?>)'
                                                    title="Editar subcategoría"
                                                    class="btn btn-secondary btn-sm"
                                                    style="padding: 4px 7px;">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>
                                                <?php if ($childActive): ?>
                                                    <a href="<?= $baseUrl ?>/admin/categories/toggle/<?= $child['id'] ?>"
                                                       onclick="return confirmDisable('<?= htmlspecialchars(addslashes($child['name'])) ?>', 0, <?= (int)$child['products_count'] ?>);"
                                                       title="Deshabilitar subcategoría (borrado lógico)"
                                                       class="btn btn-secondary btn-sm"
                                                       style="display: inline-flex; align-items: center; gap: 4px; color: #B95000; padding: 4px 7px; font-size: 11px;">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                                            <line x1="1" y1="1" x2="23" y2="23"></line>
                                                        </svg>
                                                        <span>Deshabilitar</span>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= $baseUrl ?>/admin/categories/toggle/<?= $child['id'] ?>"
                                                       title="Activar subcategoría"
                                                       class="btn btn-secondary btn-sm"
                                                       style="display: inline-flex; align-items: center; gap: 4px; color: #008060; border-color: #008060; padding: 4px 8px; font-size: 11px;">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="20 6 9 17 4 12"></polyline>
                                                        </svg>
                                                        <span>Activar</span>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Subcategorías filtradas o sin padre en la vista actual -->
                    <?php
                    $parentIds = array_column($tree, 'id');
                    $unmatchedChildren = [];
                    foreach ($categories as $cat) {
                        if (!empty($cat['parent_id']) && !in_array($cat['parent_id'], $parentIds)) {
                            $unmatchedChildren[] = $cat;
                        }
                    }
                    if (!empty($unmatchedChildren)): ?>
                        <?php foreach ($unmatchedChildren as $sub): ?>
                            <?php $subActive = !isset($sub['is_active']) || !empty($sub['is_active']); ?>
                            <div class="cat-parent-row <?= !$subActive ? 'cat-disabled' : '' ?>" style="border-bottom: 1px solid #F3F4F6; <?= !$subActive ? 'background: #FAFBFB;' : '' ?>">
                                <div style="display: flex; align-items: center; padding: 12px 20px; gap: 12px;" class="cat-row-inner">
                                    <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                                        <div style="width: 32px; height: 32px; border-radius: 8px; background: <?= $subActive ? '#EFF6FF' : '#F3F4F6' ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="<?= $subActive ? '#3B82F6' : '#9CA3AF' ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                            </svg>
                                        </div>
                                        <div style="min-width: 0;">
                                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                <span style="font-size: 13px; font-weight: 700; color: <?= $subActive ? '#111827' : '#6B7280' ?>;">
                                                    <?= htmlspecialchars($sub['name']) ?>
                                                </span>
                                                <?php if (!empty($sub['parent_name'])): ?>
                                                    <span style="font-size: 11px; color: #6B7280;">(en <?= htmlspecialchars($sub['parent_name']) ?>)</span>
                                                <?php endif; ?>
                                                <?php if (!$subActive): ?>
                                                    <span class="badge" style="background: #E5E7EB; color: #4B5563; font-size: 11px; padding: 2px 7px;">Deshabilitada</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 11px; color: #9CA3AF; font-family: monospace;">
                                                <?= htmlspecialchars($sub['slug']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 4px; flex-shrink: 0;" class="cat-actions">
                                        <button type="button" onclick='editCategory(<?= json_encode($sub) ?>)' class="btn btn-secondary btn-sm" style="padding: 5px 8px;" title="Editar subcategoría">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                        <?php if ($subActive): ?>
                                            <a href="<?= $baseUrl ?>/admin/categories/toggle/<?= $sub['id'] ?>" onclick="return confirmDisable('<?= htmlspecialchars(addslashes($sub['name'])) ?>', 0, <?= (int)$sub['products_count'] ?>);" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 4px; color: #B95000; padding: 5px 8px; font-size: 12px;">
                                                <span>Deshabilitar</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= $baseUrl ?>/admin/categories/toggle/<?= $sub['id'] ?>" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 4px; color: #008060; border-color: #008060; padding: 5px 9px; font-size: 12px;">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                                <span>Activar</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- COLUMNA DERECHA: FORMULARIO CREAR / EDITAR -->
    <div class="polaris-card" id="cat-form-card" style="position: sticky; top: 20px;">
        <div class="card-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <!-- Indicador de modo -->
                <div id="form-mode-icon" style="width: 32px; height: 32px; border-radius: 8px; background: #ECFDF5; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background 0.2s;">
                    <svg id="form-mode-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </div>
                <div>
                    <h2 class="card-title" id="form-card-title" style="margin: 0;">Nueva Categoría</h2>
                    <p id="form-card-subtitle" style="font-size: 12px; color: var(--color-text-secondary); margin: 2px 0 0 0;">Categoría principal</p>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- INDICADOR DE NIVEL -->
            <div id="level-indicator" style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
                <div id="level-icon-wrap" style="display: flex; align-items: center; gap: 6px;">
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: #059669;"></div>
                    <span id="level-label" style="font-size: 12px; font-weight: 600; color: #374151;">Categoría Principal</span>
                </div>
                <span id="level-desc" style="font-size: 12px; color: #6B7280; margin-left: 4px;">Aparecerá en el nivel superior del catálogo.</span>
            </div>

            <form id="category-form" method="POST" action="<?= $baseUrl ?>/admin/categories/store">
                <input type="hidden" id="cat_id" name="cat_id" value="">

                <div class="form-group">
                    <label for="name">Nombre *</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Ej. Prendas Superiores, Smartphones..." required onkeyup="syncSlug(this.value)">
                </div>

                <div class="form-group">
                    <label for="slug" style="display: flex; align-items: center; gap: 6px;">
                        URL Slug
                        <span style="font-size: 11px; color: #9CA3AF; font-weight: 400;">(auto-generado)</span>
                    </label>
                    <input type="text" id="slug" name="slug" class="form-control" placeholder="prendas-superiores">
                    <small style="color: var(--color-text-secondary); font-size: 11px;">Identificador en la URL: <code>/catalog?category=slug</code></small>
                </div>

                <div class="form-group">
                    <label for="parent_id" style="display: flex; align-items: center; justify-content: space-between;">
                        <span>Categoría Padre</span>
                        <button type="button" onclick="clearParent()" id="clear-parent-btn" style="display: none; font-size: 11px; color: #6B7280; background: none; border: none; cursor: pointer; padding: 0; text-decoration: underline;">Quitar padre</button>
                    </label>
                    <select id="parent_id" name="parent_id" class="form-control" onchange="onParentChange(this.value)">
                        <option value="">-- Ninguno (Categoría Principal) --</option>
                        <?php foreach ($parentCategories as $parent): ?>
                            <option value="<?= $parent['id'] ?>"><?= htmlspecialchars($parent['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: var(--color-text-secondary); font-size: 11px;">Selecciona un padre si es una subcategoría (ej. "Camisetas" dentro de "Prendas Superiores").</small>
                </div>

                <div class="form-group">
                    <label for="description">Descripción</label>
                    <textarea id="description" name="description" class="form-control" rows="2" placeholder="Breve descripción de los artículos de esta categoría..."></textarea>
                </div>

                <div class="form-group">
                    <label for="image_url">URL de imagen (Opcional)</label>
                    <input type="text" id="image_url" name="image_url" class="form-control" placeholder="assets/images/categoria.svg">
                </div>

                <!-- Botón oculto para guardado via Enter / barra contextual -->
                <button type="submit" id="form-submit-btn" style="display:none;" aria-hidden="true"></button>
            </form>

            <!-- Botón cancelar edición visible solo en modo editar -->
            <div id="cancel-edit-wrap" style="display: none; margin-top: 12px; padding-top: 12px; border-top: 1px solid #F3F4F6;">
                <button type="button" onclick="resetForm()" class="btn btn-secondary" style="width: 100%; font-size: 13px;">
                    Cancelar y crear nueva
                </button>
            </div>
        </div>
    </div>

</div>

<script>
const baseUrl = '<?= $baseUrl ?>';

function syncSlug(val) {
    const slugInput = document.getElementById('slug');
    // Solo auto-sync si es modo creación
    if (!document.getElementById('cat_id').value) {
        slugInput.value = val.toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
}

function onParentChange(parentId) {
    const clearBtn = document.getElementById('clear-parent-btn');
    const levelLabel = document.getElementById('level-label');
    const levelDesc = document.getElementById('level-desc');
    const levelIcon = document.getElementById('level-icon-wrap').querySelector('div');
    const formSubtitle = document.getElementById('form-card-subtitle');
    const formModeIcon = document.getElementById('form-mode-icon');
    const formModeSvgStroke = document.getElementById('form-mode-svg');

    if (parentId && parentId !== '') {
        clearBtn.style.display = 'inline';
        levelLabel.textContent = 'Subcategoría';
        levelIcon.style.background = '#3B82F6';
        levelDesc.textContent = 'Aparecerá dentro de la categoría padre seleccionada.';
        formSubtitle.textContent = 'Subcategoría';
        formModeIcon.style.background = '#EFF6FF';
        formModeSvgStroke.setAttribute('stroke', '#3B82F6');
    } else {
        clearBtn.style.display = 'none';
        levelLabel.textContent = 'Categoría Principal';
        levelIcon.style.background = '#059669';
        levelDesc.textContent = 'Aparecerá en el nivel superior del catálogo.';
        formSubtitle.textContent = 'Categoría principal';
        formModeIcon.style.background = '#ECFDF5';
        formModeSvgStroke.setAttribute('stroke', '#059669');
    }
}

function clearParent() {
    document.getElementById('parent_id').value = '';
    onParentChange('');
}

// Abrir formulario en modo "nueva" – opcionalmente con padre preseleccionado
function openNewCategory(parentId, parentName) {
    resetForm();
    if (parentId) {
        document.getElementById('parent_id').value = parentId;
        onParentChange(parentId);
        document.getElementById('form-card-title').textContent = 'Nueva Subcategoría';
        document.getElementById('form-card-subtitle').textContent = 'Dentro de: ' + (parentName || '');
    }
    document.getElementById('name').focus();
    // Scroll suave al formulario en mobile
    document.getElementById('cat-form-card').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function editCategory(cat) {
    document.getElementById('form-card-title').textContent = 'Editar Categoría';
    document.getElementById('cat_id').value = cat.id;
    document.getElementById('name').value = cat.name;
    document.getElementById('slug').value = cat.slug;
    document.getElementById('parent_id').value = cat.parent_id || '';
    document.getElementById('description').value = cat.description || '';
    document.getElementById('image_url').value = cat.image_url || '';

    // Cambiar la acción a update
    document.getElementById('category-form').action = baseUrl + '/admin/categories/update/' + cat.id;

    // Actualizar el indicador de nivel
    onParentChange(cat.parent_id || '');
    document.getElementById('form-card-subtitle').textContent = cat.parent_id ? 'Editando subcategoría' : 'Editando categoría principal';

    // Mostrar botón cancelar
    document.getElementById('cancel-edit-wrap').style.display = 'block';

    // Deshabilitar que se seleccione a sí misma como padre
    const parentSelect = document.getElementById('parent_id');
    for (let opt of parentSelect.options) {
        opt.disabled = (opt.value == cat.id);
    }

    // Activar barra contextual de guardado
    if (window.ShopCMS && window.ShopCMS.markDirty) {
        window.ShopCMS.markDirty(document.getElementById('category-form'));
    }

    document.getElementById('name').focus();
    document.getElementById('cat-form-card').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function resetForm() {
    document.getElementById('form-card-title').textContent = 'Nueva Categoría';
    document.getElementById('form-card-subtitle').textContent = 'Categoría principal';
    document.getElementById('cat_id').value = '';
    document.getElementById('category-form').reset();
    document.getElementById('category-form').action = baseUrl + '/admin/categories/store';
    document.getElementById('cancel-edit-wrap').style.display = 'none';

    onParentChange('');

    const parentSelect = document.getElementById('parent_id');
    for (let opt of parentSelect.options) {
        opt.disabled = false;
    }

    if (window.ShopCMS && window.ShopCMS.markClean) {
        window.ShopCMS.markClean();
    }
}

function confirmDisable(name, childrenCount, productsCount) {
    let msg = '¿Deshabilitar la categoría "' + name + '"?';
    if (childrenCount > 0) {
        msg += '\n\nTiene ' + childrenCount + ' subcategoría(s) asociadas (conservarán su estructura).';
    }
    if (productsCount > 0) {
        msg += '\n\nTiene ' + productsCount + ' producto(s) vinculados que mantendrán su historial.';
    }
    msg += '\n\nNo se mostrará en la tienda, pero podrás reactivarla en cualquier momento (borrado lógico).';
    return confirm(msg);
}

function toggleCategoryGuide() {
    const guide = document.getElementById('category-quick-guide');
    if (!guide) return;
    const isHidden = guide.style.display === 'none';
    guide.style.display = isHidden ? 'block' : 'none';
    try {
        localStorage.setItem('shopcms_hide_category_guide', isHidden ? '0' : '1');
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        if (localStorage.getItem('shopcms_hide_category_guide') === '1') {
            const guide = document.getElementById('category-quick-guide');
            if (guide) guide.style.display = 'none';
        }
    } catch (e) {}
});
</script>

<style>
.cat-row-inner:hover {
    background: #F9FAFB;
}
.cat-actions {
    opacity: 0.75;
    transition: opacity 0.15s ease;
}
.cat-parent-row:hover .cat-actions,
.cat-child-row:hover .cat-actions,
.cat-disabled .cat-actions {
    opacity: 1;
}
/* En móvil siempre visibles */
@media (max-width: 768px) {
    .cat-actions { opacity: 1; }
}
</style>
