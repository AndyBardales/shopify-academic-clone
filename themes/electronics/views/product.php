<?php \ShopCMS\Core\ThemeManager::renderHeader(get_defined_vars()); ?>
<?php
// Procesar variantes y etiquetas dinámicas de opciones (1, 2 o 3 opciones)
$maxParts = 1;
if (!empty($variants)) {
    foreach ($variants as $v) {
        $pCount = count(explode(' / ', $v['title']));
        if ($pCount > $maxParts) {
            $maxParts = $pCount;
        }
    }
}

$opt1Values = [];
$opt2Values = [];
$opt3Values = [];

if (!empty($variants)) {
    foreach ($variants as $v) {
        $parts = array_map('trim', explode(' / ', $v['title']));
        if (isset($parts[0]) && $parts[0] !== '' && !in_array($parts[0], $opt1Values)) {
            $opt1Values[] = $parts[0];
        }
        if (isset($parts[1]) && $parts[1] !== '' && !in_array($parts[1], $opt2Values)) {
            $opt2Values[] = $parts[1];
        }
        if (isset($parts[2]) && $parts[2] !== '' && !in_array($parts[2], $opt3Values)) {
            $opt3Values[] = $parts[2];
        }
    }
}

$opt1Label = !empty($product['option1_name']) ? $product['option1_name'] : '';
$opt2Label = !empty($product['option2_name']) ? $product['option2_name'] : '';
$opt3Label = !empty($product['option3_name']) ? $product['option3_name'] : '';

// Inferencia inteligente de nombres si no se han configurado
if (empty($opt1Label)) {
    $sample1 = implode(' ', $opt1Values);
    if (preg_match('/\b(GB|TB|MB|RAM|SSD)\b/i', $sample1)) {
        $opt1Label = 'Almacenamiento';
    } elseif (preg_match('/\b(kg|g|gr|ml|l|lt|oz|botella|caja|pack)\b/i', $sample1)) {
        $opt1Label = 'Presentación';
    } elseif ($maxParts >= 2) {
        $opt1Label = 'Color';
    } else {
        $opt1Label = 'Opción / Variante';
    }
}

if (empty($opt2Label) && $maxParts >= 2) {
    $sample2 = implode(' ', $opt2Values);
    if (preg_match('/\b(kg|g|gr|ml|l|lt|oz|botella|caja|pack)\b/i', $sample2)) {
        $opt2Label = 'Peso / Cantidad';
    } elseif (preg_match('/\b(S|M|L|XL|XXL|XS|[0-9]{2})\b/', $sample2)) {
        $opt2Label = 'Talla';
    } elseif (preg_match('/\b(GB|TB|MB|RAM|SSD)\b/i', $sample2)) {
        $opt2Label = 'Memoria RAM';
    } else {
        $opt2Label = 'Opción 2';
    }
}

if (empty($opt3Label) && $maxParts >= 3) {
    $opt3Label = 'Material / Opción 3';
}
$variantPayload = \ShopCMS\Core\StorefrontExperience::buildVariantExperiencePayload(
    $product,
    $variants ?? [],
    $galleryImages ?? [],
    $baseUrl,
    $currency
);
$initialVariant = $variantPayload['initial_variant'];
$initialPrice = $initialVariant ? (float)$initialVariant['price'] : (float)$product['price'];
$initialCompare = $initialVariant ? $initialVariant['compare_price'] : $variantPayload['base_compare_price'];
$initialDiscountPct = $initialVariant ? (int)$initialVariant['discount_pct'] : (int)$variantPayload['base_discount_pct'];
$initialQty = $initialVariant ? (int)$initialVariant['quantity'] : (int)$product['quantity'];
$initialImage = ($initialVariant && !empty($initialVariant['image_url'])) ? $initialVariant['image_url'] : $product['featured_image'];
$initialShortDesc = $initialVariant ? $initialVariant['short_description'] : $variantPayload['base_short_description'];
$initialDesc = $initialVariant ? $initialVariant['description'] : $variantPayload['base_description'];
$initialSpecs = $initialVariant ? $initialVariant['specs'] : $variantPayload['base_specs'];
$revCount = count($reviews);
$reviewLabel = ($revCount === 1) ? '1 opinión de clientes' : "{$revCount} opiniones de clientes";
?>

<main class="container" style="padding-top: 36px; padding-bottom: 80px;">
    <!-- BREADCRUMB TECH -->
    <div style="font-family: var(--font-mono); font-size: 12px; color: var(--color-text-light); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
        <a href="<?= $baseUrl ?>/" style="color: var(--color-primary); text-decoration: none;">INICIO</a>
        <span>/</span>
        <a href="<?= $baseUrl ?>/catalog" style="color: var(--color-primary); text-decoration: none;">CATÁLOGO</a>
        <span>/</span>
        <span style="color: #0F172A; font-weight: 600;"><?= htmlspecialchars($product['name']) ?></span>
    </div>

    <div class="product-detail-layout">
        <!-- GALERÍA TECH MÚLTIPLE -->
        <?= \ShopCMS\Core\ThemeManager::renderProductGallery(
            $product,
            $galleryImages ?? [],
            $variants ?? [],
            $initialImage ?? '',
            $baseUrl,
            '<svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>'
        ) ?>

        <!-- ESPECIFICACIONES Y COMPRA -->
        <div class="product-info-box">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: #E0E7FF; color: #3730A3; font-family: var(--font-mono); font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px;">
                    SKU: <?= htmlspecialchars($product['sku'] ?? 'TECH-' . $product['id']) ?>
                </span>
                <span style="font-size: 12px; color: var(--color-text-light); font-weight: 600; text-transform: uppercase;">
                    <?= htmlspecialchars($product['category_name'] ?? 'Dispositivos') ?>
                </span>
            </div>

            <h1><?= htmlspecialchars($product['name']) ?></h1>

            <!-- CALIFICACIÓN ESTRELLAS -->
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                <div style="display: inline-flex; align-items: center; gap: 2px;">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $s <= round($avgRating) ? '#F59E0B' : '#E2E8F0' ?>" stroke="<?= $s <= round($avgRating) ? '#F59E0B' : '#CBD5E1' ?>" stroke-width="1.5">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                    <?php endfor; ?>
                </div>
                <span style="font-family: var(--font-mono); font-size: 13px; color: var(--color-text-light);">
                    <?= $avgRating > 0 ? "{$avgRating}/5.0" : 'Sin valoraciones aún' ?> (<?= $reviewLabel ?>)
                </span>
                <?php if (($soldCount ?? 0) > 0): ?>
                    <span style="color: var(--color-border);">|</span>
                    <span style="font-family: var(--font-mono); font-size: 12px; color: var(--color-text-light);">+<?= \ShopCMS\Core\StorefrontExperience::formatCompactCount((int) $soldCount) ?> vendidos</span>
                <?php endif; ?>
                <span style="color: var(--color-border);">|</span>
                <span style="display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-mono); font-size: 12px; color: var(--color-text-light); font-weight: 700;" title="Clientes que guardaron este producto en favoritos">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="#EF4444" stroke="#EF4444" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <span id="scms_top_heart_count_<?= (int) $product['id'] ?>"><?= \ShopCMS\Core\StorefrontExperience::formatCompactCount((int) ($heartCount ?? 0)) ?></span>
                </span>
            </div>

            <!-- PRECIO TECH -->
            <div class="price-box">
                <span id="display_price"><?= $currency['symbol'] ?><?= number_format($initialPrice, 2) ?></span>
                <span id="display_compare_price" data-prefix="MSRP: " style="display: <?= ($initialCompare !== null && $initialCompare > $initialPrice) ? 'inline' : 'none' ?>; font-size: 16px; color: var(--color-text-light); text-decoration: line-through; margin-left: 12px; font-weight: 500;">
                    <?= ($initialCompare !== null && $initialCompare > $initialPrice) ? ('MSRP: ' . $currency['symbol'] . number_format($initialCompare, 2)) : '' ?>
                </span>
                <span id="display_discount_badge" class="scms-discount-pill" style="display: <?= ($initialDiscountPct > 0) ? 'inline-flex' : 'none' ?>;">
                    <?= $initialDiscountPct > 0 ? "-{$initialDiscountPct}% dto." : '' ?>
                </span>
            </div>

            <p id="product_short_description" style="display: <?= $initialShortDesc !== '' ? 'block' : 'none' ?>; font-size: 14px; color: var(--color-text-light); margin-bottom: 20px; line-height: 1.6;">
                <?= htmlspecialchars($initialShortDesc) ?>
            </p>

            <!-- FORMULARIO AGREGAR AL CARRITO -->
            <form method="POST" action="<?= $baseUrl ?>/cart/add">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                <!-- VARIANTES TÉCNICAS VISUALES -->
                <?= \ShopCMS\Core\StorefrontExperience::renderVariantSelectors($variantPayload) ?>

                <!-- DISPONIBILIDAD TÉCNICA (SEGUN VARIANTE SELECCIONADA) -->
                <div id="stock_status_container" style="margin-bottom: 20px; font-family: var(--font-mono); font-size: 12px; display: flex; align-items: center; gap: 8px;">
                    <?php if ($initialQty <= 0): ?>
                        <span id="stock_badge" data-stock-mode="pill" style="background: #FEE2E2; color: #991B1B; padding: 4px 8px; border-radius: 4px; font-weight: 700;">AGOTADO</span>
                    <?php elseif ($initialQty <= $product['low_stock_threshold']): ?>
                        <span id="stock_badge" data-stock-mode="pill" style="background: #FEF3C7; color: #92400E; padding: 4px 8px; border-radius: 4px; font-weight: 700;">ÚLTIMAS <?= $initialQty ?> UNIDADES EN STOCK</span>
                    <?php else: ?>
                        <span id="stock_badge" data-stock-mode="pill" style="background: #DCFCE7; color: #166534; padding: 4px 8px; border-radius: 4px; font-weight: 700;">EN STOCK (<?= $initialQty ?> DISPONIBLES)</span>
                    <?php endif; ?>
                </div>

                <!-- SELECTOR DE CANTIDAD Y BOTÓN -->
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
                    <input type="number" id="quantity_input" name="quantity" value="1" min="1" max="<?= max(1, $initialQty) ?>" style="width: 75px; padding: 12px; border: 1px solid var(--color-border); border-radius: 6px; text-align: center; font-family: var(--font-mono); font-size: 14px; font-weight: 700;">
                    <button type="submit" id="submit_cart_btn" class="btn-store btn-store-primary" style="flex: 1 1 170px; font-family: var(--font-mono); font-weight: 700;" <?= $initialQty <= 0 ? 'disabled' : '' ?>>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        Añadir al Carrito
                    </button>
                    <button type="submit" id="buy_now_btn" name="buy_now" value="1" data-buy-now="1" class="btn-store" style="flex: 1 1 140px; padding: 12px; font-family: var(--font-mono); font-weight: 700; background: transparent; color: var(--color-primary); border: 1.5px solid var(--color-primary); border-radius: 6px;" <?= $initialQty <= 0 ? 'disabled' : '' ?>>
                        Comprar ahora
                    </button>
                </div>

                <!-- HOOKS DE PLUGINS (Comparador, Wishlist, Stock Notifier) -->
                <?php \ShopCMS\Core\PluginManager::doAction('product_page_buttons', $product); ?>
            </form>

            <div id="product_description_wrap" style="display: <?= $initialDesc !== '' ? 'block' : 'none' ?>; margin-top: 32px; padding: 20px; background: #FFFFFF; border: 1px solid var(--color-border); border-radius: 8px;">
                <h3 style="font-family: var(--font-mono); font-size: 14px; font-weight: 700; margin-bottom: 10px; color: #0F172A; text-transform: uppercase;">Descripción y Especificaciones</h3>
                <div id="product_description_body" style="font-size: 13px; color: #334155; line-height: 1.7;">
                    <?= nl2br(htmlspecialchars($initialDesc)) ?>
                </div>
            </div>
            <?= \ShopCMS\Core\StorefrontExperience::renderProductSpecsBlock($initialSpecs) ?>
        </div>
    </div>

    <!-- SECCIÓN DE RESEÑAS TECH (SOLO COMPRADORES VERIFICADOS) -->
    <?php
    $isCustomerLoggedIn = \ShopCMS\Core\Auth::isCustomer();
    $customerInfo = \ShopCMS\Core\Auth::customer();
    $hasPurchased = $isCustomerLoggedIn ? \ShopCMS\Core\Auth::hasPurchasedProduct((int)$customerInfo['id'], (int)$product['id']) : false;
    ?>
    <section style="margin-top: 50px; padding-top: 36px; border-top: 2px solid var(--color-border);">
        <h2 style="font-family: var(--font-mono); font-size: 18px; font-weight: 700; color: #0F172A; margin-bottom: 24px; text-transform: uppercase;">
            Benchmarks & Calificaciones de Usuarios (<?= count($reviews) ?>)
        </h2>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px;">
            <div>
                <?php if (empty($reviews)): ?>
                    <div style="padding: 24px; background: #FFF; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px; color: var(--color-text-light);">
                        Sin benchmarks registrados para este hardware.
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div style="padding: 16px; border: 1px solid var(--color-border); border-radius: 6px; margin-bottom: 14px; background: #FFF;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <strong style="font-family: var(--font-mono); font-size: 13px;"><?= htmlspecialchars($rev['customer_name'] ?? $rev['author_name'] ?? 'Cliente') ?></strong>
                                    <span style="display: inline-flex; align-items: center; gap: 3px; font-family: var(--font-mono); font-size: 10px; color: #166534; background: #DCFCE7; padding: 2px 6px; border-radius: 3px; font-weight: 700;">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Comprador Verificado
                                    </span>
                                    <?php if (!empty($rev['is_featured'])): ?>
                                        <span style="font-family: var(--font-mono); font-size: 10px; color: #92400E; background: #FEF3C7; padding: 2px 6px; border-radius: 3px; font-weight: 700;">Destacada</span>
                                    <?php endif; ?>
                                </div>
                                <span style="font-family: var(--font-mono); font-size: 11px; color: var(--color-text-light);"><?= date('Y-m-d', strtotime($rev['created_at'])) ?></span>
                            </div>
                            <div style="display: flex; gap: 2px; margin-bottom: 8px;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="<?= $i <= $rev['rating'] ? '#2563EB' : '#E2E8F0' ?>" stroke="<?= $i <= $rev['rating'] ? '#2563EB' : '#CBD5E1' ?>" stroke-width="1.5">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <p style="font-size: 13px; color: #334155; margin: 0; line-height: 1.5;"><?= htmlspecialchars($rev['comment']) ?></p>
                            <?php if (!empty($rev['admin_reply'])): ?>
                                <div style="margin-top: 10px; padding: 9px 12px; background: #F8FAFC; border-left: 3px solid #2563EB; border-radius: 4px; font-size: 12.5px; color: #334155;">
                                    <strong style="display: block; font-size: 11px; color: #1D4ED8; margin-bottom: 2px;">Respuesta oficial de la tienda</strong>
                                    <?= nl2br(htmlspecialchars($rev['admin_reply'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- PANEL RESEÑA VERIFICADA TECH -->
            <div>
                <?php if ($hasPurchased): ?>
                    <div style="background: #FFF; border: 1px solid var(--color-border); border-radius: 6px; padding: 20px;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 12px; color: #166534; font-weight: 700; font-size: 12px; font-family: var(--font-mono);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Comprador Verificado</span>
                        </div>
                        <h3 style="font-family: var(--font-mono); font-size: 13px; font-weight: 700; margin-bottom: 14px; text-transform: uppercase;">Registrar Benchmark / Evaluación</h3>
                        <form method="POST" action="<?= $baseUrl ?>/account/review">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <div style="margin-bottom: 12px;">
                                <label style="display: block; font-family: var(--font-mono); font-size: 11px; font-weight: 600; margin-bottom: 4px;">Puntuación Técnica *</label>
                                <select name="rating" required style="width: 100%; padding: 8px 10px; border: 1px solid var(--color-border); border-radius: 4px; font-size: 13px; font-family: var(--font-mono);">
                                    <option value="5">5 / 5 - Rendimiento Óptimo</option>
                                    <option value="4">4 / 5 - Cumple especificaciones</option>
                                    <option value="3">3 / 5 - Aceptable</option>
                                    <option value="2">2 / 5 - Inestable</option>
                                    <option value="1">1 / 5 - Fallas críticas</option>
                                </select>
                            </div>
                            <div style="margin-bottom: 16px;">
                                <label style="display: block; font-family: var(--font-mono); font-size: 11px; font-weight: 600; margin-bottom: 4px;">Reporte de Rendimiento *</label>
                                <textarea name="comment" required rows="3" placeholder="Describe tu experiencia de hardware..." style="width: 100%; padding: 8px 10px; border: 1px solid var(--color-border); border-radius: 4px; font-size: 13px;"></textarea>
                            </div>
                            <button type="submit" class="btn-store btn-store-primary" style="width: 100%; font-family: var(--font-mono); font-size: 12px;">Publicar Evaluación Verificada</button>
                        </form>
                    </div>
                <?php elseif ($isCustomerLoggedIn): ?>
                    <div style="background: #FFF; border: 1px solid var(--color-border); border-radius: 6px; padding: 20px; text-align: center;">
                        <div style="color: #2563EB; margin-bottom: 8px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </div>
                        <h4 style="font-family: var(--font-mono); font-size: 13px; font-weight: 700; margin-bottom: 6px; text-transform: uppercase;">Evaluaciones Verificadas</h4>
                        <p style="font-size: 12px; color: var(--color-text-light); line-height: 1.5; margin-bottom: 14px;">
                            Solo los clientes que han adquirido este producto tecnológico pueden certificar su rendimiento.
                        </p>
                        <a href="<?= $baseUrl ?>/account" class="btn-store btn-store-secondary" style="font-family: var(--font-mono); font-size: 11px; width: 100%; text-decoration: none; display: block; text-align: center;">Ver Mis Pedidos</a>
                    </div>
                <?php else: ?>
                    <div style="background: #FFF; border: 1px solid var(--color-border); border-radius: 6px; padding: 20px; text-align: center;">
                        <div style="color: #2563EB; margin-bottom: 8px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <h4 style="font-family: var(--font-mono); font-size: 13px; font-weight: 700; margin-bottom: 6px; text-transform: uppercase;">Benchmarks Certificados</h4>
                        <p style="font-size: 12px; color: var(--color-text-light); line-height: 1.5; margin-bottom: 14px;">
                            Inicia sesión con tu cuenta para calificar los dispositivos que hayas adquirido en la tienda.
                        </p>
                        <a href="<?= $baseUrl ?>/login" class="btn-store btn-store-primary" style="font-family: var(--font-mono); font-size: 11px; width: 100%; text-decoration: none; display: block; text-align: center;">Iniciar Sesión</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- HOOK PARA RECOMENDACIONES (Related Products / Cross-sell) -->
    <?php \ShopCMS\Core\PluginManager::doAction('product_page_bottom', $product); ?>
</main>

<?php \ShopCMS\Core\ThemeManager::renderFooter(get_defined_vars()); ?>
