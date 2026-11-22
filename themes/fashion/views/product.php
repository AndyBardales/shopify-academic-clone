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

<main class="container" style="padding-top: 50px; padding-bottom: 90px;">
    <div class="product-detail-layout">
        <!-- GALERÍA EDITORIAL MÚLTIPLE -->
        <?= \ShopCMS\Core\ThemeManager::renderProductGallery(
            $product,
            $galleryImages ?? [],
            $variants ?? [],
            $initialImage ?? '',
            $baseUrl,
            '<svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#A3A3A3" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>'
        ) ?>

        <!-- INFORMACIÓN EDITORIAL -->
        <div class="product-info-box">
            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 2.5px; color: #737373; margin-bottom: 8px;">
                <?= htmlspecialchars($product['category_name'] ?? 'Colección Exclusiva') ?>
            </div>

            <h1><?= htmlspecialchars($product['name']) ?></h1>

            <!-- CALIFICACIÓN ESTRELLAS -->
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;">
                <div style="display: inline-flex; align-items: center; gap: 2px;">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="<?= $s <= round($avgRating) ? '#171717' : 'none' ?>" stroke="<?= $s <= round($avgRating) ? '#171717' : '#D4D4D4' ?>" stroke-width="1.5">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                    <?php endfor; ?>
                </div>
                <span style="font-size: 12px; color: #737373; letter-spacing: 0.5px;">
                    <?= $avgRating > 0 ? "{$avgRating} / 5" : 'Primera edición' ?>
                    (<?= $reviewLabel ?>)
                </span>
            </div>

            <!-- PRECIO -->
            <div class="price-box">
                <span id="display_price"><?= $currency['symbol'] ?><?= number_format($initialPrice, 2) ?></span>
                <?php if ($product['compare_price']): ?>
                    <span style="font-size: 18px; color: #A3A3A3; text-decoration: line-through; margin-left: 12px; font-weight: 400;">
                        <?= $currency['symbol'] ?><?= number_format($product['compare_price'], 2) ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($product['short_description'])): ?>
                <p style="font-size: 14px; color: #525252; margin-bottom: 28px; line-height: 1.7; letter-spacing: 0.3px;">
                    <?= htmlspecialchars($product['short_description']) ?>
                </p>
            <?php endif; ?>

            <!-- FORMULARIO AGREGAR AL CARRITO -->
            <form method="POST" action="<?= $baseUrl ?>/cart/add">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                <!-- SELECTOR DE VARIANTES FASHION (1, 2 O 3 OPCIONES DINÁMICAS) -->
                <?php if ($maxParts >= 2 && !empty($variants)): ?>
                    <div style="display: grid; grid-template-columns: repeat(<?= min($maxParts, 3) ?>, 1fr); gap: 14px; margin-bottom: 24px;">
                        <div>
                            <label style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; margin-bottom: 8px;"><?= htmlspecialchars($opt1Label) ?>:</label>
                            <select id="option_val_1" onchange="updateDynamicVariants()" style="width: 100%; padding: 12px 14px; border: 1px solid #D4D4D4; border-radius: 0; font-size: 13px; font-family: inherit; background: #FFF;">
                                <?php foreach ($opt1Values as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; margin-bottom: 8px;"><?= htmlspecialchars($opt2Label) ?>:</label>
                            <select id="option_val_2" onchange="updateDynamicVariants()" style="width: 100%; padding: 12px 14px; border: 1px solid #D4D4D4; border-radius: 0; font-size: 13px; font-family: inherit; background: #FFF;">
                                <?php foreach ($opt2Values as $s): ?>
                                    <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($maxParts >= 3): ?>
                        <div>
                            <label style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; margin-bottom: 8px;"><?= htmlspecialchars($opt3Label) ?>:</label>
                            <select id="option_val_3" onchange="updateDynamicVariants()" style="width: 100%; padding: 12px 14px; border: 1px solid #D4D4D4; border-radius: 0; font-size: 13px; font-family: inherit; background: #FFF;">
                                <?php foreach ($opt3Values as $m): ?>
                                    <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="variant_id" id="variant_id_input" value="<?= $initialVariant['id'] ?>">
                <?php elseif (!empty($variants)): ?>
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; margin-bottom: 8px;"><?= htmlspecialchars($opt1Label ?: 'Opción') ?>:</label>
                        <select name="variant_id" id="variant_select" onchange="onSingleVariantChange(this)" style="width: 100%; padding: 12px 14px; border: 1px solid #D4D4D4; border-radius: 0; font-size: 13px; font-family: inherit; background: #FFF;">
                            <?php foreach ($variants as $var): ?>
                                <option value="<?= $var['id'] ?>" data-price="<?= $var['price'] ?>" data-qty="<?= $var['quantity'] ?>" data-image="<?= htmlspecialchars($var['image_url'] ?? '') ?>">
                                    <?= htmlspecialchars($var['title']) ?> — <?= $currency['symbol'] ?><?= number_format($var['price'], 2) ?> (<?= $var['quantity'] ?> en stock)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- DISPONIBILIDAD (SEGUN VARIANTE SELECCIONADA) -->
                <div id="stock_status_container" style="margin-bottom: 24px; font-size: 12px; letter-spacing: 0.5px; text-transform: uppercase; display: flex; align-items: center; gap: 8px;">
                    <?php if ($initialQty <= 0): ?>
                        <span id="stock_badge" style="color: #DC2626; font-weight: 600;">Pieza agotada</span>
                    <?php elseif ($initialQty <= $product['low_stock_threshold']): ?>
                        <span id="stock_badge" style="color: #D97706; font-weight: 600;">Últimas <?= $initialQty ?> unidades en stock</span>
                    <?php else: ?>
                        <span id="stock_badge" style="color: #166534; font-weight: 600;">En stock (<?= $initialQty ?> disponibles)</span>
                    <?php endif; ?>
                </div>

                <!-- CANTIDAD Y BOTÓN -->
                <div style="display: flex; gap: 14px; margin-bottom: 24px;">
                    <input type="number" id="quantity_input" name="quantity" value="1" min="1" max="<?= max(1, $initialQty) ?>" style="width: 70px; padding: 12px; border: 1px solid #D4D4D4; border-radius: 0; text-align: center; font-size: 14px; font-weight: 600;">
                    <button type="submit" id="submit_cart_btn" class="btn-store btn-store-primary" style="flex: 1;" <?= $initialQty <= 0 ? 'disabled' : '' ?>>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        Añadir al Carrito
                    </button>
                </div>

                <!-- HOOKS DE PLUGINS -->
                <?php \ShopCMS\Core\PluginManager::doAction('product_page_buttons', $product); ?>
            </form>

            <?php if (!empty($product['description'])): ?>
                <div style="margin-top: 36px; padding-top: 24px; border-top: 1px solid #E5E5E5;">
                    <h3 style="font-family: var(--font-serif); font-size: 16px; font-weight: 700; letter-spacing: 1px; margin-bottom: 12px; text-transform: uppercase;">Descripción y Cuidados</h3>
                    <div style="font-size: 13px; color: #525252; line-height: 1.8;">
                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECCIÓN DE RESEÑAS FASHION -->
    <?php
    $isCustomerLoggedIn = \ShopCMS\Core\Auth::isCustomer();
    $customerInfo = \ShopCMS\Core\Auth::customer();
    $hasPurchased = $isCustomerLoggedIn ? \ShopCMS\Core\Auth::hasPurchasedProduct((int)$customerInfo['id'], (int)$product['id']) : false;
    ?>
    <section style="margin-top: 60px; padding-top: 40px; border-top: 1px solid #E5E5E5;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
            <h2 style="font-family: var(--font-serif); font-size: 22px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">Experiencias & Valoraciones (<?= count($reviews) ?>)</h2>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
            <div>
                <?php if (empty($reviews)): ?>
                    <p style="color: #737373; font-size: 13px;">Esta prenda aún no cuenta con reseñas de compradores.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div style="padding: 20px; border: 1px solid #E5E5E5; margin-bottom: 16px; background: #FFF;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px;">
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <strong style="font-size: 14px;"><?= htmlspecialchars($rev['customer_name'] ?? $rev['author_name'] ?? 'Cliente') ?></strong>
                                    <span style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #171717; background: #F5F5F5; border: 1px solid #E5E5E5; padding: 2px 6px; font-weight: 700;">
                                        Compra Verificada
                                    </span>
                                    <?php if (!empty($rev['is_featured'])): ?>
                                        <span style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #92400E; background: #FEF3C7; padding: 2px 6px; font-weight: 700;">Destacada</span>
                                    <?php endif; ?>
                                </div>
                                <span style="font-size: 11px; color: #A3A3A3;"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></span>
                            </div>
                            <div style="display: flex; gap: 2px; margin-bottom: 8px;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="<?= $i <= $rev['rating'] ? '#171717' : 'none' ?>" stroke="<?= $i <= $rev['rating'] ? '#171717' : '#D4D4D4' ?>" stroke-width="2">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <p style="font-size: 13px; color: #525252; line-height: 1.6; margin: 0;"><?= htmlspecialchars($rev['comment']) ?></p>
                            <?php if (!empty($rev['admin_reply'])): ?>
                                <div style="margin-top: 12px; padding: 10px 14px; background: #FAFAFA; border-left: 2px solid #171717; font-size: 12.5px; color: #404040;">
                                    <strong style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #171717; margin-bottom: 3px;">Respuesta oficial de la tienda</strong>
                                    <?= nl2br(htmlspecialchars($rev['admin_reply'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- PANEL RESEÑA VERIFICADA FASHION -->
            <div>
                <?php if ($hasPurchased): ?>
                    <div style="background: #FAFAFA; border: 1px solid #E5E5E5; padding: 24px;">
                        <h3 style="font-family: var(--font-serif); font-size: 15px; font-weight: 700; letter-spacing: 1px; margin-bottom: 16px; text-transform: uppercase;">Califica tu Prenda</h3>
                        <form method="POST" action="<?= $baseUrl ?>/account/review">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <div style="margin-bottom: 14px;">
                                <label style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Calificación *</label>
                                <select name="rating" required style="width: 100%; padding: 10px; border: 1px solid #D4D4D4; font-size: 13px;">
                                    <option value="5">5 estrellas - Excepcional</option>
                                    <option value="4">4 estrellas - Muy buena</option>
                                    <option value="3">3 estrellas - Aceptable</option>
                                    <option value="2">2 estrellas - Regular</option>
                                    <option value="1">1 estrella - Deficiente</option>
                                </select>
                            </div>
                            <div style="margin-bottom: 18px;">
                                <label style="display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Tu Comentario *</label>
                                <textarea name="comment" required rows="3" placeholder="Comenta los acabados, tela y talle..." style="width: 100%; padding: 10px; border: 1px solid #D4D4D4; font-size: 13px;"></textarea>
                            </div>
                            <button type="submit" class="btn-store btn-store-primary" style="width: 100%;">Publicar Valoración</button>
                        </form>
                    </div>
                <?php elseif ($isCustomerLoggedIn): ?>
                    <div style="background: #FAFAFA; border: 1px solid #E5E5E5; padding: 24px; text-align: center;">
                        <h4 style="font-family: var(--font-serif); font-size: 15px; font-weight: 700; letter-spacing: 1px; margin-bottom: 8px; text-transform: uppercase;">Valoraciones Verificadas</h4>
                        <p style="font-size: 12px; color: #737373; line-height: 1.6; margin-bottom: 14px;">
                            Solo clientes que han adquirido esta prenda pueden valorarla. Puedes calificar tus piezas adquiridas desde tu historial.
                        </p>
                        <a href="<?= $baseUrl ?>/account" class="btn-fashion" style="font-size: 11px; padding: 10px 16px; width: 100%;">Ver Mis Pedidos</a>
                    </div>
                <?php else: ?>
                    <div style="background: #FAFAFA; border: 1px solid #E5E5E5; padding: 24px; text-align: center;">
                        <h4 style="font-family: var(--font-serif); font-size: 15px; font-weight: 700; letter-spacing: 1px; margin-bottom: 8px; text-transform: uppercase;">Opiniones Reales</h4>
                        <p style="font-size: 12px; color: #737373; line-height: 1.6; margin-bottom: 16px;">
                            Para asegurar la máxima autenticidad, únicamente compradores verificados pueden calificar las colecciones.
                        </p>
                        <a href="<?= $baseUrl ?>/login" class="btn-fashion" style="font-size: 11px; padding: 10px 16px; width: 100%;">Iniciar Sesión</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- HOOK PARA RECOMENDACIONES (Related Products) -->
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
            stockBadge.innerText = 'Pieza agotada';
            if (submitBtn) submitBtn.disabled = true;
        } else if (qty <= lowStockThreshold) {
            stockBadge.style.color = '#D97706';
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
