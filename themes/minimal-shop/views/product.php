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
$initialVariant = !empty($variants) ? $variants[0] : null;
$initialPrice = $initialVariant ? $initialVariant['price'] : $product['price'];
$initialQty = $initialVariant ? (int)$initialVariant['quantity'] : (int)$product['quantity'];
$initialImage = ($initialVariant && !empty($initialVariant['image_url'])) ? $initialVariant['image_url'] : $product['featured_image'];
$revCount = count($reviews);
$reviewLabel = ($revCount === 1) ? '1 opinión de clientes' : "{$revCount} opiniones de clientes";
$prodCfg = $themeConfig['templates']['product']['sections'] ?? [];
$pMain = $prodCfg['product_main'] ?? [];
$pRev = $prodCfg['product_reviews'] ?? [];
$pRel = $prodCfg['product_related'] ?? [];
$prodBuiltIn = [];
?>

<?php ob_start(); ?>
<section data-theme-section="product_main" data-section-label="Ficha de Producto" style="padding: 40px 0 20px;">
    <div class="container">
    <div class="product-detail-layout">
        <!-- GALERÍA PRINCIPAL MÚLTIPLE -->
        <?= \ShopCMS\Core\ThemeManager::renderProductGallery(
            $product,
            $galleryImages ?? [],
            $variants ?? [],
            $initialImage ?? '',
            $baseUrl,
            '<svg width="96" height="96" viewBox="0 0 24 24" fill="#CBD5E1"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>'
        ) ?>

        <!-- INFO Y FORMULARIO DE COMPRA -->
        <div class="product-info-box">
            <div style="font-size: 13px; text-transform: uppercase; color: var(--color-text-light); font-weight: 600; margin-bottom: 6px;">
                <?= htmlspecialchars($product['category_name'] ?? 'General') ?>
            </div>

            <h1 style="font-size: <?= (int)($pMain['title_size'] ?? 28) ?>px;"><?= htmlspecialchars($product['name']) ?></h1>

            <!-- CALIFICACIÓN ESTRELLAS -->
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                <div style="display: inline-flex; align-items: center; gap: 2px;">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= $s <= round($avgRating) ? '#F59E0B' : '#E5E7EB' ?>" stroke="<?= $s <= round($avgRating) ? '#F59E0B' : '#D1D5DB' ?>" stroke-width="1.5">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                    <?php endfor; ?>
                </div>
                <span style="font-size: 14px; color: var(--color-text-light);">
                    <?= $avgRating > 0 ? "{$avgRating} / 5" : 'Sin valoraciones todavía' ?>
                    (<?= $reviewLabel ?>)
                </span>
            </div>

            <!-- PRECIO -->
            <div class="price-box" style="font-size: <?= (int)($pMain['price_size'] ?? 24) ?>px;">
                <span id="display_price"><?= $currency['symbol'] ?><?= number_format($initialPrice, 2) ?></span>
                <?php if ($product['compare_price']): ?>
                    <span style="font-size: 18px; color: var(--color-text-light); text-decoration: line-through; margin-left: 10px;">
                        <?= $currency['symbol'] ?><?= number_format($product['compare_price'], 2) ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($product['short_description'])): ?>
                <p style="font-size: 15px; color: var(--color-text-light); margin-bottom: 24px; line-height: 1.6;">
                    <?= htmlspecialchars($product['short_description']) ?>
                </p>
            <?php endif; ?>

            <!-- FORMULARIO AGREGAR AL CARRITO -->
            <form method="POST" action="<?= $baseUrl ?>/cart/add">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                <!-- SELECTOR DE VARIANTES COMPUESTAS (1, 2 O 3 OPCIONES DINÁMICAS) -->
                <?php if ($maxParts >= 2 && !empty($variants)): ?>
                    <div style="display: grid; grid-template-columns: repeat(<?= min($maxParts, 3) ?>, 1fr); gap: 14px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;"><?= htmlspecialchars($opt1Label) ?>:</label>
                            <select id="option_val_1" onchange="updateDynamicVariants()" style="width: 100%; padding: 11px; border-radius: 8px; border: 1px solid var(--color-border); font-size: 14px; font-family: inherit;">
                                <?php foreach ($opt1Values as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;"><?= htmlspecialchars($opt2Label) ?>:</label>
                            <select id="option_val_2" onchange="updateDynamicVariants()" style="width: 100%; padding: 11px; border-radius: 8px; border: 1px solid var(--color-border); font-size: 14px; font-family: inherit;">
                                <?php foreach ($opt2Values as $s): ?>
                                    <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($maxParts >= 3): ?>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;"><?= htmlspecialchars($opt3Label) ?>:</label>
                            <select id="option_val_3" onchange="updateDynamicVariants()" style="width: 100%; padding: 11px; border-radius: 8px; border: 1px solid var(--color-border); font-size: 14px; font-family: inherit;">
                                <?php foreach ($opt3Values as $m): ?>
                                    <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="variant_id" id="variant_id_input" value="<?= $initialVariant['id'] ?>">
                <?php elseif (!empty($variants)): ?>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;"><?= htmlspecialchars($opt1Label ?: 'Selecciona Opción') ?>:</label>
                        <select name="variant_id" id="variant_select" onchange="onSingleVariantChange(this)" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--color-border); font-size: 14px; font-family: inherit;">
                            <?php foreach ($variants as $var): ?>
                                <option value="<?= $var['id'] ?>" data-price="<?= $var['price'] ?>" data-qty="<?= $var['quantity'] ?>" data-image="<?= htmlspecialchars($var['image_url'] ?? '') ?>">
                                    <?= htmlspecialchars($var['title']) ?> — <?= $currency['symbol'] ?><?= number_format($var['price'], 2) ?> (<?= $var['quantity'] ?> en stock)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- DISPONIBILIDAD DE STOCK (SEGUN VARIANTE SELECCIONADA) -->
                <div id="stock_status_container" style="margin-bottom: 20px; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                    <?php if ($initialQty <= 0): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D72C0D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                        <span id="stock_badge" style="color: #D72C0D; font-weight: 600;">Agotado</span>
                    <?php elseif ($initialQty <= $product['low_stock_threshold']): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#B95000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        <span id="stock_badge" style="color: #B95000; font-weight: 600;">Últimas <?= $initialQty ?> unidades en stock</span>
                    <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span id="stock_badge" style="color: #008060; font-weight: 600;">En stock (<?= $initialQty ?> disponibles)</span>
                    <?php endif; ?>
                </div>

                <!-- SELECTOR DE CANTIDAD Y BOTÓN -->
                <div style="display: flex; gap: 12px; margin-bottom: 24px;">
                    <input type="number" id="quantity_input" name="quantity" value="1" min="1" max="<?= max(1, $initialQty) ?>" style="width: 80px; padding: 12px; border: 1px solid var(--color-border); border-radius: 8px; text-align: center; font-size: 15px; font-weight: 600;">
                    
                    <button type="submit" id="submit_cart_btn" class="btn-store btn-store-primary" style="flex: 1; font-size: 16px; padding: 14px;" <?= $initialQty <= 0 ? 'disabled' : '' ?>>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 8px;">
                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                        </svg>
                        <span data-theme-field="buy_btn_text"><?= htmlspecialchars($pMain['buy_btn_text'] ?? 'Añadir al Carrito') ?></span>
                    </button>
                </div>

                <!-- HOOK PARA BOTONES DE PLUGINS (Wishlist, Comparador, etc.) -->
                <?php \ShopCMS\Core\PluginManager::doAction('product_page_buttons', $product); ?>
            </form>

            <!-- DESCRIPCIÓN DETALLADA -->
            <?php if (!empty($product['description'])): ?>
                <?php
                $descTitle = 'Descripción y Especificaciones';
                if (!empty($product['category_name']) && in_array(strtolower($product['category_name']), ['ropa', 'moda', 'calzado', 'prendas'])) {
                    $descTitle = 'Descripción y Cuidados de la Prenda';
                } elseif (!empty($product['category_name']) && in_array(strtolower($product['category_name']), ['alimentos', 'comida', 'bebidas', 'gourmet'])) {
                    $descTitle = 'Información y Detalles del Producto';
                }
                ?>
                <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--color-border);">
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;"><?= $descTitle ?></h3>
                    <div style="font-size: 14px; color: var(--color-text); line-height: 1.7;">
                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
</section>
<?php $prodBuiltIn['product_main'] = ob_get_clean(); ?>

    <!-- SECCIÓN DE RESEÑAS (COMPRADORES VERIFICADOS) -->
    <?php
    $isCustomerLoggedIn = \ShopCMS\Core\Auth::isCustomer();
    $customerInfo = \ShopCMS\Core\Auth::customer();
    $hasPurchased = $isCustomerLoggedIn ? \ShopCMS\Core\Auth::hasPurchasedProduct((int)$customerInfo['id'], (int)$product['id']) : false;
    ob_start();
    ?>
    <section data-theme-section="product_reviews" data-section-label="Reseñas de Clientes" style="padding: 36px 0; border-top: 2px solid var(--color-border);">
        <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="font-size: 24px; font-weight: 700;" data-theme-field="title"><?= htmlspecialchars($pRev['title'] ?? 'Opiniones de Clientes') ?> (<?= count($reviews) ?>)</h2>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
            <!-- LISTA DE RESEÑAS -->
            <div>
                <?php if (empty($reviews)): ?>
                    <p style="color: var(--color-text-light);">Aún no hay reseñas verificadas para este producto.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div style="padding: 16px; border: 1px solid <?= !empty($rev['is_featured']) ? '#A7F3D0' : 'var(--color-border)' ?>; border-radius: 10px; margin-bottom: 16px; background: <?= !empty($rev['is_featured']) ? '#F9FEFC' : '#FFF' ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <div style="display: inline-flex; gap: 2px;">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="<?= $s <= $rev['rating'] ? '#F59E0B' : '#E5E7EB' ?>" stroke="<?= $s <= $rev['rating'] ? '#F59E0B' : '#D1D5DB' ?>" stroke-width="1.5">
                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <strong style="font-size: 14px;"><?= htmlspecialchars($rev['title'] ?: 'Opinión') ?></strong>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #166534; background: #DCFCE7; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Compra Verificada
                                    </span>
                                    <?php if (!empty($rev['is_featured'])): ?>
                                        <span style="font-size: 11px; color: #92400E; background: #FEF3C7; padding: 2px 8px; border-radius: 4px; font-weight: 700;">Destacada</span>
                                    <?php endif; ?>
                                </div>
                                <span style="font-size: 12px; color: var(--color-text-light);"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></span>
                            </div>
                            <p style="font-size: 14px; color: var(--color-text); margin-bottom: 8px;"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
                            <small style="color: var(--color-text-light);">Por: <?= htmlspecialchars($rev['customer_name'] ?? $rev['author_name'] ?? 'Cliente') ?></small>
                            <?php if (!empty($rev['admin_reply'])): ?>
                                <div style="margin-top: 10px; padding: 10px 12px; background: #F3F4F6; border-left: 3px solid #008060; border-radius: 6px; font-size: 13px; color: #374151;">
                                    <strong style="display: block; font-size: 11.5px; color: #008060; margin-bottom: 3px;">Respuesta oficial de la tienda</strong>
                                    <?= nl2br(htmlspecialchars($rev['admin_reply'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- PANEL RESEÑA VERIFICADA -->
            <div>
                <?php if ($hasPurchased): ?>
                    <div style="background: var(--color-surface); padding: 24px; border-radius: 12px; border: 1px solid var(--color-border); height: fit-content;">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 12px; color: #166534; font-weight: 700; font-size: 13px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Comprador Verificado</span>
                        </div>
                        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 14px;">Comparte tu Experiencia</h3>
                        <form method="POST" action="<?= $baseUrl ?>/account/review">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                            <div style="margin-bottom: 14px;">
                                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Tu Calificación *</label>
                                <select name="rating" required style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--color-border); font-size: 13px;">
                                    <option value="5">5 de 5 estrellas - Excelente</option>
                                    <option value="4">4 de 5 estrellas - Bueno</option>
                                    <option value="3">3 de 5 estrellas - Regular</option>
                                    <option value="2">2 de 5 estrellas - Regular bajo</option>
                                    <option value="1">1 de 5 estrellas - Insatisfecho</option>
                                </select>
                            </div>

                            <div style="margin-bottom: 14px;">
                                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Título de tu opinión</label>
                                <input type="text" name="title" placeholder="Ej: Muy buena calidad" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--color-border); font-size: 13px;">
                            </div>

                            <div style="margin-bottom: 16px;">
                                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Comentario *</label>
                                <textarea name="comment" rows="3" required placeholder="Cuéntale a otros clientes qué tal te pareció..." style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--color-border); font-family: inherit; font-size: 13px;"></textarea>
                            </div>

                            <button type="submit" class="btn-store btn-store-primary" style="width: 100%; padding: 10px; font-size: 14px;">Publicar Opinión</button>
                        </form>
                    </div>
                <?php elseif ($isCustomerLoggedIn): ?>
                    <div style="background: var(--color-surface); padding: 24px; border-radius: 12px; border: 1px solid var(--color-border); text-align: center;">
                        <div style="color: #008060; margin-bottom: 10px;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </div>
                        <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 6px;">Opiniones 100% Reales</h4>
                        <p style="font-size: 13px; color: var(--color-text-light); line-height: 1.5; margin-bottom: 16px;">
                            Solo los clientes que han completado una compra de este producto pueden evaluarlo. Puedes calificar tus compras anteriores desde tu cuenta.
                        </p>
                        <a href="<?= $baseUrl ?>/account" class="btn-store btn-store-secondary" style="font-size: 13px; width: 100%; text-decoration: none; display: block; text-align: center;">Ver Mis Pedidos</a>
                    </div>
                <?php else: ?>
                    <div style="background: var(--color-surface); padding: 24px; border-radius: 12px; border: 1px solid var(--color-border); text-align: center;">
                        <div style="color: #008060; margin-bottom: 10px;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 6px;">Opiniones Verificadas</h4>
                        <p style="font-size: 13px; color: var(--color-text-light); line-height: 1.5; margin-bottom: 16px;">
                            Para garantizar la autenticidad, únicamente clientes registrados que compraron este producto pueden dejar una opinión.
                        </p>
                        <a href="<?= $baseUrl ?>/login" class="btn-store btn-store-primary" style="font-size: 13px; width: 100%; text-decoration: none; display: block; text-align: center;">Iniciar Sesión</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </section>
    <?php $prodBuiltIn['product_reviews'] = ob_get_clean(); ?>

    <!-- HOOK PARA SECCIONES AL PIE DE PRODUCTO (Related Products, etc.) -->
    <?php ob_start(); ?>
    <section data-theme-section="product_related" data-section-label="Productos Relacionados" style="padding: 20px 0 50px;">
        <div class="container">
            <?php \ShopCMS\Core\PluginManager::doAction('product_page_bottom', $product); ?>
        </div>
    </section>
    <?php $prodBuiltIn['product_related'] = ob_get_clean(); ?>

    <main class="scms-template-sections">
        <?php \ShopCMS\Core\ThemeCustomizer::renderOrderedSections($themeSlug ?? 'minimal-shop', 'product', $prodBuiltIn, $baseUrl); ?>
    </main>

<script>
const variantsData = <?= json_encode($variants) ?>;
const baseUrl = "<?= $baseUrl ?>";
const currencySymbol = "<?= $currency['symbol'] ?>";
const defaultProductImage = <?= json_encode(\ShopCMS\Core\ThemeManager::resolveImageUrl(!empty($product['featured_image']) ? $product['featured_image'] : ($galleryImages[0]['image_url'] ?? ''), $baseUrl)) ?>;
const lowStockThreshold = <?= (int)($product['low_stock_threshold'] ?? 5) ?>;
const maxParts = <?= (int)$maxParts ?>;

function applyVariant(v) {
    if (!v) return;

    // 1. Actualizar ID en el formulario
    const input = document.getElementById('variant_id_input');
    if (input) input.value = v.id;

    // 2. Actualizar precio
    const displayPrice = document.getElementById('display_price');
    if (displayPrice) {
        displayPrice.innerText = currencySymbol + parseFloat(v.price).toFixed(2);
    }

    // 3. Actualizar stock y badge
    const qty = parseInt(v.quantity, 10);
    const stockBadge = document.getElementById('stock_badge');
    const submitBtn = document.getElementById('submit_cart_btn');
    const qtyInput = document.getElementById('quantity_input');

    if (qtyInput) {
        qtyInput.max = Math.max(1, qty);
        if (parseInt(qtyInput.value, 10) > qty && qty > 0) {
            qtyInput.value = qty;
        }
    }

    if (stockBadge) {
        if (qty <= 0) {
            stockBadge.style.color = '#D72C0D';
            stockBadge.innerText = 'Agotado';
            if (submitBtn) submitBtn.disabled = true;
        } else if (qty <= lowStockThreshold) {
            stockBadge.style.color = '#B95000';
            stockBadge.innerText = 'Últimas ' + qty + ' unidades en stock';
            if (submitBtn) submitBtn.disabled = false;
        } else {
            stockBadge.style.color = '#008060';
            stockBadge.innerText = 'En stock (' + qty + ' disponibles)';
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    // 4. Actualizar imagen según la variante seleccionada
    const imgElem = document.getElementById('main_product_image');
    if (imgElem) {
        if (v.image_url && v.image_url.trim() !== '') {
            const rawV = v.image_url.trim();
            imgElem.src = (/^https?:\/\//i.test(rawV) || rawV.indexOf('/') === 0) ? rawV : (baseUrl + '/' + rawV.replace(/^\/+/, ''));
        } else if (defaultProductImage) {
            imgElem.src = defaultProductImage;
        }
        if (typeof window.syncProductGalleryActive === 'function') {
            window.syncProductGalleryActive(imgElem.src);
        }
    }
}

function updateDynamicVariants() {
    if (maxParts >= 2) {
        const o1Elem = document.getElementById('option_val_1');
        const o2Elem = document.getElementById('option_val_2');
        const o3Elem = document.getElementById('option_val_3');

        const v1 = o1Elem ? o1Elem.value.trim() : '';
        const v2 = o2Elem ? o2Elem.value.trim() : '';
        const v3 = o3Elem ? o3Elem.value.trim() : '';

        let targetTitle = v1 + ' / ' + v2;
        if (maxParts >= 3 && v3 !== '') {
            targetTitle += ' / ' + v3;
        }

        let matched = variantsData.find(v => v.title === targetTitle);
        if (!matched) {
            if (maxParts >= 3) {
                matched = variantsData.find(v => v.title.startsWith(v1 + ' / ' + v2 + ' / ')) ||
                          variantsData.find(v => v.title.startsWith(v1 + ' / ')) ||
                          variantsData[0];
            } else {
                matched = variantsData.find(v => v.title.startsWith(v1 + ' / ')) ||
                          variantsData[0];
            }

            if (matched) {
                const parts = matched.title.split(' / ');
                if (parts[1] && o2Elem) o2Elem.value = parts[1].trim();
                if (parts[2] && o3Elem) o3Elem.value = parts[2].trim();
            }
        }

        if (matched) {
            applyVariant(matched);
        }
    }
}

function onSingleVariantChange(select) {
    const id = parseInt(select.value, 10);
    const matched = variantsData.find(v => parseInt(v.id, 10) === id);
    if (matched) {
        applyVariant(matched);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (maxParts >= 2) {
        updateDynamicVariants();
    } else if (variantsData.length > 0) {
        applyVariant(variantsData[0]);
    }
});
</script>

<?php \ShopCMS\Core\ThemeManager::renderFooter(get_defined_vars()); ?>

