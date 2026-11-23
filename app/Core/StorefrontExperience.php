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
}
