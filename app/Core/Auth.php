<?php
namespace ShopCMS\Core;

class Auth {
    public static function init(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @ini_set('session.use_strict_mode', '1');
            @ini_set('session.use_only_cookies', '1');
            @ini_set('session.cookie_httponly', '1');
            $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    // ==========================================
    // AUTENTICACIÓN ADMINISTRATIVA (ADMIN / STAFF)
    // ==========================================
    public static function attempt(string $email, string $password): bool {
        self::init();
        $user = Database::fetch("SELECT * FROM " . Database::table('users') . " WHERE email = ? AND role IN ('admin', 'staff')", [$email]);

        if ($user && password_verify($password, $user['password'])) {
            @session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            Security::auditLog('admin_login_success', ['user_id' => (int)$user['id'], 'role' => (string)$user['role']]);
            return true;
        }

        Security::auditLog('admin_login_failed', ['email_hash' => substr(sha1(strtolower(trim($email))), 0, 12)]);
        return false;
    }

    public static function check(): bool {
        self::init();
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin(): bool {
        self::init();
        return self::check() && in_array($_SESSION['user_role'] ?? '', ['admin', 'staff']);
    }

    public static function user(): ?array {
        self::init();
        if (!self::check()) return null;
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role']
        ];
    }

    public static function logout(): void {
        self::init();
        $uid = (int)($_SESSION['user_id'] ?? 0);
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_role']);
        if ($uid > 0) {
            Security::auditLog('admin_logout', ['user_id' => $uid]);
        }
        if (empty($_SESSION['customer_id'])) {
            $_SESSION = [];
            @session_regenerate_id(true);
        } else {
            @session_regenerate_id(true);
        }
    }

    // ==========================================
    // AUTENTICACIÓN DE CLIENTES (STOREFRONT)
    // ==========================================
    private static ?bool $customerActiveCache = null;

    public static function isCustomer(): bool {
        self::init();
        if (!isset($_SESSION['customer_id'])) {
            return false;
        }
        if (self::$customerActiveCache === null) {
            try {
                $row = Database::fetch("SELECT is_active FROM " . Database::table('users') . " WHERE id = ?", [(int)$_SESSION['customer_id']]);
                if ($row && isset($row['is_active']) && (int)$row['is_active'] === 0) {
                    self::customerLogout();
                    $_SESSION['auth_error'] = "Tu cuenta ha sido deshabilitada por el administrador de la tienda.";
                    self::$customerActiveCache = false;
                    return false;
                }
                self::$customerActiveCache = true;
            } catch (\Throwable $e) {
                self::$customerActiveCache = true;
            }
        }
        return self::$customerActiveCache && isset($_SESSION['customer_id']);
    }

    public static function customer(): ?array {
        self::init();
        if (!self::isCustomer()) return null;
        return [
            'id' => $_SESSION['customer_id'],
            'name' => $_SESSION['customer_name'],
            'email' => $_SESSION['customer_email'],
            'phone' => $_SESSION['customer_phone'] ?? '',
            'address' => $_SESSION['customer_address'] ?? ''
        ];
    }

    public static function customerLogin(string $email, string $password): bool {
        self::init();
        $user = Database::fetch("SELECT * FROM " . Database::table('users') . " WHERE email = ?", [$email]);

        if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
            if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
                $_SESSION['auth_error'] = "Esta cuenta se encuentra deshabilitada. Contacta con la tienda para más información.";
                return false;
            }
            @session_regenerate_id(true);
            unset($_SESSION['cart'], $_SESSION['cart_discount'], $_SESSION['cart_coupon'], $_SESSION['cart_owner_id'], $_SESSION['cart_error']);
            $_SESSION['customer_id'] = $user['id'];
            $_SESSION['customer_name'] = $user['name'];
            $_SESSION['customer_email'] = $user['email'];
            $_SESSION['customer_phone'] = $user['phone'] ?? '';
            $_SESSION['customer_address'] = $user['address'] ?? '';
            self::$customerActiveCache = true;
            self::loadCustomerCart((int)$user['id']);
            Security::auditLog('customer_login_success', ['customer_id' => (int)$user['id']]);
            return true;
        }

        Security::auditLog('customer_login_failed', ['email_hash' => substr(sha1(strtolower(trim($email))), 0, 12)]);
        return false;
    }

    public static function customerRegister(string $name, string $email, string $password, ?string $phone = null, ?string $address = null): int {
        self::init();
        $existing = Database::fetch("SELECT id, password, is_active FROM " . Database::table('users') . " WHERE email = ?", [$email]);
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        if ($existing) {
            if (isset($existing['is_active']) && (int)$existing['is_active'] === 0) {
                throw new \Exception("Esta cuenta se encuentra deshabilitada. Contacta al administrador de la tienda.");
            }
            if (empty($existing['password'])) {
                // Cliente sincronizado desde pedido previo o creado por admin sin contraseña web: reclamar cuenta
                Database::update('users', [
                    'name' => $name,
                    'password' => $hashed,
                    'phone' => $phone,
                    'address' => $address
                ], "id = :id", ['id' => (int)$existing['id']]);
                $userId = (int)$existing['id'];
            } else {
                throw new \Exception("Ya existe una cuenta registrada con el correo electrónico '{$email}'. Por favor, inicia sesión.");
            }
        } else {
            $userId = Database::insert('users', [
                'name' => $name,
                'email' => $email,
                'password' => $hashed,
                'role' => 'customer',
                'is_active' => 1,
                'phone' => $phone,
                'address' => $address
            ]);
        }

        @session_regenerate_id(true);
        unset($_SESSION['cart'], $_SESSION['cart_discount'], $_SESSION['cart_coupon'], $_SESSION['cart_owner_id'], $_SESSION['cart_error']);
        $_SESSION['customer_id'] = $userId;
        $_SESSION['customer_name'] = $name;
        $_SESSION['customer_email'] = $email;
        $_SESSION['customer_phone'] = $phone ?? '';
        $_SESSION['customer_address'] = $address ?? '';
        self::$customerActiveCache = true;
        self::loadCustomerCart((int)$userId);
        Security::auditLog('customer_registered', ['customer_id' => $userId]);

        return $userId;
    }

    public static function customerId(): int {
        return self::isCustomer() ? (int)$_SESSION['customer_id'] : 0;
    }

    public static function loadCustomerCart(int $customerId): void {
        self::init();
        if ($customerId <= 0) {
            unset($_SESSION['cart'], $_SESSION['cart_discount'], $_SESSION['cart_coupon'], $_SESSION['cart_owner_id'], $_SESSION['cart_error']);
            return;
        }
        $cart = [];
        try {
            $raw = Settings::getRaw('customer_cart_' . $customerId, '[]');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $cart = $decoded;
            }
        } catch (\Throwable $e) {
            $cart = [];
        }
        $_SESSION['cart'] = $cart;
        $_SESSION['cart_owner_id'] = $customerId;
        unset($_SESSION['cart_discount'], $_SESSION['cart_coupon'], $_SESSION['cart_error']);
    }

    public static function saveCustomerCart(): void {
        self::init();
        if (!self::isCustomer()) {
            return;
        }
        $uid = (int)$_SESSION['customer_id'];
        $_SESSION['cart_owner_id'] = $uid;
        try {
            $cart = is_array($_SESSION['cart'] ?? null) ? $_SESSION['cart'] : [];
            Settings::saveMany([
                'customer_cart_' . $uid => json_encode($cart, JSON_UNESCAPED_UNICODE)
            ]);
        } catch (\Throwable $e) {
            // Ignorar si BD no está disponible en pruebas aisladas
        }
    }

    public static function ensureIsolatedCartSession(): void {
        self::init();
        if (!self::isCustomer()) {
            unset($_SESSION['cart'], $_SESSION['cart_discount'], $_SESSION['cart_coupon'], $_SESSION['cart_owner_id'], $_SESSION['cart_error']);
            return;
        }
        $uid = (int)$_SESSION['customer_id'];
        if ((int)($_SESSION['cart_owner_id'] ?? 0) !== $uid) {
            self::loadCustomerCart($uid);
        }
    }

    public static function customerLogout(): void {
        self::init();
        $cid = (int)($_SESSION['customer_id'] ?? 0);
        self::saveCustomerCart();
        self::$customerActiveCache = null;
        unset(
            $_SESSION['customer_id'],
            $_SESSION['customer_name'],
            $_SESSION['customer_email'],
            $_SESSION['customer_phone'],
            $_SESSION['customer_address'],
            $_SESSION['cart'],
            $_SESSION['cart_discount'],
            $_SESSION['cart_coupon'],
            $_SESSION['cart_owner_id'],
            $_SESSION['cart_error'],
            $_SESSION['checkout_notice']
        );
        if ($cid > 0) {
            Security::auditLog('customer_logout', ['customer_id' => $cid]);
        }
        @session_regenerate_id(true);
    }

    /**
     * Verifica si un cliente tiene al menos una compra efectivamente entregada del producto.
     */
    public static function hasPurchasedProduct(int $customerId, int $productId): bool {
        if ($customerId <= 0 || $productId <= 0) {
            return false;
        }
        $count = Database::fetch("
            SELECT COUNT(*) as c
            FROM " . Database::table('order_items') . " oi
            INNER JOIN " . Database::table('orders') . " o ON oi.order_id = o.id
            WHERE o.customer_id = ? AND oi.product_id = ? AND o.status IN ('delivered', 'completed')
        ", [$customerId, $productId]);

        return ($count && (int)$count['c'] > 0);
    }
}
