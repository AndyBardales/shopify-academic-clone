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
                <?php if ($product['compare_price']): ?>
                    <span style="font-size: 17px; color: var(--color-text-muted); text-decoration: line-through; margin-left: 10px; font-weight: 500;">
                        <?= $currency['symbol'] ?><?= number_format($product['compare_price'], 2) ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($product['short_description'])): ?>
                <p style="font-size: 15px; color: var(--color-text-muted); margin-bottom: 22px; line-height: 1.6;">
                    <?= htmlspecialchars($product['short_description']) ?>
                </p>
            <?php endif; ?>

            <!-- FORMULARIO AGREGAR AL CARRITO -->
            <form method="POST" action="<?= $baseUrl ?>/cart/add">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                <!-- SELECTORES DINÁMICOS DE VARIANTES (1, 2 O 3 OPCIONES SEPARADAS) -->
                <?php if ($maxParts >= 2 && !empty($variants)): ?>
                    <div style="display: grid; grid-template-columns: repeat(<?= min($maxParts, 3) ?>, 1fr); gap: 14px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: var(--color-text);"><?= htmlspecialchars($opt1Label) ?>:</label>
                            <select id="option_val_1" onchange="updateDynamicVariants()" style="width: 100%; padding: 10px 12px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 14px; font-family: inherit;">
                                <?php foreach ($opt1Values as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: var(--color-text);"><?= htmlspecialchars($opt2Label) ?>:</label>
                            <select id="option_val_2" onchange="updateDynamicVariants()" style="width: 100%; padding: 10px 12px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 14px; font-family: inherit;">
                                <?php foreach ($opt2Values as $s): ?>
                                    <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($maxParts >= 3): ?>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: var(--color-text);"><?= htmlspecialchars($opt3Label) ?>:</label>
                            <select id="option_val_3" onchange="updateDynamicVariants()" style="width: 100%; padding: 10px 12px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 14px; font-family: inherit;">
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
                        <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: var(--color-text);"><?= htmlspecialchars($opt1Label ?: 'Opción / Variante') ?>:</label>
                        <select name="variant_id" id="variant_select" onchange="onSingleVariantChange(this)" style="width: 100%; padding: 11px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 14px; font-family: inherit;">
                            <?php foreach ($variants as $var): ?>
                                <option value="<?= $var['id'] ?>" data-price="<?= $var['price'] ?>" data-qty="<?= $var['quantity'] ?>" data-image="<?= htmlspecialchars($var['image_url'] ?? '') ?>">
                                    <?= htmlspecialchars($var['title']) ?> — <?= $currency['symbol'] ?><?= number_format($var['price'], 2) ?> (<?= $var['quantity'] ?> en stock)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

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

            <?php if (!empty($product['description'])): ?>
                <?php
                $descTitle = 'Descripción y Especificaciones';
                if (!empty($product['category_name']) && in_array(strtolower($product['category_name']), ['ropa', 'moda', 'calzado', 'prendas'])) {
                    $descTitle = 'Descripción y Cuidados de la Prenda';
                } elseif (!empty($product['category_name']) && in_array(strtolower($product['category_name']), ['alimentos', 'comida', 'bebidas', 'gourmet'])) {
                    $descTitle = 'Información del Producto y Consumo';
                }
                ?>
                <div style="margin-top: 32px; padding: 22px; background: #FFFFFF; border: 1px solid var(--color-card-border); border-radius: var(--radius-md);">
                    <h3 style="font-size: 15px; font-weight: 800; color: var(--color-primary-dark); margin-bottom: 8px;"><?= $descTitle ?></h3>
                    <div style="font-size: 14px; color: var(--color-text); line-height: 1.7;">
                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECCIÓN DE RESEÑAS FOOD & MARKET -->
    <?php
    $isCustomerLoggedIn = \ShopCMS\Core\Auth::isCustomer();
    $customerInfo = \ShopCMS\Core\Auth::customer();
    $hasPurchased = $isCustomerLoggedIn ? \ShopCMS\Core\Auth::hasPurchasedProduct((int)$customerInfo['id'], (int)$product['id']) : false;
    ?>
    <section style="margin-top: 50px; padding-top: 36px; border-top: 1px solid var(--color-card-border);">
        <h2 style="font-size: 22px; font-weight: 800; color: var(--color-text); margin-bottom: 24px;">
            Opiniones de Clientes (<?= count($reviews) ?>)
        </h2>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 36px;">
            <div>
                <?php if (empty($reviews)): ?>
                    <div style="padding: 24px; background: #FFF; border: 1px solid var(--color-card-border); border-radius: var(--radius-md); font-size: 14px; color: var(--color-text-muted);">
                        Aún no hay opiniones de clientes para este producto.
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div style="padding: 18px; border: 1px solid var(--color-card-border); border-radius: var(--radius-md); margin-bottom: 14px; background: #FFF; box-shadow: var(--shadow-soft);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px;">
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <strong style="font-size: 14px; color: var(--color-primary-dark);"><?= htmlspecialchars($rev['customer_name'] ?? $rev['author_name'] ?? 'Cliente') ?></strong>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #166534; background: #DCFCE7; padding: 2px 8px; border-radius: 4px; font-weight: 700;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Compra Verificada
                                    </span>
                                    <?php if (!empty($rev['is_featured'])): ?>
                                        <span style="font-size: 11px; color: #92400E; background: #FEF3C7; padding: 2px 8px; border-radius: 4px; font-weight: 700;">Destacada</span>
                                    <?php endif; ?>
                                </div>
                                <span style="font-size: 12px; color: var(--color-text-muted);"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></span>
                            </div>
                            <div style="display: flex; gap: 2px; margin-bottom: 8px;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="<?= $i <= $rev['rating'] ? '#D97706' : '#E7E0D5' ?>" stroke="<?= $i <= $rev['rating'] ? '#D97706' : '#D1D5DB' ?>" stroke-width="1.5">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <p style="font-size: 13px; color: var(--color-text); margin: 0; line-height: 1.5;"><?= htmlspecialchars($rev['comment']) ?></p>
                            <?php if (!empty($rev['admin_reply'])): ?>
                                <div style="margin-top: 10px; padding: 10px 12px; background: #FAF8F5; border-left: 3px solid var(--color-primary-dark); border-radius: 6px; font-size: 12.5px; color: var(--color-text);">
                                    <strong style="display: block; font-size: 11px; color: var(--color-primary-dark); margin-bottom: 2px;">Respuesta oficial de la tienda</strong>
                                    <?= nl2br(htmlspecialchars($rev['admin_reply'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- PANEL DE RESEÑAS VERIFICADAS -->
            <div>
                <?php if ($hasPurchased): ?>
                    <div style="background: #FFF; border: 1px solid var(--color-card-border); border-radius: var(--radius-md); padding: 22px; box-shadow: var(--shadow-soft);">
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 12px; color: #166534; font-weight: 700; font-size: 14px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Comprador Verificado</span>
                        </div>
                        <h3 style="font-size: 15px; font-weight: 800; color: var(--color-primary-dark); margin-bottom: 10px;">Califica tu Compra</h3>
                        <form method="POST" action="<?= $baseUrl ?>/account/review">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <div style="margin-bottom: 12px;">
                                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Puntuación *</label>
                                <select name="rating" required style="width: 100%; padding: 10px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 13px;">
                                    <option value="5">5 estrellas - Excelente</option>
                                    <option value="4">4 estrellas - Muy buena</option>
                                    <option value="3">3 estrellas - Regular</option>
                                    <option value="2">2 estrellas - Deficiente</option>
                                    <option value="1">1 estrella - Mala</option>
                                </select>
                            </div>
                            <div style="margin-bottom: 16px;">
                                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Tu Opinión *</label>
                                <textarea name="comment" required rows="3" placeholder="Comparte cómo fue tu experiencia..." style="width: 100%; padding: 10px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 13px;"></textarea>
                            </div>
                            <button type="submit" class="btn-store btn-store-primary" style="width: 100%;">Publicar Opinión</button>
                        </form>
                    </div>
                <?php elseif ($isCustomerLoggedIn): ?>
                    <div style="background: #FFF; border: 1px solid var(--color-card-border); border-radius: var(--radius-md); padding: 22px; text-align: center; box-shadow: var(--shadow-soft);">
                        <div style="color: #166534; margin-bottom: 10px;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </div>
                        <h4 style="font-size: 15px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 6px;">Opiniones 100% Reales</h4>
                        <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.5; margin-bottom: 14px;">
                            Solo los clientes que han adquirido este producto pueden valorarlo. Puedes calificar tus compras anteriores desde tu sección de pedidos.
                        </p>
                        <a href="<?= $baseUrl ?>/account" class="btn-store btn-store-secondary" style="font-size: 13px; width: 100%;">Ver Mis Pedidos</a>
                    </div>
                <?php else: ?>
                    <div style="background: #FFF; border: 1px solid var(--color-card-border); border-radius: var(--radius-md); padding: 22px; text-align: center; box-shadow: var(--shadow-soft);">
                        <div style="color: #166534; margin-bottom: 10px;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <h4 style="font-size: 15px; font-weight: 700; color: var(--color-primary-dark); margin-bottom: 6px;">Opiniones Verificadas</h4>
                        <p style="font-size: 13px; color: var(--color-text-muted); line-height: 1.5; margin-bottom: 16px;">
                            Para garantizar la autenticidad, únicamente los clientes registrados que han recibido su pedido pueden publicar calificaciones.
                        </p>
                        <a href="<?= $baseUrl ?>/login" class="btn-store btn-store-primary" style="font-size: 13px; width: 100%;">Iniciar Sesión</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- HOOK PARA RECOMENDACIONES (Related Products / Cross-sell) -->
    <?php \ShopCMS\Core\PluginManager::doAction('product_page_bottom', $product); ?>
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
            stockBadge.style.color = '#DC2626';
            stockBadge.innerText = 'Agotado';
            if (submitBtn) submitBtn.disabled = true;
        } else if (qty <= lowStockThreshold) {
            stockBadge.style.color = 'var(--color-accent)';
            stockBadge.innerText = 'Últimas ' + qty + ' unidades en stock';
            if (submitBtn) submitBtn.disabled = false;
        } else {
            stockBadge.style.color = '#166534';
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
