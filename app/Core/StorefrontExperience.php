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

    private static bool $resetTableEnsured = false;

    /**
     * Crea de forma idempotente la tabla de recuperación de contraseña si falta.
     */
    public static function ensurePasswordResetsTable(): void {
        if (self::$resetTableEnsured) {
            return;
        }
        self::$resetTableEnsured = true;
        try {
            $table = Database::table('password_resets');
            Database::getInstance()->exec("
                CREATE TABLE IF NOT EXISTS {$table} (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `email` VARCHAR(150) NOT NULL,
                    `token_hash` VARCHAR(64) NOT NULL,
                    `expires_at` DATETIME NOT NULL,
                    `used_at` DATETIME NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_reset_token_hash` (`token_hash`),
                    INDEX `idx_reset_email` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\Throwable $e) {
        }
    }

    private static bool $reviewsSchemaEnsured = false;

    /**
     * Asegura columnas extendidas en reviews (order_id, customer_id, updated_at, followup_comment, followup_at).
     */
    public static function ensureReviewsSchema(): void {
        if (self::$reviewsSchemaEnsured) {
            return;
        }
        self::$reviewsSchemaEnsured = true;
        try {
            $table = Database::table('reviews');
            $cols = array_column(Database::fetchAll("SHOW COLUMNS FROM {$table}"), 'Field');
            if (!in_array('order_id', $cols, true)) {
                Database::getInstance()->exec("ALTER TABLE {$table} ADD COLUMN `order_id` INT NULL AFTER `product_id`");
            }
            if (!in_array('customer_id', $cols, true)) {
                Database::getInstance()->exec("ALTER TABLE {$table} ADD COLUMN `customer_id` INT NULL AFTER `order_id`");
            }
            if (!in_array('followup_comment', $cols, true)) {
                Database::getInstance()->exec("ALTER TABLE {$table} ADD COLUMN `followup_comment` TEXT NULL AFTER `comment`");
            }
            if (!in_array('followup_at', $cols, true)) {
                Database::getInstance()->exec("ALTER TABLE {$table} ADD COLUMN `followup_at` DATETIME NULL AFTER `followup_comment`");
            }
            if (!in_array('updated_at', $cols, true)) {
                Database::getInstance()->exec("ALTER TABLE {$table} ADD COLUMN `updated_at` DATETIME NULL AFTER `is_featured`");
            }
        } catch (\Throwable $e) {
        }
    }

    public static function getReviewsWindowDays(): int {
        return max(0, (int)Settings::get('reviews_window_days', '90'));
    }

    public static function getOrderDeliveredAt(int $orderId, string $fallbackDatetime): string {
        try {
            $hist = Database::fetch("
                SELECT created_at FROM " . Database::table('order_history') . "
                WHERE order_id = ? AND status IN ('delivered', 'completed')
                ORDER BY id DESC LIMIT 1
            ", [$orderId]);
            if ($hist && !empty($hist['created_at'])) {
                return (string)$hist['created_at'];
            }
        } catch (\Throwable $e) {}
        return $fallbackDatetime;
    }

    public static function isWithinReviewWindow(string $deliveredAt, ?int $windowDays = null): array {
        $days = $windowDays ?? self::getReviewsWindowDays();
        if ($days <= 0) {
            return ['allowed' => true, 'days_left' => null, 'expires_at' => null];
        }
        $ts = strtotime($deliveredAt);
        if (!$ts) {
            return ['allowed' => true, 'days_left' => $days, 'expires_at' => null];
        }
        $expiresTs = $ts + ($days * 86400);
        $now = time();
        $allowed = ($now <= $expiresTs);
        $daysLeft = $allowed ? max(1, (int)ceil(($expiresTs - $now) / 86400)) : 0;
        return [
            'allowed' => $allowed,
            'days_left' => $daysLeft,
            'expires_at' => date('Y-m-d H:i:s', $expiresTs),
        ];
    }

    /**
     * Obtiene el contexto de compras y reseñas de un cliente para un producto (1 reseña por compra entregada + plazo).
     */
    public static function getCustomerProductReviewContext(int $customerId, string $customerEmail, int $productId): array {
        self::ensureReviewsSchema();
        $empty = [
            'reviewable_orders' => [],
            'expired_unreviewed_orders' => [],
            'pending_orders_in_transit' => [],
            'customer_reviews' => [],
            'window_days' => self::getReviewsWindowDays(),
        ];
        if ($customerId <= 0 || $productId <= 0) {
            return $empty;
        }

        $windowDays = self::getReviewsWindowDays();
        $orderRows = Database::fetchAll("
            SELECT o.id, o.order_number, o.status, o.created_at, o.updated_at
            FROM " . Database::table('orders') . " o
            INNER JOIN " . Database::table('order_items') . " oi ON oi.order_id = o.id
            WHERE (o.customer_id = ? OR LOWER(o.customer_email) = LOWER(?))
              AND oi.product_id = ?
              AND o.status NOT IN ('cancelled', 'returned')
            GROUP BY o.id, o.order_number, o.status, o.created_at, o.updated_at
            ORDER BY o.id DESC
        ", [$customerId, $customerEmail, $productId]);

        $reviewsRows = Database::fetchAll("
            SELECT r.*, o.order_number
            FROM " . Database::table('reviews') . " r
            LEFT JOIN " . Database::table('orders') . " o ON r.order_id = o.id
            WHERE r.product_id = ?
              AND (r.customer_id = ? OR LOWER(r.customer_email) = LOWER(?))
              AND r.status != 'hidden'
            ORDER BY r.id DESC
        ", [$productId, $customerId, $customerEmail]);

        $revByOrderId = [];
        $legacyReviews = [];
        foreach ($reviewsRows as $r) {
            $oid = !empty($r['order_id']) ? (int)$r['order_id'] : 0;
            if ($oid > 0) {
                $revByOrderId[$oid] = $r;
            } else {
                $legacyReviews[] = $r;
            }
        }

        $deliveredOrdersMap = [];
        $reviewableOrders = [];
        $expiredUnreviewed = [];
        $inTransit = [];

        foreach ($orderRows as $o) {
            $oid = (int)$o['id'];
            $st = (string)$o['status'];
            if (!in_array($st, ['delivered', 'completed'], true)) {
                $inTransit[] = $o;
                continue;
            }

            $deliveredAt = self::getOrderDeliveredAt($oid, (string)($o['updated_at'] ?: $o['created_at']));
            $win = self::isWithinReviewWindow($deliveredAt, $windowDays);
            $o['delivered_at'] = $deliveredAt;
            $o['window'] = $win;
            $deliveredOrdersMap[$oid] = $o;

            if (isset($revByOrderId[$oid])) {
                continue;
            }
            if (!empty($legacyReviews)) {
                // Compatibilidad: reseña antigua creada antes de existir order_id
                $rev = array_shift($legacyReviews);
                $rev['order_id'] = $oid;
                $rev['order_number'] = $o['order_number'];
                $revByOrderId[$oid] = $rev;
                continue;
            }

            if ($win['allowed']) {
                $reviewableOrders[] = $o;
            } else {
                $expiredUnreviewed[] = $o;
            }
        }

        $customerReviews = [];
        foreach ($reviewsRows as $r) {
            $oid = !empty($r['order_id']) ? (int)$r['order_id'] : 0;
            $win = ['allowed' => true, 'days_left' => null, 'expires_at' => null];
            if ($oid > 0 && isset($deliveredOrdersMap[$oid])) {
                $win = $deliveredOrdersMap[$oid]['window'];
            } else {
                $win = self::isWithinReviewWindow((string)$r['created_at'], $windowDays);
            }
            $r['can_edit'] = !empty($win['allowed']);
            $r['can_followup'] = !empty($win['allowed']) && trim((string)($r['followup_comment'] ?? '')) === '';
            $r['days_left'] = $win['days_left'];
            $customerReviews[] = $r;
        }

        return [
            'reviewable_orders' => $reviewableOrders,
            'expired_unreviewed_orders' => $expiredUnreviewed,
            'pending_orders_in_transit' => $inTransit,
            'customer_reviews' => $customerReviews,
            'window_days' => $windowDays,
        ];
    }

    /**
     * Renderiza la sección unificada de reseñas verificadas, edición y comentario adicional para cualquier tema.
     */
    public static function renderProductReviewsSection(
        array $product,
        array $reviews,
        string $baseUrl,
        array $themeOpts = []
    ): string {
        self::ensureReviewsSchema();
        $isCustomerLoggedIn = Auth::isCustomer();
        $customerInfo = Auth::customer();
        $prodId = (int)$product['id'];
        $ctx = $isCustomerLoggedIn
            ? self::getCustomerProductReviewContext((int)$customerInfo['id'], (string)$customerInfo['email'], $prodId)
            : ['reviewable_orders' => [], 'expired_unreviewed_orders' => [], 'pending_orders_in_transit' => [], 'customer_reviews' => [], 'window_days' => self::getReviewsWindowDays()];

        $sectionTitle = (string)($themeOpts['title'] ?? 'Opiniones de Clientes');
        $starActiveColor = (string)($themeOpts['star_active'] ?? '#F59E0B');
        $starEmptyColor = (string)($themeOpts['star_empty'] ?? '#E5E7EB');
        $isSerif = !empty($themeOpts['serif']);
        $isMono = !empty($themeOpts['mono']);
        $useContainer = !array_key_exists('use_container', $themeOpts) || !empty($themeOpts['use_container']);

        $titleStyle = $isSerif
            ? 'font-family: var(--font-serif); font-size: 22px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;'
            : ($isMono
                ? 'font-family: var(--font-mono); font-size: 18px; font-weight: 700; text-transform: uppercase;'
                : 'font-size: 24px; font-weight: 700;');

        ob_start();
        ?>
        <section data-theme-section="product_reviews" data-section-label="Reseñas de Clientes" style="padding: 36px 0; border-top: 2px solid var(--color-border);">
            <?php if ($useContainer): ?><div class="container"><?php endif; ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 10px;">
                    <h2 style="<?= $titleStyle ?>" data-theme-field="title"><?= htmlspecialchars($sectionTitle) ?> (<?= count($reviews) ?>)</h2>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 36px;">
                    <!-- LISTA PÚBLICA DE RESEÑAS -->
                    <div>
                        <?php if (empty($reviews)): ?>
                            <p style="color: var(--color-text-light, var(--color-text-muted, #6B7280));">Aún no hay reseñas verificadas para este producto.</p>
                        <?php else: ?>
                            <?php foreach ($reviews as $rev): ?>
                                <?php
                                $isEdited = !empty($rev['updated_at']);
                                $followup = trim((string)($rev['followup_comment'] ?? ''));
                                ?>
                                <div style="padding: 16px; border: 1px solid <?= !empty($rev['is_featured']) ? '#A7F3D0' : 'var(--color-border)' ?>; border-radius: 10px; margin-bottom: 16px; background: <?= !empty($rev['is_featured']) ? '#F9FEFC' : 'var(--color-bg, #FFF)' ?>;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 6px;">
                                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                            <div style="display: inline-flex; gap: 2px;">
                                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="<?= $s <= (int)$rev['rating'] ? $starActiveColor : $starEmptyColor ?>" stroke="<?= $s <= (int)$rev['rating'] ? $starActiveColor : '#D1D5DB' ?>" stroke-width="1.5">
                                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                                    </svg>
                                                <?php endfor; ?>
                                            </div>
                                            <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #166534; background: #DCFCE7; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                Compra Verificada
                                            </span>
                                            <?php if (!empty($rev['is_featured'])): ?>
                                                <span style="font-size: 11px; color: #92400E; background: #FEF3C7; padding: 2px 8px; border-radius: 4px; font-weight: 700;">Destacada</span>
                                            <?php endif; ?>
                                            <?php if ($isEdited): ?>
                                                <span style="font-size: 10.5px; color: var(--color-text-light, #6B7280); background: var(--color-surface, #F3F4F6); border: 1px solid var(--color-border, #E5E7EB); padding: 1px 6px; border-radius: 4px; font-weight: 600;">Editada</span>
                                            <?php endif; ?>
                                        </div>
                                        <span style="font-size: 12px; color: var(--color-text-light, var(--color-text-muted, #6B7280));">
                                            <?= date('d/m/Y', strtotime((string)$rev['created_at'])) ?>
                                        </span>
                                    </div>

                                    <p style="font-size: 14px; color: var(--color-text); margin: 0 0 8px; line-height: 1.55;">
                                        <?= nl2br(htmlspecialchars((string)$rev['comment'])) ?>
                                    </p>

                                    <?php if ($followup !== ''): ?>
                                        <div style="margin: 10px 0 8px; padding: 10px 12px; background: var(--color-surface, #F8FAFC); border-left: 3px solid var(--color-primary, #008060); border-radius: 6px; font-size: 13px; color: var(--color-text);">
                                            <div style="font-size: 11.5px; font-weight: 700; color: var(--color-primary, #008060); margin-bottom: 3px;">
                                                Comentario adicional después de recibir el producto<?= !empty($rev['followup_at']) ? ' · ' . date('d/m/Y', strtotime((string)$rev['followup_at'])) : '' ?>
                                            </div>
                                            <?= nl2br(htmlspecialchars($followup)) ?>
                                        </div>
                                    <?php endif; ?>

                                    <small style="color: var(--color-text-light, var(--color-text-muted, #6B7280));">
                                        Por: <?= htmlspecialchars((string)($rev['customer_name'] ?? $rev['author_name'] ?? 'Cliente')) ?>
                                    </small>

                                    <?php if (!empty($rev['admin_reply'])): ?>
                                        <div style="margin-top: 10px; padding: 10px 12px; background: var(--color-surface, #F3F4F6); border-left: 3px solid var(--color-primary, #008060); border-radius: 6px; font-size: 13px; color: var(--color-text);">
                                            <strong style="display: block; font-size: 11.5px; color: var(--color-primary, #008060); margin-bottom: 3px;">Respuesta oficial de la tienda</strong>
                                            <?= nl2br(htmlspecialchars((string)$rev['admin_reply'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- PANEL INTERACTIVO DEL COMPRADOR -->
                    <div>
                        <?php if (!$isCustomerLoggedIn): ?>
                            <div style="background: var(--color-surface, #FFF); padding: 24px; border-radius: 12px; border: 1px solid var(--color-border); text-align: center;">
                                <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 6px;">Opiniones Verificadas</h4>
                                <p style="font-size: 13px; color: var(--color-text-light, #6B7280); line-height: 1.5; margin-bottom: 16px;">
                                    Solo los clientes que han recibido este producto pueden publicar, editar o ampliar su opinión.
                                </p>
                                <a href="<?= htmlspecialchars($baseUrl) ?>/login" class="btn-store btn-store-primary" style="font-size: 13px; width: 100%; text-decoration: none; display: block; text-align: center;">Iniciar Sesión</a>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($ctx['reviewable_orders'])):
                                $revOrders = $ctx['reviewable_orders'];
                                $firstOrder = $revOrders[0];
                            ?>
                                <div style="background: var(--color-surface, #FFF); padding: 22px; border-radius: 12px; border: 1px solid var(--color-border); margin-bottom: 16px;">
                                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px; color: #166534; font-weight: 700; font-size: 12.5px;">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        <span>Compra entregada verificada</span>
                                    </div>
                                    <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 12px;">Comparte tu Experiencia</h3>
                                    <form method="POST" action="<?= htmlspecialchars($baseUrl) ?>/account/review">
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="product_id" value="<?= $prodId ?>">

                                        <?php if (count($revOrders) > 1): ?>
                                            <div style="margin-bottom: 12px;">
                                                <label style="display: block; font-size: 12.5px; font-weight: 600; margin-bottom: 4px;">Compra a calificar *</label>
                                                <select name="order_id" required style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--color-border); font-size: 12.5px;">
                                                    <?php foreach ($revOrders as $ro): ?>
                                                        <option value="<?= (int)$ro['id'] ?>">
                                                            Pedido <?= htmlspecialchars((string)$ro['order_number']) ?> (entregado <?= date('d/m/Y', strtotime((string)$ro['delivered_at'])) ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php else: ?>
                                            <input type="hidden" name="order_id" value="<?= (int)$firstOrder['id'] ?>">
                                            <div style="font-size: 12px; color: var(--color-text-light, #6B7280); margin-bottom: 12px;">
                                                Pedido <strong><?= htmlspecialchars((string)$firstOrder['order_number']) ?></strong>
                                                <?php if (!empty($firstOrder['window']['days_left'])): ?>
                                                    · Tienes <?= (int)$firstOrder['window']['days_left'] ?> días para opinar
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div style="margin-bottom: 12px;">
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
                                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Descripción / Comentario *</label>
                                            <textarea name="comment" rows="3" required placeholder="Cuéntale a otros clientes cómo te fue con el producto..." style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--color-border); font-family: inherit; font-size: 13px;"></textarea>
                                        </div>

                                        <button type="submit" class="btn-store btn-store-primary" style="width: 100%; padding: 10px; font-size: 14px;">Publicar Opinión</button>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($ctx['customer_reviews'])): ?>
                                <?php foreach ($ctx['customer_reviews'] as $myRev):
                                    $rid = (int)$myRev['id'];
                                ?>
                                    <div style="background: var(--color-surface, #FFF); padding: 18px; border-radius: 12px; border: 1px solid var(--color-border); margin-bottom: 14px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 6px; margin-bottom: 6px;">
                                            <span style="font-size: 12.5px; font-weight: 700; color: var(--color-primary, #008060);">
                                                Tu reseña <?= !empty($myRev['order_number']) ? '(' . htmlspecialchars((string)$myRev['order_number']) . ')' : '' ?>
                                            </span>
                                            <span style="font-size: 11px; color: var(--color-text-light, #6B7280);">
                                                <?= (int)$myRev['rating'] ?>/5 estrellas
                                            </span>
                                        </div>
                                        <p style="font-size: 13px; color: var(--color-text); margin: 0 0 10px; line-height: 1.45;">
                                            <?= nl2br(htmlspecialchars((string)$myRev['comment'])) ?>
                                        </p>

                                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                            <?php if (!empty($myRev['can_edit'])): ?>
                                                <button type="button" onclick="var el=document.getElementById('scms_rev_edit_<?= $rid ?>');if(el)el.style.display=(el.style.display==='none'?'block':'none');" class="btn-store btn-store-secondary" style="font-size: 12px; padding: 6px 10px;">
                                                    Editar reseña
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!empty($myRev['can_followup'])): ?>
                                                <button type="button" onclick="var el=document.getElementById('scms_rev_fu_<?= $rid ?>');if(el)el.style.display=(el.style.display==='none'?'block':'none');" class="btn-store btn-store-secondary" style="font-size: 12px; padding: 6px 10px;">
                                                    Añadir comentario adicional
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($myRev['can_edit'])): ?>
                                            <form id="scms_rev_edit_<?= $rid ?>" method="POST" action="<?= htmlspecialchars($baseUrl) ?>/account/review/update/<?= $rid ?>" style="display: none; margin-top: 12px; padding-top: 10px; border-top: 1px dashed var(--color-border);">
                                                <?= Security::csrfField() ?>
                                                <div style="margin-bottom: 8px;">
                                                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 3px;">Calificación *</label>
                                                    <select name="rating" required style="width: 100%; padding: 6px; border-radius: 6px; border: 1px solid var(--color-border); font-size: 12.5px;">
                                                        <?php for ($rv = 5; $rv >= 1; $rv--): ?>
                                                            <option value="<?= $rv ?>" <?= (int)$myRev['rating'] === $rv ? 'selected' : '' ?>><?= $rv ?> estrellas</option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                                <div style="margin-bottom: 8px;">
                                                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 3px;">Comentario actualizado *</label>
                                                    <textarea name="comment" rows="3" required style="width: 100%; padding: 6px; border-radius: 6px; border: 1px solid var(--color-border); font-size: 12.5px;"><?= htmlspecialchars((string)$myRev['comment']) ?></textarea>
                                                </div>
                                                <button type="submit" class="btn-store btn-store-primary" style="width: 100%; padding: 7px; font-size: 12.5px;">Guardar Cambios</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (!empty($myRev['can_followup'])): ?>
                                            <form id="scms_rev_fu_<?= $rid ?>" method="POST" action="<?= htmlspecialchars($baseUrl) ?>/account/review/followup/<?= $rid ?>" style="display: none; margin-top: 12px; padding-top: 10px; border-top: 1px dashed var(--color-border);">
                                                <?= Security::csrfField() ?>
                                                <div style="margin-bottom: 8px;">
                                                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 3px;">Comentario adicional tras el uso *</label>
                                                    <textarea name="followup_comment" rows="3" required placeholder="Cuenta cómo sigue el producto después de días o semanas..." style="width: 100%; padding: 6px; border-radius: 6px; border: 1px solid var(--color-border); font-size: 12.5px;"></textarea>
                                                </div>
                                                <button type="submit" class="btn-store btn-store-primary" style="width: 100%; padding: 7px; font-size: 12.5px;">Publicar Comentario Adicional</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (empty($myRev['can_edit']) && !empty($ctx['window_days'])): ?>
                                            <div style="margin-top: 8px; font-size: 11.5px; color: var(--color-text-light, #6B7280);">
                                                Plazo de edición cerrado (límite: <?= (int)$ctx['window_days'] ?> días tras la entrega).
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($ctx['pending_orders_in_transit'])): ?>
                                <div style="background: var(--color-surface, #FFF); padding: 18px; border-radius: 12px; border: 1px solid var(--color-border); margin-bottom: 14px;">
                                    <h4 style="font-size: 14px; font-weight: 700; margin: 0 0 6px;">Pedido en curso</h4>
                                    <p style="font-size: 12.5px; color: var(--color-text-light, #6B7280); line-height: 1.5; margin: 0 0 10px;">
                                        Tienes un pedido con este producto en camino. Podrás calificarlo únicamente cuando su estado sea <strong>Entregado</strong>.
                                    </p>
                                    <a href="<?= htmlspecialchars($baseUrl) ?>/account" class="btn-store btn-store-secondary" style="font-size: 12.5px; width: 100%; text-decoration: none; display: block; text-align: center;">Ver Seguimiento en Mi Cuenta</a>
                                </div>
                            <?php elseif (empty($ctx['reviewable_orders']) && !empty($ctx['expired_unreviewed_orders'])): ?>
                                <div style="background: var(--color-surface, #FFF); padding: 18px; border-radius: 12px; border: 1px solid var(--color-border); text-align: center;">
                                    <h4 style="font-size: 14px; font-weight: 700; margin: 0 0 6px;">Plazo de calificación cerrado</h4>
                                    <p style="font-size: 12.5px; color: var(--color-text-light, #6B7280); line-height: 1.5; margin: 0;">
                                        Transcurrieron más de <?= (int)$ctx['window_days'] ?> días desde la entrega de tu pedido.
                                    </p>
                                </div>
                            <?php elseif (empty($ctx['reviewable_orders']) && empty($ctx['customer_reviews'])): ?>
                                <div style="background: var(--color-surface, #FFF); padding: 24px; border-radius: 12px; border: 1px solid var(--color-border); text-align: center;">
                                    <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 6px;">Opiniones 100% Reales</h4>
                                    <p style="font-size: 13px; color: var(--color-text-light, #6B7280); line-height: 1.5; margin-bottom: 16px;">
                                        Solo los clientes que han recibido este producto (estado Entregado) pueden calificarlo.
                                    </p>
                                    <a href="<?= htmlspecialchars($baseUrl) ?>/account" class="btn-store btn-store-secondary" style="font-size: 13px; width: 100%; text-decoration: none; display: block; text-align: center;">Ver Mis Pedidos</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php if ($useContainer): ?></div><?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
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
