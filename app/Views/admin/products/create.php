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
        </div>
        <h1 class="page-title">Agregar Producto</h1>
        <p class="page-subtitle">Crea un nuevo artículo para tu catálogo, define variantes, precios y control de inventario.</p>
    </div>
</div>

<form id="product-create-form" method="POST" action="<?= $baseUrl ?>/admin/products/store" enctype="multipart/form-data" onsubmit="return validateProductBeforeSubmit();">
    <div id="base-product-validation-alert" style="display: none; background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; border-radius: 10px; padding: 14px 18px; margin-bottom: 18px; font-size: 13.5px; line-height: 1.5;"></div>
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
                        <input type="text" id="name" name="name" class="form-control" placeholder="Ej. Camiseta Algodón Premium" required>
                    </div>

                    <div class="form-group">
                        <label for="slug">URL Slug personalizado (opcional)</label>
                        <input type="text" id="slug" name="slug" class="form-control" placeholder="camiseta-algodon-premium">
                        <small style="color: var(--color-text-secondary); font-size: 11px;">Se generará automáticamente a partir del nombre si se deja vacío.</small>
                    </div>

                    <div class="form-group">
                        <label for="short_description">Descripción corta</label>
                        <input type="text" id="short_description" name="short_description" class="form-control" placeholder="Resumen breve para listados">
                    </div>

                    <div class="form-group">
                        <label for="description">Descripción completa y cuidados</label>
                        <textarea id="description" name="description" class="form-control" rows="6" placeholder="Detalles de materiales, características, medidas y cuidados..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="specs">Características técnicas base (opcional, una por línea "Clave: Valor")</label>
                        <textarea id="specs" name="specs" class="form-control" rows="4" placeholder="Material: 100% Algodón orgánico&#10;Corte: Oversize&#10;Garantía: 6 meses"></textarea>
                    </div>
                </div>
            </div>

            <!-- PRECIOS E INVENTARIO GENERAL (SE OCULTA AUTOMÁTICAMENTE SI SE HABILITAN VARIANTES) -->
            <div class="polaris-card" id="general_pricing_inventory_card">
                <div class="card-header">
                    <h2 class="card-title">Precio e Inventario General</h2>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Precio de venta (<?= $currency ?>) *</label>
                            <input type="number" step="0.01" id="price" name="price" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label for="compare_price">Precio de comparación (Tachado)</label>
                            <input type="number" step="0.01" id="compare_price" name="compare_price" class="form-control" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="cost_price">Costo por artículo (Opcional)</label>
                            <input type="number" step="0.01" id="cost_price" name="cost_price" class="form-control" placeholder="0.00">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="sku">SKU base</label>
                            <input type="text" id="sku" name="sku" class="form-control" placeholder="SKU-001">
                        </div>
                        <div class="form-group">
                            <label for="barcode">Código de barras (UPC / EAN)</label>
                            <input type="text" id="barcode" name="barcode" class="form-control" placeholder="123456789">
                        </div>
                        <div class="form-group">
                            <label for="quantity">Cantidad disponible *</label>
                            <input type="number" id="quantity" name="quantity" class="form-control" value="10" required>
                        </div>
                        <div class="form-group">
                            <label for="low_stock_threshold">Umbral alerta stock bajo</label>
                            <input type="number" id="low_stock_threshold" name="low_stock_threshold" class="form-control" value="5">
                        </div>
                    </div>
                </div>
            </div>

            <!-- VARIANTES DEL PRODUCTO -->
            <div class="polaris-card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 class="card-title">Variantes de Producto</h2>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; cursor: pointer; color: var(--color-text-main);">
                        <input type="checkbox" id="has_variants" name="has_variants" value="1" onchange="toggleVariants(this.checked)">
                        Habilitar múltiples variantes (color, talla, opciones)
                    </label>
                </div>
                <div class="card-body" id="variants_section" style="display: none;">
                    <?php
                    $defaultOpt1 = 'Color';
                    $defaultOpt2 = 'Talla';
                    if (($industry ?? '') === 'food') {
                        $defaultOpt1 = 'Presentación';
                        $defaultOpt2 = 'Peso';
                    } elseif (($industry ?? '') === 'electronics') {
                        $defaultOpt1 = 'Almacenamiento';
                        $defaultOpt2 = 'Color';
                    } elseif (($industry ?? '') === 'general') {
                        $defaultOpt1 = 'Opción';
                        $defaultOpt2 = 'Variante';
                    }
                    ?>
                    
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

                    <!-- NOMBRES DE ATRIBUTOS SEGÚN EL RUBRO (1, 2 O 3 OPCIONES DINÁMICAS) -->
                    <div class="form-row" style="margin-bottom: 16px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        <div class="form-group">
                            <label for="option1_name" style="font-size: 13px; font-weight: 600;">Atributo 1 *</label>
                            <input type="text" id="option1_name" name="option1_name" class="form-control" value="<?= htmlspecialchars($defaultOpt1) ?>" placeholder="Ej: Color, Sabor, Almacenamiento">
                        </div>
                        <div class="form-group">
                            <label for="option2_name" style="font-size: 13px; font-weight: 600;">Atributo 2 (Opcional)</label>
                            <input type="text" id="option2_name" name="option2_name" class="form-control" value="<?= htmlspecialchars($defaultOpt2) ?>" placeholder="Ej: Talla, Peso, Memoria">
                        </div>
                        <div class="form-group">
                            <label for="option3_name" style="font-size: 13px; font-weight: 600;">Atributo 3 (Opcional)</label>
                            <input type="text" id="option3_name" name="option3_name" class="form-control" value="" placeholder="Ej: Material, Conectividad, Acabado">
                        </div>
                    </div>

                    <?php require __DIR__ . '/_variant_tools.php'; ?>

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
                                    <th>Foto de Variante</th>
                                    <th style="text-align: center;"></th>
                                </tr>
                            </thead>
                            <tbody id="variants_table_body">
                                <tr class="variant-row">
                                    <td>
                                        <input type="text" name="variant_title[]" class="form-control" placeholder="Ej: Negro / S" style="padding: 8px 10px; font-size: 13px; width: 100%; box-sizing: border-box;">
                                    </td>
                                    <td>
                                        <input type="text" name="variant_sku[]" class="form-control" placeholder="SKU" style="padding: 8px 10px; font-size: 13px; width: 100%; box-sizing: border-box;">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="variant_price[]" class="form-control variant-num-input" placeholder="0.00" style="padding: 8px 10px; font-size: 13px; width: 100%; text-align: right; font-weight: 600; box-sizing: border-box;">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="variant_compare_price[]" class="form-control variant-num-input" placeholder="0.00" style="padding: 8px 10px; font-size: 13px; width: 100%; text-align: right; font-weight: 600; box-sizing: border-box;">
                                    </td>
                                    <td>
                                        <input type="number" min="0" name="variant_qty[]" class="form-control variant-num-input" value="5" style="padding: 8px 10px; font-size: 13px; width: 100%; text-align: right; font-weight: 600; box-sizing: border-box;">
                                    </td>
                                    <td>
                                        <div class="scms-var-img-picker" style="display: flex; align-items: center; gap: 6px;">
                                            <input type="hidden" name="variant_existing_image[]" value="">
                                            <input type="hidden" name="variant_clear_image[]" value="0">
                                            <input type="hidden" name="variant_bulk_ref[]" value="">
                                            <input type="hidden" name="variant_short_description[]" value="">
                                            <input type="hidden" name="variant_description[]" value="">
                                            <input type="hidden" name="variant_specs[]" value="">
                                            <div class="scms-var-thumb" style="width: 34px; height: 34px; border-radius: 6px; border: 1px solid #E5E7EB; background: #F9FAFB; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            </div>
                                            <label class="btn btn-secondary btn-sm" style="padding: 5px 8px; font-size: 11.5px; cursor: pointer; margin: 0;">
                                                <span class="scms-var-pick-label">Subir</span>
                                                <input type="file" name="variant_image_file[]" accept=".webp,.jpg,.jpeg,.png,image/webp,image/jpeg,image/png" style="display: none;" onchange="onVariantImagePicked(this)">
                                            </label>
                                            <button type="button" class="scms-var-clear btn btn-secondary btn-sm" onclick="clearVariantImage(this)" title="Quitar foto" style="display: none; padding: 4px 7px; font-size: 12px; color: #DC2626; border-color: #FCA5A5;">&times;</button>
                                        </div>
                                    </td>
                                    <td style="text-align: center; white-space: nowrap;">
                                        <div style="display: inline-flex; align-items: center; gap: 4px;">
                                            <button type="button" class="btn btn-secondary btn-sm scms-var-details-btn" onclick="scmsOpenVarDetailsModal(this)" title="Descripción y ficha técnica propia" style="padding: 5px 8px; font-size: 11px;">Ficha</button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="removeVariantRow(this)" title="Quitar variante" style="padding: 6px 8px; color: #DC2626; border-color: #FCA5A5;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
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
            <!-- ESTADO Y VISIBILIDAD -->
            <div class="polaris-card">
                <div class="card-header">
                    <h2 class="card-title">Estado del Producto</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="is_active">Visibilidad en Tienda</label>
                        <select id="is_active" name="is_active" class="form-control" style="font-weight: 600;">
                            <option value="1" selected>Activo (Visible en tienda web)</option>
                            <option value="0">Deshabilitado (Borrado lógico)</option>
                        </select>
                    </div>
                    <div style="font-size: 12px; color: var(--color-text-secondary); line-height: 1.4; background: #F9FAFB; padding: 10px; border-radius: 6px; border: 1px solid #E5E7EB;">
                        <strong>Borrado Lógico:</strong> Los productos activos aparecen en el catálogo. Si luego deshabilitas un producto, no aparecerá en ventas pero mantendrá intacto el historial de pedidos.
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
                                <option value="<?= $cat['id'] ?>" style="<?= $style ?>">
                                    <?= $prefix . htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- IMAGEN DESTACADA -->
            <div class="polaris-card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 class="card-title">Imagen Principal</h2>
                    <span class="badge" style="background: #ECFDF5; color: #065F46; font-size: 10.5px;">WebP recomendado</span>
                </div>
                <div class="card-body">
                    <div id="featured_image_error" style="display: none; background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 8px 10px; border-radius: 6px; font-size: 12px; margin-bottom: 10px;"></div>

                    <div id="featured_image_preview_wrap" style="display: none; position: relative; margin-bottom: 12px; border: 1px solid #E5E7EB; border-radius: 8px; padding: 8px; background: #F9FAFB; text-align: center;">
                        <button type="button" onclick="clearMainImageSelection()" title="Quitar imagen principal" style="position: absolute; top: 6px; right: 6px; background: #DC2626; color: #FFF; border: none; border-radius: 999px; width: 22px; height: 22px; font-size: 14px; font-weight: 700; cursor: pointer; line-height: 1;">&times;</button>
                        <img id="featured_image_preview_img" src="" alt="Vista previa" style="max-width: 100%; max-height: 150px; object-fit: contain; border-radius: 6px;">
                        <div id="featured_image_preview_meta" style="font-size: 11px; color: #4B5563; margin-top: 6px; word-break: break-all;"></div>
                    </div>

                    <div class="form-group">
                        <label for="featured_image" class="btn btn-secondary" style="width: 100%; justify-content: center; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span>Buscar imagen en mi equipo</span>
                        </label>
                        <input type="file" id="featured_image" name="featured_image" style="display: none;" accept=".webp,.jpg,.jpeg,.png,image/webp,image/jpeg,image/png" onchange="onMainImagePicked(this)">
                        <small style="display: block; margin-top: 6px; color: var(--color-text-secondary); font-size: 11px;">
                            Formatos: <strong>WebP (recomendado)</strong>, JPG o PNG · Máx. <strong>2 MB</strong>.
                        </small>
                    </div>

                    <details style="font-size: 12px; color: var(--color-text-secondary);">
                        <summary style="cursor: pointer; font-weight: 600;">O usar URL externa (opcional)</summary>
                        <div class="form-group" style="margin-top: 8px; margin-bottom: 0;">
                            <input type="text" id="featured_image_url" name="featured_image_url" class="form-control" placeholder="https://...">
                        </div>
                    </details>
                </div>
            </div>

            <!-- GALERÍA MÚLTIPLE DE IMÁGENES -->
            <div class="polaris-card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 class="card-title">Galería Adicional</h2>
                    <span id="gallery_counter_badge" class="badge" style="background: #EFF6FF; color: #1D4ED8; font-size: 11px;">0 / 6 fotos</span>
                </div>
                <div class="card-body">
                    <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 6px; padding: 8px 10px; font-size: 11.5px; color: #166534; margin-bottom: 12px; line-height: 1.4;">
                        <strong>Reglas:</strong> Usa formato <strong>WebP</strong> (recomendado por su ligereza y nitidez), JPG o PNG. Hasta <strong>6 fotos</strong> de máximo <strong>2 MB</strong> c/u.
                    </div>

                    <div id="gallery_validation_alert" style="display: none; background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 8px 10px; border-radius: 6px; font-size: 12px; margin-bottom: 10px; line-height: 1.4;"></div>

                    <div class="form-group">
                        <label for="gallery_images" class="btn btn-secondary" style="width: 100%; justify-content: center; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <span>Agregar fotos desde mi equipo</span>
                        </label>
                        <input type="file" id="gallery_images" name="gallery_images[]" style="display: none;" accept=".webp,.jpg,.jpeg,.png,image/webp,image/jpeg,image/png" multiple onchange="previewGalleryFiles(this)">
                    </div>
                    <div id="gallery_files_preview" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;"></div>

                    <details style="font-size: 12px; color: var(--color-text-secondary);">
                        <summary style="cursor: pointer; font-weight: 600;">O añadir URLs externas (opcional)</summary>
                        <div style="margin-top: 8px;">
                            <div id="gallery_urls_container" style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 8px;">
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <input type="text" name="gallery_urls[]" class="form-control" placeholder="https://..." oninput="renderGalleryQueue()">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="removeGalleryUrlRow(this)" title="Quitar URL" style="padding: 6px 10px; color: #DC2626; border-color: #FCA5A5;">&times;</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addGalleryUrlInput()">+ Otra URL</button>
                        </div>
                    </details>
                </div>
            </div>

            <!-- BOTÓN SUBMIT OCULTO PARA SOPORTE DE TECLADO / ENTER -->
            <button type="submit" style="display:none;" aria-hidden="true"></button>
        </div>
    </div>
</form>

<script>
function getMissingBaseFields() {
    const checks = [
        { id: 'name', label: 'Nombre del producto', check: v => v.trim() !== '' },
        { id: 'short_description', label: 'Descripción corta', check: v => v.trim() !== '' },
        { id: 'description', label: 'Descripción completa', check: v => v.trim() !== '' },
        { id: 'price', label: 'Precio de venta (mayor a 0)', check: v => v.trim() !== '' && parseFloat(v) > 0 },
        { id: 'sku', label: 'SKU base', check: v => v.trim() !== '' },
        { id: 'quantity', label: 'Cantidad disponible', check: v => v.trim() !== '' && !isNaN(parseInt(v, 10)) },
        { id: 'category_id', label: 'Categoría', check: v => v.trim() !== '' }
    ];

    const missing = [];
    checks.forEach(c => {
        const el = document.getElementById(c.id);
        const ok = el ? c.check(el.value || '') : false;
        if (el) {
            el.style.borderColor = ok ? '' : '#DC2626';
            if (!ok && !el.dataset.valListener) {
                el.dataset.valListener = '1';
                el.addEventListener('input', () => { el.style.borderColor = ''; });
                el.addEventListener('change', () => { el.style.borderColor = ''; });
            }
        }
        if (!ok) missing.push({ id: c.id, label: c.label, el });
    });
    return missing;
}

function showBaseValidationMessage(missing) {
    const box = document.getElementById('base-product-validation-alert');
    if (!box) return;
    if (!missing.length) {
        box.style.display = 'none';
        box.innerHTML = '';
        return;
    }
    box.innerHTML = '<strong>Completa todos los datos del producto base antes de crear variantes:</strong> '
        + missing.map(m => m.label).join(', ') + '.';
    box.style.display = 'block';
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    if (missing[0] && missing[0].el) {
        setTimeout(() => missing[0].el.focus(), 200);
    }
}

function validateProductBeforeSubmit() {
    const hasVar = document.getElementById('has_variants');
    if (hasVar && hasVar.checked) {
        const missing = getMissingBaseFields();
        if (missing.length > 0) {
            showBaseValidationMessage(missing);
            return false;
        }
        const titles = Array.from(document.querySelectorAll('input[name="variant_title[]"]'))
            .map(i => (i.value || '').trim())
            .filter(Boolean);
        if (titles.length === 0) {
            const box = document.getElementById('base-product-validation-alert');
            if (box) {
                box.innerHTML = '<strong>Falta al menos una variante:</strong> ingresa la combinación de opciones (ej. Negro / M) o desactiva las variantes.';
                box.style.display = 'block';
                box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return false;
        }
    }
    return true;
}

function toggleVariants(show) {
    const checkbox = document.getElementById('has_variants');
    const generalCard = document.getElementById('general_pricing_inventory_card');
    const variantsSection = document.getElementById('variants_section');
    const priceInput = document.getElementById('price');
    const qtyInput = document.getElementById('quantity');

    if (show) {
        const missing = getMissingBaseFields();
        if (missing.length > 0) {
            if (checkbox) checkbox.checked = false;
            showBaseValidationMessage(missing);
            return;
        }
        showBaseValidationMessage([]);
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
        const firstVarSku = document.querySelector('input[name="variant_sku[]"]');
        const baseSku = document.getElementById('sku');
        if (firstVarSku && !firstVarSku.value && baseSku && baseSku.value) {
            firstVarSku.value = baseSku.value.trim() + '-V1';
        }
    } else {
        showBaseValidationMessage([]);
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
            <input type="text" name="variant_title[]" class="form-control" placeholder="Ej: Blanco / L" required style="padding: 8px 10px; font-size: 13px; width: 100%; box-sizing: border-box;">
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
            <input type="number" min="0" name="variant_qty[]" class="form-control variant-num-input" value="5" style="padding: 8px 10px; font-size: 13px; width: 100%; text-align: right; font-weight: 600; box-sizing: border-box;">
        </td>
        <td>
            <div class="scms-var-img-picker" style="display: flex; align-items: center; gap: 6px;">
                <input type="hidden" name="variant_existing_image[]" value="">
                <input type="hidden" name="variant_clear_image[]" value="0">
                <input type="hidden" name="variant_bulk_ref[]" value="">
                <input type="hidden" name="variant_short_description[]" value="">
                <input type="hidden" name="variant_description[]" value="">
                <input type="hidden" name="variant_specs[]" value="">
                <div class="scms-var-thumb" style="width: 34px; height: 34px; border-radius: 6px; border: 1px solid #E5E7EB; background: #F9FAFB; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </div>
                <label class="btn btn-secondary btn-sm" style="padding: 5px 8px; font-size: 11.5px; cursor: pointer; margin: 0;">
                    <span class="scms-var-pick-label">Subir</span>
                    <input type="file" name="variant_image_file[]" accept=".webp,.jpg,.jpeg,.png,image/webp,image/jpeg,image/png" style="display: none;" onchange="onVariantImagePicked(this)">
                </label>
                <button type="button" class="scms-var-clear btn btn-secondary btn-sm" onclick="clearVariantImage(this)" title="Quitar foto" style="display: none; padding: 4px 7px; font-size: 12px; color: #DC2626; border-color: #FCA5A5;">&times;</button>
            </div>
        </td>
        <td style="text-align: center; white-space: nowrap;">
            <div style="display: inline-flex; align-items: center; gap: 4px;">
                <button type="button" class="btn btn-secondary btn-sm scms-var-details-btn" onclick="scmsOpenVarDetailsModal(this)" title="Descripción y ficha técnica propia" style="padding: 5px 8px; font-size: 11px;">Ficha</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="removeVariantRow(this)" title="Quitar variante" style="padding: 6px 8px; color: #DC2626; border-color: #FCA5A5;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
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
        const inputs = row.querySelectorAll('input[type="text"], input[type="number"]');
        inputs.forEach(input => input.value = '');
    }

    const form = tbody ? tbody.closest('form') : document.querySelector('form');
    if (form) {
        form.dispatchEvent(new Event('input', { bubbles: true }));
    }
}
</script>
