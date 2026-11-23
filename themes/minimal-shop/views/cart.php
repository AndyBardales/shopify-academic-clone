<?php \ShopCMS\Core\ThemeManager::renderHeader(get_defined_vars());
$cartCfg = $themeConfig['templates']['cart']['sections'] ?? [];
$cartHdr = $cartCfg['cart_header'] ?? [];
$cartSum = $cartCfg['cart_summary'] ?? [];
?>

<main class="container scms-template-sections" style="padding-top: 40px; padding-bottom: 80px;">
    <?php if (($cartHdr['enabled'] ?? true) !== false): ?>
        <section data-theme-section="cart_header" data-section-label="Cabecera del Carrito" style="margin-bottom: 24px;">
            <h1 style="font-size: <?= (int)($cartHdr['title_size'] ?? 28) ?>px; font-weight: 700;" data-theme-field="title"><?= htmlspecialchars($cartHdr['title'] ?? 'Tu Carrito de Compras') ?></h1>
        </section>
    <?php endif; ?>

    <?php if (empty($cart)): ?>
        <div data-theme-section="cart_items" data-section-label="Contenido del Carrito" style="text-align: center; padding: 60px 20px; background: var(--color-surface); border-radius: 12px; border: 1px solid var(--color-border);">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="#9CA3AF" style="margin-bottom: 16px;">
                <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
            </svg>
            <h2>Tu carrito está vacío</h2>
            <p style="color: var(--color-text-light); margin: 8px 0 24px 0;">Parece que aún no has agregado productos a tu compra.</p>
            <a href="<?= $baseUrl ?>/catalog" class="btn-store btn-store-primary">Ir al Catálogo de Productos</a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px;">
            <!-- TABLA DE ARTÍCULOS -->
            <div data-theme-section="cart_items" data-section-label="Lista de Artículos">
                <form method="POST" action="<?= $baseUrl ?>/cart/update">
                    <?= \ShopCMS\Core\Security::csrfField() ?>
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Cantidad</th>
                                <th style="text-align: right;">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart as $key => $item): ?>
                                <tr>
                                    <td style="display: flex; align-items: center; gap: 14px;">
                                        <div style="width: 50px; height: 50px; background: #EEE; border-radius: 6px; overflow: hidden; flex-shrink: 0;">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($item['image']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($item['name']) ?></strong>
                                        </div>
                                    </td>
                                    <td><?= $currency['symbol'] ?><?= number_format($item['price'], 2) ?></td>
                                    <td>
                                        <input type="number" name="quantities[<?= $key ?>]" value="<?= $item['quantity'] ?>" min="1" max="99" style="width: 60px; padding: 6px; border: 1px solid var(--color-border); border-radius: 6px; text-align: center;">
                                    </td>
                                    <td style="text-align: right;">
                                        <strong><?= $currency['symbol'] ?><?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="submit" formaction="<?= $baseUrl ?>/cart/remove/<?= $key ?>" formmethod="POST" style="background: none; border: none; padding: 0; cursor: pointer; color: #D72C0D; display: inline-flex; align-items: center;" title="Quitar producto">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <a href="<?= $baseUrl ?>/catalog" style="color: var(--color-text-light); text-decoration: none; font-size: 14px;">&larr; Continuar Comprando</a>
                        <button type="submit" class="btn-store btn-store-secondary" style="font-size: 13px; padding: 8px 16px;">Actualizar Carrito</button>
                    </div>
                </form>
            </div>

            <!-- RESUMEN Y CUPÓN -->
            <div data-theme-section="cart_summary" data-section-label="Cupones y Resumen">
                <?php if (($cartSum['show_coupon_box'] ?? true) !== false): ?>
                <!-- APLICAR CUPÓN -->
                <div style="background: var(--color-surface); padding: 20px; border-radius: 12px; border: 1px solid var(--color-border); margin-bottom: 20px;">
                    <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 10px;">¿Tienes un Cupón de Descuento?</h3>
                    
                    <?php if (!empty($_SESSION['cart_error'])): ?>
                        <div style="font-size: 13px; color: #D72C0D; margin-bottom: 10px;">
                            <?= htmlspecialchars($_SESSION['cart_error']) ?>
                        </div>
                        <?php unset($_SESSION['cart_error']); ?>
                    <?php endif; ?>

                    <form method="POST" action="<?= $baseUrl ?>/cart/apply-coupon" style="display: flex; gap: 8px;">
                        <input type="text" name="coupon_code" placeholder="Código (ej: BIENVENIDO10)" value="<?= htmlspecialchars($couponCode ?? '') ?>" style="flex: 1; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 13px; text-transform: uppercase;" required>
                        <button type="submit" class="btn-store btn-store-secondary" style="font-size: 13px; padding: 8px 14px;">Aplicar</button>
                    </form>

                    <?php if ($couponCode): ?>
                        <div style="font-size: 12px; color: #008060; margin-top: 8px; font-weight: 600; display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                            <span style="display: flex; align-items: center; gap: 4px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>Cupón <strong><?= htmlspecialchars($couponCode) ?></strong> aplicado exitosamente.</span>
                            </span>
                            <form method="POST" action="<?= $baseUrl ?>/cart/remove-coupon" style="display: inline;">
                                <?= \ShopCMS\Core\Security::csrfField() ?>
                                <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer; color: #D72C0D; font-weight: 500; text-decoration: underline; font-size: 12px;">Quitar</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- RESUMEN DE TOTALES -->
                <div style="background: #FFF; padding: 24px; border-radius: 12px; border: 1px solid var(--color-border);">
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 16px;">Resumen del Pedido</h3>

                    <?php
                    $shippingAmount = (float)($shippingAmount ?? 0);
                    $taxAmount = (float)($taxAmount ?? 0);
                    $taxRate = (float)($taxRate ?? 0);
                    $taxIncluded = $taxIncluded ?? true;
                    $freeThreshold = (float)($freeShippingThreshold ?? 0);
                    $freeRemaining = (float)($freeShippingRemaining ?? 0);
                    $orderMinAmount = (float)($orderMinAmount ?? 0);
                    ?>

                    <?php if ($freeThreshold > 0 && $shippingAmount > 0): ?>
                        <?php $pct = min(100, round(($subtotal / $freeThreshold) * 100)); ?>
                        <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; font-size: 12.5px; color: #166534;">
                            <div>Te faltan <strong><?= $currency['symbol'] ?><?= number_format($freeRemaining, 2) ?></strong> para desbloquear <strong>Envío Gratis</strong>.</div>
                            <div style="height: 6px; background: #DCFCE7; border-radius: 999px; overflow: hidden; margin-top: 6px;">
                                <div style="height: 100%; width: <?= $pct ?>%; background: #008060;"></div>
                            </div>
                        </div>
                    <?php elseif ($freeThreshold > 0 && $shippingAmount == 0.0): ?>
                        <div style="background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 8px 12px; margin-bottom: 14px; font-size: 12.5px; color: #065F46; font-weight: 600;">
                            ¡Felicidades! Alcanzaste el mínimo para Envío Gratis.
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px;">
                        <span>Subtotal:</span>
                        <span><?= $currency['symbol'] ?><?= number_format($subtotal, 2) ?></span>
                    </div>

                    <?php if ($discount > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #008060; font-weight: 600;">
                            <span>Descuento:</span>
                            <span>-<?= $currency['symbol'] ?><?= number_format($discount, 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: var(--color-text-light);">
                        <span>Envío estimado:</span>
                        <span style="<?= $shippingAmount == 0.0 ? 'color: #008060; font-weight: 600;' : '' ?>">
                            <?= $shippingAmount > 0 ? $currency['symbol'] . number_format($shippingAmount, 2) : 'Gratis' ?>
                        </span>
                    </div>

                    <?php if ($taxRate > 0 && $taxAmount > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 13px; color: var(--color-text-light);">
                            <span><?= $taxIncluded ? 'Impuesto (' . number_format($taxRate, 2) . '% incluido):' : 'Impuesto (' . number_format($taxRate, 2) . '%):' ?></span>
                            <span><?= $currency['symbol'] ?><?= number_format($taxAmount, 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; justify-content: space-between; padding-top: 14px; border-top: 1px solid var(--color-border); font-size: 20px; font-weight: 700;">
                        <span>Total:</span>
                        <span><?= $currency['symbol'] ?><?= number_format($total, 2) ?></span>
                    </div>

                    <?php if ($orderMinAmount > 0 && $subtotal < $orderMinAmount): ?>
                        <div style="background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; border-radius: 8px; padding: 10px 12px; margin-top: 16px; font-size: 12.5px;">
                            El pedido mínimo es de <strong><?= $currency['symbol'] ?><?= number_format($orderMinAmount, 2) ?></strong>. Agrega <strong><?= $currency['symbol'] ?><?= number_format($orderMinAmount - $subtotal, 2) ?></strong> más para continuar.
                        </div>
                        <a href="<?= $baseUrl ?>/catalog" class="btn-store btn-store-secondary" style="width: 100%; margin-top: 12px; padding: 12px; font-size: 14px;">
                            Seguir Agregando Productos
                        </a>
                    <?php else: ?>
                        <div style="margin-top: 24px;">
                            <a href="<?= $baseUrl ?>/checkout" class="btn-store btn-store-primary" style="width: 100%; padding: 14px; font-size: 16px;" data-theme-field="checkout_btn_text">
                                <?= htmlspecialchars($cartSum['checkout_btn_text'] ?? 'Proceder al Pago') ?> &rarr;
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- HOOK PARA CROSS-SELL DE PLUGINS (Related Products, etc.) -->
    <?php \ShopCMS\Core\PluginManager::doAction('cart_page_bottom', $cart); ?>
</main>

<?php \ShopCMS\Core\ThemeManager::renderFooter(get_defined_vars()); ?>

