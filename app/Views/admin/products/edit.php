<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
            <a href="<?= $baseUrl ?>/admin/products" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 4px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Volver a Productos</span>
            </a>
            <?php if (!empty($product['is_active'])): ?>
                <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 4px;">
                    <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                    Activo
                </span>
            <?php else: ?>
                <span class="badge" style="background: #E5E7EB; color: #4B5563; display: inline-flex; align-items: center; gap: 4px;">
                    <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                    Deshabilitado
                </span>
            <?php endif; ?>
        </div>
        <h1 class="page-title">Editar: <?= htmlspecialchars($product['name']) ?></h1>
        <p class="page-subtitle">Modifica la información general, precios, inventario y variantes de este artículo.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($product['slug']) ?>" target="_blank" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                <polyline points="15 3 21 3 21 9"></polyline>
                <line x1="10" y1="14" x2="21" y2="3"></line>
            </svg>
            <span>Ver en Tienda</span>
        </a>
    </div>
</div>

<form id="product-edit-form" method="POST" action="<?= $baseUrl ?>/admin/products/update/<?= $product['id'] ?>" enctype="multipart/form-data">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        <!-- COLUMNA PRINCIPAL -->
        <div>
            <!-- INFORMACIÓN BÁSICA -->
            <div class="polaris-card">
                <div class="card-header">
                    <h2 class="card-title">Información Básica</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nombre del producto *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="slug">URL Slug amigable</label>
                        <input type="text" id="slug" name="slug" class="form-control" value="<?= htmlspecialchars($product['slug']) ?>" placeholder="nombre-del-producto">
                        <small style="color: var(--color-text-secondary); font-size: 11px;">Identificador en la URL pública: <code>/product/<?= htmlspecialchars($product['slug']) ?></code></small>
                    </div>

                    <div class="form-group">
                        <label for="short_description">Descripción corta</label>
                        <input type="text" id="short_description" name="short_description" class="form-control" value="<?= htmlspecialchars($product['short_description'] ?? '') ?>" placeholder="Resumen breve visible en tarjetas y listados">
                    </div>

                    <div class="form-group">
                        <label for="description">Descripción completa y cuidados</label>
                        <textarea id="description" name="description" class="form-control" rows="6" placeholder="Detalles de materiales, cuidados, medidas, especificaciones..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- PRECIO E INVENTARIO GENERAL (SE OCULTA AUTOMÁTICAMENTE SI SE HABILITAN VARIANTES) -->
            <div class="polaris-card" id="general_pricing_inventory_card" style="<?= !empty($product['has_variants']) ? 'display: none;' : '' ?>">
                <div class="card-header">
                    <h2 class="card-title">Precio e Inventario General</h2>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Precio de venta (<?= $currency ?>) *</label>
                            <input type="number" step="0.01" id="price" name="price" class="form-control" value="<?= htmlspecialchars($product['price']) ?>" <?= empty($product['has_variants']) ? 'required' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label for="compare_price">Precio de comparación (Tachado)</label>
                            <input type="number" step="0.01" id="compare_price" name="compare_price" class="form-control" value="<?= htmlspecialchars($product['compare_price'] ?? '') ?>" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="cost_price">Costo por artículo (Opcional)</label>
                            <input type="number" step="0.01" id="cost_price" name="cost_price" class="form-control" value="<?= htmlspecialchars($product['cost_price'] ?? '') ?>" placeholder="0.00">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="sku">SKU base</label>
                            <input type="text" id="sku" name="sku" class="form-control" value="<?= htmlspecialchars($product['sku'] ?? '') ?>" placeholder="SKU-001">
                        </div>
                        <div class="form-group">
                            <label for="barcode">Código de barras (UPC / EAN)</label>
                            <input type="text" id="barcode" name="barcode" class="form-control" value="<?= htmlspecialchars($product['barcode'] ?? '') ?>" placeholder="123456789">
                        </div>
                        <div class="form-group">
                            <label for="quantity">Stock disponible *</label>
                            <input type="number" id="quantity" name="quantity" class="form-control" value="<?= htmlspecialchars($product['quantity']) ?>" <?= empty($product['has_variants']) ? 'required' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label for="low_stock_threshold">Alerta de stock bajo</label>
                            <input type="number" id="low_stock_threshold" name="low_stock_threshold" class="form-control" value="<?= htmlspecialchars($product['low_stock_threshold'] ?? 5) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- GESTIÓN DE VARIANTES -->
            <div class="polaris-card" id="seccion-variantes">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 class="card-title">Variantes de Producto</h2>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; cursor: pointer; color: var(--color-text-main);">
                        <input type="checkbox" id="has_variants" name="has_variants" value="1" <?= !empty($product['has_variants']) ? 'checked' : '' ?> onchange="toggleVariants(this.checked)">
                        Habilitar múltiples variantes (color, talla, opciones)
                    </label>
                </div>
                <div class="card-body" id="variants_section" style="<?= empty($product['has_variants']) ? 'display: none;' : '' ?>">
                    <!-- BANNER INFORMATIVO: PRECIOS Y STOCK GESTIONADOS POR VARIANTE -->
                    <div id="variants_pricing_notice" style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 10px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 1px;">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <div style="font-size: 13px; color: #166534; line-height: 1.45;">
                            <strong>Precios, SKU y Stock gestionados por variante:</strong> Al activar variantes múltiples, cada opción se administra de forma individual en la tabla inferior. El precio base en catálogo y el inventario total de la tienda se sincronizarán automáticamente.
                        </div>
                    </div>

                    <!-- GUÍA DE DIMENSIONES SEGÚN RUBRO -->
                    <div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 10px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <div style="font-size: 13px; color: #374151; line-height: 1.4;">
                            Define aquí las dimensiones de tu producto según el rubro de tu tienda (ej: <strong>Color / Talla</strong> para ropa, <strong>Almacenamiento / Color</strong> para tecnología, o <strong>Presentación / Peso</strong> para alimentos).
                        </div>
                    </div>

                    <!-- ATRIBUTOS PERSONALIZABLES SEGÚN EL RUBRO (1, 2 O 3 OPCIONES DINÁMICAS) -->
                    <div class="form-row" style="margin-bottom: 16px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        <div class="form-group">
                            <label for="option1_name" style="font-size: 13px; font-weight: 600;">Atributo 1 *</label>
                            <input type="text" id="option1_name" name="option1_name" class="form-control" value="<?= htmlspecialchars($product['option1_name'] ?? 'Color') ?>" placeholder="Ej: Color, Sabor, Almacenamiento">
                        </div>
                        <div class="form-group">
                            <label for="option2_name" style="font-size: 13px; font-weight: 600;">Atributo 2 (Opcional)</label>
                            <input type="text" id="option2_name" name="option2_name" class="form-control" value="<?= htmlspecialchars($product['option2_name'] ?? 'Talla') ?>" placeholder="Ej: Talla, Peso, Memoria">
                        </div>
                        <div class="form-group">
                            <label for="option3_name" style="font-size: 13px; font-weight: 600;">Atributo 3 (Opcional)</label>
                            <input type="text" id="option3_name" name="option3_name" class="form-control" value="<?= htmlspecialchars($product['option3_name'] ?? '') ?>" placeholder="Ej: Material, Conectividad, Acabado">
                        </div>
                    </div>

                    <div class="variant-table-container">
                        <table class="polaris-table variant-table">
                            <colgroup>
                                <col style="width: 22%; min-width: 175px;">
                                <col style="width: 16%; min-width: 125px;">
                                <col style="width: 15%; min-width: 115px;">
                                <col style="width: 15%; min-width: 115px;">
                                <col style="width: 12%; min-width: 95px;">
                                <col style="width: 14%; min-width: 125px;">
                                <col style="width: 6%; min-width: 48px;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Combinación de Opciones *</th>
                                    <th>SKU</th>
                                    <th style="text-align: right;">Precio (<?= $currency ?>) *</th>
                                    <th style="text-align: right;">P. Tachado</th>
                                    <th style="text-align: right;">Stock *</th>
                                    <th>URL Imagen</th>
                                    <th style="text-align: center;"></th>
                                </tr>
                            </thead>
                            <tbody id="variants_table_body">
                                <?php if (!empty($variants)): ?>
                                    <?php foreach ($variants as $idx => $v): ?>
                                        <tr class="variant-row">
                                            <td>
                                                <input type="hidden" name="variant_id[]" value="<?= $v['id'] ?>">
                                                <input type="text" name="variant_title[]" class="form-control" value="<?= htmlspecialchars($v['title']) ?>" placeholder="Ej: Negro / M" required style="padding: 8px 10px; font-size: 13px; width: 100%; box-sizing: border-box;">
                                            </td>
                                            <td>
                                                <input type="text" name="variant_sku[]" class="form-control" value="<?= htmlspecialchars($v['sku'] ?? '') ?>" placeholder="SKU-VAR" style="padding: 8px 10px; font-size: 13px; width: 100%; box-sizing: border-box;">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="variant_price[]" class="form-control variant-num-input" value="<?= htmlspecialchars($v['price']) ?>" placeholder="0.00" style="padding: 8px 10px; font-size: 13px; width: 100%; text-align: right; font-weight: 600; box-sizing: border-box;">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="variant_compare_price[]" class="form-control variant-num-input" value="<?= htmlspecialchars($v['compare_price'] ?? '') ?>" placeholder="0.00" style="padding: 8px 10px; font-size: 13px; width: 100%; text-align: right; font-weight: 600; box-sizing: border-box;">
                                            </td>
                                            <td>
                                                <input type="number" min="0" name="variant_qty[]" class="form-control variant-num-input" value="<?= htmlspecialchars($v['quantity']) ?>" style="padding: 8px 10px; font-size: 13px; width: 100%; text-align: right; font-weight: 600; box-sizing: border-box;">
                                            </td>
                                            <td>
                                                <input type="text" name="variant_image_url[]" class="form-control" value="<?= htmlspecialchars($v['image_url'] ?? '') ?>" placeholder="assets/images/..." style="padding: 8px 10px; font-size: 13px; width: 100%; box-sizing: border-box;">
                                            </td>
                                            <td style="text-align: center;">
                                                <button type="button" class="btn btn-secondary btn-sm" onclick="removeVariantRow(this)" title="Quitar variante" style="padding: 6px 8px; color: #DC2626; border-color: #FCA5A5;">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr class="variant-row">
                                        <td>
                                            <input type="hidden" name="variant_id[]" value="0">
                                            <input type="text" name="variant_title[]" class="form-control" placeholder="Ej: Negro / S" style="padding: 8px 10px; font-size: 13px; width: 100%; box-sizing: border-box;">
                                        </td>
                                        <td>
                                            <input type="text" name="variant_sku[]" class="form-control" placeholder="SKU" style="padding: 8px 10px; font-size: 13px; width: 100%; box-sizing: border-box;">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="variant_price[]" class="form-control variant-num-input" value="<?= htmlspecialchars($product['price']) ?>" placeholder="0.00" style="padding: 8px 10px; font-size: 13px; width: 100%; text-align: right; font-weight: 600; box-sizing: border-box;">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="variant_compare_price[]" class="form-control variant-num-input" placeholder="0.00" style="padding: 8px 10px; font-size: 13px; width: 100%; text-align: right; font-weight: 600; box-sizing: border-box;">
                                        </td>
                                        <td>
                                            <input type="number" min="0" name="variant_qty[]" class="form-control variant-num-input" value="5" style="padding: 8px 10px; font-size: 13px; width: 100%; text-align: right; font-weight: 600; box-sizing: border-box;">
                                        </td>
                                        <td>
                                            <input type="text" name="variant_image_url[]" class="form-control" placeholder="assets/images/..." style="padding: 8px 10px; font-size: 13px; width: 100%; box-sizing: border-box;">
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="removeVariantRow(this)" title="Quitar variante" style="padding: 6px 8px; color: #DC2626; border-color: #FCA5A5;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 14px;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addVariantRow()" style="display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <span>Agregar otra variante</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMNA LATERAL -->
        <div>
            <!-- ESTADO Y BORRADO LÓGICO -->
            <div class="polaris-card">
                <div class="card-header">
                    <h2 class="card-title">Estado del Producto</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="is_active">Visibilidad en Tienda</label>
                        <select id="is_active" name="is_active" class="form-control" style="font-weight: 600;">
                            <option value="1" <?= !empty($product['is_active']) ? 'selected' : '' ?>>Activo (Visible en tienda web)</option>
                            <option value="0" <?= empty($product['is_active']) ? 'selected' : '' ?>>Deshabilitado (Borrado lógico)</option>
                        </select>
                    </div>
                    <div style="font-size: 12px; color: var(--color-text-secondary); line-height: 1.4; background: #F9FAFB; padding: 10px; border-radius: 6px; border: 1px solid #E5E7EB;">
                        <strong>Borrado Lógico:</strong> Al deshabilitar el producto, queda oculto en el catálogo y búsquedas, pero se preserva todo el historial de pedidos y clientes que lo adquirieron previamente.
                    </div>
                </div>
            </div>

            <!-- CATEGORÍA -->
            <div class="polaris-card">
                <div class="card-header">
                    <h2 class="card-title">Organización</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="category_id">Categoría</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="">-- Sin categoría --</option>
                            <?php foreach ($categories as $cat): ?>
                                <?php 
                                    $isSub = !empty($cat['parent_id']);
                                    $prefix = $isSub ? '&nbsp;&nbsp;&nbsp;&nbsp;└── ' : '';
                                    $style = $isSub ? 'color: #4B5563;' : 'font-weight: 600; color: #111827; background-color: #F9FAFB;';
                                ?>
                                <option value="<?= $cat['id'] ?>" style="<?= $style ?>" <?= ($product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= $prefix . htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- IMAGEN DESTACADA -->
            <div class="polaris-card">
                <div class="card-header">
                    <h2 class="card-title">Imagen Principal</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($product['featured_image'])): ?>
                        <div style="margin-bottom: 14px; text-align: center;">
                            <div style="width: 140px; height: 140px; margin: 0 auto; border-radius: 8px; border: 1px solid #E5E7EB; background: #F3F4F6; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($product['featured_image']) ?>" alt="Imagen actual" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            </div>
                            <small style="display: block; margin-top: 6px; color: var(--color-text-secondary); font-size: 11px;">
                                Imagen actual: <code><?= htmlspecialchars($product['featured_image']) ?></code>
                            </small>
                            <input type="hidden" name="existing_featured_image" value="<?= htmlspecialchars($product['featured_image']) ?>">
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="featured_image">Subir nueva imagen</label>
                        <input type="file" id="featured_image" name="featured_image" class="form-control" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label for="featured_image_url">O escribir URL / ruta de imagen</label>
                        <input type="text" id="featured_image_url" name="featured_image_url" class="form-control" placeholder="assets/images/producto.svg" value="<?= htmlspecialchars($product['featured_image'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- BOTÓN SUBMIT OCULTO PARA SOPORTE DE TECLADO / ENTER -->
            <button type="submit" style="display:none;" aria-hidden="true"></button>
        </div>
    </div>
</form>

<script>
function toggleVariants(show) {
    const generalCard = document.getElementById('general_pricing_inventory_card');
    const variantsSection = document.getElementById('variants_section');
    const priceInput = document.getElementById('price');
    const qtyInput = document.getElementById('quantity');
    
    if (show) {
        if (generalCard) generalCard.style.display = 'none';
        if (variantsSection) variantsSection.style.display = 'block';
        if (priceInput) priceInput.required = false;
        if (qtyInput) qtyInput.required = false;

        // Si la primera variante no tiene precio, copiar el precio del formulario general si existía
        const firstVarPrice = document.querySelector('input[name="variant_price[]"]');
        if (firstVarPrice && (!firstVarPrice.value || firstVarPrice.value === '0.00' || firstVarPrice.value === '0')) {
            if (priceInput && priceInput.value && parseFloat(priceInput.value) > 0) {
                firstVarPrice.value = priceInput.value;
            }
        }
    } else {
        if (generalCard) generalCard.style.display = 'block';
        if (variantsSection) variantsSection.style.display = 'none';
        if (priceInput) priceInput.required = true;
        if (qtyInput) qtyInput.required = true;
    }

    const form = document.querySelector('form');
    if (form) form.dispatchEvent(new Event('input', { bubbles: true }));
}

function addVariantRow() {
    const tbody = document.getElementById('variants_table_body');
    const tr = document.createElement('tr');
    tr.className = 'variant-row';
    const firstPriceInput = document.querySelector('input[name="variant_price[]"]');
    const basePrice = (firstPriceInput && firstPriceInput.value) ? firstPriceInput.value : (document.getElementById('price') ? document.getElementById('price').value : '0.00');
    
    tr.innerHTML = `
        <td>
            <input type="hidden" name="variant_id[]" value="0">
            <input type="text" name="variant_title[]" class="form-control" placeholder="Ej: Blanco / XL" required style="padding: 8px 10px; font-size: 13px; width: 100%; box-sizing: border-box;">
        </td>
        <td>
            <input type="text" name="variant_sku[]" class="form-control" placeholder="SKU" style="padding: 8px 10px; font-size: 13px; width: 100%; box-sizing: border-box;">
        </td>
        <td>
            <input type="number" step="0.01" min="0" name="variant_price[]" class="form-control variant-num-input" value="${basePrice}" placeholder="0.00" style="padding: 8px 10px; font-size: 13px; width: 100%; text-align: right; font-weight: 600; box-sizing: border-box;">
        </td>
        <td>
            <input type="number" step="0.01" min="0" name="variant_compare_price[]" class="form-control variant-num-input" placeholder="0.00" style="padding: 8px 10px; font-size: 13px; width: 100%; text-align: right; font-weight: 600; box-sizing: border-box;">
        </td>
        <td>
            <input type="number" min="0" name="variant_qty[]" class="form-control variant-num-input" value="10" style="padding: 8px 10px; font-size: 13px; width: 100%; text-align: right; font-weight: 600; box-sizing: border-box;">
        </td>
        <td>
            <input type="text" name="variant_image_url[]" class="form-control" placeholder="assets/images/..." style="padding: 8px 10px; font-size: 13px; width: 100%; box-sizing: border-box;">
        </td>
        <td style="text-align: center;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="removeVariantRow(this)" title="Quitar variante" style="padding: 6px 8px; color: #DC2626; border-color: #FCA5A5;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </button>
        </td>
    `;
    tbody.appendChild(tr);

    const form = tbody.closest('form');
    if (form) {
        form.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

function removeVariantRow(btn) {
    const row = btn.closest('tr');
    const tbody = document.getElementById('variants_table_body');
    if (tbody.querySelectorAll('tr').length > 1) {
        row.remove();
    } else {
        // Si es la última fila, solo limpiar valores
        const inputs = row.querySelectorAll('input[type="text"], input[type="number"]');
        inputs.forEach(input => input.value = '');
    }

    const form = tbody ? tbody.closest('form') : document.querySelector('form');
    if (form) {
        form.dispatchEvent(new Event('input', { bubbles: true }));
    }
}
</script>
