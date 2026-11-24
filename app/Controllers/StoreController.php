<?php
namespace ShopCMS\Controllers;

use ShopCMS\Core\Database;
use ShopCMS\Core\ThemeManager;
use ShopCMS\Core\PluginManager;
use ShopCMS\Core\OrderWorkflow;
use ShopCMS\Core\InventoryManager;
use ShopCMS\Core\Settings;
use ShopCMS\Core\ThemeCustomizer;
use ShopCMS\Core\CatalogHelper;
use ShopCMS\Core\StorefrontExperience;

class StoreController {

    public function __construct() {
        $this->enforceMaintenanceIfNeeded();
    }

    private function enforceMaintenanceIfNeeded(): void {
        if (Settings::getBool('maintenance_mode', false) && !\ShopCMS\Core\Auth::isAdmin()) {
            http_response_code(503);
            $baseUrl = defined('APP_URL') ? APP_URL : '';
            $storeName = Settings::get('store_name', 'ShopCMS Store');
            $message = Settings::get('maintenance_message', 'Estamos realizando mejoras en nuestra tienda. Volvemos pronto.');
            $storeEmail = Settings::get('store_email', '');
            $storePhone = Settings::get('store_phone', '');
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title><?= htmlspecialchars($storeName) ?> — En Mantenimiento</title>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #F6F6F7; color: #111827; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
                    .maint-card { background: #FFFFFF; max-width: 520px; width: 100%; border-radius: 14px; border: 1px solid #E5E7EB; padding: 40px 32px; text-align: center; box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
                    .maint-icon { width: 58px; height: 58px; border-radius: 50%; background: #FEF3C7; color: #B45309; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 18px; }
                    h1 { font-size: 22px; margin: 0 0 10px; }
                    p { font-size: 14.5px; color: #4B5563; line-height: 1.6; margin: 0 0 22px; }
                    .maint-contact { font-size: 13px; color: #6B7280; padding-top: 16px; border-top: 1px solid #E5E7EB; }
                </style>
            </head>
            <body>
                <div class="maint-card">
                    <div class="maint-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                        </svg>
                    </div>
                    <h1><?= htmlspecialchars($storeName) ?> vuelve pronto</h1>
                    <p><?= nl2br(htmlspecialchars($message)) ?></p>
                    <div class="maint-contact">
                        <?php if ($storeEmail): ?><div>Correo: <?= htmlspecialchars($storeEmail) ?></div><?php endif; ?>
                        <?php if ($storePhone): ?><div>Atención: <?= htmlspecialchars($storePhone) ?></div><?php endif; ?>
                        <div style="margin-top: 12px;"><a href="<?= htmlspecialchars($baseUrl) ?>/admin/login" style="color: #008060; font-weight: 600; text-decoration: none;">Acceso Administrador</a></div>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }

    private function getCurrency(): array {
        $code = Settings::get('store_currency', 'USD');
        $symbol = Settings::get('store_currency_symbol', '$');
        return ['code' => $code, 'symbol' => $symbol];
    }

    public function home(): void {
        $products = Database::fetchAll("
            SELECT p.*, c.name as category_name 
            FROM " . Database::table('products') . " p
            LEFT JOIN " . Database::table('categories') . " c ON p.category_id = c.id AND c.is_active = 1
            WHERE p.is_active = 1
            ORDER BY p.id DESC LIMIT 8
        ");

        $categories = Database::fetchAll("
            SELECT c.*,
                   (
                       COALESCE((
                           SELECT SUM(oi.quantity)
                           FROM " . Database::table('order_items') . " oi
                           INNER JOIN " . Database::table('products') . " pr ON oi.product_id = pr.id
                           WHERE pr.category_id = c.id
                       ), 0) * 10
                       +
                       (SELECT COUNT(*) FROM " . Database::table('products') . " pr2 WHERE pr2.category_id = c.id AND pr2.is_active = 1)
                   ) as demand_score
            FROM " . Database::table('categories') . " c
            WHERE c.is_active = 1
            ORDER BY demand_score DESC, c.name ASC
        ");
        $storeName = Settings::get('store_name', 'ShopCMS Store');

        ThemeManager::render('home', [
            'products' => $products,
            'categories' => $categories,
            'storeName' => $storeName,
            'currency' => $this->getCurrency()
        ]);
    }

    public function catalog(): void {
        $themeSlug = ThemeManager::getActiveTheme();
        $themeCfg = ThemeCustomizer::getConfig($themeSlug);
        $defaultPerPage = (int)($themeCfg['templates']['catalog']['sections']['catalog_grid']['per_page'] ?? 12);

        $catalogData = CatalogHelper::queryCatalog($_GET, $defaultPerPage);
        $categories = Database::fetchAll("SELECT * FROM " . Database::table('categories') . " WHERE is_active = 1 ORDER BY name ASC");

        ThemeManager::render('catalog', [
            'products'       => $catalogData['products'],
            'categories'     => $categories,
            'activeCategory' => $catalogData['filters']['category'],
            'search'         => $catalogData['filters']['q'],
            'filters'        => $catalogData['filters'],
            'totalProducts'  => $catalogData['totalProducts'],
            'totalPages'     => $catalogData['totalPages'],
            'currentPage'    => $catalogData['currentPage'],
            'perPage'        => $catalogData['perPage'],
            'showingFrom'    => $catalogData['showingFrom'],
            'showingTo'      => $catalogData['showingTo'],
            'priceBounds'    => $catalogData['priceBounds'],
            'currency'       => $this->getCurrency(),
        ]);
    }

    public function product(string $slug): void {
        StorefrontExperience::ensureVariantRichColumns();
        $product = Database::fetch("
            SELECT p.*, c.name as category_name 
            FROM " . Database::table('products') . " p
            LEFT JOIN " . Database::table('categories') . " c ON p.category_id = c.id AND c.is_active = 1
            WHERE p.slug = ? AND p.is_active = 1
        ", [$slug]);

        if (!$product) {
            http_response_code(404);
            die("Producto no encontrado.");
        }

        // Variantes
        $variants = Database::fetchAll("SELECT * FROM " . Database::table('product_variants') . " WHERE product_id = ? ORDER BY id ASC", [$product['id']]);

        // Galería adicional de imágenes
        $galleryImages = [];
        try {
            $galleryImages = Database::fetchAll("SELECT * FROM " . Database::table('product_images') . " WHERE product_id = ? ORDER BY sort_order ASC, id ASC", [$product['id']]);
        } catch (\Throwable $e) {
            $galleryImages = [];
        }

        // Reseñas aprobadas (destacadas primero)
        StorefrontExperience::ensureReviewsSchema();
        $reviews = Database::fetchAll("
            SELECT r.*, o.order_number
            FROM " . Database::table('reviews') . " r
            LEFT JOIN " . Database::table('orders') . " o ON r.order_id = o.id
            WHERE r.product_id = ? AND r.status = 'approved'
            ORDER BY r.is_featured DESC, r.id DESC
        ", [$product['id']]);
        
        $avgRating = 0;
        if (!empty($reviews)) {
            $sum = array_sum(array_column($reviews, 'rating'));
            $avgRating = round($sum / count($reviews), 1);
        }

        ThemeManager::render('product', [
            'product' => $product,
            'variants' => $variants,
            'galleryImages' => $galleryImages,
            'reviews' => $reviews,
            'avgRating' => $avgRating,
            'heartCount' => StorefrontExperience::getProductHeartCount((int)$product['id']),
            'soldCount' => StorefrontExperience::getProductSoldCount((int)$product['id']),
            'currency' => $this->getCurrency()
        ]);
    }

    // ==========================================
    // MÓDULO 2: CARRITO DE COMPRAS
    // ==========================================
    private function hasUserRedeemedCoupon(string $code, ?int $customerId, array $emails): bool {
        $conditions = [];
        $params = [$code];

        if ($customerId !== null && $customerId > 0) {
            $conditions[] = "customer_id = ?";
            $params[] = $customerId;
        }
        foreach (array_unique(array_filter(array_map(fn($e) => strtolower(trim((string)$e)), $emails))) as $email) {
            $conditions[] = "LOWER(customer_email) = ?";
            $params[] = $email;
        }

        if (empty($conditions)) {
            return false;
        }

        $sql = "SELECT id FROM " . Database::table('orders') . " 
                WHERE UPPER(coupon_code) = UPPER(?) 
                  AND status != 'cancelled' 
                  AND (" . implode(' OR ', $conditions) . ") 
                LIMIT 1";
        return (bool)Database::fetch($sql, $params);
    }

    private function evaluateCoupon(array $coupon, float $subtotal, string $currencySymbol, ?int $customerId = null, array $customerEmails = []): array {
        if (!empty($coupon['usage_limit']) && (int)$coupon['usage_count'] >= (int)$coupon['usage_limit']) {
            return ['valid' => false, 'error' => "El cupón {$coupon['code']} ya alcanzó su límite máximo de usos.", 'discount' => 0.0];
        }
        if (!empty($coupon['expires_at']) && date('Y-m-d') > $coupon['expires_at']) {
            return ['valid' => false, 'error' => "El cupón {$coupon['code']} ha caducado.", 'discount' => 0.0];
        }

        // Validar límite de 1 canje por usuario (habilitado por defecto)
        $singleUse = !isset($coupon['single_use_per_user']) || !empty($coupon['single_use_per_user']);
        if ($singleUse && $this->hasUserRedeemedCoupon($coupon['code'], $customerId, $customerEmails)) {
            return [
                'valid' => false,
                'error' => "Ya canjeaste el cupón {$coupon['code']} en una compra anterior. Solo se permite un uso por usuario.",
                'discount' => 0.0
            ];
        }

        // Validar regla de monto de compra (mínimo, exacto o rango específico)
        $spendRule = $coupon['spend_rule'] ?? 'min';
        $minSpend = (float)($coupon['min_spend'] ?? 0);
        $maxSpend = isset($coupon['max_spend']) && $coupon['max_spend'] !== null ? (float)$coupon['max_spend'] : 0.0;

        if ($spendRule === 'exact') {
            if (abs(round($subtotal, 2) - round($minSpend, 2)) > 0.009) {
                return [
                    'valid' => false,
                    'error' => "El cupón {$coupon['code']} solo aplica comprando un monto exacto de " . $currencySymbol . number_format($minSpend, 2) . " (subtotal actual: " . $currencySymbol . number_format($subtotal, 2) . ").",
                    'discount' => 0.0
                ];
            }
        } elseif ($spendRule === 'range') {
            if ($subtotal < $minSpend || ($maxSpend > 0 && $subtotal > $maxSpend)) {
                return [
                    'valid' => false,
                    'error' => "El cupón {$coupon['code']} solo aplica para compras entre " . $currencySymbol . number_format($minSpend, 2) . " y " . $currencySymbol . number_format($maxSpend, 2) . ".",
                    'discount' => 0.0
                ];
            }
        } elseif ($minSpend > 0 && $subtotal < $minSpend) {
            return [
                'valid' => false,
                'error' => "El cupón {$coupon['code']} requiere una compra mínima de " . $currencySymbol . number_format($minSpend, 2),
                'discount' => 0.0
            ];
        }

        $discount = ($coupon['type'] === 'percent')
            ? round($subtotal * ((float)$coupon['value'] / 100), 2)
            : (float)$coupon['value'];

        return ['valid' => true, 'error' => null, 'discount' => min($subtotal, max(0.0, $discount))];
    }

    private function getActiveCustomerIdentifiers(array $extraEmails = []): array {
        $customerId = null;
        $emails = $extraEmails;
        if (\ShopCMS\Core\Auth::isCustomer()) {
            $cust = \ShopCMS\Core\Auth::customer();
            if ($cust) {
                $customerId = (int)$cust['id'];
                if (!empty($cust['email'])) {
                    $emails[] = $cust['email'];
                }
            }
        }
        return [$customerId, $emails];
    }

    private function recalculateCartCoupon(float $subtotal, array $extraEmails = []): float {
        $code = $_SESSION['cart_coupon'] ?? null;
        if (empty($code) || $subtotal <= 0) {
            unset($_SESSION['cart_discount'], $_SESSION['cart_coupon']);
            return 0.0;
        }

        $coupon = Database::fetch("SELECT * FROM " . Database::table('coupons') . " WHERE code = ? AND is_active = 1", [$code]);
        if (!$coupon) {
            unset($_SESSION['cart_discount'], $_SESSION['cart_coupon']);
            return 0.0;
        }

        $currency = $this->getCurrency();
        [$customerId, $emails] = $this->getActiveCustomerIdentifiers($extraEmails);
        $res = $this->evaluateCoupon($coupon, $subtotal, $currency['symbol'], $customerId, $emails);
        if (!$res['valid']) {
            unset($_SESSION['cart_discount'], $_SESSION['cart_coupon']);
            $_SESSION['cart_error'] = $res['error'];
            return 0.0;
        }

        $_SESSION['cart_discount'] = $res['discount'];
        return $res['discount'];
    }

    private function requireCustomer(string $noticeMsg, string $redirectTarget = ''): array {
        if (!\ShopCMS\Core\Auth::isCustomer()) {
            \ShopCMS\Core\Auth::ensureIsolatedCartSession();
            $_SESSION['checkout_notice'] = $noticeMsg;
            $qs = $redirectTarget !== '' ? '?redirect=' . urlencode($redirectTarget) : '';
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/login' . $qs);
            exit;
        }
        \ShopCMS\Core\Auth::ensureIsolatedCartSession();
        return \ShopCMS\Core\Auth::customer();
    }

    private function resolvePostAuthDestination(string $redirect): string {
        $redirect = trim($redirect);
        if ($redirect === 'checkout') return '/checkout';
        if ($redirect === 'cart') return '/cart';
        if ($redirect === 'catalog') return '/catalog';
        if ($redirect !== '' && str_starts_with($redirect, '/') && !str_starts_with($redirect, '//') && !str_contains($redirect, '\\') && !str_contains($redirect, '://')) {
            return $redirect;
        }
        return '/account';
    }

    public function cart(): void {
        $this->requireCustomer("Inicia sesión con tu cuenta de cliente para ver tu carrito de compras.", "cart");
        $cart = $_SESSION['cart'] ?? [];
        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $discount = $this->recalculateCartCoupon((float)$subtotal);
        $totals = Settings::calculateCheckoutTotals((float)$subtotal, (float)$discount);

        ThemeManager::render('cart', [
            'cart' => $cart,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shippingAmount' => $totals['shipping_amount'],
            'taxAmount' => $totals['tax_amount'],
            'taxRate' => $totals['tax_rate'],
            'taxIncluded' => $totals['tax_included'],
            'freeShippingThreshold' => $totals['free_shipping_threshold'],
            'freeShippingUnlocked' => $totals['free_shipping_unlocked'],
            'freeShippingRemaining' => $totals['free_shipping_remaining'],
            'orderMinAmount' => Settings::getFloat('order_min_amount', 0.0),
            'total' => $totals['total'],
            'couponCode' => $_SESSION['cart_coupon'] ?? null,
            'currency' => $this->getCurrency()
        ]);
    }

    private function addProductToSessionCart(array $product, ?int $variantId, int $quantity): array {
        $productId = (int)$product['id'];
        $title = (string)$product['name'];
        $price = (float)$product['price'];
        $image = (string)($product['featured_image'] ?? '');

        if ($variantId) {
            $variant = Database::fetch("SELECT * FROM " . Database::table('product_variants') . " WHERE id = ? AND product_id = ?", [$variantId, $productId]);
            if ($variant) {
                $title .= " (" . $variant['title'] . ")";
                $price = (float)$variant['price'];
                if (!empty($variant['image_url'])) {
                    $image = (string)$variant['image_url'];
                }
            } else {
                $variantId = null;
            }
        }

        $cartKey = $variantId ? "prod_{$productId}_var_{$variantId}" : "prod_{$productId}";
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $currentInCart = isset($_SESSION['cart'][$cartKey]) ? (int)$_SESSION['cart'][$cartKey]['quantity'] : 0;
        $desiredQty = $currentInCart + max(1, $quantity);
        $stockInfo = InventoryManager::getAvailableStock($productId, $variantId);
        $noticeMsg = 'Producto agregado a tu cesta.';
        $wasClamped = false;

        if ($stockInfo['track']) {
            if ($stockInfo['available'] <= 0) {
                return ['ok' => false, 'error' => "Lo sentimos, '{$title}' se encuentra agotado temporalmente.", 'notice' => '', 'clamped' => false];
            }
            if ($desiredQty > $stockInfo['available']) {
                $desiredQty = $stockInfo['available'];
                $noticeMsg = "Se ajustó la cantidad de '{$title}' al máximo disponible en almacén ({$stockInfo['available']} unid.).";
                $wasClamped = true;
            }
        }

        if (isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey]['quantity'] = $desiredQty;
        } else {
            $_SESSION['cart'][$cartKey] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'name' => $title,
                'price' => $price,
                'quantity' => $desiredQty,
                'image' => $image
            ];
        }

        \ShopCMS\Core\Auth::saveCustomerCart();
        return ['ok' => true, 'error' => null, 'notice' => $noticeMsg, 'clamped' => $wasClamped, 'title' => $title];
    }

    private function consumePendingCartAdd(): ?string {
        if (empty($_SESSION['pending_cart_add']) || !is_array($_SESSION['pending_cart_add']) || !\ShopCMS\Core\Auth::isCustomer()) {
            unset($_SESSION['pending_cart_add']);
            return null;
        }
        $pending = $_SESSION['pending_cart_add'];
        unset($_SESSION['pending_cart_add']);

        $pid = (int)($pending['product_id'] ?? 0);
        $vid = !empty($pending['variant_id']) ? (int)$pending['variant_id'] : null;
        $qty = max(1, (int)($pending['quantity'] ?? 1));
        $buyNow = !empty($pending['buy_now']);

        if ($pid <= 0) return null;
        $product = Database::fetch("SELECT * FROM " . Database::table('products') . " WHERE id = ? AND is_active = 1", [$pid]);
        if (!$product) return null;

        $res = $this->addProductToSessionCart($product, $vid, $qty);
        if (!$res['ok']) {
            $_SESSION['cart_error'] = $res['error'];
            return '/cart';
        }
        $_SESSION['cart_notice'] = 'Artículo "' . ($res['title'] ?? $product['name']) . '" agregado automáticamente a tu cesta.';
        return $buyNow ? '/checkout' : null;
    }

    public function cartAdd(): void {
        $wantsJson = str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json')
            || (($_POST['ajax'] ?? '') === '1');
        $baseUrl = defined('APP_URL') ? APP_URL : '';

        $productId = (int)($_POST['product_id'] ?? 0);
        $variantId = !empty($_POST['variant_id']) ? (int)$_POST['variant_id'] : null;
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        $product = Database::fetch("SELECT * FROM " . Database::table('products') . " WHERE id = ? AND is_active = 1", [$productId]);
        if (!$product) {
            if (!\ShopCMS\Core\Auth::isCustomer()) {
                if ($wantsJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(401);
                    $_SESSION['checkout_notice'] = "Inicia sesión para agregar productos a tu carrito de compras.";
                    echo json_encode(['ok' => false, 'require_login' => true, 'login_url' => $baseUrl . '/login?auth_msg=cart&redirect=catalog']);
                    exit;
                }
                $this->requireCustomer("Inicia sesión para agregar productos a tu carrito de compras.", "catalog");
            }
            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Producto no encontrado.']);
                exit;
            }
            header('Location: ' . $baseUrl . '/catalog');
            exit;
        }

        if (!\ShopCMS\Core\Auth::isCustomer()) {
            $_SESSION['pending_cart_add'] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'buy_now' => !empty($_POST['buy_now']),
            ];
            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(401);
                $_SESSION['checkout_notice'] = "Inicia sesión para agregar productos a tu carrito de compras.";
                echo json_encode([
                    'ok' => false,
                    'require_login' => true,
                    'login_url' => $baseUrl . '/login?auth_msg=cart&redirect=' . urlencode('/product/' . $product['slug']),
                ]);
                exit;
            }
            $this->requireCustomer("Inicia sesión para agregar productos a tu carrito de compras.", "/product/" . $product['slug']);
        }

        $res = $this->addProductToSessionCart($product, $variantId, $quantity);
        if (!$res['ok']) {
            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(409);
                echo json_encode(['ok' => false, 'error' => $res['error'], 'basket' => StorefrontExperience::buildSideBasketData()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $_SESSION['cart_error'] = $res['error'];
            header('Location: ' . $baseUrl . '/cart');
            exit;
        }
        if ($res['clamped'] && !$wantsJson) {
            $_SESSION['cart_error'] = $res['notice'];
        }
        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'notice' => $res['notice'],
                'basket' => StorefrontExperience::buildSideBasketData(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if (!empty($_POST['buy_now'])) {
            header('Location: ' . $baseUrl . '/checkout');
            exit;
        }
        header('Location: ' . $baseUrl . '/cart');
        exit;
    }

    public function cartSideUpdate(): void {
        header('Content-Type: application/json; charset=utf-8');
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        if (!\ShopCMS\Core\Auth::isCustomer()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'require_login' => true, 'login_url' => $baseUrl . '/login?auth_msg=cart']);
            exit;
        }
        \ShopCMS\Core\Auth::ensureIsolatedCartSession();

        $body = json_decode(file_get_contents('php://input') ?: '{}', true);
        if (!is_array($body)) $body = $_POST;
        $key = trim((string)($body['key'] ?? ''));
        $qty = (int)($body['quantity'] ?? 0);
        $notice = 'Cesta actualizada.';

        if ($key !== '' && isset($_SESSION['cart'][$key])) {
            if ($qty <= 0) {
                unset($_SESSION['cart'][$key]);
                $notice = 'Artículo retirado de tu cesta.';
            } else {
                $item = $_SESSION['cart'][$key];
                $stockInfo = InventoryManager::getAvailableStock((int)$item['product_id'], !empty($item['variant_id']) ? (int)$item['variant_id'] : null);
                if ($stockInfo['track']) {
                    if ($stockInfo['available'] <= 0) {
                        unset($_SESSION['cart'][$key]);
                        $notice = "'{$item['name']}' se agotó y fue retirado de tu cesta.";
                    } elseif ($qty > $stockInfo['available']) {
                        $qty = $stockInfo['available'];
                        $_SESSION['cart'][$key]['quantity'] = $qty;
                        $notice = "Cantidad máxima disponible en stock: {$qty}.";
                    } else {
                        $_SESSION['cart'][$key]['quantity'] = $qty;
                    }
                } else {
                    $_SESSION['cart'][$key]['quantity'] = $qty;
                }
            }
            \ShopCMS\Core\Auth::saveCustomerCart();
        }

        echo json_encode([
            'ok' => true,
            'notice' => $notice,
            'basket' => StorefrontExperience::buildSideBasketData(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function cartUpdate(): void {
        $this->requireCustomer("Inicia sesión para modificar tu carrito de compras.", "cart");
        if (!empty($_POST['quantities']) && is_array($_POST['quantities'])) {
            foreach ($_POST['quantities'] as $key => $qty) {
                $qty = (int)$qty;
                if ($qty <= 0) {
                    unset($_SESSION['cart'][$key]);
                } else if (isset($_SESSION['cart'][$key])) {
                    $item = $_SESSION['cart'][$key];
                    $stockInfo = InventoryManager::getAvailableStock((int)$item['product_id'], !empty($item['variant_id']) ? (int)$item['variant_id'] : null);
                    if ($stockInfo['track']) {
                        if ($stockInfo['available'] <= 0) {
                            unset($_SESSION['cart'][$key]);
                            $_SESSION['cart_error'] = "'{$item['name']}' se agotó y fue retirado del carrito.";
                            continue;
                        }
                        if ($qty > $stockInfo['available']) {
                            $qty = $stockInfo['available'];
                            $_SESSION['cart_error'] = "La cantidad de '{$item['name']}' se ajustó al stock disponible ({$stockInfo['available']} unid.).";
                        }
                    }
                    $_SESSION['cart'][$key]['quantity'] = $qty;
                }
            }
        }
        \ShopCMS\Core\Auth::saveCustomerCart();
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/cart');
        exit;
    }

    public function cartRemove(string $itemKey): void {
        $this->requireCustomer("Inicia sesión para gestionar tu carrito de compras.", "cart");
        if (isset($_SESSION['cart'][$itemKey])) {
            unset($_SESSION['cart'][$itemKey]);
            \ShopCMS\Core\Auth::saveCustomerCart();
        }
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/cart');
        exit;
    }

    public function applyCoupon(): void {
        $this->requireCustomer("Inicia sesión para aplicar cupones en tu carrito.", "cart");
        $code = strtoupper(preg_replace('/\s+/', '', trim($_POST['coupon_code'] ?? '')));
        $coupon = Database::fetch("SELECT * FROM " . Database::table('coupons') . " WHERE code = ? AND is_active = 1", [$code]);
        $currency = $this->getCurrency();

        $subtotal = 0;
        foreach ($_SESSION['cart'] ?? [] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        if ($coupon) {
            [$customerId, $emails] = $this->getActiveCustomerIdentifiers();
            $res = $this->evaluateCoupon($coupon, (float)$subtotal, $currency['symbol'], $customerId, $emails);
            if ($res['valid']) {
                $_SESSION['cart_discount'] = $res['discount'];
                $_SESSION['cart_coupon'] = $coupon['code'];
            } else {
                $_SESSION['cart_error'] = $res['error'];
                unset($_SESSION['cart_discount'], $_SESSION['cart_coupon']);
            }
        } else {
            $_SESSION['cart_error'] = "Cupón inválido o inactivo.";
            unset($_SESSION['cart_discount'], $_SESSION['cart_coupon']);
        }

        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/cart');
        exit;
    }

    public function removeCoupon(): void {
        $this->requireCustomer("Inicia sesión para gestionar tu carrito.", "cart");
        unset($_SESSION['cart_discount'], $_SESSION['cart_coupon']);
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/cart');
        exit;
    }

    // ==========================================
    // CHECKOUT Y PROCESO DE COMPRA
    // ==========================================
    public function checkout(): void {
        \ShopCMS\Core\Auth::ensureIsolatedCartSession();
        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/cart');
            exit;
        }

        // Exigir autenticación del cliente antes del checkout
        if (!\ShopCMS\Core\Auth::isCustomer()) {
            $_SESSION['checkout_notice'] = "Para completar tu pedido, por favor inicia sesión o crea una cuenta en un solo paso.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/login?redirect=checkout');
            exit;
        }

        $customer = \ShopCMS\Core\Auth::customer();

        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $orderMinAmount = Settings::getFloat('order_min_amount', 0.0);
        if ($orderMinAmount > 0 && $subtotal < $orderMinAmount) {
            $currency = $this->getCurrency();
            $_SESSION['cart_error'] = "El monto mínimo de compra en esta tienda es de " . $currency['symbol'] . number_format($orderMinAmount, 2) . ".";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/cart');
            exit;
        }

        $discount = $this->recalculateCartCoupon((float)$subtotal);
        $totals = Settings::calculateCheckoutTotals((float)$subtotal, (float)$discount);

        $hasPaymentPlugin = false;
        try {
            $pg = Database::fetch("SELECT id FROM " . Database::table('plugins') . " WHERE slug = 'payment-gateway' AND is_active = 1");
            $hasPaymentPlugin = !empty($pg);
        } catch (\Throwable $e) {}

        $enableCod = Settings::getBool('enable_payment_cod', false);
        $enableBank = Settings::getBool('enable_payment_bank', false);
        if (!$enableCod && !$enableBank && !$hasPaymentPlugin) {
            $enableCod = true;
        }

        ThemeManager::render('checkout', [
            'cart' => $cart,
            'customer' => $customer,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shippingAmount' => $totals['shipping_amount'],
            'taxAmount' => $totals['tax_amount'],
            'taxRate' => $totals['tax_rate'],
            'taxIncluded' => $totals['tax_included'],
            'estimatedDays' => Settings::get('shipping_estimated_days', '2 a 4 días hábiles'),
            'enablePaymentCod' => $enableCod,
            'enablePaymentBank' => $enableBank,
            'bankInstructions' => Settings::get('bank_transfer_instructions', ''),
            'total' => $totals['total'],
            'couponCode' => $_SESSION['cart_coupon'] ?? null,
            'currency' => $this->getCurrency()
        ]);
    }

    public function processOrder(): void {
        \ShopCMS\Core\Auth::ensureIsolatedCartSession();
        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/catalog');
            exit;
        }

        if (!\ShopCMS\Core\Auth::isCustomer()) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/login?redirect=checkout');
            exit;
        }

        $customer = \ShopCMS\Core\Auth::customer();
        $customerId = (int)$customer['id'];

        $customerName = trim($_POST['customer_name'] ?? $customer['name']);
        $customerEmail = trim($_POST['customer_email'] ?? $customer['email']);
        $customerPhone = trim($_POST['customer_phone'] ?? $customer['phone']);
        $shippingAddress = trim($_POST['shipping_address'] ?? $customer['address']);
        $paymentMethod = $_POST['payment_method'] ?? 'cod';

        // Actualizar datos de contacto y dirección del cliente en la BD
        Database::query("UPDATE " . Database::table('users') . " SET phone = COALESCE(NULLIF(?, ''), phone), address = COALESCE(NULLIF(?, ''), address) WHERE id = ?", [
            $customerPhone,
            $shippingAddress,
            $customerId
        ]);

        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $orderMinAmount = Settings::getFloat('order_min_amount', 0.0);
        if ($orderMinAmount > 0 && $subtotal < $orderMinAmount) {
            $currency = $this->getCurrency();
            $_SESSION['cart_error'] = "El monto mínimo de compra en esta tienda es de " . $currency['symbol'] . number_format($orderMinAmount, 2) . ".";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/cart');
            exit;
        }

        $hadCoupon = !empty($_SESSION['cart_coupon']);
        $discount = $this->recalculateCartCoupon((float)$subtotal, [$customerEmail]);
        if ($hadCoupon && empty($_SESSION['cart_coupon'])) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/cart');
            exit;
        }

        $totals = Settings::calculateCheckoutTotals((float)$subtotal, (float)$discount);
        $shippingAmount = $totals['shipping_amount'];
        $taxAmount = $totals['tax_amount'];
        $total = $totals['total'];

        // Verificar que el método de pago elegido siga habilitado
        $hasPaymentPlugin = false;
        try {
            $pg = Database::fetch("SELECT id FROM " . Database::table('plugins') . " WHERE slug = 'payment-gateway' AND is_active = 1");
            $hasPaymentPlugin = !empty($pg);
        } catch (\Throwable $e) {}

        $allowCod = Settings::getBool('enable_payment_cod', false);
        $allowBank = Settings::getBool('enable_payment_bank', false);
        if ($paymentMethod === 'cod' && !$allowCod) {
            $paymentMethod = $allowBank ? 'bank_transfer' : ($hasPaymentPlugin ? 'culqi_card' : 'cod');
        } elseif ($paymentMethod === 'bank_transfer' && !$allowBank) {
            $paymentMethod = $allowCod ? 'cod' : ($hasPaymentPlugin ? 'culqi_card' : 'bank_transfer');
        }

        OrderWorkflow::ensurePaymentProofSchema();
        $paymentReference = substr(trim((string)($_POST['payment_reference'] ?? '')), 0, 120);
        $paymentProofPath = null;
        if ($paymentMethod === 'bank_transfer' && !empty($_FILES['payment_proof']['name']) && (int)($_FILES['payment_proof']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $paymentProofPath = OrderWorkflow::storeVoucherUpload($_FILES['payment_proof']);
            } catch (\Throwable $e) {
                $_SESSION['cart_error'] = $e->getMessage();
                header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/checkout');
                exit;
            }
        }

        $orderPrefix = Settings::get('order_prefix', '#ORD-');
        $orderNumber = $orderPrefix . strtoupper(substr(uniqid(), -6));
        $allowBackorder = Settings::getBool('allow_backorder', false);

        $pdo = Database::getInstance();
        try {
            $pdo->beginTransaction();

            // 1. Verificar disponibilidad real, refrescar precios vivos en BD y reservar stock con bloqueo (FOR UPDATE)
            $liveSubtotal = 0.0;
            foreach ($cart as $cartKey => &$item) {
                $reqQty = max(1, (int)$item['quantity']);
                $prod = Database::fetch("SELECT id, name, price, track_quantity, quantity FROM " . Database::table('products') . " WHERE id = ? AND is_active = 1 FOR UPDATE", [(int)$item['product_id']]);
                if (!$prod) {
                    throw new \RuntimeException("El producto '{$item['name']}' ya no está disponible.");
                }
                $unitPrice = (float)$prod['price'];
                if (!empty($item['variant_id'])) {
                    $var = Database::fetch("SELECT id, price, quantity FROM " . Database::table('product_variants') . " WHERE id = ? AND product_id = ? FOR UPDATE", [(int)$item['variant_id'], (int)$prod['id']]);
                    if (!$var || (!$allowBackorder && !empty($prod['track_quantity']) && (int)$var['quantity'] < $reqQty)) {
                        $disp = $var ? (int)$var['quantity'] : 0;
                        throw new \RuntimeException("Stock insuficiente para '{$item['name']}'. Disponible: {$disp}.");
                    }
                    $unitPrice = (float)$var['price'];
                } elseif (!$allowBackorder && !empty($prod['track_quantity']) && (int)$prod['quantity'] < $reqQty) {
                    throw new \RuntimeException("Stock insuficiente para '{$item['name']}'. Disponible: " . (int)$prod['quantity'] . ".");
                }
                $item['price'] = $unitPrice;
                $liveSubtotal += $unitPrice * $reqQty;
            }
            unset($item);

            $subtotal = $liveSubtotal;
            $discount = $this->recalculateCartCoupon((float)$subtotal, [$customerEmail]);
            $totals = Settings::calculateCheckoutTotals((float)$subtotal, (float)$discount);
            $shippingAmount = $totals['shipping_amount'];
            $taxAmount = $totals['tax_amount'];
            $total = $totals['total'];

            // 2. Guardar orden vinculada al cliente
            $orderId = Database::insert('orders', [
                'order_number' => $orderNumber,
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'shipping_address' => $shippingAddress,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'coupon_code' => $_SESSION['cart_coupon'] ?? null,
                'shipping_amount' => $shippingAmount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'status' => 'pending',
                'payment_method' => $paymentMethod,
                'payment_status' => 'unpaid',
                'payment_proof' => $paymentProofPath,
                'payment_reference' => ($paymentMethod === 'bank_transfer' && $paymentReference !== '') ? $paymentReference : null,
                'stock_restored' => 0
            ]);

            // 3. Guardar líneas, descontar inventario reservado y registrar en Kardex
            foreach ($cart as $item) {
                $prodId = (int)$item['product_id'];
                $varId = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
                $buyQty = max(1, (int)$item['quantity']);

                Database::insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $prodId,
                    'variant_id' => $varId,
                    'product_name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $buyQty,
                    'total' => $item['price'] * $buyQty
                ]);

                if ($varId) {
                    $curV = Database::fetch("SELECT quantity FROM " . Database::table('product_variants') . " WHERE id = ?", [$varId]);
                    $prevV = (int)($curV['quantity'] ?? 0);
                    $nextV = max(0, $prevV - $buyQty);
                    Database::query("UPDATE " . Database::table('product_variants') . " SET quantity = ? WHERE id = ?", [$nextV, $varId]);
                    InventoryManager::syncParentProductStock($prodId);
                    InventoryManager::logMovement($prodId, $varId, $prevV, $nextV, 'order_reserve', $orderNumber, 'Reserva de stock por pedido en tienda');
                } else {
                    $curP = Database::fetch("SELECT quantity FROM " . Database::table('products') . " WHERE id = ?", [$prodId]);
                    $prevP = (int)($curP['quantity'] ?? 0);
                    $nextP = max(0, $prevP - $buyQty);
                    Database::query("UPDATE " . Database::table('products') . " SET quantity = ? WHERE id = ?", [$nextP, $prodId]);
                    InventoryManager::logMovement($prodId, null, $prevP, $nextP, 'order_reserve', $orderNumber, 'Reserva de stock por pedido en tienda');
                }
            }

            // 4. Incrementar uso de cupón si aplica
            if (!empty($_SESSION['cart_coupon'])) {
                Database::query("UPDATE " . Database::table('coupons') . " SET usage_count = usage_count + 1 WHERE code = ?", [$_SESSION['cart_coupon']]);
            }

            // 5. Registrar primer hito en el historial de seguimiento (Tracking)
            $methodLabel = OrderWorkflow::paymentMethodLabel($paymentMethod);
            OrderWorkflow::recordHistory(
                $orderId,
                'pending',
                "Pedido recibido y registrado correctamente ({$methodLabel}). Unidades reservadas en inventario.",
                'customer'
            );

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['cart_error'] = $e->getMessage();
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/cart');
            exit;
        }

        // 6. Disparar hooks de plugins (ej. pasarela de pago o generador de facturas PDF)
        PluginManager::doAction('order_created', $orderId, [
            'order_number' => $orderNumber,
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'total' => $total,
            'payment_method' => $paymentMethod
        ]);

        // 7. Vaciar carrito de sesión y guardado en BD
        unset($_SESSION['cart'], $_SESSION['cart_discount'], $_SESSION['cart_coupon']);
        \ShopCMS\Core\Auth::saveCustomerCart();

        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/order/success/' . ltrim($orderNumber, '#'));
        exit;
    }

    public function orderSuccess(string $orderNumber): void {
        OrderWorkflow::ensurePaymentProofSchema();
        $customer = $this->requireCustomer("Inicia sesión para ver el comprobante de tu pedido.", "/order/success/" . ltrim($orderNumber, '#'));
        $clean = ltrim($orderNumber, '#');
        $order = Database::fetch("SELECT * FROM " . Database::table('orders') . " WHERE order_number = ? OR order_number = ?", [$clean, '#' . $clean]);
        $sameOwner = $order && (
            ((int)($order['customer_id'] ?? 0) === (int)$customer['id'])
            || (strtolower(trim((string)($order['customer_email'] ?? ''))) === strtolower(trim((string)$customer['email'])))
        );
        if (!$order || (!$sameOwner && !\ShopCMS\Core\Auth::isAdmin())) {
            if ($order) {
                \ShopCMS\Core\Security::auditLog('idor_order_denied', ['customer_id' => (int)$customer['id']]);
            }
            http_response_code(404);
            $_SESSION['flash_error'] = "No se encontró el pedido solicitado.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/account');
            exit;
        }

        $items = Database::fetchAll("SELECT * FROM " . Database::table('order_items') . " WHERE order_id = ?", [$order['id']]);
        $history = Database::fetchAll("SELECT * FROM " . Database::table('order_history') . " WHERE order_id = ? ORDER BY id DESC", [$order['id']]);

        ThemeManager::render('order-success', [
            'order' => $order,
            'items' => $items,
            'history' => $history,
            'bankInstructions' => Settings::get('bank_transfer_instructions', ''),
            'currency' => $this->getCurrency()
        ]);
    }

    // ==========================================
    // AUTENTICACIÓN Y CUENTA DE CLIENTES
    // ==========================================
    private function resolveAuthNotice(?string $sessionNotice): ?string {
        if (!empty($sessionNotice)) return $sessionNotice;
        $code = $_GET['auth_msg'] ?? '';
        $map = [
            'wishlist' => 'Inicia sesión con tu cuenta para guardar y gestionar tu lista de favoritos.',
            'comparator' => 'Inicia sesión con tu cuenta para comparar productos (hasta 4).',
            'stock_notify' => 'Inicia sesión con tu cuenta para recibir alertas cuando repongamos stock.',
            'cart' => 'Inicia sesión con tu cuenta para agregar productos al carrito de compras.',
        ];
        return $map[$code] ?? null;
    }

    public function customerLogin(): void {
        if (\ShopCMS\Core\Auth::isCustomer()) {
            $redirect = $_GET['redirect'] ?? '';
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . $this->resolvePostAuthDestination($redirect));
            exit;
        }

        $error = $_SESSION['auth_error'] ?? null;
        $notice = $this->resolveAuthNotice($_SESSION['checkout_notice'] ?? null);
        $lockoutSeconds = (int)($_SESSION['customer_lockout_seconds'] ?? 0);
        unset($_SESSION['auth_error'], $_SESSION['checkout_notice'], $_SESSION['customer_lockout_seconds']);

        if ($lockoutSeconds <= 0) {
            $lastEmail = (string)($_SESSION['customer_last_attempt_email'] ?? '');
            $lock = \ShopCMS\Core\Security::checkLoginLockout('customer', $lastEmail);
            if ($lock['locked']) {
                $lockoutSeconds = $lock['remaining_seconds'];
                $error = 'Demasiados intentos fallidos. Acceso bloqueado temporalmente por seguridad. Intenta nuevamente en ' . \ShopCMS\Core\Security::formatWaitTime($lockoutSeconds) . '.';
            }
        }

        ThemeManager::render('login', [
            'error' => $error,
            'notice' => $notice,
            'lockoutSeconds' => $lockoutSeconds,
            'redirect' => $_GET['redirect'] ?? '',
            'currency' => $this->getCurrency()
        ]);
    }

    public function customerLoginProcess(): void {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $redirect = trim($_POST['redirect'] ?? '');
        $redirQuery = $redirect ? '?redirect=' . urlencode($redirect) : '';
        $_SESSION['customer_last_attempt_email'] = $email;

        $lock = \ShopCMS\Core\Security::checkLoginLockout('customer', $email);
        if ($lock['locked']) {
            http_response_code(429);
            $_SESSION['customer_lockout_seconds'] = $lock['remaining_seconds'];
            $_SESSION['auth_error'] = 'Demasiados intentos fallidos. Cuenta bloqueada temporalmente. Espera ' . \ShopCMS\Core\Security::formatWaitTime($lock['remaining_seconds']) . ' antes de volver a intentarlo.';
            session_write_close();
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/login' . $redirQuery);
            exit;
        }

        if (\ShopCMS\Core\Auth::customerLogin($email, $password)) {
            \ShopCMS\Core\Security::clearLoginAttempts('customer', $email);
            unset($_SESSION['customer_last_attempt_email'], $_SESSION['customer_lockout_seconds']);
            @session_regenerate_id(true);
            $pendingDest = $this->consumePendingCartAdd();
            session_write_close();
            $destination = $pendingDest ?: $this->resolvePostAuthDestination($redirect);
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . $destination);
            exit;
        }

        if (empty($_SESSION['auth_error'])) {
            $res = \ShopCMS\Core\Security::recordFailedLogin('customer', $email);
            if ($res['locked']) {
                http_response_code(429);
                $_SESSION['customer_lockout_seconds'] = $res['remaining_seconds'];
                $_SESSION['auth_error'] = 'Alcanzaste el máximo de ' . \ShopCMS\Core\Security::MAX_ACCOUNT_ATTEMPTS . ' intentos fallidos. Inicio de sesión bloqueado durante ' . \ShopCMS\Core\Security::formatWaitTime($res['remaining_seconds']) . '.';
            } else {
                $rem = $res['remaining_attempts'];
                $_SESSION['auth_error'] = 'Credenciales incorrectas. Te ' . ($rem === 1 ? 'queda 1 intento' : "quedan {$rem} intentos") . ' antes del bloqueo temporal.';
            }
        }
        session_write_close();
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/login' . $redirQuery);
        exit;
    }

    public function customerRegister(): void {
        if (\ShopCMS\Core\Auth::isCustomer()) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/account');
            exit;
        }

        $error = $_SESSION['auth_error'] ?? null;
        $notice = $_SESSION['checkout_notice'] ?? null;
        unset($_SESSION['auth_error'], $_SESSION['checkout_notice']);

        ThemeManager::render('register', [
            'error' => $error,
            'notice' => $notice,
            'redirect' => $_GET['redirect'] ?? '',
            'currency' => $this->getCurrency()
        ]);
    }

    private function validatePasswordStrength(string $password): ?string {
        if (strlen($password) < 8) {
            return "La contraseña debe tener al menos 8 caracteres.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return "La contraseña debe incluir al menos una letra mayúscula (A-Z).";
        }
        if (!preg_match('/[a-z]/', $password)) {
            return "La contraseña debe incluir al menos una letra minúscula (a-z).";
        }
        if (!preg_match('/[0-9]/', $password)) {
            return "La contraseña debe incluir al menos un número (0-9).";
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return "La contraseña debe incluir al menos un carácter especial (ej. ! @ # $ % . _ -).";
        }
        return null;
    }

    public function customerRegisterProcess(): void {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $redirect = trim($_POST['redirect'] ?? '');

        $redirQuery = $redirect ? '?redirect=' . urlencode($redirect) : '';
        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['auth_error'] = "Por favor completa todos los campos requeridos.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/register' . $redirQuery);
            exit;
        }

        $strengthError = $this->validatePasswordStrength($password);
        if ($strengthError !== null) {
            $_SESSION['auth_error'] = $strengthError;
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/register' . $redirQuery);
            exit;
        }

        if ($password !== $passwordConfirm) {
            $_SESSION['auth_error'] = "Las contraseñas no coinciden. Verifica ambos campos.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/register' . $redirQuery);
            exit;
        }

        try {
            \ShopCMS\Core\Auth::customerRegister($name, $email, $password, $phone, $address);
            $pendingDest = $this->consumePendingCartAdd();
            $destination = $pendingDest ?: $this->resolvePostAuthDestination($redirect);
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . $destination);
            exit;
        } catch (\Exception $e) {
            $_SESSION['auth_error'] = $e->getMessage();
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/register' . $redirQuery);
            exit;
        }
    }

    public function customerProfileUpdate(): void {
        if (!\ShopCMS\Core\Auth::isCustomer()) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/login');
            exit;
        }

        $customer = \ShopCMS\Core\Auth::customer();
        $customerId = (int)$customer['id'];
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['new_password_confirm'] ?? '';

        if ($name === '') {
            $_SESSION['flash_error'] = "El nombre completo no puede estar vacío.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/account');
            exit;
        }

        $updateData = [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
        ];

        if ($newPass !== '') {
            $dbUser = Database::fetch("SELECT password FROM " . Database::table('users') . " WHERE id = ?", [$customerId]);
            if ($dbUser && !empty($dbUser['password']) && !password_verify($currentPass, $dbUser['password'])) {
                $_SESSION['flash_error'] = "Tu contraseña actual es incorrecta.";
                header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/account');
                exit;
            }
            $strengthError = $this->validatePasswordStrength($newPass);
            if ($strengthError !== null) {
                $_SESSION['flash_error'] = $strengthError;
                header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/account');
                exit;
            }
            if ($newPass !== $confirmPass) {
                $_SESSION['flash_error'] = "La nueva contraseña y su confirmación no coinciden.";
                header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/account');
                exit;
            }
            $updateData['password'] = password_hash($newPass, PASSWORD_DEFAULT);
        }

        Database::update('users', $updateData, "id = :id", ['id' => $customerId]);
        $_SESSION['customer_name'] = $name;
        $_SESSION['customer_phone'] = $phone;
        $_SESSION['customer_address'] = $address;

        $_SESSION['flash_success'] = "Tus datos personales fueron actualizados correctamente.";
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/account');
        exit;
    }

    public function customerLogout(): void {
        \ShopCMS\Core\Auth::customerLogout();
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/login');
        exit;
    }

    public function customerAccount(): void {
        if (!\ShopCMS\Core\Auth::isCustomer()) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/login');
            exit;
        }

        $customer = \ShopCMS\Core\Auth::customer();
        $dbCustomer = Database::fetch("SELECT * FROM " . Database::table('users') . " WHERE id = ?", [$customer['id']]);

        // Obtener historial de pedidos con artículos y línea de tiempo (Tracking)
        $orders = Database::fetchAll("
            SELECT * FROM " . Database::table('orders') . " 
            WHERE customer_id = ? OR LOWER(customer_email) = LOWER(?) 
            ORDER BY id DESC
        ", [$customer['id'], $customer['email']]);

        foreach ($orders as &$order) {
            $order['items'] = Database::fetchAll("
                SELECT oi.*, p.slug as product_slug, p.featured_image
                FROM " . Database::table('order_items') . " oi
                LEFT JOIN " . Database::table('products') . " p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ", [$order['id']]);

            $history = Database::fetchAll("
                SELECT * FROM " . Database::table('order_history') . "
                WHERE order_id = ?
                ORDER BY id DESC
            ", [$order['id']]);

            if (empty($history)) {
                $meta = OrderWorkflow::getStatusMeta($order['status']);
                $history = [[
                    'status' => $order['status'],
                    'comment' => $meta['desc'],
                    'actor' => 'system',
                    'created_at' => $order['updated_at'] ?? $order['created_at']
                ]];
            }
            $order['history'] = $history;
        }
        unset($order);

        // Reseñas hechas por el cliente (excluir las deshabilitadas/ocultas)
        StorefrontExperience::ensureReviewsSchema();
        $reviews = Database::fetchAll("
            SELECT r.*, p.name as product_name, p.slug as product_slug, o.order_number, o.status as order_status, o.updated_at as order_updated_at, o.created_at as order_created_at
            FROM " . Database::table('reviews') . " r
            INNER JOIN " . Database::table('products') . " p ON r.product_id = p.id
            LEFT JOIN " . Database::table('orders') . " o ON r.order_id = o.id
            WHERE (r.customer_id = ? OR LOWER(r.customer_email) = LOWER(?)) AND r.status != 'hidden'
            ORDER BY r.id DESC
        ", [(int)$customer['id'], $customer['email']]);

        $windowDays = StorefrontExperience::getReviewsWindowDays();
        $revByOrderProd = [];
        $legacyByProd = [];
        foreach ($reviews as &$revRow) {
            $oid = !empty($revRow['order_id']) ? (int)$revRow['order_id'] : 0;
            $pid = (int)$revRow['product_id'];
            $deliveredAt = $oid > 0
                ? StorefrontExperience::getOrderDeliveredAt($oid, (string)($revRow['order_updated_at'] ?: $revRow['order_created_at']))
                : (string)$revRow['created_at'];
            $win = StorefrontExperience::isWithinReviewWindow($deliveredAt, $windowDays);
            $revRow['can_edit'] = !empty($win['allowed']);
            $revRow['can_followup'] = !empty($win['allowed']) && trim((string)($revRow['followup_comment'] ?? '')) === '';
            $revRow['days_left'] = $win['days_left'];
            if ($oid > 0) {
                $revByOrderProd[$oid . ':' . $pid] = $revRow;
            } else {
                $legacyByProd[$pid][] = $revRow;
            }
        }
        unset($revRow);

        foreach ($orders as &$ordRef) {
            $ordId = (int)$ordRef['id'];
            $isDelivered = in_array((string)$ordRef['status'], ['delivered', 'completed'], true);
            $ordWin = ['allowed' => false, 'days_left' => 0];
            if ($isDelivered) {
                $dAt = StorefrontExperience::getOrderDeliveredAt($ordId, (string)($ordRef['updated_at'] ?: $ordRef['created_at']));
                $ordWin = StorefrontExperience::isWithinReviewWindow($dAt, $windowDays);
            }
            foreach ($ordRef['items'] as &$itRef) {
                $pid = (int)($itRef['product_id'] ?? 0);
                $matchedRev = $revByOrderProd[$ordId . ':' . $pid] ?? null;
                if (!$matchedRev && $isDelivered && !empty($legacyByProd[$pid])) {
                    $matchedRev = array_shift($legacyByProd[$pid]);
                }
                $itRef['review'] = $matchedRev;
                $itRef['can_review'] = ($isDelivered && $pid > 0 && !$matchedRev && !empty($ordWin['allowed']));
                $itRef['review_window_expired'] = ($isDelivered && $pid > 0 && !$matchedRev && empty($ordWin['allowed']));
                $itRef['review_days_left'] = $ordWin['days_left'];
            }
            unset($itRef);
        }
        unset($ordRef);

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        ThemeManager::render('account', [
            'customer' => $dbCustomer,
            'orders' => $orders,
            'reviews' => $reviews,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
            'currency' => $this->getCurrency()
        ]);
    }

    public function customerOrderAction(string $id): void {
        if (!\ShopCMS\Core\Auth::isCustomer()) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/login');
            exit;
        }

        $customer = \ShopCMS\Core\Auth::customer();
        $orderId = (int)$id;
        $order = Database::fetch("
            SELECT * FROM " . Database::table('orders') . " 
            WHERE id = ? AND (customer_id = ? OR LOWER(customer_email) = LOWER(?))
        ", [$orderId, (int)$customer['id'], $customer['email']]);

        if (!$order) {
            $_SESSION['flash_error'] = "No se encontró el pedido solicitado.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/account');
            exit;
        }

        $action = $_POST['action'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if ($action === 'cancel') {
            if (!in_array($order['status'], ['pending', 'processing'], true)) {
                $_SESSION['flash_error'] = "El pedido {$order['order_number']} ya entró en etapa de empaque o envío y no puede cancelarse directamente.";
            } else {
                $cancelNote = $reason !== '' ? $reason : 'Cancelado a solicitud del cliente antes del despacho.';
                Database::update('orders', [
                    'status' => 'cancelled',
                    'cancel_reason' => $cancelNote
                ], "id = :id", ['id' => $orderId]);
                OrderWorkflow::syncInventoryForStatus($orderId, 'cancelled');
                OrderWorkflow::recordHistory($orderId, 'cancelled', "Pedido cancelado por el cliente. Motivo: {$cancelNote}", 'customer');
                $_SESSION['flash_success'] = "Tu pedido {$order['order_number']} fue cancelado y el stock reservado fue liberado.";
            }
        } elseif ($action === 'confirm_delivery') {
            if (!in_array($order['status'], ['shipped', 'out_for_delivery'], true)) {
                $_SESSION['flash_error'] = "Solo puedes confirmar la recepción de pedidos que ya fueron enviados.";
            } else {
                $update = ['status' => 'delivered'];
                if ($order['payment_method'] === 'cod') {
                    $update['payment_status'] = 'paid';
                }
                Database::update('orders', $update, "id = :id", ['id' => $orderId]);
                OrderWorkflow::recordHistory($orderId, 'delivered', 'El cliente confirmó la recepción conforme del paquete en destino.', 'customer');
                $_SESSION['flash_success'] = "¡Gracias por confirmar la recepción de tu pedido {$order['order_number']}!";
            }
        } elseif ($action === 'request_return') {
            if (!in_array($order['status'], ['delivered', 'completed'], true)) {
                $_SESSION['flash_error'] = "Solo puedes solicitar devolución sobre pedidos ya entregados.";
            } else {
                $returnNote = $reason !== '' ? $reason : 'Solicitud de devolución iniciada por el cliente.';
                Database::update('orders', [
                    'status' => 'returned',
                    'payment_status' => $order['payment_status'] === 'paid' ? 'refunded' : $order['payment_status'],
                    'cancel_reason' => $returnNote
                ], "id = :id", ['id' => $orderId]);
                OrderWorkflow::syncInventoryForStatus($orderId, 'returned');
                OrderWorkflow::recordHistory($orderId, 'returned', "Devolución registrada por el cliente. Motivo: {$returnNote}", 'customer');
                $_SESSION['flash_success'] = "Se registró la devolución del pedido {$order['order_number']}.";
            }
        }

        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/account');
        exit;
    }

    public function orderReceipt(string $orderNumber): void {
        OrderWorkflow::ensurePaymentProofSchema();
        $clean = ltrim($orderNumber, '#');
        $customer = $this->requireCustomer("Inicia sesión para descargar el comprobante de tu pedido.", "/order/receipt/" . $clean);
        $order = Database::fetch("SELECT * FROM " . Database::table('orders') . " WHERE order_number = ? OR order_number = ?", [$clean, '#' . $clean]);
        $sameOwner = $order && (
            ((int)($order['customer_id'] ?? 0) === (int)$customer['id'])
            || (strtolower(trim((string)($order['customer_email'] ?? ''))) === strtolower(trim((string)$customer['email'])))
        );
        if (!$order || (!$sameOwner && !\ShopCMS\Core\Auth::isAdmin())) {
            if ($order) {
                \ShopCMS\Core\Security::auditLog('idor_receipt_denied', ['customer_id' => (int)$customer['id']]);
            }
            http_response_code(404);
            $_SESSION['flash_error'] = "No se encontró el pedido solicitado.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/account');
            exit;
        }

        $items = Database::fetchAll("SELECT * FROM " . Database::table('order_items') . " WHERE order_id = ?", [$order['id']]);
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        $backUrl = $baseUrl . '/account';
        $currencySymbol = Settings::get('store_currency_symbol', '$');

        require dirname(__DIR__) . '/Views/shared/order_receipt.php';
    }

    public function uploadOrderVoucher(string $id): void {
        if (!\ShopCMS\Core\Auth::isCustomer()) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/login');
            exit;
        }

        OrderWorkflow::ensurePaymentProofSchema();
        $customer = \ShopCMS\Core\Auth::customer();
        $orderId = (int)$id;
        $order = Database::fetch("
            SELECT * FROM " . Database::table('orders') . "
            WHERE id = ? AND (customer_id = ? OR LOWER(customer_email) = LOWER(?))
        ", [$orderId, (int)$customer['id'], $customer['email']]);

        $baseUrl = defined('APP_URL') ? APP_URL : '';
        $returnTo = trim((string)($_POST['return_to'] ?? ''));
        $safeRedirect = ($returnTo !== '' && str_starts_with($returnTo, '/order/success/'))
            ? ($baseUrl . $returnTo)
            : ($baseUrl . '/account');

        if (!$order) {
            $_SESSION['flash_error'] = "No se encontró el pedido solicitado.";
            header('Location: ' . $safeRedirect);
            exit;
        }

        if (($order['payment_method'] ?? '') !== 'bank_transfer') {
            $_SESSION['flash_error'] = "Este pedido no utiliza transferencia bancaria o billetera digital.";
            header('Location: ' . $safeRedirect);
            exit;
        }

        if (in_array($order['status'] ?? '', ['cancelled', 'returned'], true) || ($order['payment_status'] ?? '') === 'paid') {
            $_SESSION['flash_error'] = "El pago de este pedido ya fue validado o el pedido fue cerrado.";
            header('Location: ' . $safeRedirect);
            exit;
        }

        $ref = substr(trim((string)($_POST['payment_reference'] ?? '')), 0, 120);
        $hasFile = !empty($_FILES['payment_proof']['name']) && (int)($_FILES['payment_proof']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if (!$hasFile && $ref === '') {
            $_SESSION['flash_error'] = "Adjunta la imagen/PDF del comprobante o ingresa el número de operación.";
            header('Location: ' . $safeRedirect);
            exit;
        }

        $updateData = [];
        if ($ref !== '') {
            $updateData['payment_reference'] = $ref;
        }
        if ($hasFile) {
            try {
                $updateData['payment_proof'] = OrderWorkflow::storeVoucherUpload($_FILES['payment_proof']);
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
                header('Location: ' . $safeRedirect);
                exit;
            }
        }

        Database::update('orders', $updateData, "id = :id", ['id' => $orderId]);
        $note = "El cliente registró constancia de pago" . ($ref !== '' ? " (Nro. Op: {$ref})" : '') . ".";
        OrderWorkflow::recordHistory($orderId, (string)$order['status'], $note, 'customer');

        $_SESSION['flash_success'] = "Comprobante de pago registrado para el pedido {$order['order_number']}. Nuestro equipo lo verificará a la brevedad.";
        header('Location: ' . $safeRedirect);
        exit;
    }

    // ==========================================
    // RESEÑAS VERIFICADAS DE CLIENTES
    // ==========================================
    private function resolveReviewRedirect(int $productId, string $returnTo = ''): string {
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        if ($returnTo === '/account') {
            return $baseUrl . '/account';
        }
        $product = Database::fetch("SELECT slug FROM " . Database::table('products') . " WHERE id = ?", [$productId]);
        if ($product && !empty($product['slug'])) {
            return $baseUrl . '/product/' . $product['slug'];
        }
        return $baseUrl . '/account';
    }

    private function verifyEditableCustomerReview(int $reviewId, array $customer): array {
        StorefrontExperience::ensureReviewsSchema();
        $review = Database::fetch("
            SELECT r.*, o.status as order_status, o.updated_at as order_updated_at, o.created_at as order_created_at
            FROM " . Database::table('reviews') . " r
            LEFT JOIN " . Database::table('orders') . " o ON r.order_id = o.id
            WHERE r.id = ?
              AND (r.customer_id = ? OR LOWER(r.customer_email) = LOWER(?))
              AND r.status != 'hidden'
        ", [$reviewId, (int)$customer['id'], (string)$customer['email']]);

        if (!$review) {
            return ['ok' => false, 'error' => 'No se encontró la reseña solicitada en tu cuenta.', 'review' => null];
        }

        $windowDays = StorefrontExperience::getReviewsWindowDays();
        $oid = !empty($review['order_id']) ? (int)$review['order_id'] : 0;
        if ($oid > 0 && !in_array((string)($review['order_status'] ?? ''), ['delivered', 'completed'], true)) {
            return ['ok' => false, 'error' => 'Solo se pueden modificar reseñas de pedidos entregados.', 'review' => $review];
        }

        $deliveredAt = $oid > 0
            ? StorefrontExperience::getOrderDeliveredAt($oid, (string)($review['order_updated_at'] ?: $review['order_created_at']))
            : (string)$review['created_at'];
        $win = StorefrontExperience::isWithinReviewWindow($deliveredAt, $windowDays);
        if (empty($win['allowed'])) {
            return ['ok' => false, 'error' => "El plazo máximo de {$windowDays} días para modificar esta reseña ya expiró.", 'review' => $review];
        }

        return ['ok' => true, 'error' => null, 'review' => $review];
    }

    public function submitVerifiedReview(): void {
        if (!\ShopCMS\Core\Auth::isCustomer()) {
            $_SESSION['flash_error'] = "Debes iniciar sesión para publicar una reseña.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/login');
            exit;
        }

        StorefrontExperience::ensureReviewsSchema();
        $customer = \ShopCMS\Core\Auth::customer();
        $customerId = (int)$customer['id'];
        $customerEmail = (string)$customer['email'];
        $productId = (int)($_POST['product_id'] ?? 0);
        $requestedOrderId = (int)($_POST['order_id'] ?? 0);
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $comment = trim((string)($_POST['comment'] ?? ''));
        $returnTo = trim((string)($_POST['return_to'] ?? ''));

        if ($productId <= 0 || $comment === '') {
            $_SESSION['flash_error'] = "Por favor ingresa tu calificación y descripción de experiencia.";
            header('Location: ' . $this->resolveReviewRedirect($productId, $returnTo));
            exit;
        }

        $ctx = StorefrontExperience::getCustomerProductReviewContext($customerId, $customerEmail, $productId);
        $targetOrder = null;
        if ($requestedOrderId > 0) {
            foreach ($ctx['reviewable_orders'] as $ro) {
                if ((int)$ro['id'] === $requestedOrderId) {
                    $targetOrder = $ro;
                    break;
                }
            }
            if (!$targetOrder) {
                $existingOrder = Database::fetch("
                    SELECT o.id, o.status
                    FROM " . Database::table('orders') . " o
                    INNER JOIN " . Database::table('order_items') . " oi ON oi.order_id = o.id
                    WHERE o.id = ? AND oi.product_id = ?
                      AND (o.customer_id = ? OR LOWER(o.customer_email) = LOWER(?))
                    LIMIT 1
                ", [$requestedOrderId, $productId, $customerId, $customerEmail]);
                if (!$existingOrder) {
                    $_SESSION['flash_error'] = "Solo puedes calificar productos incluidos en tus pedidos entregados.";
                } elseif (!in_array((string)$existingOrder['status'], ['delivered', 'completed'], true)) {
                    $_SESSION['flash_error'] = "Solo puedes publicar una reseña cuando tu pedido esté marcado como Entregado.";
                } else {
                    $_SESSION['flash_error'] = "Esta compra ya cuenta con una reseña registrada o superó el plazo permitido.";
                }
                header('Location: ' . $this->resolveReviewRedirect($productId, $returnTo));
                exit;
            }
        } else {
            if (!empty($ctx['reviewable_orders'])) {
                $targetOrder = $ctx['reviewable_orders'][0];
            } else {
                if (!empty($ctx['pending_orders_in_transit'])) {
                    $_SESSION['flash_error'] = "Tu pedido aún no ha sido entregado. Podrás calificar cuando su estado sea Entregado.";
                } elseif (!empty($ctx['expired_unreviewed_orders'])) {
                    $_SESSION['flash_error'] = "El plazo máximo de {$ctx['window_days']} días para calificar esta compra ha finalizado.";
                } elseif (!empty($ctx['customer_reviews'])) {
                    $_SESSION['flash_error'] = "Ya publicaste una reseña para tu compra entregada. Puedes editarla o añadir un comentario adicional.";
                } else {
                    $_SESSION['flash_error'] = "Solo los clientes con pedido entregado de este producto pueden calificarlo.";
                }
                header('Location: ' . $this->resolveReviewRedirect($productId, $returnTo));
                exit;
            }
        }

        $autoApprove = Settings::getBool('reviews_auto_approve', true);
        $reviewStatus = $autoApprove ? 'approved' : 'pending';

        Database::insert('reviews', [
            'product_id' => $productId,
            'order_id' => (int)$targetOrder['id'],
            'customer_id' => $customerId,
            'customer_name' => $customer['name'],
            'customer_email' => $customerEmail,
            'rating' => $rating,
            'title' => null,
            'comment' => $comment,
            'status' => $reviewStatus
        ]);

        $_SESSION['flash_success'] = $autoApprove
            ? "¡Muchas gracias! Tu opinión verificada ha sido publicada correctamente."
            : "¡Muchas gracias! Tu opinión verificada fue recibida y está pendiente de moderación.";

        header('Location: ' . $this->resolveReviewRedirect($productId, $returnTo));
        exit;
    }

    public function updateVerifiedReview(string $id): void {
        if (!\ShopCMS\Core\Auth::isCustomer()) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/login');
            exit;
        }
        $customer = \ShopCMS\Core\Auth::customer();
        $reviewId = (int)$id;
        $returnTo = trim((string)($_POST['return_to'] ?? ''));

        $check = $this->verifyEditableCustomerReview($reviewId, $customer);
        if (!$check['ok']) {
            $_SESSION['flash_error'] = $check['error'];
            $pid = (int)($check['review']['product_id'] ?? 0);
            header('Location: ' . $this->resolveReviewRedirect($pid, $returnTo));
            exit;
        }

        $review = $check['review'];
        $pid = (int)$review['product_id'];
        $rating = max(1, min(5, (int)($_POST['rating'] ?? $review['rating'])));
        $comment = trim((string)($_POST['comment'] ?? ''));

        if ($comment === '') {
            $_SESSION['flash_error'] = "El comentario de la reseña no puede quedar vacío.";
            header('Location: ' . $this->resolveReviewRedirect($pid, $returnTo));
            exit;
        }

        $autoApprove = Settings::getBool('reviews_auto_approve', true);
        Database::update('reviews', [
            'rating' => $rating,
            'title' => null,
            'comment' => $comment,
            'status' => $autoApprove ? 'approved' : 'pending',
            'updated_at' => date('Y-m-d H:i:s'),
        ], "id = :id", ['id' => $reviewId]);

        $_SESSION['flash_success'] = "Tu reseña ha sido actualizada correctamente.";
        header('Location: ' . $this->resolveReviewRedirect($pid, $returnTo));
        exit;
    }

    public function addReviewFollowup(string $id): void {
        if (!\ShopCMS\Core\Auth::isCustomer()) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/login');
            exit;
        }
        $customer = \ShopCMS\Core\Auth::customer();
        $reviewId = (int)$id;
        $returnTo = trim((string)($_POST['return_to'] ?? ''));

        $check = $this->verifyEditableCustomerReview($reviewId, $customer);
        if (!$check['ok']) {
            $_SESSION['flash_error'] = $check['error'];
            $pid = (int)($check['review']['product_id'] ?? 0);
            header('Location: ' . $this->resolveReviewRedirect($pid, $returnTo));
            exit;
        }

        $review = $check['review'];
        $pid = (int)$review['product_id'];
        if (trim((string)($review['followup_comment'] ?? '')) !== '') {
            $_SESSION['flash_error'] = "Ya añadiste un comentario adicional para esta reseña.";
            header('Location: ' . $this->resolveReviewRedirect($pid, $returnTo));
            exit;
        }

        $followup = trim((string)($_POST['followup_comment'] ?? ''));
        if ($followup === '') {
            $_SESSION['flash_error'] = "Escribe tu comentario adicional antes de enviarlo.";
            header('Location: ' . $this->resolveReviewRedirect($pid, $returnTo));
            exit;
        }

        Database::update('reviews', [
            'followup_comment' => $followup,
            'followup_at' => date('Y-m-d H:i:s'),
        ], "id = :id", ['id' => $reviewId]);

        $_SESSION['flash_success'] = "Tu comentario adicional fue publicado sin alterar tu reseña original.";
        header('Location: ' . $this->resolveReviewRedirect($pid, $returnTo));
        exit;
    }

    public function submitReview(): void {
        // Redirige al nuevo flujo verificado
        $this->submitVerifiedReview();
    }

    // ==========================================
    // ENDPOINTS DE PLUGINS EN STOREFRONT
    // ==========================================
    private function fetchActiveProductsSummary(array $ids): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($n) => $n > 0)));
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::fetchAll(
            "SELECT p.id, p.name, p.slug, p.price, p.featured_image, p.quantity, p.sku, p.short_description, c.name as category_name
             FROM " . Database::table('products') . " p
             LEFT JOIN " . Database::table('categories') . " c ON p.category_id = c.id
             WHERE p.is_active = 1 AND p.id IN ({$placeholders})",
            $ids
        );
        $byId = [];
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        $sym = Settings::get('store_currency_symbol', '$');
        foreach ($rows as $r) {
            $byId[(int)$r['id']] = [
                'id' => (int)$r['id'],
                'name' => $r['name'],
                'slug' => $r['slug'],
                'price' => (float)$r['price'],
                'currency' => $sym,
                'category' => $r['category_name'] ?? 'General',
                'sku' => $r['sku'] ?: 'N/A',
                'stock' => (int)$r['quantity'],
                'quantity' => (int)$r['quantity'],
                'short_desc' => $r['short_description'] ?? '',
                'image' => !empty($r['featured_image']) ? ($baseUrl . '/' . ltrim($r['featured_image'], '/')) : '',
                'url' => $baseUrl . '/product/' . $r['slug'],
            ];
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }
        return $ordered;
    }

    public function pluginWishlistProducts(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (!\ShopCMS\Core\Auth::isCustomer()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'require_login' => true, 'error' => 'Debes iniciar sesión para acceder a la lista de deseos.']);
            exit;
        }
        $rawIds = trim((string)($_GET['ids'] ?? ''));
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $rawIds)), fn($n) => $n > 0)));
        echo json_encode(['ok' => true, 'products' => $this->fetchActiveProductsSummary($ids)]);
        exit;
    }

    public function pluginWishlistSync(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (!\ShopCMS\Core\Auth::isCustomer()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'require_login' => true, 'error' => 'Inicia sesión para guardar favoritos.']);
            exit;
        }
        $uid = (int)\ShopCMS\Core\Auth::customer()['id'];
        $body = json_decode(file_get_contents('php://input') ?: '{}', true);
        if (!is_array($body)) $body = $_POST;
        $action = (string)($body['action'] ?? 'toggle');
        $prodId = (int)($body['product_id'] ?? 0);

        $raw = Settings::getRaw('customer_wishlist_' . $uid, '[]');
        $ids = json_decode($raw, true);
        $ids = is_array($ids) ? array_values(array_unique(array_filter(array_map('intval', $ids), fn($n) => $n > 0))) : [];

        if ($action === 'clear') {
            $ids = [];
        } elseif ($action === 'add' && $prodId > 0) {
            if (!in_array($prodId, $ids, true)) {
                $ids[] = $prodId;
            }
        } elseif ($action === 'toggle' && $prodId > 0) {
            if (in_array($prodId, $ids, true)) {
                $ids = array_values(array_filter($ids, fn($id) => $id !== $prodId));
            } else {
                $ids[] = $prodId;
            }
        }

        $products = $this->fetchActiveProductsSummary($ids);
        $cleanIds = array_column($products, 'id');
        Settings::saveMany(['customer_wishlist_' . $uid => json_encode($cleanIds)]);
        $productHearts = $prodId > 0 ? StorefrontExperience::getProductHeartCount($prodId) : null;
        echo json_encode(['ok' => true, 'ids' => $cleanIds, 'products' => $products, 'product_hearts' => $productHearts]);
        exit;
    }

    public function pluginComparatorSync(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (!\ShopCMS\Core\Auth::isCustomer()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'require_login' => true, 'error' => 'Inicia sesión para comparar productos.']);
            exit;
        }
        $uid = (int)\ShopCMS\Core\Auth::customer()['id'];
        $body = json_decode(file_get_contents('php://input') ?: '{}', true);
        if (!is_array($body)) $body = $_POST;
        $action = (string)($body['action'] ?? 'add');
        $prodId = (int)($body['product_id'] ?? 0);

        $raw = Settings::getRaw('customer_comparator_' . $uid, '[]');
        $ids = json_decode($raw, true);
        $ids = is_array($ids) ? array_values(array_unique(array_filter(array_map('intval', $ids), fn($n) => $n > 0))) : [];

        $notice = null;
        if ($action === 'clear') {
            $ids = [];
        } elseif ($action === 'remove' && $prodId > 0) {
            $ids = array_values(array_filter($ids, fn($id) => $id !== $prodId));
        } elseif ($action === 'add' && $prodId > 0) {
            if (in_array($prodId, $ids, true)) {
                $notice = 'Este producto ya está en tu comparador.';
            } elseif (count($ids) >= 4) {
                $notice = 'Máximo 4 productos en comparación simultánea.';
            } else {
                $ids[] = $prodId;
            }
        }

        $items = $this->fetchActiveProductsSummary(array_slice($ids, 0, 4));
        $cleanIds = array_column($items, 'id');
        Settings::saveMany(['customer_comparator_' . $uid => json_encode($cleanIds)]);
        echo json_encode(['ok' => true, 'ids' => $cleanIds, 'items' => $items, 'notice' => $notice]);
        exit;
    }

    public function pluginStockNotifierSubscribe(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (!\ShopCMS\Core\Auth::isCustomer()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'require_login' => true, 'error' => 'Inicia sesión con tu cuenta de cliente para activar avisos de stock.']);
            exit;
        }
        $customer = \ShopCMS\Core\Auth::customer();
        $raw = file_get_contents('php://input');
        $body = json_decode($raw ?: '{}', true);
        if (!is_array($body) || empty($body)) {
            $body = $_POST;
        }
        $productId = (int)($body['product_id'] ?? 0);
        $email = trim((string)($body['email'] ?? $customer['email']));

        if ($productId <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Ingresa un correo electrónico válido.']);
            exit;
        }

        $product = Database::fetch("SELECT id, name, slug FROM " . Database::table('products') . " WHERE id = ? AND is_active = 1", [$productId]);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Producto no encontrado.']);
            exit;
        }

        PluginManager::doAction('stock_notifier_subscribe', $product, $email);
        echo json_encode([
            'ok' => true,
            'message' => "Registrado correctamente. Te enviaremos un email a {$email} apenas repongamos existencias de {$product['name']}."
        ]);
        exit;
    }

    // ==========================================
    // PÁGINAS CMS / LEGALES Y NEWSLETTER EN BD
    // ==========================================
    public function page(string $slug): void {
        \ShopCMS\Core\ContentManager::ensureSchema();
        $cleanSlug = trim($slug);
        $page = Database::fetch(
            "SELECT * FROM " . Database::table('pages') . " WHERE slug = ? AND (is_published = 1 OR ? = 1)",
            [$cleanSlug, \ShopCMS\Core\Auth::isAdmin() ? 1 : 0]
        );

        if (!$page) {
            http_response_code(404);
            ThemeManager::render('page', [
                'page' => null,
                'pageTitle' => 'Página no encontrada',
                'currency' => $this->getCurrency(),
            ]);
            return;
        }

        ThemeManager::render('page', [
            'page' => $page,
            'pageTitle' => $page['title'],
            'currency' => $this->getCurrency(),
        ]);
    }

    public function newsletterSubscribe(): void {
        header('Content-Type: application/json; charset=utf-8');
        $raw = file_get_contents('php://input');
        $body = json_decode($raw ?: '{}', true);
        if (!is_array($body) || empty($body)) {
            $body = $_POST;
        }
        $email = trim((string)($body['email'] ?? ''));
        $source = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($body['source'] ?? 'footer')) ?: 'footer';
        $customerId = \ShopCMS\Core\Auth::isCustomer() ? (int)\ShopCMS\Core\Auth::customer()['id'] : null;

        $res = \ShopCMS\Core\ContentManager::subscribeEmail($email, $customerId, $source);
        if (!$res['ok']) {
            http_response_code(400);
        }
        echo json_encode([
            'ok' => $res['ok'],
            'duplicate' => $res['duplicate'],
            'message' => $res['message'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==========================================
    // BUSCADOR EN VIVO (API) Y SEO (SITEMAP / ROBOTS)
    // ==========================================
    public function apiSearch(): void {
        header('Content-Type: application/json; charset=utf-8');
        $q = trim((string)($_GET['q'] ?? ''));
        $curr = $this->getCurrency();
        $baseUrl = defined('APP_URL') ? rtrim((string)APP_URL, '/') : '';

        if (strlen($q) < 2) {
            echo json_encode([
                'ok' => true,
                'query' => $q,
                'total' => 0,
                'currency' => $curr,
                'items' => [],
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $res = CatalogHelper::queryCatalog(['q' => $q, 'per_page' => 6, 'page' => 1], 6);
        $items = [];
        foreach ($res['products'] as $p) {
            $sku = !empty($p['sku']) ? (string)$p['sku'] : (string)($p['variant_sku_sample'] ?? '');
            $items[] = [
                'id' => (int)$p['id'],
                'name' => (string)$p['name'],
                'slug' => (string)$p['slug'],
                'sku' => $sku,
                'category_name' => (string)($p['category_name'] ?? 'Catálogo'),
                'price' => (float)$p['price'],
                'compare_price' => !empty($p['compare_price']) ? (float)$p['compare_price'] : null,
                'image_url' => !empty($p['featured_image']) ? ThemeManager::resolveImageUrl((string)$p['featured_image'], $baseUrl) : '',
                'url' => $baseUrl . '/product/' . rawurlencode((string)$p['slug']),
            ];
        }

        echo json_encode([
            'ok' => true,
            'query' => $q,
            'total' => (int)$res['totalProducts'],
            'currency' => $curr,
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function sitemapXml(): void {
        header('Content-Type: application/xml; charset=utf-8');
        echo \ShopCMS\Core\SeoHelper::buildSitemapXml();
        exit;
    }

    public function forgotPasswordForm(): void {
        if (\ShopCMS\Core\Auth::isCustomer()) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/account');
            exit;
        }
        StorefrontExperience::ensurePasswordResetsTable();
        $notice = $_SESSION['forgot_notice'] ?? null;
        $error = $_SESSION['forgot_error'] ?? null;
        $devResetLink = $_SESSION['forgot_dev_link'] ?? null;
        unset($_SESSION['forgot_notice'], $_SESSION['forgot_error'], $_SESSION['forgot_dev_link']);

        ThemeManager::render('forgot_password', [
            'notice' => $notice,
            'error' => $error,
            'devResetLink' => $devResetLink,
            'currency' => $this->getCurrency(),
        ]);
    }

    public function forgotPasswordSubmit(): void {
        StorefrontExperience::ensurePasswordResetsTable();
        $baseUrl = defined('APP_URL') ? rtrim((string)APP_URL, '/') : '';
        $email = strtolower(trim((string)($_POST['email'] ?? '')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['forgot_error'] = 'Ingresa un correo electrónico válido.';
            header('Location: ' . $baseUrl . '/forgot-password');
            exit;
        }

        $user = Database::fetch(
            "SELECT id, name, email, role, is_active FROM " . Database::table('users') . " WHERE LOWER(email) = ? LIMIT 1",
            [$email]
        );

        \ShopCMS\Core\Security::auditLog('password_reset_requested', ['email_hash' => substr(sha1($email), 0, 12)]);

        if ($user && (!isset($user['is_active']) || (int)$user['is_active'] === 1) && (($user['role'] ?? 'customer') === 'customer')) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + 1800);

            $resetTable = Database::table('password_resets');
            Database::query("DELETE FROM {$resetTable} WHERE LOWER(email) = ?", [$email]);
            Database::insert('password_resets', [
                'email' => $email,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
            ]);

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $resetPath = $baseUrl . '/reset-password?token=' . urlencode($token);
            $fullUrl = str_starts_with($resetPath, 'http') ? $resetPath : ($scheme . '://' . $host . $resetPath);

            $storeTitle = Settings::get('store_name', 'ShopCMS');
            @mail(
                $user['email'],
                "Restablecer contraseña - {$storeTitle}",
                "Hola {$user['name']},\n\nSolicitaste restablecer tu contraseña. Usa el siguiente enlace (válido por 30 minutos):\n{$fullUrl}\n\nSi no solicitaste este cambio, ignora este mensaje."
            );

            $isLocalHost = in_array(strtolower((string)parse_url('http://' . $host, PHP_URL_HOST)), ['localhost', '127.0.0.1'], true);
            $isLocalDev = (defined('APP_ENV') && APP_ENV === 'development') && $isLocalHost;
            if ($isLocalDev) {
                $_SESSION['forgot_dev_link'] = $resetPath;
            }
        }

        $_SESSION['forgot_notice'] = 'Si el correo está registrado y activo, hemos preparado un enlace seguro para restablecer tu contraseña (válido por 30 minutos).';
        header('Location: ' . $baseUrl . '/forgot-password');
        exit;
    }

    public function resetPasswordForm(): void {
        StorefrontExperience::ensurePasswordResetsTable();
        $token = trim((string)($_GET['token'] ?? ''));
        $error = $_SESSION['reset_error'] ?? null;
        unset($_SESSION['reset_error']);

        $tokenValid = false;
        if ($token !== '') {
            $tokenHash = hash('sha256', $token);
            $row = Database::fetch(
                "SELECT id FROM " . Database::table('password_resets') . " WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1",
                [$tokenHash]
            );
            $tokenValid = !empty($row);
        }

        if (!$tokenValid && $error === null) {
            $error = 'El enlace de recuperación es inválido o ya expiró.';
        }

        ThemeManager::render('reset_password', [
            'token' => $token,
            'tokenValid' => $tokenValid,
            'error' => $error,
            'currency' => $this->getCurrency(),
        ]);
    }

    public function resetPasswordSubmit(): void {
        StorefrontExperience::ensurePasswordResetsTable();
        $baseUrl = defined('APP_URL') ? rtrim((string)APP_URL, '/') : '';
        $token = trim((string)($_POST['token'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');

        if ($token === '') {
            $_SESSION['reset_error'] = 'Token de recuperación inválido.';
            header('Location: ' . $baseUrl . '/reset-password');
            exit;
        }

        $tokenHash = hash('sha256', $token);
        $resetRow = Database::fetch(
            "SELECT * FROM " . Database::table('password_resets') . " WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1",
            [$tokenHash]
        );
        if (!$resetRow) {
            $_SESSION['reset_error'] = 'El enlace de recuperación es inválido o ha expirado.';
            header('Location: ' . $baseUrl . '/reset-password?token=' . urlencode($token));
            exit;
        }

        $strengthErr = $this->validatePasswordStrength($password);
        if ($strengthErr !== null) {
            $_SESSION['reset_error'] = $strengthErr;
            header('Location: ' . $baseUrl . '/reset-password?token=' . urlencode($token));
            exit;
        }
        if ($password !== $confirm) {
            $_SESSION['reset_error'] = 'Las contraseñas no coinciden. Verifica ambos campos.';
            header('Location: ' . $baseUrl . '/reset-password?token=' . urlencode($token));
            exit;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        Database::query(
            "UPDATE " . Database::table('users') . " SET password = ? WHERE LOWER(email) = ?",
            [$hashed, strtolower((string)$resetRow['email'])]
        );
        Database::query(
            "UPDATE " . Database::table('password_resets') . " SET used_at = NOW() WHERE id = ?",
            [(int)$resetRow['id']]
        );
        \ShopCMS\Core\Security::clearLoginAttempts('customer', (string)$resetRow['email']);

        $_SESSION['checkout_notice'] = 'Tu contraseña se actualizó correctamente. Ya puedes iniciar sesión.';
        header('Location: ' . $baseUrl . '/login');
        exit;
    }

    public function robotsTxt(): void {
        header('Content-Type: text/plain; charset=utf-8');
        echo \ShopCMS\Core\SeoHelper::buildRobotsTxt();
        exit;
    }
}

