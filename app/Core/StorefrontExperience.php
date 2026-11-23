<?php
namespace ShopCMS\Core;

class StorefrontExperience {
    /**
     * Cuenta cuántos clientes distintos tienen guardado este producto en su Wishlist (corazones).
     */
    public static function getProductHeartCount(int $productId): int {
        if ($productId <= 0) {
            return 0;
        }
        try {
            $rows = Database::fetchAll(
                "SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key LIKE 'customer_wishlist_%'"
            );
            $count = 0;
            foreach ($rows as $row) {
                $ids = json_decode((string)($row['setting_value'] ?? '[]'), true);
                if (!is_array($ids)) {
                    continue;
                }
                foreach ($ids as $id) {
                    if ((int)$id === $productId) {
                        $count++;
                        break;
                    }
                }
            }
            return $count;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Cuenta unidades vendidas de este producto en pedidos válidos (no cancelados/devueltos).
     */
    public static function getProductSoldCount(int $productId): int {
        if ($productId <= 0) {
            return 0;
        }
        try {
            $row = Database::fetch("
                SELECT COALESCE(SUM(oi.quantity), 0) AS sold_units
                FROM " . Database::table('order_items') . " oi
                INNER JOIN " . Database::table('orders') . " o ON oi.order_id = o.id
                WHERE oi.product_id = ? AND o.status NOT IN ('cancelled', 'returned')
            ", [$productId]);
            return max(0, (int)($row['sold_units'] ?? 0));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function formatCompactCount(int $count): string {
        if ($count >= 1000) {
            return number_format($count / 1000, 1, '.', '') . 'K';
        }
        return (string)max(0, $count);
    }

    /**
     * Construye el estado enriquecido de la cesta lateral derecha para el cliente autenticado.
     */
    public static function buildSideBasketData(): array {
        Auth::ensureIsolatedCartSession();
        $baseUrl = defined('APP_URL') ? rtrim((string)APP_URL, '/') : '';
        $curr = [
            'code'   => Settings::get('store_currency', 'USD'),
            'symbol' => Settings::get('store_currency_symbol', '$'),
        ];

        if (!Auth::isCustomer() || empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            return [
                'has_items' => false,
                'total_qty' => 0,
                'subtotal'  => 0.0,
                'savings'   => 0.0,
                'currency'  => $curr,
                'items'     => [],
            ];
        }

        $cart = $_SESSION['cart'];
        $prodIds = array_values(array_unique(array_filter(array_map(
            fn($it) => (int)($it['product_id'] ?? 0),
            $cart
        ))));

        $prodMeta = [];
        if (!empty($prodIds)) {
            $ph = implode(',', array_fill(0, count($prodIds), '?'));
            try {
                $rows = Database::fetchAll(
                    "SELECT id, slug, compare_price, track_quantity, quantity, featured_image FROM " . Database::table('products') . " WHERE id IN ({$ph})",
                    $prodIds
                );
                foreach ($rows as $r) {
                    $prodMeta[(int)$r['id']] = $r;
                }
            } catch (\Throwable $e) {
                $prodMeta = [];
            }
        }

        $items = [];
        $totalQty = 0;
        $subtotal = 0.0;
        $savings = 0.0;

        foreach ($cart as $key => $it) {
            $pid = (int)($it['product_id'] ?? 0);
            $vid = !empty($it['variant_id']) ? (int)$it['variant_id'] : null;
            $qty = max(1, (int)($it['quantity'] ?? 1));
            $price = (float)($it['price'] ?? 0);
            $meta = $prodMeta[$pid] ?? null;

            $cmpPrice = ($meta && !empty($meta['compare_price'])) ? (float)$meta['compare_price'] : 0.0;
            if ($cmpPrice > $price) {
                $savings += ($cmpPrice - $price) * $qty;
            }

            $stockInfo = ['track' => false, 'available' => 999];
            try {
                $stockInfo = InventoryManager::getAvailableStock($pid, $vid);
            } catch (\Throwable $e) {}

            $imgRaw = !empty($it['image']) ? (string)$it['image'] : (string)($meta['featured_image'] ?? '');
            $imgUrl = $imgRaw !== '' ? ThemeManager::resolveImageUrl($imgRaw, $baseUrl) : '';
            $prodUrl = ($meta && !empty($meta['slug'])) ? ($baseUrl . '/product/' . rawurlencode((string)$meta['slug'])) : ($baseUrl . '/catalog');

            $totalQty += $qty;
            $subtotal += ($price * $qty);

            $items[] = [
                'key'             => (string)$key,
                'product_id'      => $pid,
                'variant_id'      => $vid,
                'name'            => (string)($it['name'] ?? 'Producto'),
                'price'           => round($price, 2),
                'quantity'        => $qty,
                'line_total'      => round($price * $qty, 2),
                'track_stock'     => (bool)$stockInfo['track'],
                'available_stock' => (int)$stockInfo['available'],
                'image_url'       => $imgUrl,
                'product_url'     => $prodUrl,
            ];
        }

        return [
            'has_items' => !empty($items),
            'total_qty' => $totalQty,
            'subtotal'  => round($subtotal, 2),
            'savings'   => round($savings, 2),
            'currency'  => $curr,
            'items'     => $items,
        ];
    }

    /**
     * Renderiza la cesta lateral derecha persistente en vistas de producto.
     * Solo se muestra cuando el cliente tiene al menos 1 artículo en la cesta.
     */
    public static function renderProductSideBasket(string $baseUrl): string {
        $basket = self::buildSideBasketData();
        $hasItems = !empty($basket['has_items']);
        $sym = htmlspecialchars((string)($basket['currency']['symbol'] ?? '$'));
        $jsonBasket = json_encode($basket, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        ob_start();
        ?>
        <button type="button"
                id="scms-side-basket-fab"
                onclick="window.scmsToggleMobileSideBasket && window.scmsToggleMobileSideBasket()"
                style="display: none;"
                aria-label="Abrir cesta lateral">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span>Cesta (<span id="scms-sb-fab-qty"><?= (int)$basket['total_qty'] ?></span>)</span>
        </button>

        <aside id="scms-side-basket"
               class="scms-side-basket <?= $hasItems ? 'is-visible' : '' ?>"
               aria-label="Cesta de compras"
               data-initial-basket="<?= htmlspecialchars((string)$jsonBasket, ENT_QUOTES, 'UTF-8') ?>">
            <div class="scms-sb-head">
                <div class="scms-sb-total-row">
                    <span id="scms-sb-subtotal" class="scms-sb-subtotal"><?= $sym ?> <?= number_format((float)$basket['subtotal'], 2) ?></span>
                    <button type="button" class="scms-sb-mobile-close" onclick="window.scmsToggleMobileSideBasket && window.scmsToggleMobileSideBasket(false)" aria-label="Cerrar cesta">&times;</button>
                </div>
                <div id="scms-sb-savings" class="scms-sb-savings" style="display: <?= ($basket['savings'] > 0) ? 'block' : 'none' ?>;">
                    Ahorrado: <?= $sym ?> <?= number_format((float)$basket['savings'], 2) ?>
                </div>
                <a href="<?= htmlspecialchars($baseUrl) ?>/checkout" class="scms-sb-btn-checkout">Continuar</a>
                <a href="<?= htmlspecialchars($baseUrl) ?>/cart" class="scms-sb-btn-cart">Ir a la Cesta</a>
            </div>
            <div class="scms-sb-subhead">
                Artículos en tu cesta (<span id="scms-sb-count"><?= (int)$basket['total_qty'] ?></span>)
            </div>
            <div id="scms-sb-items" class="scms-sb-items"></div>
        </aside>
        <div id="scms-sb-confirm-modal" class="scms-sb-confirm-backdrop" role="dialog" aria-modal="true" aria-labelledby="scms-sb-confirm-title" style="display: none;">
            <div class="scms-sb-confirm-card">
                <div class="scms-sb-confirm-head">
                    <h4 id="scms-sb-confirm-title" class="scms-sb-confirm-title">Quitar artículo</h4>
                    <button type="button" id="scms-sb-confirm-close" class="scms-sb-confirm-close" aria-label="Cerrar">&times;</button>
                </div>
                <p id="scms-sb-confirm-msg" class="scms-sb-confirm-msg">¿Quieres quitar este producto de tu cesta?</p>
                <div class="scms-sb-confirm-actions">
                    <button type="button" id="scms-sb-confirm-cancel" class="scms-sb-confirm-btn scms-sb-confirm-cancel">Cancelar</button>
                    <button type="button" id="scms-sb-confirm-ok" class="scms-sb-confirm-btn scms-sb-confirm-ok">Quitar</button>
                </div>
            </div>
        </div>
        <div id="scms-sb-toast" class="scms-sb-toast" role="status" aria-live="polite"></div>
        <?php
        return ob_get_clean();
    }

    private static bool $variantSchemaEnsured = false;

    /**
     * Garantiza de forma idempotente las columnas extendidas para variantes ricas y ficha técnica.
     */
    public static function ensureVariantRichColumns(): void {
        if (self::$variantSchemaEnsured) {
            return;
        }
        self::$variantSchemaEnsured = true;
        try {
            $prodCols = array_column(Database::fetchAll("SHOW COLUMNS FROM " . Database::table('products')), 'Field');
            if (!in_array('specs', $prodCols, true)) {
                Database::query("ALTER TABLE " . Database::table('products') . " ADD COLUMN `specs` TEXT NULL AFTER `description`");
            }
            $varCols = array_column(Database::fetchAll("SHOW COLUMNS FROM " . Database::table('product_variants')), 'Field');
            if (!in_array('short_description', $varCols, true)) {
                Database::query("ALTER TABLE " . Database::table('product_variants') . " ADD COLUMN `short_description` TEXT NULL AFTER `image_url`");
            }
            if (!in_array('description', $varCols, true)) {
                Database::query("ALTER TABLE " . Database::table('product_variants') . " ADD COLUMN `description` TEXT NULL AFTER `short_description`");
            }
            if (!in_array('specs', $varCols, true)) {
                Database::query("ALTER TABLE " . Database::table('product_variants') . " ADD COLUMN `specs` TEXT NULL AFTER `description`");
            }
        } catch (\Throwable $e) {
            // No bloquear flujo si no hay permisos DDL
        }
    }

    /**
     * Convierte texto multilinea ("Clave: Valor") o JSON en filas estructuradas de especificaciones.
     */
    public static function parseSpecs(?string $raw): array {
        $text = trim((string)$raw);
        if ($text === '') {
            return [];
        }
        if ($text[0] === '[' || $text[0] === '{') {
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                $rows = [];
                foreach ($decoded as $k => $v) {
                    if (is_array($v) && isset($v['label'])) {
                        $rows[] = ['label' => trim((string)$v['label']), 'value' => trim((string)($v['value'] ?? ''))];
                    } elseif (is_string($k)) {
                        $rows[] = ['label' => trim($k), 'value' => trim((string)$v)];
                    }
                }
                if (!empty($rows)) {
                    return $rows;
                }
            }
        }
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $rows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (strpos($line, ':') !== false) {
                [$lbl, $val] = explode(':', $line, 2);
                $rows[] = ['label' => trim($lbl), 'value' => trim($val)];
            } else {
                $rows[] = ['label' => 'Detalle', 'value' => $line];
            }
        }
        return $rows;
    }

    /**
     * Prepara el payload unificado para swatches visuales, chips de tallas y sincronización bidireccional galería-variante.
     */
    public static function buildVariantExperiencePayload(
        array $product,
        array $variants,
        array $galleryImages,
        string $baseUrl,
        array $currency
    ): array {
        $maxParts = 1;
        foreach ($variants as $v) {
            $pCount = count(array_filter(array_map('trim', explode(' / ', (string)($v['title'] ?? ''))), fn($x) => $x !== ''));
            if ($pCount > $maxParts) {
                $maxParts = min(3, $pCount);
            }
        }

        $axesValues = [[], [], []];
        $axisImages = [[], [], []];
        foreach ($variants as $v) {
            $parts = array_map('trim', explode(' / ', (string)($v['title'] ?? '')));
            $rawImg = trim((string)($v['image_url'] ?? ''));
            $resolvedImg = $rawImg !== '' ? ThemeManager::resolveImageUrl($rawImg, $baseUrl) : '';
            for ($a = 0; $a < $maxParts; $a++) {
                $val = $parts[$a] ?? '';
                if ($val === '') continue;
                if (!in_array($val, $axesValues[$a], true)) {
                    $axesValues[$a][] = $val;
                }
                if ($resolvedImg !== '' && empty($axisImages[$a][$val])) {
                    $axisImages[$a][$val] = $resolvedImg;
                }
            }
        }

        $labels = [
            trim((string)($product['option1_name'] ?? '')),
            trim((string)($product['option2_name'] ?? '')),
            trim((string)($product['option3_name'] ?? '')),
        ];
        if ($labels[0] === '') {
            $s1 = implode(' ', $axesValues[0]);
            if (preg_match('/\b(GB|TB|MB|RAM|SSD)\b/i', $s1)) $labels[0] = 'Almacenamiento';
            elseif (preg_match('/\b(kg|g|gr|ml|l|lt|oz|botella|caja|pack)\b/i', $s1)) $labels[0] = 'Presentación';
            elseif ($maxParts >= 2) $labels[0] = 'Color';
            else $labels[0] = 'Opción';
        }
        if ($maxParts >= 2 && $labels[1] === '') {
            $s2 = implode(' ', $axesValues[1]);
            if (preg_match('/\b(kg|g|gr|ml|l|lt|oz|botella|caja|pack)\b/i', $s2)) $labels[1] = 'Peso / Cantidad';
            elseif (preg_match('/\b(S|M|L|XL|XXL|XS|[0-9]{2})\b/', $s2)) $labels[1] = 'Talla';
            elseif (preg_match('/\b(GB|TB|MB|RAM|SSD)\b/i', $s2)) $labels[1] = 'Memoria';
            else $labels[1] = 'Opción 2';
        }
        if ($maxParts >= 3 && $labels[2] === '') {
            $labels[2] = 'Acabado / Material';
        }

        $basePrice = (float)($product['price'] ?? 0);
        $baseCompare = !empty($product['compare_price']) ? (float)$product['compare_price'] : null;
        $baseShort = trim((string)($product['short_description'] ?? ''));
        $baseDesc = trim((string)($product['description'] ?? ''));
        $baseSpecsRaw = trim((string)($product['specs'] ?? ''));
        $baseFeaturedImg = trim((string)($product['featured_image'] ?? ''));
        $defaultImgResolved = $baseFeaturedImg !== '' ? ThemeManager::resolveImageUrl($baseFeaturedImg, $baseUrl) : '';

        $enrichedVariants = [];
        foreach ($variants as $v) {
            $vPrice = (float)($v['price'] ?? $basePrice);
            $vCompare = (isset($v['compare_price']) && $v['compare_price'] !== null && (float)$v['compare_price'] > 0)
                ? (float)$v['compare_price']
                : $baseCompare;
            $discountPct = ($vCompare !== null && $vCompare > $vPrice && $vCompare > 0)
                ? (int)round((($vCompare - $vPrice) / $vCompare) * 100)
                : 0;

            $vShort = trim((string)($v['short_description'] ?? ''));
            $vDesc = trim((string)($v['description'] ?? ''));
            $vSpecsRaw = trim((string)($v['specs'] ?? ''));

            $rawVImg = trim((string)($v['image_url'] ?? ''));
            $resolvedVImg = $rawVImg !== '' ? ThemeManager::resolveImageUrl($rawVImg, $baseUrl) : $defaultImgResolved;
            $parts = array_slice(array_map('trim', explode(' / ', (string)($v['title'] ?? ''))), 0, $maxParts);

            $enrichedVariants[] = [
                'id' => (int)$v['id'],
                'title' => (string)$v['title'],
                'parts' => $parts,
                'sku' => (string)($v['sku'] ?? ($product['sku'] ?? '')),
                'price' => round($vPrice, 2),
                'compare_price' => ($vCompare !== null && $vCompare > $vPrice) ? round($vCompare, 2) : null,
                'discount_pct' => $discountPct,
                'quantity' => (int)($v['quantity'] ?? 0),
                'image_url' => $resolvedVImg,
                'own_image_url' => $rawVImg !== '' ? $resolvedVImg : '',
                'short_description' => $vShort !== '' ? $vShort : $baseShort,
                'description' => $vDesc !== '' ? $vDesc : $baseDesc,
                'specs' => self::parseSpecs($vSpecsRaw !== '' ? $vSpecsRaw : $baseSpecsRaw),
            ];
        }

        $baseSpecs = self::parseSpecs($baseSpecsRaw);
        $initialVariant = $enrichedVariants[0] ?? null;

        return [
            'product_id' => (int)$product['id'],
            'max_parts' => $maxParts,
            'labels' => array_slice($labels, 0, $maxParts),
            'axes' => array_slice($axesValues, 0, $maxParts),
            'axis_images' => array_slice($axisImages, 0, $maxParts),
            'currency_symbol' => (string)($currency['symbol'] ?? '$'),
            'low_stock_threshold' => max(1, (int)($product['low_stock_threshold'] ?? 5)),
            'default_image' => $defaultImgResolved,
            'base_compare_price' => ($baseCompare !== null && $baseCompare > $basePrice) ? round($baseCompare, 2) : null,
            'base_discount_pct' => ($baseCompare !== null && $baseCompare > $basePrice && $baseCompare > 0)
                ? (int)round((($baseCompare - $basePrice) / $baseCompare) * 100)
                : 0,
            'base_short_description' => $baseShort,
            'base_description' => $baseDesc,
            'base_specs' => $baseSpecs,
            'variants' => $enrichedVariants,
            'initial_variant' => $initialVariant,
        ];
    }

    /**
     * Renderiza selectores visuales (miniaturas para eje visual y chips/píldoras para tallas/opciones).
     */
    public static function renderVariantSelectors(array $payload): string {
        $variants = $payload['variants'] ?? [];
        if (empty($variants)) {
            return '';
        }
        $maxParts = (int)($payload['max_parts'] ?? 1);
        $labels = $payload['labels'] ?? ['Opción'];
        $axes = $payload['axes'] ?? [[]];
        $axisImages = $payload['axis_images'] ?? [[]];
        $initial = $payload['initial_variant'] ?? $variants[0];
        $initParts = $initial['parts'] ?? [];
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        ob_start();
        ?>
        <div id="scms-variant-selectors" class="scms-variant-selectors">
            <?php for ($axis = 0; $axis < $maxParts; $axis++):
                $vals = $axes[$axis] ?? [];
                if (empty($vals)) continue;
                $axisLabel = (string)($labels[$axis] ?? ('Opción ' . ($axis + 1)));
                $curVal = (string)($initParts[$axis] ?? $vals[0]);
                $imgMap = $axisImages[$axis] ?? [];
                $useVisualSwatches = ($axis === 0 && !empty($imgMap));
            ?>
                <div class="scms-var-group" data-axis="<?= $axis ?>">
                    <div class="scms-var-label-row">
                        <span class="scms-var-label"><?= htmlspecialchars($axisLabel) ?>:</span>
                        <strong class="scms-var-selected-text" id="scms_axis_label_<?= $axis ?>"><?= htmlspecialchars($curVal) ?></strong>
                    </div>
                    <div class="scms-var-options <?= $useVisualSwatches ? 'is-swatches' : 'is-chips' ?>">
                        <?php foreach ($vals as $val):
                            $isActive = ($val === $curVal);
                            $thumb = $imgMap[$val] ?? '';
                        ?>
                            <?php if ($useVisualSwatches && $thumb !== ''): ?>
                                <button type="button"
                                        class="scms-var-btn scms-var-swatch <?= $isActive ? 'is-active' : '' ?>"
                                        data-axis="<?= $axis ?>"
                                        data-value="<?= htmlspecialchars($val) ?>"
                                        title="<?= htmlspecialchars($val) ?>"
                                        aria-label="<?= htmlspecialchars($axisLabel . ': ' . $val) ?>">
                                    <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($val) ?>">
                                </button>
                            <?php else: ?>
                                <button type="button"
                                        class="scms-var-btn scms-var-chip <?= $isActive ? 'is-active' : '' ?>"
                                        data-axis="<?= $axis ?>"
                                        data-value="<?= htmlspecialchars($val) ?>">
                                    <?= htmlspecialchars($val) ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endfor; ?>

            <input type="hidden" name="variant_id" id="variant_id_input" value="<?= (int)$initial['id'] ?>">
            <script type="application/json" id="scms-variant-payload"><?= $jsonPayload ?></script>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza la tabla de características técnicas del producto o variante activa.
     */
    public static function renderProductSpecsBlock(array $specs): string {
        $hasSpecs = !empty($specs);
        ob_start();
        ?>
        <div id="scms_product_specs_wrap" class="scms-specs-wrap" style="display: <?= $hasSpecs ? 'block' : 'none' ?>;">
            <h4 class="scms-specs-title">Características técnicas</h4>
            <dl id="scms_product_specs_list" class="scms-specs-grid">
                <?php foreach ($specs as $sp): ?>
                    <div class="scms-spec-row">
                        <dt><?= htmlspecialchars((string)$sp['label']) ?></dt>
                        <dd><?= htmlspecialchars((string)$sp['value']) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
        <?php
        return ob_get_clean();
    }
}
