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
    <!-- BREADCRUMB -->
    <div style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
        <a href="<?= $baseUrl ?>/" style="color: var(--color-primary); text-decoration: none; font-weight: 600;">Inicio</a>
        <span>/</span>
        <a href="<?= $baseUrl ?>/catalog" style="color: var(--color-primary); text-decoration: none; font-weight: 600;">Catálogo</a>
        <span>/</span>
        <span style="color: var(--color-text); font-weight: 700;"><?= htmlspecialchars($product['name']) ?></span>
    </div>

    <div class="product-detail-layout">
        <!-- GALERÍA MÚLTIPLE PRODUCTO -->
        <?= \ShopCMS\Core\ThemeManager::renderProductGallery(
            $product,
            $galleryImages ?? [],
            $variants ?? [],
            $initialImage ?? '',
            $baseUrl,
            '<svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line></svg>'
        ) ?>

        <!-- INFO DEL PRODUCTO Y COMPRA -->
        <div class="product-info-box">
            <span style="display: inline-block; background: var(--color-primary-light); color: var(--color-primary-dark); font-size: 12px; font-weight: 700; text-transform: uppercase; padding: 4px 10px; border-radius: var(--radius-sm); margin-bottom: 10px;">
                <?= htmlspecialchars($product['category_name'] ?? 'Producto Destacado') ?>
            </span>

            <h1><?= htmlspecialchars($product['name']) ?></h1>

            <!-- ESTRELLAS Y RESEÑAS -->
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                <div style="display: inline-flex; align-items: center; gap: 2px;">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $s <= round($avgRating) ? '#D97706' : '#E7E0D5' ?>" stroke="<?= $s <= round($avgRating) ? '#D97706' : '#D1D5DB' ?>" stroke-width="1.5">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                    <?php endfor; ?>
                </div>
                <span style="font-size: 13px; color: var(--color-text-muted);">
                    <?= $avgRating > 0 ? "{$avgRating} / 5" : 'Sin calificaciones aún' ?>
                    (<?= $reviewLabel ?>)
                </span>
                <?php if (($soldCount ?? 0) > 0): ?>
                    <span style="color: var(--color-border);">|</span>
                    <span style="font-size: 13px; color: var(--color-text-muted); font-weight: 500;">+<?= \ShopCMS\Core\StorefrontExperience::formatCompactCount((int) $soldCount) ?> vendidos</span>
                <?php endif; ?>
                <span style="color: var(--color-border);">|</span>
                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 13px; color: var(--color-text-muted); font-weight: 600;" title="Clientes que guardaron este producto en favoritos">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="#EF4444" stroke="#EF4444" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <span id="scms_top_heart_count_<?= (int) $product['id'] ?>"><?= \ShopCMS\Core\StorefrontExperience::formatCompactCount((int) ($heartCount ?? 0)) ?></span>
                </span>
            </div>

            <!-- PRECIO -->
            <div class="price-box">
                <span id="display_price"><?= $currency['symbol'] ?><?= number_format($initialPrice, 2) ?></span>
                <span id="display_compare_price" style="display: <?= ($initialCompare !== null && $initialCompare > $initialPrice) ? 'inline' : 'none' ?>; font-size: 17px; color: var(--color-text-muted); text-decoration: line-through; margin-left: 10px; font-weight: 500;">
                    <?= ($initialCompare !== null && $initialCompare > $initialPrice) ? ($currency['symbol'] . number_format($initialCompare, 2)) : '' ?>
                </span>
                <span id="display_discount_badge" class="scms-discount-pill" style="display: <?= ($initialDiscountPct > 0) ? 'inline-flex' : 'none' ?>;">
                    <?= $initialDiscountPct > 0 ? "-{$initialDiscountPct}% dto." : '' ?>
                </span>
            </div>

            <p id="product_short_description" style="display: <?= $initialShortDesc !== '' ? 'block' : 'none' ?>; font-size: 15px; color: var(--color-text-muted); margin-bottom: 22px; line-height: 1.6;">
                <?= htmlspecialchars($initialShortDesc) ?>
            </p>

            <!-- FORMULARIO AGREGAR AL CARRITO -->
            <form method="POST" action="<?= $baseUrl ?>/cart/add">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                <!-- SELECTORES VISUALES DE VARIANTES -->
                <?= \ShopCMS\Core\StorefrontExperience::renderVariantSelectors($variantPayload) ?>

                <!-- DISPONIBILIDAD DE STOCK (SEGUN VARIANTE SELECCIONADA) -->
                <div id="stock_status_container" style="margin-bottom: 20px; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                    <?php if ($initialQty <= 0): ?>
                        <span id="stock_badge" style="color: #DC2626; font-weight: 700;">Agotado</span>
                    <?php elseif ($initialQty <= $product['low_stock_threshold']): ?>
                        <span id="stock_badge" style="color: var(--color-accent); font-weight: 700;">Últimas <?= $initialQty ?> unidades en stock</span>
                    <?php else: ?>
                        <span id="stock_badge" style="color: #166534; font-weight: 700;">En stock (<?= $initialQty ?> disponibles)</span>
                    <?php endif; ?>
                </div>

                <!-- SELECTOR DE CANTIDAD Y BOTÓN -->
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 22px;">
                    <input type="number" id="quantity_input" name="quantity" value="1" min="1" max="<?= max(1, $initialQty) ?>" style="width: 75px; padding: 12px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); text-align: center; font-size: 15px; font-weight: 700;">
                    <button type="submit" id="submit_cart_btn" class="btn-store btn-store-primary" style="flex: 1 1 170px;" <?= $initialQty <= 0 ? 'disabled' : '' ?>>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        Añadir al Carrito
                    </button>
                    <button type="submit" id="buy_now_btn" name="buy_now" value="1" data-buy-now="1" class="btn-store" style="flex: 1 1 140px; padding: 12px; background: transparent; color: var(--color-primary); border: 1.5px solid var(--color-primary); font-weight: 700; border-radius: var(--radius-sm);" <?= $initialQty <= 0 ? 'disabled' : '' ?>>
                        Comprar ahora
                    </button>
                </div>

                <!-- HOOKS DE PLUGINS (Wishlist, Comparador, Notificador) -->
                <?php \ShopCMS\Core\PluginManager::doAction('product_page_buttons', $product); ?>
            </form>

            <?php
            $descTitle = 'Descripción y Especificaciones';
            if (!empty($product['category_name']) && in_array(strtolower($product['category_name']), ['ropa', 'moda', 'calzado', 'prendas'])) {
                $descTitle = 'Descripción y Cuidados de la Prenda';
            } elseif (!empty($product['category_name']) && in_array(strtolower($product['category_name']), ['alimentos', 'comida', 'bebidas', 'gourmet'])) {
                $descTitle = 'Información del Producto y Consumo';
            }
            ?>
            <div id="product_description_wrap" style="display: <?= $initialDesc !== '' ? 'block' : 'none' ?>; margin-top: 32px; padding: 22px; background: #FFFFFF; border: 1px solid var(--color-card-border); border-radius: var(--radius-md);">
                <h3 style="font-size: 15px; font-weight: 800; color: var(--color-primary-dark); margin-bottom: 8px;"><?= $descTitle ?></h3>
                <div id="product_description_body" style="font-size: 14px; color: var(--color-text); line-height: 1.7;">
                    <?= nl2br(htmlspecialchars($initialDesc)) ?>
                </div>
            </div>
            <?= \ShopCMS\Core\StorefrontExperience::renderProductSpecsBlock($initialSpecs) ?>
        </div>
    </div>

    <!-- SECCIÓN DE RESEÑAS FOOD & MARKET -->
    <?= \ShopCMS\Core\StorefrontExperience::renderProductReviewsSection(
        $product,
        $reviews ?? [],
        $baseUrl,
        [
            'title'         => 'Opiniones de Clientes',
            'star_active'   => '#D97706',
            'star_empty'    => '#E7E0D5',
            'use_container' => false,
        ]
    ) ?>

    <!-- HOOK PARA RECOMENDACIONES (Related Products / Cross-sell) -->
    <?php \ShopCMS\Core\PluginManager::doAction('product_page_bottom', $product); ?>
</main>

<?php \ShopCMS\Core\ThemeManager::renderFooter(get_defined_vars()); ?>
