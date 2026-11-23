<?php
namespace ShopCMS\Controllers;

use ShopCMS\Core\Database;
use ShopCMS\Core\Auth;
use ShopCMS\Core\PluginManager;
use ShopCMS\Core\ThemeManager;
use ShopCMS\Core\OrderWorkflow;
use ShopCMS\Core\InventoryManager;
use ShopCMS\Core\Settings;
use ShopCMS\Core\ThemeCustomizer;
use ShopCMS\Core\Security;
use Exception;

class AdminController {
    public function __construct() {
        // Verificar autenticación salvo en rutas de login
        $route = $_GET['route'] ?? '';
        if ($route !== 'admin/login' && !Auth::isAdmin()) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/login');
            exit;
        }
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        $user = Auth::user();
        $storeName = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_name'")['setting_value'] ?? 'ShopCMS';
        $currentRoute = $_GET['route'] ?? 'admin';
        
        require dirname(__DIR__) . '/Views/admin/layout.php';
    }

    // ==========================================
    // LOGIN & LOGOUT
    // ==========================================
    public function loginForm(): void {
        if (Auth::isAdmin()) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin');
            exit;
        }
        $error = $_SESSION['flash_error'] ?? null;
        $lockoutSeconds = (int)($_SESSION['admin_lockout_seconds'] ?? 0);
        unset($_SESSION['flash_error'], $_SESSION['admin_lockout_seconds']);

        if ($lockoutSeconds <= 0) {
            $lastEmail = (string)($_SESSION['admin_last_attempt_email'] ?? '');
            $lock = Security::checkLoginLockout('admin', $lastEmail);
            if ($lock['locked']) {
                $lockoutSeconds = $lock['remaining_seconds'];
                $error = 'Demasiados intentos fallidos. Acceso bloqueado temporalmente por seguridad. Intenta nuevamente en ' . Security::formatWaitTime($lockoutSeconds) . '.';
            }
        }
        require dirname(__DIR__) . '/Views/admin/login.php';
    }

    public function loginSubmit(): void {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $_SESSION['admin_last_attempt_email'] = $email;

        $lock = Security::checkLoginLockout('admin', $email);
        if ($lock['locked']) {
            http_response_code(429);
            $_SESSION['admin_lockout_seconds'] = $lock['remaining_seconds'];
            $_SESSION['flash_error'] = 'Demasiados intentos fallidos. Acceso bloqueado temporalmente. Espera ' . Security::formatWaitTime($lock['remaining_seconds']) . ' antes de volver a intentarlo.';
            session_write_close();
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/login');
            exit;
        }

        if (Auth::attempt($email, $password)) {
            Security::clearLoginAttempts('admin', $email);
            unset($_SESSION['admin_last_attempt_email'], $_SESSION['admin_lockout_seconds']);
            @session_regenerate_id(true);
            session_write_close();
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin');
            exit;
        }

        $res = Security::recordFailedLogin('admin', $email);
        if ($res['locked']) {
            http_response_code(429);
            $_SESSION['admin_lockout_seconds'] = $res['remaining_seconds'];
            $_SESSION['flash_error'] = 'Alcanzaste el máximo de ' . Security::MAX_ACCOUNT_ATTEMPTS . ' intentos fallidos. Inicio de sesión bloqueado durante ' . Security::formatWaitTime($res['remaining_seconds']) . '.';
        } else {
            $rem = $res['remaining_attempts'];
            $_SESSION['flash_error'] = 'Credenciales incorrectas. Te ' . ($rem === 1 ? 'queda 1 intento' : "quedan {$rem} intentos") . ' antes del bloqueo temporal.';
        }
        session_write_close();
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/login');
        exit;
    }

    public function logout(): void {
        Auth::logout();
        session_write_close();
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/login');
        exit;
    }

    // ==========================================
    // DASHBOARD
    // ==========================================
    public function dashboard(): void {
        $period = \ShopCMS\Core\DashboardAnalytics::normalizePeriod($_GET['period'] ?? '30d');
        $analytics = \ShopCMS\Core\DashboardAnalytics::getSummaryAndCharts($period);

        $totalProducts = Database::fetch("SELECT COUNT(*) as count_products FROM " . Database::table('products') . " WHERE is_active = 1")['count_products'] ?? 0;
        $lowStockNotify = Settings::getBool('inventory_low_stock_notification', true);
        if ($lowStockNotify) {
            $lowStockCount = Database::fetch("
                SELECT COUNT(*) as count_low FROM " . Database::table('products') . " p
                WHERE p.is_active = 1 AND p.track_quantity = 1 AND (
                    p.quantity <= p.low_stock_threshold
                    OR (p.has_variants = 1 AND EXISTS(SELECT 1 FROM " . Database::table('product_variants') . " v WHERE v.product_id = p.id AND v.quantity <= p.low_stock_threshold))
                )
            ")['count_low'] ?? 0;

            $recentOrders = Database::fetchAll("SELECT * FROM " . Database::table('orders') . " ORDER BY id DESC LIMIT 5");
            $lowStockItems = Database::fetchAll("
                SELECT p.* FROM " . Database::table('products') . " p
                WHERE p.is_active = 1 AND p.track_quantity = 1 AND (
                    p.quantity <= p.low_stock_threshold
                    OR (p.has_variants = 1 AND EXISTS(SELECT 1 FROM " . Database::table('product_variants') . " v WHERE v.product_id = p.id AND v.quantity <= p.low_stock_threshold))
                )
                ORDER BY p.quantity ASC LIMIT 6
            ");
        } else {
            $lowStockCount = 0;
            $lowStockItems = [];
            $recentOrders = Database::fetchAll("SELECT * FROM " . Database::table('orders') . " ORDER BY id DESC LIMIT 5");
        }

        $currency = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_currency_symbol'")['setting_value'] ?? '$';
        $summary = $analytics['summary'];

        $this->render('dashboard', [
            'totalSales'      => number_format((float)$summary['gross_sales'], 2),
            'paidSales'       => number_format((float)$summary['paid_sales'], 2),
            'pendingSales'    => number_format((float)$summary['pending_sales'], 2),
            'avgTicket'       => number_format((float)$summary['avg_ticket'], 2),
            'totalOrders'     => (int)$summary['total_orders'],
            'activeOrders'    => (int)$summary['active_orders'],
            'cancelledOrders' => (int)$summary['cancelled_orders'],
            'totalProducts'   => $totalProducts,
            'lowStockCount'   => $lowStockCount,
            'recentOrders'    => $recentOrders,
            'lowStockItems'   => $lowStockItems,
            'currency'        => $currency,
            'analytics'       => $analytics,
        ]);
    }

    public function salesReportExport(): void {
        $period = \ShopCMS\Core\DashboardAnalytics::normalizePeriod($_GET['period'] ?? '30d');
        $orders = \ShopCMS\Core\DashboardAnalytics::getExportOrders($period);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte-ventas-' . $period . '-' . date('Ymd-His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Nro Pedido', 'Fecha', 'Cliente', 'Correo', 'Metodo Pago', 'Estado Pago', 'Estado Logistico', 'Subtotal', 'Descuento', 'Envio', 'Impuesto', 'Total']);
        foreach ($orders as $ord) {
            $stMeta = OrderWorkflow::getStatusMeta((string)$ord['status']);
            $payMeta = OrderWorkflow::getPaymentMeta((string)$ord['payment_status']);
            fputcsv($out, [
                $ord['order_number'],
                $ord['created_at'],
                $ord['customer_name'],
                $ord['customer_email'],
                OrderWorkflow::paymentMethodLabel((string)$ord['payment_method']),
                $payMeta['label'],
                $stMeta['short'],
                number_format((float)$ord['subtotal'], 2, '.', ''),
                number_format((float)$ord['discount_amount'], 2, '.', ''),
                number_format((float)($ord['shipping_amount'] ?? 0), 2, '.', ''),
                number_format((float)($ord['tax_amount'] ?? 0), 2, '.', ''),
                number_format((float)$ord['total'], 2, '.', ''),
            ]);
        }
        fclose($out);
        exit;
    }

    // ==========================================
    // MÓDULO 1: PRODUCTOS Y VARIANTES
    // ==========================================
    public function products(): void {
        $search = trim($_GET['q'] ?? '');
        $statusFilter = $_GET['status'] ?? '';

        $sql = "SELECT p.*, c.name as category_name, 
                       (SELECT COUNT(*) FROM " . Database::table('product_variants') . " v WHERE v.product_id = p.id) as variants_count 
                FROM " . Database::table('products') . " p 
                LEFT JOIN " . Database::table('categories') . " c ON p.category_id = c.id
                WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($statusFilter === 'active') {
            $sql .= " AND p.is_active = 1";
        } elseif ($statusFilter === 'disabled') {
            $sql .= " AND p.is_active = 0";
        }

        $sql .= " ORDER BY p.id DESC";

        $products = Database::fetchAll($sql, $params);
        $currency = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_currency_symbol'")['setting_value'] ?? '$';

        $totalActive = Database::fetch("SELECT COUNT(*) as count FROM " . Database::table('products') . " WHERE is_active = 1")['count'] ?? 0;
        $totalDisabled = Database::fetch("SELECT COUNT(*) as count FROM " . Database::table('products') . " WHERE is_active = 0")['count'] ?? 0;

        $this->render('products/index', [
            'products' => $products,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'totalActive' => (int)$totalActive,
            'totalDisabled' => (int)$totalDisabled,
            'currency' => $currency
        ]);
    }

    private const MAX_IMAGE_BYTES = 2097152; // 2 MB
    private const MAX_GALLERY_IMAGES = 6;

    private function uploadProductImageFile(string $tmpName, string $originalName, string $prefix = 'prod_', ?array &$errors = null, int $fileSize = 0): ?string {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExt = ['webp', 'jpg', 'jpeg', 'png'];
        if ($ext === '' || !in_array($ext, $allowedExt, true)) {
            if ($errors !== null) {
                $errors[] = "Formato no permitido en '" . basename($originalName) . "'. Usa WebP (recomendado), JPG o PNG.";
            }
            return null;
        }

        $actualSize = $fileSize > 0 ? $fileSize : (is_file($tmpName) ? (int)filesize($tmpName) : 0);
        if ($actualSize <= 0 || $actualSize > self::MAX_IMAGE_BYTES) {
            if ($errors !== null) {
                $errors[] = "La imagen '" . basename($originalName) . "' supera el límite máximo de 2 MB.";
            }
            return null;
        }

        // Validación real de contenido de imagen / MIME
        $allowedMimes = ['image/webp', 'image/jpeg', 'image/png'];
        $mime = '';
        if (function_exists('finfo_open') && is_file($tmpName)) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)@finfo_file($finfo, $tmpName);
                @finfo_close($finfo);
            }
        }
        if ($mime !== '' && !in_array($mime, $allowedMimes, true)) {
            if ($errors !== null) {
                $errors[] = "El archivo '" . basename($originalName) . "' no es una imagen válida WebP, JPG o PNG.";
            }
            return null;
        }
        if (function_exists('getimagesize') && is_file($tmpName)) {
            $imgInfo = @getimagesize($tmpName);
            if ($imgInfo === false) {
                if ($errors !== null) {
                    $errors[] = "No se pudo verificar '" . basename($originalName) . "' como imagen válida.";
                }
                return null;
            }
        }

        $uploadDir = dirname(__DIR__, 2) . '/uploads/products/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid($prefix) . '.' . $ext;
        if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
            return 'uploads/products/' . $fileName;
        }
        return null;
    }

    private function uploadProductGalleryFiles(string $fieldName = 'gallery_images', ?array &$errors = null, int $maxAllowed = self::MAX_GALLERY_IMAGES): array {
        $paths = [];
        if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName]['name'] ?? null)) {
            return $paths;
        }
        $names = $_FILES[$fieldName]['name'];
        $tmps = $_FILES[$fieldName]['tmp_name'] ?? [];
        $errs = $_FILES[$fieldName]['error'] ?? [];
        $sizes = $_FILES[$fieldName]['size'] ?? [];
        $count = count($names);
        for ($i = 0; $i < $count; $i++) {
            if (empty($names[$i])) continue;
            $errCode = $errs[$i] ?? UPLOAD_ERR_NO_FILE;
            if ($errCode === UPLOAD_ERR_NO_FILE) continue;
            if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
                if ($errors !== null) $errors[] = "La imagen '" . basename($names[$i]) . "' supera el límite máximo de 2 MB.";
                continue;
            }
            if ($errCode !== UPLOAD_ERR_OK || empty($tmps[$i])) {
                continue;
            }
            if (count($paths) >= $maxAllowed) {
                if ($errors !== null) {
                    $errors[] = "Solo se permiten hasta " . self::MAX_GALLERY_IMAGES . " imágenes adicionales en la galería.";
                }
                break;
            }
            $saved = $this->uploadProductImageFile($tmps[$i], $names[$i], 'prod_gal_', $errors, (int)($sizes[$i] ?? 0));
            if ($saved !== null) {
                $paths[] = $saved;
            }
        }
        return $paths;
    }

    private function saveProductGalleryItems(int $productId, array $uploadedPaths, array $urlInputs, ?array &$errors = null): void {
        if ($productId <= 0) return;
        $existing = Database::fetchAll("SELECT image_url, sort_order FROM " . Database::table('product_images') . " WHERE product_id = ?", [$productId]);
        $seen = [];
        $maxOrder = 0;
        $currentCount = count($existing);
        foreach ($existing as $row) {
            $seen[trim((string)$row['image_url'])] = true;
            $ord = (int)($row['sort_order'] ?? 0);
            if ($ord > $maxOrder) $maxOrder = $ord;
        }

        $candidates = [];
        foreach ($uploadedPaths as $p) {
            $p = trim((string)$p);
            if ($p !== '') $candidates[] = $p;
        }
        foreach ($urlInputs as $u) {
            $u = trim((string)$u);
            if ($u !== '') $candidates[] = $u;
        }

        foreach ($candidates as $path) {
            if (isset($seen[$path])) continue;
            if ($currentCount >= self::MAX_GALLERY_IMAGES) {
                if ($errors !== null) {
                    $errors[] = "Límite alcanzado: máximo " . self::MAX_GALLERY_IMAGES . " fotos adicionales en galería.";
                }
                break;
            }
            $seen[$path] = true;
            $maxOrder += 1;
            $currentCount += 1;
            Database::insert('product_images', [
                'product_id' => $productId,
                'image_url' => $path,
                'sort_order' => $maxOrder
            ]);
        }
    }

    private function resolveVariantImageAtIndex(int $idx, string $existingImg, bool $clearFlag, ?array &$errors = null): ?string {
        if (!empty($_FILES['variant_image_file']['name'][$idx])) {
            $errCode = $_FILES['variant_image_file']['error'][$idx] ?? UPLOAD_ERR_NO_FILE;
            if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
                if ($errors !== null) {
                    $errors[] = "La foto de la variante supera el límite máximo de 2 MB.";
                }
            } elseif ($errCode === UPLOAD_ERR_OK && !empty($_FILES['variant_image_file']['tmp_name'][$idx])) {
                $uploaded = $this->uploadProductImageFile(
                    $_FILES['variant_image_file']['tmp_name'][$idx],
                    $_FILES['variant_image_file']['name'][$idx],
                    'prod_var_',
                    $errors,
                    (int)($_FILES['variant_image_file']['size'][$idx] ?? 0)
                );
                if ($uploaded !== null) {
                    return $uploaded;
                }
            }
        }
        if ($clearFlag) {
            return null;
        }
        $existingTrim = trim($existingImg);
        if ($existingTrim !== '') {
            return $existingTrim;
        }
        $urlFallback = trim((string)($_POST['variant_image_url'][$idx] ?? ''));
        return $urlFallback !== '' ? $urlFallback : null;
    }

    private function deleteProductGalleryItems(int $productId, array $deleteIds): void {
        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $deleteIds), fn($n) => $n > 0)));
        if ($productId <= 0 || empty($cleanIds)) return;

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $params = array_merge([$productId], $cleanIds);
        $rows = Database::fetchAll(
            "SELECT id, image_url FROM " . Database::table('product_images') . " WHERE product_id = ? AND id IN ({$placeholders})",
            $params
        );
        if (empty($rows)) return;

        Database::query(
            "DELETE FROM " . Database::table('product_images') . " WHERE product_id = ? AND id IN ({$placeholders})",
            $params
        );

        foreach ($rows as $row) {
            $img = trim((string)($row['image_url'] ?? ''));
            if ($img !== '' && str_starts_with($img, 'uploads/products/')) {
                $usedElsewhere = Database::fetch(
                    "SELECT 1 FROM " . Database::table('products') . " WHERE featured_image = ? LIMIT 1",
                    [$img]
                ) || Database::fetch(
                    "SELECT 1 FROM " . Database::table('product_variants') . " WHERE image_url = ? LIMIT 1",
                    [$img]
                ) || Database::fetch(
                    "SELECT 1 FROM " . Database::table('product_images') . " WHERE image_url = ? LIMIT 1",
                    [$img]
                );
                if (!$usedElsewhere) {
                    $abs = dirname(__DIR__, 2) . '/' . $img;
                    if (is_file($abs)) @unlink($abs);
                }
            }
        }
    }

    public function productCreate(): void {
        $categories = Database::fetchAll("
            SELECT c.*, p.name as parent_name 
            FROM " . Database::table('categories') . " c 
            LEFT JOIN " . Database::table('categories') . " p ON c.parent_id = p.id 
            WHERE c.is_active = 1
            ORDER BY COALESCE(c.parent_id, c.id) ASC, c.parent_id ASC, c.name ASC
        ");
        $attributes = Database::fetchAll("SELECT * FROM " . Database::table('product_attributes') . " ORDER BY name ASC");
        $currency = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_currency_symbol'")['setting_value'] ?? '$';
        $industry = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_industry'")['setting_value'] ?? 'fashion';

        $this->render('products/create', [
            'categories' => $categories,
            'attributes' => $attributes,
            'currency' => $currency,
            'industry' => $industry
        ]);
    }

    public function productStore(): void {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = preg_replace('/[^a-z0-9_-]/', '-', strtolower($name));
        }
        $price = max(0, (float)($_POST['price'] ?? 0));
        $comparePrice = !empty($_POST['compare_price']) ? max(0, (float)$_POST['compare_price']) : null;
        $costPrice = !empty($_POST['cost_price']) ? max(0, (float)$_POST['cost_price']) : null;
        $sku = trim($_POST['sku'] ?? '');
        $barcode = trim($_POST['barcode'] ?? '');
        $quantity = max(0, (int)($_POST['quantity'] ?? 0));
        $lowThreshold = max(0, (int)($_POST['low_stock_threshold'] ?? 5));
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $description = trim($_POST['description'] ?? '');
        $shortDescription = trim($_POST['short_description'] ?? '');
        $hasVariants = isset($_POST['has_variants']) ? 1 : 0;
        $option1Name = !empty($_POST['option1_name']) ? trim($_POST['option1_name']) : 'Color';
        $option2Name = !empty($_POST['option2_name']) ? trim($_POST['option2_name']) : 'Talla';
        $option3Name = !empty($_POST['option3_name']) ? trim($_POST['option3_name']) : null;
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if ($hasVariants) {
            $missingBase = [];
            if ($name === '') $missingBase[] = 'Nombre';
            if ($shortDescription === '') $missingBase[] = 'Descripción corta';
            if ($description === '') $missingBase[] = 'Descripción completa';
            if ($price <= 0) $missingBase[] = 'Precio base (> 0)';
            if ($sku === '') $missingBase[] = 'SKU base';
            if (empty($categoryId)) $missingBase[] = 'Categoría';

            if (!empty($missingBase)) {
                $_SESSION['flash_error'] = 'Para crear un producto con variantes debes completar todos los datos del producto base: ' . implode(', ', $missingBase) . '.';
                header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/products/create');
                exit;
            }
        }

        // Subida de imagen destacada y galería adicional con validación de formato/peso
        $imageErrors = [];
        $featuredImage = null;
        if (!empty($_FILES['featured_image']['name'])) {
            $errMain = $_FILES['featured_image']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($errMain === UPLOAD_ERR_INI_SIZE || $errMain === UPLOAD_ERR_FORM_SIZE) {
                $imageErrors[] = "La imagen principal supera el límite máximo de 2 MB.";
            } elseif ($errMain === UPLOAD_ERR_OK) {
                $featuredImage = $this->uploadProductImageFile(
                    $_FILES['featured_image']['tmp_name'],
                    $_FILES['featured_image']['name'],
                    'prod_',
                    $imageErrors,
                    (int)($_FILES['featured_image']['size'] ?? 0)
                );
            }
        } elseif (!empty($_POST['featured_image_url'])) {
            $featuredImage = trim($_POST['featured_image_url']);
        }
        $galleryUploads = $this->uploadProductGalleryFiles('gallery_images', $imageErrors, self::MAX_GALLERY_IMAGES);
        $galleryUrls = is_array($_POST['gallery_urls'] ?? null) ? $_POST['gallery_urls'] : [];
        if (($featuredImage === null || $featuredImage === '') && !empty($galleryUploads)) {
            $featuredImage = $galleryUploads[0];
        }

        // Procesar Variantes y sincronizar precio/stock maestro si tiene variantes habilitadas
        $firstVariantPrice = null;
        $firstVariantComparePrice = null;
        $totalVariantStock = 0;
        $validVariants = [];

        if ($hasVariants && !empty($_POST['variant_title'])) {
            $variantTitles = $_POST['variant_title'];
            $variantSkus = $_POST['variant_sku'] ?? [];
            $variantPrices = $_POST['variant_price'] ?? [];
            $variantComparePrices = $_POST['variant_compare_price'] ?? [];
            $variantQtys = $_POST['variant_qty'] ?? [];
            $variantExistingImages = $_POST['variant_existing_image'] ?? [];
            $variantClearImages = $_POST['variant_clear_image'] ?? [];

            for ($i = 0; $i < count($variantTitles); $i++) {
                $vTitle = trim($variantTitles[$i]);
                if ($vTitle !== '') {
                    $vPrice = (isset($variantPrices[$i]) && trim((string)$variantPrices[$i]) !== '') ? max(0, (float)$variantPrices[$i]) : $price;
                    $vCompare = (!empty($variantComparePrices[$i]) && trim((string)$variantComparePrices[$i]) !== '') ? max(0, (float)$variantComparePrices[$i]) : null;
                    $vQty = (isset($variantQtys[$i]) && trim((string)$variantQtys[$i]) !== '') ? max(0, (int)$variantQtys[$i]) : 0;
                    $vSku = trim($variantSkus[$i] ?? '');
                    $vImg = $this->resolveVariantImageAtIndex(
                        $i,
                        (string)($variantExistingImages[$i] ?? ''),
                        !empty($variantClearImages[$i]),
                        $imageErrors
                    );

                    if ($firstVariantPrice === null) {
                        $firstVariantPrice = $vPrice;
                        $firstVariantComparePrice = $vCompare;
                    }
                    $totalVariantStock += $vQty;

                    $validVariants[] = [
                        'title' => $vTitle,
                        'sku' => $vSku,
                        'price' => $vPrice,
                        'compare_price' => $vCompare,
                        'quantity' => $vQty,
                        'image_url' => $vImg
                    ];
                }
            }

            // Sincronizar automáticamente precio base y stock desde las variantes
            if ($firstVariantPrice !== null) {
                $price = $firstVariantPrice;
                $comparePrice = $firstVariantComparePrice;
                $quantity = $totalVariantStock;
            }
        }

        $productId = Database::insert('products', [
            'name' => $name,
            'slug' => $slug,
            'short_description' => $shortDescription,
            'description' => $description,
            'price' => $price,
            'compare_price' => $comparePrice,
            'cost_price' => $costPrice,
            'sku' => $sku,
            'barcode' => $barcode,
            'track_quantity' => 1,
            'quantity' => $quantity,
            'low_stock_threshold' => $lowThreshold,
            'is_active' => $isActive,
            'has_variants' => $hasVariants,
            'option1_name' => $option1Name,
            'option2_name' => $option2Name,
            'option3_name' => $option3Name,
            'category_id' => $categoryId,
            'featured_image' => $featuredImage
        ]);

        // Insertar variantes validadas y registrar stock inicial en Kardex
        if (!empty($validVariants)) {
            foreach ($validVariants as $varData) {
                $newVid = Database::insert('product_variants', [
                    'product_id' => $productId,
                    'title' => $varData['title'],
                    'sku' => $varData['sku'],
                    'price' => $varData['price'],
                    'compare_price' => $varData['compare_price'],
                    'quantity' => $varData['quantity'],
                    'image_url' => $varData['image_url']
                ]);
                if ((int)$varData['quantity'] > 0) {
                    InventoryManager::logMovement((int)$productId, (int)$newVid, 0, (int)$varData['quantity'], 'catalog_sync', 'Catálogo', 'Stock inicial al crear variante');
                }
            }
        } elseif ($quantity > 0) {
            InventoryManager::logMovement((int)$productId, null, 0, (int)$quantity, 'catalog_sync', 'Catálogo', 'Stock inicial al crear producto');
        }

        // Guardar galería múltiple de imágenes
        $this->saveProductGalleryItems((int)$productId, $galleryUploads, $galleryUrls, $imageErrors);

        if (!empty($imageErrors)) {
            $_SESSION['flash_error'] = implode(' ', array_unique($imageErrors));
        }
        $_SESSION['flash_success'] = 'Producto creado exitosamente.';
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/products');
        exit;
    }

    public function productEdit(string $id): void {
        $product = Database::fetch("
            SELECT p.*, c.name as category_name 
            FROM " . Database::table('products') . " p 
            LEFT JOIN " . Database::table('categories') . " c ON p.category_id = c.id 
            WHERE p.id = ?
        ", [(int)$id]);

        if (!$product) {
            $_SESSION['flash_error'] = 'El producto no fue encontrado.';
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/products');
            exit;
        }

        $variants = Database::fetchAll("SELECT * FROM " . Database::table('product_variants') . " WHERE product_id = ? ORDER BY id ASC", [(int)$id]);
        $galleryImages = Database::fetchAll("SELECT * FROM " . Database::table('product_images') . " WHERE product_id = ? ORDER BY sort_order ASC, id ASC", [(int)$id]);
        $categories = Database::fetchAll("
            SELECT c.*, p.name as parent_name 
            FROM " . Database::table('categories') . " c 
            LEFT JOIN " . Database::table('categories') . " p ON c.parent_id = p.id 
            WHERE c.is_active = 1 OR c.id = ?
            ORDER BY COALESCE(c.parent_id, c.id) ASC, c.parent_id ASC, c.name ASC
        ", [(int)($product['category_id'] ?? 0)]);
        $currency = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_currency_symbol'")['setting_value'] ?? '$';
        $industry = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_industry'")['setting_value'] ?? 'fashion';

        $this->render('products/edit', [
            'product' => $product,
            'variants' => $variants,
            'galleryImages' => $galleryImages,
            'categories' => $categories,
            'currency' => $currency,
            'industry' => $industry
        ]);
    }

    public function productUpdate(string $id): void {
        $productId = (int)$id;
        $product = Database::fetch("SELECT * FROM " . Database::table('products') . " WHERE id = ?", [$productId]);
        if (!$product) {
            $_SESSION['flash_error'] = 'El producto no existe.';
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/products');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = preg_replace('/[^a-z0-9_-]/', '-', strtolower($name));
        }
        $price = max(0, (float)($_POST['price'] ?? 0));
        $comparePrice = !empty($_POST['compare_price']) ? max(0, (float)$_POST['compare_price']) : null;
        $costPrice = !empty($_POST['cost_price']) ? max(0, (float)$_POST['cost_price']) : null;
        $sku = trim($_POST['sku'] ?? '');
        $barcode = trim($_POST['barcode'] ?? '');
        $quantity = max(0, (int)($_POST['quantity'] ?? 0));
        $lowThreshold = max(0, (int)($_POST['low_stock_threshold'] ?? 5));
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $description = trim($_POST['description'] ?? '');
        $shortDescription = trim($_POST['short_description'] ?? '');
        $hasVariants = isset($_POST['has_variants']) ? 1 : 0;
        $option1Name = !empty($_POST['option1_name']) ? trim($_POST['option1_name']) : ($product['option1_name'] ?? 'Color');
        $option2Name = !empty($_POST['option2_name']) ? trim($_POST['option2_name']) : ($product['option2_name'] ?? 'Talla');
        $option3Name = !empty($_POST['option3_name']) ? trim($_POST['option3_name']) : ($product['option3_name'] ?? null);
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;

        // Subida, limpieza o conservación de imagen destacada
        $imageErrors = [];
        $featuredImage = !empty($_POST['clear_featured_image']) ? null : $product['featured_image'];
        if (!empty($_FILES['featured_image']['name'])) {
            $errMain = $_FILES['featured_image']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($errMain === UPLOAD_ERR_INI_SIZE || $errMain === UPLOAD_ERR_FORM_SIZE) {
                $imageErrors[] = "La imagen principal supera el límite máximo de 2 MB.";
            } elseif ($errMain === UPLOAD_ERR_OK) {
                $uploadedMain = $this->uploadProductImageFile(
                    $_FILES['featured_image']['tmp_name'],
                    $_FILES['featured_image']['name'],
                    'prod_',
                    $imageErrors,
                    (int)($_FILES['featured_image']['size'] ?? 0)
                );
                if ($uploadedMain !== null) {
                    $featuredImage = $uploadedMain;
                }
            }
        } elseif (empty($_POST['clear_featured_image']) && isset($_POST['featured_image_url']) && trim($_POST['featured_image_url']) !== '') {
            $featuredImage = trim($_POST['featured_image_url']);
        } elseif (empty($_POST['clear_featured_image']) && !empty($_POST['existing_featured_image'])) {
            $featuredImage = trim($_POST['existing_featured_image']);
        }

        // Gestión de galería múltiple de imágenes (eliminar, reordenar, promover, añadir nuevas)
        $deleteGalleryIds = is_array($_POST['delete_gallery_ids'] ?? null) ? $_POST['delete_gallery_ids'] : [];
        $this->deleteProductGalleryItems($productId, $deleteGalleryIds);

        if (!empty($_POST['existing_gallery_order']) && is_array($_POST['existing_gallery_order'])) {
            foreach ($_POST['existing_gallery_order'] as $gid => $ord) {
                $gidInt = (int)$gid;
                if ($gidInt > 0 && !in_array((string)$gidInt, array_map('strval', $deleteGalleryIds), true)) {
                    Database::query(
                        "UPDATE " . Database::table('product_images') . " SET sort_order = ? WHERE id = ? AND product_id = ?",
                        [(int)$ord, $gidInt, $productId]
                    );
                }
            }
        }

        if (!empty($_POST['promote_gallery_id'])) {
            $promoteRow = Database::fetch(
                "SELECT image_url FROM " . Database::table('product_images') . " WHERE id = ? AND product_id = ?",
                [(int)$_POST['promote_gallery_id'], $productId]
            );
            if ($promoteRow && !empty($promoteRow['image_url'])) {
                $featuredImage = trim((string)$promoteRow['image_url']);
            }
        }

        $existingGalCount = (int)(Database::fetch("SELECT COUNT(*) as c FROM " . Database::table('product_images') . " WHERE product_id = ?", [$productId])['c'] ?? 0);
        $remainingSlots = max(0, self::MAX_GALLERY_IMAGES - $existingGalCount);
        $galleryUploads = $this->uploadProductGalleryFiles('gallery_images', $imageErrors, $remainingSlots);
        $galleryUrls = is_array($_POST['gallery_urls'] ?? null) ? $_POST['gallery_urls'] : [];
        $this->saveProductGalleryItems($productId, $galleryUploads, $galleryUrls, $imageErrors);

        // Gestión y sincronización de Variantes
        $totalVariantStock = 0;
        if ($hasVariants && !empty($_POST['variant_title'])) {
            $variantIds = $_POST['variant_id'] ?? [];
            $variantTitles = $_POST['variant_title'] ?? [];
            $variantSkus = $_POST['variant_sku'] ?? [];
            $variantPrices = $_POST['variant_price'] ?? [];
            $variantComparePrices = $_POST['variant_compare_price'] ?? [];
            $variantQtys = $_POST['variant_qty'] ?? [];
            $variantExistingImages = $_POST['variant_existing_image'] ?? [];
            $variantClearImages = $_POST['variant_clear_image'] ?? [];

            $savedVariantIds = [];
            $firstVariantPrice = null;
            $firstVariantComparePrice = null;

            for ($i = 0; $i < count($variantTitles); $i++) {
                $title = trim($variantTitles[$i]);
                if ($title === '') continue;

                $vId = !empty($variantIds[$i]) ? (int)$variantIds[$i] : 0;
                $vSku = trim($variantSkus[$i] ?? '');
                $vPrice = (isset($variantPrices[$i]) && trim((string)$variantPrices[$i]) !== '') ? max(0, (float)$variantPrices[$i]) : $price;
                $vComparePrice = (!empty($variantComparePrices[$i]) && trim((string)$variantComparePrices[$i]) !== '') ? max(0, (float)$variantComparePrices[$i]) : null;
                $vQty = isset($variantQtys[$i]) && trim((string)$variantQtys[$i]) !== '' ? max(0, (int)$variantQtys[$i]) : 0;
                $vImg = $this->resolveVariantImageAtIndex(
                    $i,
                    (string)($variantExistingImages[$i] ?? ''),
                    !empty($variantClearImages[$i]),
                    $imageErrors
                );
                $totalVariantStock += $vQty;

                if ($firstVariantPrice === null) {
                    $firstVariantPrice = $vPrice;
                    $firstVariantComparePrice = $vComparePrice;
                }

                if ($vId > 0) {
                    $oldVarRow = Database::fetch("SELECT quantity FROM " . Database::table('product_variants') . " WHERE id = ?", [$vId]);
                    $oldVarQty = (int)($oldVarRow['quantity'] ?? 0);
                    Database::update('product_variants', [
                        'title' => $title,
                        'sku' => $vSku,
                        'price' => $vPrice,
                        'compare_price' => $vComparePrice,
                        'quantity' => $vQty,
                        'image_url' => $vImg
                    ], "id = :id AND product_id = :product_id", [
                        'id' => $vId,
                        'product_id' => $productId
                    ]);
                    InventoryManager::logMovement($productId, $vId, $oldVarQty, (int)$vQty, 'catalog_sync', 'Catálogo', 'Ajuste desde ficha de producto');
                    $savedVariantIds[] = $vId;
                } else {
                    $newVid = Database::insert('product_variants', [
                        'product_id' => $productId,
                        'title' => $title,
                        'sku' => $vSku,
                        'price' => $vPrice,
                        'compare_price' => $vComparePrice,
                        'quantity' => $vQty,
                        'image_url' => $vImg
                    ]);
                    if ((int)$vQty > 0) {
                        InventoryManager::logMovement($productId, (int)$newVid, 0, (int)$vQty, 'catalog_sync', 'Catálogo', 'Nueva variante creada');
                    }
                    $savedVariantIds[] = (int)$newVid;
                }
            }

            // Variantes eliminadas en el formulario
            $existingVariants = Database::fetchAll("SELECT id FROM " . Database::table('product_variants') . " WHERE product_id = ?", [$productId]);
            foreach ($existingVariants as $ev) {
                $eid = (int)$ev['id'];
                if (!in_array($eid, $savedVariantIds)) {
                    // Verificar si tiene referencias en order_items para no romper FK
                    $orderCount = Database::fetch("SELECT COUNT(*) as count FROM " . Database::table('order_items') . " WHERE variant_id = ?", [$eid])['count'] ?? 0;
                    if ($orderCount > 0) {
                        Database::update('product_variants', ['quantity' => 0], "id = :id", ['id' => $eid]);
                    } else {
                        Database::delete('product_variants', "id = ?", [$eid]);
                    }
                }
            }

            if (count($savedVariantIds) > 0) {
                $quantity = $totalVariantStock;
                if ($firstVariantPrice !== null) {
                    $price = $firstVariantPrice;
                    $comparePrice = $firstVariantComparePrice;
                }
            }
        } elseif (!$hasVariants) {
            $existingVariants = Database::fetchAll("SELECT id FROM " . Database::table('product_variants') . " WHERE product_id = ?", [$productId]);
            foreach ($existingVariants as $ev) {
                $eid = (int)$ev['id'];
                $orderCount = Database::fetch("SELECT COUNT(*) as count FROM " . Database::table('order_items') . " WHERE variant_id = ?", [$eid])['count'] ?? 0;
                if ($orderCount == 0) {
                    Database::delete('product_variants', "id = ?", [$eid]);
                } else {
                    Database::update('product_variants', ['quantity' => 0], "id = :id", ['id' => $eid]);
                }
            }
        }

        if (!$hasVariants && (int)$product['quantity'] !== (int)$quantity) {
            InventoryManager::logMovement($productId, null, (int)$product['quantity'], (int)$quantity, 'catalog_sync', 'Catálogo', 'Ajuste desde ficha de producto');
        }

        Database::update('products', [
            'name' => $name,
            'slug' => $slug,
            'short_description' => $shortDescription,
            'description' => $description,
            'price' => $price,
            'compare_price' => $comparePrice,
            'cost_price' => $costPrice,
            'sku' => $sku,
            'barcode' => $barcode,
            'quantity' => $quantity,
            'low_stock_threshold' => $lowThreshold,
            'is_active' => $isActive,
            'has_variants' => $hasVariants,
            'option1_name' => $option1Name,
            'option2_name' => $option2Name,
            'option3_name' => $option3Name,
            'category_id' => $categoryId,
            'featured_image' => $featuredImage
        ], "id = :id", ['id' => $productId]);

        $finalProd = Database::fetch("SELECT quantity FROM " . Database::table('products') . " WHERE id = ?", [$productId]);
        $newTotalQty = (int)($finalProd['quantity'] ?? $quantity);
        if ((int)$product['quantity'] <= 0 && $newTotalQty > 0) {
            PluginManager::doAction('stock_replenished', $productId, $newTotalQty);
        }
        $extraNotice = $_SESSION['flash_plugin_notice'] ?? '';
        unset($_SESSION['flash_plugin_notice']);

        if (!empty($imageErrors)) {
            $_SESSION['flash_error'] = implode(' ', array_unique($imageErrors));
        }
        $_SESSION['flash_success'] = 'Producto y variantes actualizados correctamente.' . ($extraNotice ? ' ' . $extraNotice : '');
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/products/edit/' . $productId);
        exit;
    }

    public function productToggle(string $id): void {
        $product = Database::fetch("SELECT id, is_active, name FROM " . Database::table('products') . " WHERE id = ?", [(int)$id]);
        if ($product) {
            $newStatus = $product['is_active'] ? 0 : 1;
            Database::update('products', ['is_active' => $newStatus], "id = :id", ['id' => (int)$id]);
            $_SESSION['flash_success'] = $newStatus 
                ? 'Producto "' . htmlspecialchars($product['name']) . '" activado y visible en la tienda.' 
                : 'Producto "' . htmlspecialchars($product['name']) . '" deshabilitado y oculto del catálogo (borrado lógico aplicado).';
        }
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/products');
        exit;
    }

    public function productDelete(string $id): void {
        // Borrado lógico: desactivar el producto en lugar de destruir registros para proteger pedidos históricos
        Database::update('products', ['is_active' => 0], "id = :id", ['id' => (int)$id]);
        $_SESSION['flash_success'] = 'Producto deshabilitado del catálogo (borrado lógico: el historial de pedidos y clientes permanece protegido).';
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/products');
        exit;
    }

    // ==========================================
    // MÓDULO: GESTIÓN DE CATEGORÍAS JERÁRQUICAS
    // ==========================================
    public function categories(): void {
        $search = trim($_GET['q'] ?? '');
        $statusFilter = $_GET['status'] ?? '';

        $sql = "SELECT c.*, p.name as parent_name, 
                       (SELECT COUNT(*) FROM " . Database::table('products') . " pr WHERE pr.category_id = c.id) as products_count 
                FROM " . Database::table('categories') . " c 
                LEFT JOIN " . Database::table('categories') . " p ON c.parent_id = p.id
                WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (c.name LIKE ? OR c.slug LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($statusFilter === 'active') {
            $sql .= " AND c.is_active = 1";
        } elseif ($statusFilter === 'disabled') {
            $sql .= " AND c.is_active = 0";
        }

        $sql .= " ORDER BY COALESCE(c.parent_id, c.id) ASC, c.parent_id ASC, c.name ASC";

        $categories = Database::fetchAll($sql, $params);
        $parentCategories = Database::fetchAll("SELECT * FROM " . Database::table('categories') . " WHERE parent_id IS NULL AND is_active = 1 ORDER BY name ASC");
        $industry = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_industry'")['setting_value'] ?? 'fashion';

        $totalActive = Database::fetch("SELECT COUNT(*) as count FROM " . Database::table('categories') . " WHERE is_active = 1")['count'] ?? 0;
        $totalDisabled = Database::fetch("SELECT COUNT(*) as count FROM " . Database::table('categories') . " WHERE is_active = 0")['count'] ?? 0;

        $this->render('categories/index', [
            'categories' => $categories,
            'parentCategories' => $parentCategories,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'totalActive' => (int)$totalActive,
            'totalDisabled' => (int)$totalDisabled,
            'industry' => $industry
        ]);
    }

    public function categoryStore(): void {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = preg_replace('/[^a-z0-9_-]/', '-', strtolower($name));
        }
        $description = trim($_POST['description'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $imageUrl = trim($_POST['image_url'] ?? '');

        // Validar unicidad de slug
        $existing = Database::fetch("SELECT id FROM " . Database::table('categories') . " WHERE slug = ?", [$slug]);
        if ($existing) {
            $slug .= '-' . substr(uniqid(), -4);
        }

        Database::insert('categories', [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'parent_id' => $parentId,
            'image_url' => $imageUrl,
            'is_active' => 1
        ]);

        $_SESSION['flash_success'] = "Categoría '{$name}' creada correctamente.";
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/categories');
        exit;
    }

    public function categoryUpdate(string $id): void {
        $catId = (int)$id;
        $category = Database::fetch("SELECT * FROM " . Database::table('categories') . " WHERE id = ?", [$catId]);
        if (!$category) {
            $_SESSION['flash_error'] = "La categoría no existe.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/categories');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = preg_replace('/[^a-z0-9_-]/', '-', strtolower($name));
        }
        $description = trim($_POST['description'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $imageUrl = trim($_POST['image_url'] ?? '');

        if ($parentId === $catId) {
            $parentId = null;
        }

        Database::update('categories', [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'parent_id' => $parentId,
            'image_url' => $imageUrl
        ], "id = :id", ['id' => $catId]);

        $_SESSION['flash_success'] = "Categoría '{$name}' actualizada correctamente.";
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/categories');
        exit;
    }

    public function categoryToggle(string $id): void {
        $catId = (int)$id;
        $category = Database::fetch("SELECT id, is_active, name FROM " . Database::table('categories') . " WHERE id = ?", [$catId]);
        if ($category) {
            $newStatus = !empty($category['is_active']) ? 0 : 1;
            Database::update('categories', ['is_active' => $newStatus], "id = :id", ['id' => $catId]);
            $_SESSION['flash_success'] = $newStatus
                ? "Categoría '{$category['name']}' activada y visible en el catálogo."
                : "Categoría '{$category['name']}' deshabilitada (borrado lógico). Se conservan sus productos y estructura.";
        }
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/categories');
        exit;
    }

    public function categoryDelete(string $id): void {
        // Borrado lógico: deshabilitar la categoría en lugar de destruir registros para conservar relaciones e historial
        $catId = (int)$id;
        $category = Database::fetch("SELECT * FROM " . Database::table('categories') . " WHERE id = ?", [$catId]);
        if ($category) {
            Database::update('categories', ['is_active' => 0], "id = :id", ['id' => $catId]);
            $_SESSION['flash_success'] = "Categoría '{$category['name']}' deshabilitada (borrado lógico). No aparecerá en la tienda, pero sus productos e historial permanecen intactos.";
        }
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/categories');
        exit;
    }

    public function categorySeedPreset(): void {
        $industry = trim($_POST['industry'] ?? ($_GET['industry'] ?? ''));
        if (empty($industry)) {
            $industry = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_industry'")['setting_value'] ?? 'fashion';
        }

        $presets = [
            'fashion' => [
                'name' => 'Moda, Ropa y Calzado',
                'categories' => [
                    ['name' => 'Prendas Superiores', 'slug' => 'prendas-superiores', 'children' => [
                        ['name' => 'Camisetas & Tops', 'slug' => 'camisetas-tops'],
                        ['name' => 'Camisas & Blusas', 'slug' => 'camisas-blusas'],
                        ['name' => 'Abrigos, Casacas & Suéteres', 'slug' => 'abrigos-casacas-sueteres']
                    ]],
                    ['name' => 'Prendas Inferiores', 'slug' => 'prendas-inferiores', 'children' => [
                        ['name' => 'Pantalones & Jeans', 'slug' => 'pantalones-jeans'],
                        ['name' => 'Faldas & Shorts', 'slug' => 'faldas-shorts'],
                        ['name' => 'Leggins & Joggers', 'slug' => 'leggins-joggers']
                    ]],
                    ['name' => 'Ropa Interior & Descanso', 'slug' => 'ropa-interior-descanso', 'children' => [
                        ['name' => 'Pijamas & Batas', 'slug' => 'pijamas-batas'],
                        ['name' => 'Lencería & Ropa Térmica', 'slug' => 'lenceria-termica']
                    ]],
                    ['name' => 'Calzado Urbano & Formal', 'slug' => 'calzado', 'children' => [
                        ['name' => 'Zapatillas Deportivas & Urbanas', 'slug' => 'zapatillas-urbanas'],
                        ['name' => 'Zapatos Formales & Mocasines', 'slug' => 'zapatos-formales'],
                        ['name' => 'Botas & Sandalias', 'slug' => 'botas-sandalias']
                    ]],
                    ['name' => 'Accesorios & Bolsos', 'slug' => 'accesorios', 'children' => [
                        ['name' => 'Bolsos, Mochilas & Billeteras', 'slug' => 'bolsos-mochilas'],
                        ['name' => 'Gorras, Cinturones & Bufandas', 'slug' => 'gorras-cinturones']
                    ]]
                ]
            ],
            'electronics' => [
                'name' => 'Tecnología y Gadgets',
                'categories' => [
                    ['name' => 'Dispositivos Móviles', 'slug' => 'dispositivos-moviles', 'children' => [
                        ['name' => 'Smartphones & Teléfonos', 'slug' => 'smartphones'],
                        ['name' => 'Tablets & iPads', 'slug' => 'tablets'],
                        ['name' => 'Smartwatches & Pulseras', 'slug' => 'smartwatches']
                    ]],
                    ['name' => 'Computación & Laptops', 'slug' => 'computacion', 'children' => [
                        ['name' => 'Laptops Ultraligeras & Gaming', 'slug' => 'laptops'],
                        ['name' => 'Monitores & Pantallas', 'slug' => 'monitores'],
                        ['name' => 'Teclados, Mouse & Periféricos', 'slug' => 'teclados-mouse']
                    ]],
                    ['name' => 'Audio & Video', 'slug' => 'audio-video', 'children' => [
                        ['name' => 'Audífonos Bluetooth & Noise Cancelling', 'slug' => 'audifonos'],
                        ['name' => 'Parlantes Portátiles & Soundbars', 'slug' => 'parlantes'],
                        ['name' => 'Smart TVs & Proyectores', 'slug' => 'televisores']
                    ]],
                    ['name' => 'Accesorios & Carga', 'slug' => 'accesorios-carga', 'children' => [
                        ['name' => 'Cargadores Rápidos & Cables USB-C', 'slug' => 'cargadores-cables'],
                        ['name' => 'Baterías Portátiles (Power Banks)', 'slug' => 'power-banks'],
                        ['name' => 'Fundas, Protectores & Soportes', 'slug' => 'fundas-soportes']
                    ]],
                    ['name' => 'Hogar Inteligente & Seguridad', 'slug' => 'hogar-inteligente', 'children' => [
                        ['name' => 'Cámaras de Seguridad WiFi', 'slug' => 'camaras-seguridad'],
                        ['name' => 'Iluminación & Focos Smart', 'slug' => 'iluminacion-smart'],
                        ['name' => 'Asistentes de Voz & Enchufes', 'slug' => 'asistentes-voz']
                    ]]
                ]
            ],
            'food' => [
                'name' => 'Alimentos, Bebidas y Gourmet',
                'categories' => [
                    ['name' => 'Frutas & Verduras Frescas', 'slug' => 'frutas-verduras', 'children' => [
                        ['name' => 'Frutas Seleccionadas', 'slug' => 'frutas-seleccionadas'],
                        ['name' => 'Verduras & Hortalizas del Campo', 'slug' => 'verduras-hortalizas']
                    ]],
                    ['name' => 'Panadería & Repostería Artesanal', 'slug' => 'panaderia-artesanal', 'children' => [
                        ['name' => 'Panes de Masa Madre', 'slug' => 'panes-masa-madre'],
                        ['name' => 'Pasteles & Repostería Dulce', 'slug' => 'pasteles-reposteria']
                    ]],
                    ['name' => 'Lácteos & Quesos Madurados', 'slug' => 'lacteos-quesos', 'children' => [
                        ['name' => 'Leches & Yogures Naturales', 'slug' => 'leches-yogures'],
                        ['name' => 'Quesos Artesanales & Tablas', 'slug' => 'quesos-artesanales']
                    ]],
                    ['name' => 'Bebidas, Cafés & Tés', 'slug' => 'bebidas-cafes', 'children' => [
                        ['name' => 'Cafés de Especialidad en Grano', 'slug' => 'cafes-especialidad'],
                        ['name' => 'Infusiones & Tés Orgánicos', 'slug' => 'infusiones-tes'],
                        ['name' => 'Jugos Prensados en Frío', 'slug' => 'jugos-naturales']
                    ]],
                    ['name' => 'Snacks, Frutos Secos & Gourmet', 'slug' => 'snacks-gourmet', 'children' => [
                        ['name' => 'Frutos Secos & Semillas', 'slug' => 'frutos-secos'],
                        ['name' => 'Chocolates & Dulces Selectos', 'slug' => 'chocolates-selectos']
                    ]]
                ]
            ]
        ];

        $selectedPreset = $presets[$industry] ?? $presets['fashion'];
        $createdCount = 0;

        foreach ($selectedPreset['categories'] as $cat) {
            $existingParent = Database::fetch("SELECT id FROM " . Database::table('categories') . " WHERE slug = ?", [$cat['slug']]);
            if ($existingParent) {
                $parentId = (int)$existingParent['id'];
            } else {
                $parentId = (int)Database::insert('categories', [
                    'name' => $cat['name'],
                    'slug' => $cat['slug'],
                    'description' => "Categoría principal de {$cat['name']}",
                    'parent_id' => null,
                    'is_active' => 1
                ]);
                $createdCount++;
            }

            if (!empty($cat['children'])) {
                foreach ($cat['children'] as $sub) {
                    $existingSub = Database::fetch("SELECT id FROM " . Database::table('categories') . " WHERE slug = ?", [$sub['slug']]);
                    if (!$existingSub) {
                        Database::insert('categories', [
                            'name' => $sub['name'],
                            'slug' => $sub['slug'],
                            'description' => "Subcategoría de {$sub['name']}",
                            'parent_id' => $parentId,
                            'is_active' => 1
                        ]);
                        $createdCount++;
                    }
                }
            }
        }

        Database::query("UPDATE " . Database::table('settings') . " SET setting_value = ? WHERE setting_key = 'store_industry'", [$industry]);

        $_SESSION['flash_success'] = "Estructura de categorías para '{$selectedPreset['name']}' sincronizada ({$createdCount} nuevas agregadas). Puedes personalizarlas a tu gusto.";
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/categories');
        exit;
    }

    // ==========================================
    // MÓDULO 3: GESTIÓN DE PEDIDOS
    // ==========================================
    public function orders(): void {
        $statusFilter = $_GET['status'] ?? '';
        $paymentFilter = $_GET['payment'] ?? '';
        $search = trim($_GET['q'] ?? '');

        $sql = "SELECT o.*,
                       (SELECT SUM(oi.quantity) FROM " . Database::table('order_items') . " oi WHERE oi.order_id = o.id) as total_items
                FROM " . Database::table('orders') . " o
                WHERE 1=1";
        $params = [];

        if ($statusFilter === 'delivered') {
            $sql .= " AND o.status IN ('delivered', 'completed')";
        } elseif (!empty($statusFilter) && isset(OrderWorkflow::statuses()[$statusFilter])) {
            $sql .= " AND o.status = ?";
            $params[] = $statusFilter;
        }

        if (in_array($paymentFilter, ['unpaid', 'paid', 'refunded'], true)) {
            $sql .= " AND o.payment_status = ?";
            $params[] = $paymentFilter;
        }

        if ($search !== '') {
            $sql .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ? OR o.tracking_number LIKE ? OR o.carrier LIKE ?)";
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like, $like]);
        }

        $sql .= " ORDER BY o.id DESC";
        $orders = Database::fetchAll($sql, $params);
        $currency = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_currency_symbol'")['setting_value'] ?? '$';

        $allOrders = Database::fetchAll("SELECT status, payment_status, total FROM " . Database::table('orders'));
        $counts = [
            'all' => count($allOrders),
            'pending' => 0,
            'processing' => 0,
            'packing' => 0,
            'shipped' => 0,
            'out_for_delivery' => 0,
            'delivered' => 0,
            'cancelled' => 0,
            'returned' => 0,
        ];
        $inProgressCount = 0;
        $totalRevenue = 0.0;

        foreach ($allOrders as $row) {
            $st = $row['status'] === 'completed' ? 'delivered' : $row['status'];
            if (isset($counts[$st])) {
                $counts[$st]++;
            }
            if (in_array($st, ['pending', 'processing', 'packing', 'shipped', 'out_for_delivery'], true)) {
                $inProgressCount++;
            }
            if (!in_array($st, ['cancelled', 'returned'], true)) {
                $totalRevenue += (float)$row['total'];
            }
        }

        $this->render('orders/index', [
            'orders' => $orders,
            'currentFilter' => $statusFilter,
            'paymentFilter' => $paymentFilter,
            'search' => $search,
            'counts' => $counts,
            'inProgressCount' => $inProgressCount,
            'totalRevenue' => $totalRevenue,
            'currency' => $currency,
            'pageTitle' => 'Gestión de Pedidos y Logística'
        ]);
    }

    public function orderView(string $id): void {
        OrderWorkflow::ensurePaymentProofSchema();
        $orderId = (int)$id;
        $order = Database::fetch("SELECT * FROM " . Database::table('orders') . " WHERE id = ?", [$orderId]);
        if (!$order) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/orders');
            exit;
        }

        $items = Database::fetchAll("
            SELECT oi.*, p.featured_image, p.slug as product_slug
            FROM " . Database::table('order_items') . " oi
            LEFT JOIN " . Database::table('products') . " p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ", [$orderId]);

        $history = Database::fetchAll("SELECT * FROM " . Database::table('order_history') . " WHERE order_id = ? ORDER BY id DESC", [$orderId]);
        if (empty($history)) {
            $meta = OrderWorkflow::getStatusMeta($order['status']);
            $history = [[
                'status' => $order['status'],
                'comment' => $meta['desc'],
                'actor' => 'system',
                'created_at' => $order['updated_at'] ?? $order['created_at']
            ]];
        }

        $currency = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_currency_symbol'")['setting_value'] ?? '$';

        $this->render('orders/view', [
            'order' => $order,
            'items' => $items,
            'history' => $history,
            'currency' => $currency,
            'pageTitle' => 'Pedido ' . $order['order_number']
        ]);
    }

    public function orderUpdateStatus(string $id): void {
        $orderId = (int)$id;
        $order = Database::fetch("SELECT * FROM " . Database::table('orders') . " WHERE id = ?", [$orderId]);
        if (!$order) {
            $_SESSION['flash_error'] = 'El pedido no existe.';
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/orders');
            exit;
        }

        $validStatuses = array_keys(OrderWorkflow::statuses());
        $status = in_array($_POST['status'] ?? '', $validStatuses, true) ? $_POST['status'] : $order['status'];
        if ($status === 'completed') {
            $status = 'delivered';
        }

        $paymentStatus = in_array($_POST['payment_status'] ?? '', ['unpaid', 'paid', 'refunded'], true)
            ? $_POST['payment_status']
            : $order['payment_status'];

        $carrier = trim($_POST['carrier'] ?? ($order['carrier'] ?? ''));
        $trackingNumber = trim($_POST['tracking_number'] ?? ($order['tracking_number'] ?? ''));
        $estimatedDelivery = trim($_POST['estimated_delivery'] ?? ($order['estimated_delivery'] ?? ''));
        $cancelReason = trim($_POST['cancel_reason'] ?? ($order['cancel_reason'] ?? ''));
        $comment = trim($_POST['comment'] ?? '');

        // Reglas automáticas coherentes entre pago y logística
        if ($status === 'delivered' && $order['payment_method'] === 'cod' && $paymentStatus === 'unpaid') {
            $paymentStatus = 'paid';
        }
        if ($status === 'returned' && $order['payment_status'] === 'paid' && !isset($_POST['payment_status'])) {
            $paymentStatus = 'refunded';
        }

        Database::update('orders', [
            'status' => $status,
            'payment_status' => $paymentStatus,
            'carrier' => $carrier !== '' ? $carrier : null,
            'tracking_number' => $trackingNumber !== '' ? $trackingNumber : null,
            'estimated_delivery' => $estimatedDelivery !== '' ? $estimatedDelivery : null,
            'cancel_reason' => in_array($status, ['cancelled', 'returned'], true) && $cancelReason !== '' ? $cancelReason : ($order['cancel_reason'] ?? null),
        ], "id = :id", ['id' => $orderId]);

        $stockAdjusted = OrderWorkflow::syncInventoryForStatus($orderId, $status);

        $statusMeta = OrderWorkflow::getStatusMeta($status);
        $payMeta = OrderWorkflow::getPaymentMeta($paymentStatus);

        if ($comment === '') {
            $parts = ["Fase actualizada a: {$statusMeta['label']}"];
            if ($paymentStatus !== $order['payment_status']) {
                $parts[] = "Pago: {$payMeta['label']}";
            }
            if (in_array($status, ['shipped', 'out_for_delivery'], true) && ($carrier !== '' || $trackingNumber !== '')) {
                $trackInfo = trim($carrier . ($trackingNumber !== '' ? " (Guía: {$trackingNumber})" : ''));
                $parts[] = "Despacho: {$trackInfo}";
            }
            if (!empty($estimatedDelivery) && in_array($status, ['shipped', 'out_for_delivery'], true)) {
                $parts[] = "Entrega estimada: " . date('d/m/Y', strtotime($estimatedDelivery));
            }
            if (in_array($status, ['cancelled', 'returned'], true) && $cancelReason !== '') {
                $parts[] = "Motivo: {$cancelReason}";
            }
            if ($stockAdjusted) {
                $parts[] = in_array($status, ['cancelled', 'returned'], true)
                    ? "Inventario repuesto automáticamente al catálogo"
                    : "Inventario reservado nuevamente";
            }
            $comment = implode(' · ', $parts);
        }

        OrderWorkflow::recordHistory($orderId, $status, $comment, 'admin');

        $_SESSION['flash_success'] = "Pedido {$order['order_number']} actualizado a '{$statusMeta['short']}'" . ($stockAdjusted ? " (inventario sincronizado)." : ".");
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/orders/view/' . $orderId);
        exit;
    }

    public function orderReceipt(string $id): void {
        OrderWorkflow::ensurePaymentProofSchema();
        $orderId = (int)$id;
        $order = Database::fetch("SELECT * FROM " . Database::table('orders') . " WHERE id = ?", [$orderId]);
        if (!$order) {
            $_SESSION['flash_error'] = 'El pedido no existe.';
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/orders');
            exit;
        }

        $items = Database::fetchAll("SELECT * FROM " . Database::table('order_items') . " WHERE order_id = ?", [$orderId]);
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        $backUrl = $baseUrl . '/admin/orders/view/' . $orderId;
        $currencySymbol = Settings::get('store_currency_symbol', '$');

        require dirname(__DIR__) . '/Views/shared/order_receipt.php';
    }

    // ==========================================
    // MÓDULO 4: CUPONES Y DESCUENTOS
    // ==========================================
    public function coupons(): void {
        $statusFilter = $_GET['status'] ?? '';
        $search = trim($_GET['q'] ?? '');
        $today = date('Y-m-d');

        $sql = "SELECT * FROM " . Database::table('coupons') . " WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (code LIKE ? OR description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($statusFilter === 'active') {
            $sql .= " AND is_active = 1 AND (expires_at IS NULL OR expires_at >= ?) AND (usage_limit IS NULL OR usage_count < usage_limit)";
            $params[] = $today;
        } elseif ($statusFilter === 'expired') {
            $sql .= " AND is_active = 1 AND ((expires_at IS NOT NULL AND expires_at < ?) OR (usage_limit IS NOT NULL AND usage_count >= usage_limit))";
            $params[] = $today;
        } elseif ($statusFilter === 'disabled') {
            $sql .= " AND is_active = 0";
        }
        $sql .= " ORDER BY id DESC";

        $coupons = Database::fetchAll($sql, $params);
        $currency = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_currency_symbol'")['setting_value'] ?? '$';

        $allCoupons = Database::fetchAll("SELECT is_active, expires_at, usage_limit, usage_count FROM " . Database::table('coupons'));
        $totalActive = 0;
        $totalExpired = 0;
        $totalDisabled = 0;
        $totalUses = 0;

        foreach ($allCoupons as $row) {
            $totalUses += (int)($row['usage_count'] ?? 0);
            if (empty($row['is_active'])) {
                $totalDisabled++;
            } else {
                $isExp = !empty($row['expires_at']) && $row['expires_at'] < $today;
                $isExh = !empty($row['usage_limit']) && (int)$row['usage_count'] >= (int)$row['usage_limit'];
                if ($isExp || $isExh) {
                    $totalExpired++;
                } else {
                    $totalActive++;
                }
            }
        }

        $discountStats = Database::fetch("
            SELECT COALESCE(SUM(discount_amount), 0) as total_discounted, COUNT(*) as orders_with_coupon
            FROM " . Database::table('orders') . "
            WHERE discount_amount > 0 AND status != 'cancelled'
        ");

        $this->render('coupons/index', [
            'coupons' => $coupons,
            'currency' => $currency,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'totalActive' => $totalActive,
            'totalExpired' => $totalExpired,
            'totalDisabled' => $totalDisabled,
            'totalAll' => count($allCoupons),
            'totalUses' => $totalUses,
            'totalDiscounted' => (float)($discountStats['total_discounted'] ?? 0),
            'ordersWithCoupon' => (int)($discountStats['orders_with_coupon'] ?? 0),
            'today' => $today,
            'pageTitle' => 'Cupones y Descuentos'
        ]);
    }

    private function parseAndValidateCouponInput(?int $excludeId = null): ?array {
        $code = strtoupper(preg_replace('/[^A-Z0-9_-]/i', '', trim($_POST['code'] ?? '')));
        $description = trim($_POST['description'] ?? '');
        $type = in_array($_POST['type'] ?? 'percent', ['percent', 'fixed'], true) ? $_POST['type'] : 'percent';
        $value = max(0, (float)($_POST['value'] ?? 0));
        $spendRule = in_array($_POST['spend_rule'] ?? 'min', ['min', 'exact', 'range'], true) ? $_POST['spend_rule'] : 'min';
        $minSpend = max(0, (float)($_POST['min_spend'] ?? 0));
        $maxSpendRaw = trim((string)($_POST['max_spend'] ?? ''));
        $maxSpend = ($spendRule === 'range' && $maxSpendRaw !== '') ? max(0, (float)$maxSpendRaw) : null;
        $usageLimitRaw = trim((string)($_POST['usage_limit'] ?? ''));
        $usageLimit = ($usageLimitRaw !== '' && (int)$usageLimitRaw > 0) ? (int)$usageLimitRaw : null;
        $singleUsePerUser = isset($_POST['single_use_per_user']) ? 1 : 0;
        $expiresAtRaw = trim((string)($_POST['expires_at'] ?? ''));
        $expiresAt = $expiresAtRaw !== '' ? $expiresAtRaw : null;

        if ($code === '' || $value <= 0) {
            $_SESSION['flash_error'] = 'Ingresa un código válido y un valor de descuento mayor a 0.';
            return null;
        }

        if ($type === 'percent' && $value > 100) {
            $_SESSION['flash_error'] = 'El descuento porcentual no puede superar el 100%.';
            return null;
        }

        if ($spendRule === 'exact' && $minSpend <= 0) {
            $_SESSION['flash_error'] = 'Para la regla de monto exacto, especifica el monto requerido mayor a 0.';
            return null;
        }

        if ($spendRule === 'range' && ($maxSpend === null || $maxSpend <= $minSpend)) {
            $_SESSION['flash_error'] = 'Para un rango de compra, el monto máximo debe ser mayor al monto mínimo.';
            return null;
        }

        $dupSql = "SELECT id FROM " . Database::table('coupons') . " WHERE code = ?";
        $dupParams = [$code];
        if ($excludeId !== null) {
            $dupSql .= " AND id != ?";
            $dupParams[] = $excludeId;
        }
        if (Database::fetch($dupSql, $dupParams)) {
            $_SESSION['flash_error'] = "Ya existe un cupón con el código '{$code}'. Usa otro código o edita el existente.";
            return null;
        }

        return [
            'code' => $code,
            'description' => $description !== '' ? $description : null,
            'type' => $type,
            'value' => $value,
            'min_spend' => $minSpend,
            'spend_rule' => $spendRule,
            'max_spend' => $spendRule === 'range' ? $maxSpend : null,
            'usage_limit' => $usageLimit,
            'single_use_per_user' => $singleUsePerUser,
            'expires_at' => $expiresAt
        ];
    }

    public function couponStore(): void {
        $data = $this->parseAndValidateCouponInput(null);
        if ($data !== null) {
            $data['is_active'] = 1;
            Database::insert('coupons', $data);
            $_SESSION['flash_success'] = "Cupón '{$data['code']}' creado y activado con éxito.";
        }

        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/coupons');
        exit;
    }

    public function couponUpdate(string $id): void {
        $couponId = (int)$id;
        $coupon = Database::fetch("SELECT * FROM " . Database::table('coupons') . " WHERE id = ?", [$couponId]);
        if (!$coupon) {
            $_SESSION['flash_error'] = 'El cupón solicitado no existe.';
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/coupons');
            exit;
        }

        $data = $this->parseAndValidateCouponInput($couponId);
        if ($data !== null) {
            $data['is_active'] = isset($_POST['is_active']) ? ((int)$_POST['is_active'] ? 1 : 0) : (int)$coupon['is_active'];
            Database::update('coupons', $data, "id = :id", ['id' => $couponId]);
            $_SESSION['flash_success'] = "Cupón '{$data['code']}' actualizado correctamente.";
        }

        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/coupons');
        exit;
    }

    public function couponToggle(string $id): void {
        $couponId = (int)$id;
        $coupon = Database::fetch("SELECT id, is_active, code FROM " . Database::table('coupons') . " WHERE id = ?", [$couponId]);
        if ($coupon) {
            $newStatus = !empty($coupon['is_active']) ? 0 : 1;
            Database::update('coupons', ['is_active' => $newStatus], "id = :id", ['id' => $couponId]);
            $_SESSION['flash_success'] = $newStatus
                ? "Cupón '{$coupon['code']}' activado y disponible en la tienda."
                : "Cupón '{$coupon['code']}' deshabilitado (borrado lógico). Ya no podrá usarse en compras, pero se conserva su historial.";
        }
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/coupons');
        exit;
    }

    public function couponDelete(string $id): void {
        // Borrado lógico: deshabilitar el cupón en lugar de destruir el registro histórico
        $couponId = (int)$id;
        $coupon = Database::fetch("SELECT id, code FROM " . Database::table('coupons') . " WHERE id = ?", [$couponId]);
        if ($coupon) {
            Database::update('coupons', ['is_active' => 0], "id = :id", ['id' => $couponId]);
            $_SESSION['flash_success'] = "Cupón '{$coupon['code']}' deshabilitado (borrado lógico). Ya no podrá usarse en la tienda.";
        }
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/coupons');
        exit;
    }

    // ==========================================
    // MÓDULO 5: INVENTARIO Y ALERTAS DE STOCK
    // ==========================================
    public function inventory(): void {
        $alertFilter = $_GET['alert'] ?? '';
        $categoryFilter = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : 0;
        $search = trim($_GET['q'] ?? '');

        $allProducts = Database::fetchAll("
            SELECT p.*, c.name as category_name 
            FROM " . Database::table('products') . " p
            LEFT JOIN " . Database::table('categories') . " c ON p.category_id = c.id
            WHERE p.track_quantity = 1 AND p.is_active = 1
            ORDER BY (p.quantity <= p.low_stock_threshold) DESC, p.quantity ASC, p.id DESC
        ");

        $allVariants = Database::fetchAll("SELECT * FROM " . Database::table('product_variants') . " ORDER BY id ASC");
        $variantsByProduct = [];
        foreach ($allVariants as $v) {
            $variantsByProduct[(int)$v['product_id']][] = $v;
        }

        $counts = [
            'all' => 0,
            'low' => 0,
            'out' => 0,
            'ok'  => 0,
            'total_units' => 0,
            'retail_value' => 0.0,
            'cost_value' => 0.0,
        ];

        $filteredProducts = [];

        foreach ($allProducts as $p) {
            $pid = (int)$p['id'];
            $pVariants = (!empty($p['has_variants']) && !empty($variantsByProduct[$pid])) ? $variantsByProduct[$pid] : [];

            if (!empty($pVariants)) {
                $sumVarQty = array_sum(array_map(fn($x) => (int)$x['quantity'], $pVariants));
                if ((int)$p['quantity'] !== $sumVarQty) {
                    Database::update('products', ['quantity' => $sumVarQty], "id = :id", ['id' => $pid]);
                    $p['quantity'] = $sumVarQty;
                }
            }

            $p['variants'] = $pVariants;
            $threshold = max(0, (int)$p['low_stock_threshold']);
            $qty = max(0, (int)$p['quantity']);

            $hasZeroVariant = false;
            $hasLowVariant = false;
            if (!empty($pVariants)) {
                foreach ($pVariants as $v) {
                    $vq = (int)$v['quantity'];
                    if ($vq <= 0) {
                        $hasZeroVariant = true;
                    } elseif ($vq <= $threshold) {
                        $hasLowVariant = true;
                    }
                    $counts['retail_value'] += (float)$v['price'] * max(0, $vq);
                }
            } else {
                $counts['retail_value'] += (float)$p['price'] * $qty;
            }

            $counts['total_units'] += $qty;
            $counts['cost_value'] += (float)($p['cost_price'] ?? 0) * $qty;

            if ($qty <= 0 || $hasZeroVariant) {
                $p['alert_state'] = 'out';
                $counts['out']++;
            } elseif ($qty <= $threshold || $hasLowVariant) {
                $p['alert_state'] = 'low';
                $counts['low']++;
            } else {
                $p['alert_state'] = 'ok';
                $counts['ok']++;
            }
            $counts['all']++;

            // Aplicar filtros de vista
            if ($alertFilter !== '' && $p['alert_state'] !== $alertFilter) {
                continue;
            }
            if ($categoryFilter > 0 && (int)($p['category_id'] ?? 0) !== $categoryFilter) {
                continue;
            }
            if ($search !== '') {
                $haystack = strtolower($p['name'] . ' ' . ($p['sku'] ?? '') . ' ' . ($p['category_name'] ?? ''));
                foreach ($pVariants as $v) {
                    $haystack .= ' ' . strtolower($v['title'] . ' ' . ($v['sku'] ?? ''));
                }
                if (strpos($haystack, strtolower($search)) === false) {
                    continue;
                }
            }

            $filteredProducts[] = $p;
        }

        $movements = [];
        try {
            $movements = Database::fetchAll("
                SELECT m.*, p.name as product_name, p.sku as product_sku, v.title as variant_title, v.sku as variant_sku
                FROM " . Database::table('inventory_movements') . " m
                INNER JOIN " . Database::table('products') . " p ON m.product_id = p.id
                LEFT JOIN " . Database::table('product_variants') . " v ON m.variant_id = v.id
                ORDER BY m.id DESC
                LIMIT 20
            ");
        } catch (\Throwable $e) {
            $movements = [];
        }

        $categories = Database::fetchAll("SELECT id, name FROM " . Database::table('categories') . " WHERE is_active = 1 ORDER BY name ASC");
        $currency = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_currency_symbol'")['setting_value'] ?? '$';
        $globalLimit = (int)(Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'inventory_low_stock_limit'")['setting_value'] ?? 5);

        $this->render('inventory/index', [
            'products' => $filteredProducts,
            'counts' => $counts,
            'movements' => $movements,
            'categories' => $categories,
            'alertFilter' => $alertFilter,
            'categoryFilter' => $categoryFilter,
            'search' => $search,
            'currency' => $currency,
            'globalLimit' => $globalLimit,
            'pageTitle' => 'Inventario y Alertas de Stock'
        ]);
    }

    public function inventoryUpdate(): void {
        $movType = in_array($_POST['movement_type'] ?? 'adjustment', array_keys(InventoryManager::movementTypes()), true)
            ? $_POST['movement_type']
            : 'adjustment';
        $movNote = trim($_POST['movement_note'] ?? '');
        if ($movNote === '') {
            $movNote = InventoryManager::getTypeMeta($movType)['label'];
        }

        $updatedItems = 0;

        // 1. Modo Lote (tabla unificada con productos, variantes y umbrales)
        if (isset($_POST['products_qty']) || isset($_POST['variants_qty']) || isset($_POST['thresholds'])) {
            $productsQty = $_POST['products_qty'] ?? [];
            $variantsQty = $_POST['variants_qty'] ?? [];
            $thresholds = $_POST['thresholds'] ?? [];
            $parentsToSync = [];

            if (is_array($thresholds)) {
                foreach ($thresholds as $pid => $thVal) {
                    $prodId = (int)$pid;
                    $newTh = max(0, (int)$thVal);
                    $cur = Database::fetch("SELECT id, low_stock_threshold FROM " . Database::table('products') . " WHERE id = ?", [$prodId]);
                    if ($cur && (int)$cur['low_stock_threshold'] !== $newTh) {
                        Database::update('products', ['low_stock_threshold' => $newTh], "id = :id", ['id' => $prodId]);
                        $updatedItems++;
                    }
                }
            }

            if (is_array($productsQty)) {
                foreach ($productsQty as $pid => $qVal) {
                    $prodId = (int)$pid;
                    $newQty = max(0, (int)$qVal);
                    $cur = Database::fetch("SELECT id, quantity, has_variants FROM " . Database::table('products') . " WHERE id = ?", [$prodId]);
                    if ($cur && empty($cur['has_variants']) && (int)$cur['quantity'] !== $newQty) {
                        $prevQty = (int)$cur['quantity'];
                        Database::update('products', ['quantity' => $newQty], "id = :id", ['id' => $prodId]);
                        InventoryManager::logMovement($prodId, null, $prevQty, $newQty, $movType, 'Panel Inventario', $movNote);
                        if ($prevQty <= 0 && $newQty > 0) {
                            PluginManager::doAction('stock_replenished', $prodId, $newQty);
                        }
                        $updatedItems++;
                    }
                }
            }

            if (is_array($variantsQty)) {
                foreach ($variantsQty as $vid => $qVal) {
                    $varId = (int)$vid;
                    $newQty = max(0, (int)$qVal);
                    $curVar = Database::fetch("SELECT id, product_id, quantity FROM " . Database::table('product_variants') . " WHERE id = ?", [$varId]);
                    if ($curVar && (int)$curVar['quantity'] !== $newQty) {
                        $prevQty = (int)$curVar['quantity'];
                        $prodId = (int)$curVar['product_id'];
                        Database::update('product_variants', ['quantity' => $newQty], "id = :id", ['id' => $varId]);
                        InventoryManager::logMovement($prodId, $varId, $prevQty, $newQty, $movType, 'Panel Inventario', $movNote);
                        if (!isset($parentsToSync[$prodId])) {
                            $par = Database::fetch("SELECT quantity FROM " . Database::table('products') . " WHERE id = ?", [$prodId]);
                            $parentsToSync[$prodId] = (int)($par['quantity'] ?? 0);
                        }
                        $updatedItems++;
                    }
                }
            }

            foreach ($parentsToSync as $prodId => $prevParentQty) {
                $newParentQty = InventoryManager::syncParentProductStock((int)$prodId);
                if ($prevParentQty <= 0 && $newParentQty > 0) {
                    PluginManager::doAction('stock_replenished', (int)$prodId, $newParentQty);
                }
            }
        } elseif (!empty($_POST['product_id'])) {
            // 2. Modo Individual (compatibilidad hacia atrás)
            $productId = (int)$_POST['product_id'];
            $quantity = max(0, (int)($_POST['quantity'] ?? 0));
            $threshold = max(0, (int)($_POST['low_stock_threshold'] ?? 5));
            $cur = Database::fetch("SELECT id, quantity FROM " . Database::table('products') . " WHERE id = ?", [$productId]);
            if ($cur) {
                $prevQty = (int)$cur['quantity'];
                Database::update('products', [
                    'quantity' => $quantity,
                    'low_stock_threshold' => $threshold
                ], "id = :id", ['id' => $productId]);
                InventoryManager::logMovement($productId, null, $prevQty, $quantity, $movType, 'Panel Inventario', $movNote);
                if ($prevQty <= 0 && $quantity > 0) {
                    PluginManager::doAction('stock_replenished', $productId, $quantity);
                }
                $updatedItems++;
            }
        }

        $extraNotice = $_SESSION['flash_plugin_notice'] ?? '';
        unset($_SESSION['flash_plugin_notice']);
        $_SESSION['flash_success'] = ($updatedItems > 0
            ? "Inventario actualizado ({$updatedItems} cambio(s) registrados en el Kardex)."
            : "No se detectaron cambios en las cantidades de inventario.") . ($extraNotice ? ' ' . $extraNotice : '');
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/inventory');
        exit;
    }

    // ==========================================
    // MÓDULO 6: RESEÑAS Y CALIFICACIONES
    // ==========================================
    public function reviews(): void {
        $statusFilter = $_GET['status'] ?? '';
        $ratingFilter = isset($_GET['rating']) && $_GET['rating'] !== '' ? (int)$_GET['rating'] : 0;
        $search = trim($_GET['q'] ?? '');

        $sql = "SELECT r.*, 
                       p.name as product_name, 
                       p.slug as product_slug,
                       EXISTS(
                           SELECT 1 FROM " . Database::table('orders') . " o
                           INNER JOIN " . Database::table('order_items') . " oi ON oi.order_id = o.id
                           WHERE LOWER(o.customer_email) = LOWER(r.customer_email)
                             AND oi.product_id = r.product_id
                             AND o.status != 'cancelled'
                       ) as is_verified_buyer
                FROM " . Database::table('reviews') . " r
                JOIN " . Database::table('products') . " p ON r.product_id = p.id
                WHERE 1=1";
        $params = [];

        if (in_array($statusFilter, ['pending', 'approved', 'rejected', 'hidden'], true)) {
            $sql .= " AND r.status = ?";
            $params[] = $statusFilter;
        }
        if ($ratingFilter >= 1 && $ratingFilter <= 5) {
            $sql .= " AND r.rating = ?";
            $params[] = $ratingFilter;
        }
        if ($search !== '') {
            $sql .= " AND (p.name LIKE ? OR r.customer_name LIKE ? OR r.customer_email LIKE ? OR r.title LIKE ? OR r.comment LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= " ORDER BY r.is_featured DESC, r.id DESC";

        $reviews = Database::fetchAll($sql, $params);

        $allStatsRows = Database::fetchAll("SELECT rating, status, admin_reply FROM " . Database::table('reviews'));
        $counts = ['all' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0, 'hidden' => 0];
        $starBreakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $ratingSum = 0;
        $activeRatingCount = 0;
        $repliedCount = 0;
        $positiveCount = 0;

        foreach ($allStatsRows as $row) {
            $st = $row['status'];
            $counts['all']++;
            if (isset($counts[$st])) {
                $counts[$st]++;
            }
            if ($st !== 'hidden') {
                $rt = max(1, min(5, (int)$row['rating']));
                $starBreakdown[$rt]++;
                $ratingSum += $rt;
                $activeRatingCount++;
                if ($rt >= 4) {
                    $positiveCount++;
                }
                if (!empty(trim((string)($row['admin_reply'] ?? '')))) {
                    $repliedCount++;
                }
            }
        }

        $avgRating = $activeRatingCount > 0 ? round($ratingSum / $activeRatingCount, 1) : 0.0;
        $satisfactionPct = $activeRatingCount > 0 ? (int)round(($positiveCount / $activeRatingCount) * 100) : 0;

        $this->render('reviews/index', [
            'reviews' => $reviews,
            'statusFilter' => $statusFilter,
            'ratingFilter' => $ratingFilter,
            'search' => $search,
            'counts' => $counts,
            'starBreakdown' => $starBreakdown,
            'activeRatingCount' => $activeRatingCount,
            'avgRating' => $avgRating,
            'satisfactionPct' => $satisfactionPct,
            'repliedCount' => $repliedCount,
            'pageTitle' => 'Reseñas y Calificaciones'
        ]);
    }

    public function reviewUpdateStatus(string $id): void {
        $status = $_POST['status'] ?? 'approved';
        if ($status === 'delete') {
            $status = 'hidden';
        }
        $allowed = ['pending', 'approved', 'rejected', 'hidden'];
        if (in_array($status, $allowed, true)) {
            Database::update('reviews', ['status' => $status], "id = :id", ['id' => (int)$id]);
            $_SESSION['flash_success'] = ($status === 'hidden')
                ? 'Reseña deshabilitada y ocultada de la tienda (borrado lógico: el registro se conserva en la base de datos).'
                : 'Estado de la reseña actualizado.';
        }

        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/reviews');
        exit;
    }

    public function reviewReply(string $id): void {
        $reviewId = (int)$id;
        $review = Database::fetch("SELECT id, status FROM " . Database::table('reviews') . " WHERE id = ?", [$reviewId]);
        if (!$review) {
            $_SESSION['flash_error'] = 'La reseña no existe.';
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/reviews');
            exit;
        }

        $reply = trim($_POST['admin_reply'] ?? '');
        $approveOnReply = !empty($_POST['approve_on_reply']);
        $updateData = [
            'admin_reply' => $reply !== '' ? $reply : null,
            'admin_replied_at' => $reply !== '' ? date('Y-m-d H:i:s') : null
        ];
        if ($approveOnReply && $review['status'] === 'pending') {
            $updateData['status'] = 'approved';
        }

        Database::update('reviews', $updateData, "id = :id", ['id' => $reviewId]);
        $_SESSION['flash_success'] = $reply !== ''
            ? 'Respuesta oficial guardada correctamente.'
            : 'Respuesta eliminada de la reseña.';

        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/reviews');
        exit;
    }

    public function reviewToggleFeatured(string $id): void {
        $reviewId = (int)$id;
        $review = Database::fetch("SELECT id, is_featured FROM " . Database::table('reviews') . " WHERE id = ?", [$reviewId]);
        if ($review) {
            $next = !empty($review['is_featured']) ? 0 : 1;
            Database::update('reviews', ['is_featured' => $next], "id = :id", ['id' => $reviewId]);
            $_SESSION['flash_success'] = $next
                ? 'Reseña fijada como Destacada. Aparecerá primero en la página del producto.'
                : 'Se quitó la marca de Reseña Destacada.';
        }

        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/reviews');
        exit;
    }

    // ==========================================
    // GESTIÓN DE PLUGINS (SUBIDA ZIP)
    // ==========================================
    public function plugins(): void {
        $plugins = PluginManager::all();

        $this->render('plugins/index', [
            'plugins' => $plugins
        ]);
    }

    public function pluginUpload(): void {
        if (!empty($_FILES['plugin_zip']['name'])) {
            try {
                $res = PluginManager::installZip($_FILES['plugin_zip']);
                $_SESSION['flash_success'] = $res['message'];
            } catch (Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/plugins');
        exit;
    }

    public function pluginToggle(string $slug): void {
        $plugin = Database::fetch("SELECT is_active FROM " . Database::table('plugins') . " WHERE slug = ?", [$slug]);
        if ($plugin) {
            $newStatus = $plugin['is_active'] ? 0 : 1;
            PluginManager::toggle($slug, (bool)$newStatus);
            $_SESSION['flash_success'] = "Estado del plugin modificado.";
        }
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/plugins');
        exit;
    }

    public function pluginDelete(string $slug): void {
        PluginManager::delete($slug);
        $_SESSION['flash_success'] = "Plugin eliminado correctamente.";
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/plugins');
        exit;
    }

    // ==========================================
    // GESTIÓN DE TEMAS
    // ==========================================
    public function themes(): void {
        $themes = ThemeManager::all();
        $activeTheme = ThemeManager::getActiveTheme();

        $this->render('themes/index', [
            'themes' => $themes,
            'activeTheme' => $activeTheme
        ]);
    }

    public function themeUpload(): void {
        if (!empty($_FILES['theme_zip']['name'])) {
            try {
                $res = ThemeManager::installZip($_FILES['theme_zip']);
                $_SESSION['flash_success'] = $res['message'];
            } catch (Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/themes');
        exit;
    }

    public function themeActivate(string $slug): void {
        ThemeManager::setActiveTheme($slug);
        $_SESSION['flash_success'] = "Tema '{$slug}' activado exitosamente.";
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/themes');
        exit;
    }

    public function themeDelete(string $slug): void {
        try {
            ThemeManager::delete($slug);
            $_SESSION['flash_success'] = "Tema '{$slug}' eliminado correctamente.";
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/themes');
        exit;
    }

    public function themeCustomize(string $slug): void {
        $cleanSlug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
        $themes = ThemeManager::all();
        $themeMeta = null;
        foreach ($themes as $t) {
            if ($t['slug'] === $cleanSlug) {
                $themeMeta = $t;
                break;
            }
        }
        if (!$themeMeta) {
            $_SESSION['flash_error'] = "La plantilla '{$cleanSlug}' no existe.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/themes');
            exit;
        }

        $activeTheme = ThemeManager::getActiveTheme();
        $themeConfig = ThemeCustomizer::getConfig($cleanSlug, true);
        $blockTypes = ThemeCustomizer::availableBlockTypes();
        $sampleProduct = Database::fetch("SELECT slug FROM " . Database::table('products') . " WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
        $sampleProductSlug = $sampleProduct['slug'] ?? '';
        $allCategories = Database::fetchAll("SELECT id, name, slug, parent_id FROM " . Database::table('categories') . " WHERE is_active = 1 ORDER BY COALESCE(parent_id, id) ASC, parent_id ASC, name ASC");
        $storeName = Settings::get('store_name', 'ShopCMS Store');
        $baseUrl = defined('APP_URL') ? APP_URL : '';

        require dirname(__DIR__) . '/Views/admin/themes/customize.php';
    }

    public function themeCustomizeSave(string $slug): void {
        header('Content-Type: application/json; charset=utf-8');
        $cleanSlug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw ?: '{}', true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Payload JSON inválido.']);
            exit;
        }

        $saved = ThemeCustomizer::saveConfig($cleanSlug, $payload);
        echo json_encode(['ok' => true, 'config' => $saved, 'message' => 'Diseño guardado correctamente.']);
        exit;
    }

    public function themeCustomizeReset(string $slug): void {
        header('Content-Type: application/json; charset=utf-8');
        $cleanSlug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
        $defaults = ThemeCustomizer::resetConfig($cleanSlug);
        echo json_encode(['ok' => true, 'config' => $defaults, 'message' => 'Diseño restablecido a valores de fábrica.']);
        exit;
    }

    public function themeCustomizeUploadAsset(string $slug): void {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_FILES['asset_file']['tmp_name']) || $_FILES['asset_file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No se recibió una imagen válida.']);
            exit;
        }

        $ext = strtolower(pathinfo((string)$_FILES['asset_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
        if (!in_array($ext, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Formato permitido: PNG, JPG, WEBP o SVG.']);
            exit;
        }

        if ((int)$_FILES['asset_file']['size'] > 4 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'La imagen no debe superar los 4 MB.']);
            exit;
        }

        $cleanSlug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
        $uploadDir = dirname(__DIR__, 2) . '/assets/images/themes';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = $cleanSlug . '-' . time() . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
        $dest = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($_FILES['asset_file']['tmp_name'], $dest)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el archivo en el servidor.']);
            exit;
        }

        echo json_encode([
            'ok' => true,
            'path' => 'assets/images/themes/' . $filename
        ]);
        exit;
    }

    // ==========================================
    // AJUSTES GENERALES
    // ==========================================
    public function settings(): void {
        $settings = Settings::all(true);
        $hasPaymentPlugin = false;
        try {
            $pg = Database::fetch("SELECT id FROM " . Database::table('plugins') . " WHERE slug = 'payment-gateway' AND is_active = 1");
            $hasPaymentPlugin = !empty($pg);
        } catch (Exception $e) {}

        $this->render('settings/index', [
            'pageTitle' => 'Ajustes de la Tienda',
            'settings' => $settings,
            'hasPaymentPlugin' => $hasPaymentPlugin
        ]);
    }

    public function settingsSave(): void {
        $defaults = Settings::defaults();
        $boolKeys = [
            'tax_included',
            'enable_payment_cod',
            'enable_payment_bank',
            'reviews_auto_approve',
            'inventory_low_stock_notification',
            'allow_backorder',
            'announcement_enabled',
            'whatsapp_widget_enabled',
            'maintenance_mode',
        ];
        $floatKeys = [
            'tax_rate',
            'shipping_flat_rate',
            'shipping_free_threshold',
            'order_min_amount',
        ];

        $toSave = [];
        foreach ($defaults as $key => $defaultVal) {
            if (in_array($key, $boolKeys, true)) {
                $toSave[$key] = !empty($_POST[$key]) && $_POST[$key] !== '0' ? '1' : '0';
                continue;
            }

            if (!array_key_exists($key, $_POST)) {
                continue;
            }

            $raw = trim((string)$_POST[$key]);

            if (in_array($key, $floatKeys, true)) {
                $num = is_numeric($raw) ? max(0.0, (float)$raw) : (float)$defaultVal;
                if ($key === 'tax_rate') {
                    $num = min(100.0, $num);
                }
                $toSave[$key] = rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.') ?: '0';
            } elseif ($key === 'inventory_low_stock_limit') {
                $toSave[$key] = (string)max(0, (int)$raw);
            } elseif ($key === 'order_prefix') {
                $clean = preg_replace('/[^A-Za-z0-9#\-_]/', '', $raw);
                $toSave[$key] = $clean !== '' ? substr($clean, 0, 12) : '#ORD-';
            } elseif ($key === 'store_currency') {
                $clean = strtoupper(preg_replace('/[^A-Za-z]/', '', $raw));
                $toSave[$key] = $clean !== '' ? substr($clean, 0, 5) : 'USD';
            } elseif ($key === 'store_currency_symbol') {
                $toSave[$key] = $raw !== '' ? mb_substr($raw, 0, 6) : '$';
            } elseif ($key === 'store_industry') {
                $allowedIndustries = ['fashion', 'electronics', 'food', 'general'];
                $toSave[$key] = in_array($raw, $allowedIndustries, true) ? $raw : 'general';
            } elseif ($key === 'announcement_bg') {
                $toSave[$key] = preg_match('/^#[0-9A-Fa-f]{6}$/', $raw) ? $raw : '#008060';
            } else {
                $toSave[$key] = $raw;
            }
        }

        // Salvaguarda: asegurar al menos 1 método de pago si no hay plugin de pasarela activo
        $notice = '';
        $hasPaymentPlugin = false;
        try {
            $pg = Database::fetch("SELECT id FROM " . Database::table('plugins') . " WHERE slug = 'payment-gateway' AND is_active = 1");
            $hasPaymentPlugin = !empty($pg);
        } catch (Exception $e) {}

        if (($toSave['enable_payment_cod'] ?? '1') === '0'
            && ($toSave['enable_payment_bank'] ?? '1') === '0'
            && !$hasPaymentPlugin) {
            $toSave['enable_payment_cod'] = '1';
            $notice = ' Se mantuvo activo Pago Contra Entrega porque no hay otra pasarela habilitada.';
        }

        Settings::saveMany($toSave);

        $_SESSION['flash_success'] = "Configuración guardada correctamente." . $notice;
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/settings');
        exit;
    }

    // ==========================================
    // MÓDULO DE GESTIÓN DE CLIENTES
    // ==========================================
    public function customers(): void {
        // Auto-sincronizar clientes provenientes de pedidos o reseñas para garantizar consistencia total
        try {
            Database::query("
                INSERT IGNORE INTO " . Database::table('users') . " (name, email, password, phone, address, role, is_active, created_at)
                SELECT o.customer_name, o.customer_email, '', o.customer_phone, o.shipping_address, 'customer', 1, o.created_at
                FROM " . Database::table('orders') . " o
                WHERE o.customer_email NOT IN (SELECT email FROM " . Database::table('users') . ")
                GROUP BY o.customer_email
            ");

            Database::query("
                INSERT IGNORE INTO " . Database::table('users') . " (name, email, password, phone, address, role, is_active, created_at)
                SELECT r.customer_name, r.customer_email, '', NULL, NULL, 'customer', 1, r.created_at
                FROM " . Database::table('reviews') . " r
                WHERE r.customer_email NOT IN (SELECT email FROM " . Database::table('users') . ")
                GROUP BY r.customer_email
            ");

            Database::query("
                UPDATE " . Database::table('orders') . " o
                INNER JOIN " . Database::table('users') . " u ON o.customer_email = u.email
                SET o.customer_id = u.id
                WHERE o.customer_id IS NULL
            ");
        } catch (\Exception $e) {
            // Continuar si la BD aún no cuenta con alguna tabla
        }

        $search = trim($_GET['q'] ?? '');
        $statusFilter = $_GET['status'] ?? '';

        $sql = "
            SELECT 
                u.id, 
                u.name, 
                u.email, 
                u.phone, 
                u.address, 
                u.is_active,
                u.created_at,
                COUNT(o.id) as total_orders,
                COALESCE(SUM(o.total), 0) as total_spent,
                MAX(o.created_at) as last_order_date
            FROM " . Database::table('users') . " u
            LEFT JOIN " . Database::table('orders') . " o ON (o.customer_id = u.id OR o.customer_email = u.email)
            WHERE u.role = 'customer'
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($statusFilter === 'active') {
            $sql .= " AND u.is_active = 1";
        } elseif ($statusFilter === 'disabled') {
            $sql .= " AND u.is_active = 0";
        }

        $sql .= " GROUP BY u.id, u.name, u.email, u.phone, u.address, u.is_active, u.created_at ORDER BY total_spent DESC, u.id DESC";
        $customers = Database::fetchAll($sql, $params);

        $currency = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_currency_symbol'")['setting_value'] ?? '$';
        $totalActive = (int)(Database::fetch("SELECT COUNT(*) as count FROM " . Database::table('users') . " WHERE role = 'customer' AND is_active = 1")['count'] ?? 0);
        $totalDisabled = (int)(Database::fetch("SELECT COUNT(*) as count FROM " . Database::table('users') . " WHERE role = 'customer' AND is_active = 0")['count'] ?? 0);

        $globalStats = Database::fetch("
            SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(o.total), 0) as total_spent
            FROM " . Database::table('orders') . " o
            INNER JOIN " . Database::table('users') . " u ON (o.customer_id = u.id OR o.customer_email = u.email)
            WHERE u.role = 'customer'
        ");

        $this->render('customers/index', [
            'customers' => $customers,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'totalActive' => $totalActive,
            'totalDisabled' => $totalDisabled,
            'totalCustomers' => $totalActive + $totalDisabled,
            'totalOrdersSum' => (int)($globalStats['total_orders'] ?? 0),
            'totalSpentSum' => (float)($globalStats['total_spent'] ?? 0),
            'currency' => $currency,
            'pageTitle' => 'Gestión de Clientes'
        ]);
    }

    public function customerStore(): void {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = "Ingresa un nombre completo y un correo electrónico válido.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/customers');
            exit;
        }

        $existing = Database::fetch("SELECT id FROM " . Database::table('users') . " WHERE email = ?", [$email]);
        if ($existing) {
            $_SESSION['flash_error'] = "Ya existe una cuenta registrada con el correo '{$email}'.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/customers');
            exit;
        }

        $hashed = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : '';
        Database::insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $hashed,
            'phone' => $phone !== '' ? $phone : null,
            'address' => $address !== '' ? $address : null,
            'role' => 'customer',
            'is_active' => 1
        ]);

        $_SESSION['flash_success'] = "Cliente '{$name}' registrado correctamente.";
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/customers');
        exit;
    }

    public function customerView(string $id): void {
        $custId = (int)$id;
        $customer = Database::fetch("SELECT * FROM " . Database::table('users') . " WHERE id = ? AND role = 'customer'", [$custId]);
        if (!$customer) {
            $_SESSION['flash_error'] = "Cliente no encontrado.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/customers');
            exit;
        }

        // Historial de pedidos
        $orders = Database::fetchAll("
            SELECT * FROM " . Database::table('orders') . "
            WHERE customer_id = ? OR customer_email = ?
            ORDER BY id DESC
        ", [$customer['id'], $customer['email']]);

        // Reseñas hechas por el cliente
        $reviews = Database::fetchAll("
            SELECT r.*, p.name as product_name, p.slug as product_slug
            FROM " . Database::table('reviews') . " r
            INNER JOIN " . Database::table('products') . " p ON r.product_id = p.id
            WHERE r.customer_email = ?
            ORDER BY r.id DESC
        ", [$customer['email']]);

        $totalSpent = array_sum(array_column($orders, 'total'));
        $currency = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'store_currency_symbol'")['setting_value'] ?? '$';

        $this->render('customers/view', [
            'customer' => $customer,
            'orders' => $orders,
            'reviews' => $reviews,
            'totalSpent' => $totalSpent,
            'currency' => $currency,
            'pageTitle' => 'Detalle del Cliente — ' . $customer['name']
        ]);
    }

    public function customerUpdate(string $id): void {
        $custId = (int)$id;
        $customer = Database::fetch("SELECT * FROM " . Database::table('users') . " WHERE id = ? AND role = 'customer'", [$custId]);
        if (!$customer) {
            $_SESSION['flash_error'] = "El cliente no existe.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/customers');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $isActive = isset($_POST['is_active']) ? ((int)$_POST['is_active'] ? 1 : 0) : (int)($customer['is_active'] ?? 1);

        if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = "El nombre y un correo electrónico válido son obligatorios.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/customers/view/' . $custId);
            exit;
        }

        $duplicate = Database::fetch("SELECT id FROM " . Database::table('users') . " WHERE email = ? AND id != ?", [$email, $custId]);
        if ($duplicate) {
            $_SESSION['flash_error'] = "El correo '{$email}' ya está en uso por otro usuario.";
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/customers/view/' . $custId);
            exit;
        }

        $oldEmail = $customer['email'];
        Database::update('users', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'address' => $address !== '' ? $address : null,
            'is_active' => $isActive
        ], "id = :id", ['id' => $custId]);

        // Mantener sincronizados pedidos y reseñas si se actualizó el correo
        if (strcasecmp($oldEmail, $email) !== 0) {
            Database::query("UPDATE " . Database::table('orders') . " SET customer_email = ? WHERE customer_id = ? OR customer_email = ?", [$email, $custId, $oldEmail]);
            Database::query("UPDATE " . Database::table('reviews') . " SET customer_email = ? WHERE customer_email = ?", [$email, $oldEmail]);
        }

        $_SESSION['flash_success'] = "Información del cliente '{$name}' actualizada correctamente.";
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/customers/view/' . $custId);
        exit;
    }

    public function customerToggle(string $id): void {
        $custId = (int)$id;
        $customer = Database::fetch("SELECT id, name, is_active FROM " . Database::table('users') . " WHERE id = ? AND role = 'customer'", [$custId]);
        if ($customer) {
            $newStatus = !empty($customer['is_active']) ? 0 : 1;
            Database::update('users', ['is_active' => $newStatus], "id = :id", ['id' => $custId]);
            $_SESSION['flash_success'] = $newStatus
                ? "Cliente '{$customer['name']}' reactivado. Puede volver a iniciar sesión y comprar."
                : "Cliente '{$customer['name']}' deshabilitado (borrado lógico). Su acceso web fue bloqueado manteniendo intacto su historial de pedidos.";
        }

        $redirectTo = (($_POST['redirect'] ?? ($_GET['redirect'] ?? '')) === 'view') ? '/admin/customers/view/' . $custId : '/admin/customers';
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . $redirectTo);
        exit;
    }

    public function customerDelete(string $id): void {
        $custId = (int)$id;
        $customer = Database::fetch("SELECT id, name FROM " . Database::table('users') . " WHERE id = ? AND role = 'customer'", [$custId]);
        if ($customer) {
            Database::update('users', ['is_active' => 0], "id = :id", ['id' => $custId]);
            $_SESSION['flash_success'] = "Cliente '{$customer['name']}' deshabilitado (borrado lógico). Sus compras y reseñas históricas permanecen seguras.";
        }
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/customers');
        exit;
    }

    // ==========================================
    // PÁGINAS LEGALES CMS & NEWSLETTER
    // ==========================================
    public function pages(): void {
        \ShopCMS\Core\ContentManager::ensureSchema();
        $pages = \ShopCMS\Core\ContentManager::getAllPages();
        $subscribers = \ShopCMS\Core\ContentManager::getSubscribers();
        $editingPage = null;
        if (!empty($_GET['edit'])) {
            $editingPage = Database::fetch("SELECT * FROM " . Database::table('pages') . " WHERE id = ?", [(int)$_GET['edit']]);
        }

        $this->render('pages/index', [
            'pageTitle' => 'Páginas Legales y Boletín',
            'pages' => $pages,
            'subscribers' => $subscribers,
            'editingPage' => $editingPage,
        ]);
    }

    public function pageStore(): void {
        \ShopCMS\Core\ContentManager::ensureSchema();
        $title = trim($_POST['title'] ?? '');
        $rawSlug = trim($_POST['slug'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $isPublished = !empty($_POST['is_published']) ? 1 : 0;
        $showInFooter = !empty($_POST['show_in_footer']) ? 1 : 0;

        if ($title === '' || $content === '') {
            $_SESSION['flash_error'] = 'El título y contenido de la página son obligatorios.';
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/pages');
            exit;
        }

        $slug = \ShopCMS\Core\ContentManager::slugify($rawSlug !== '' ? $rawSlug : $title);
        $dup = Database::fetch("SELECT id FROM " . Database::table('pages') . " WHERE slug = ?", [$slug]);
        if ($dup) {
            $slug .= '-' . substr(uniqid(), -4);
        }

        Database::insert('pages', [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'is_published' => $isPublished,
            'show_in_footer' => $showInFooter,
        ]);

        $_SESSION['flash_success'] = "Página '{$title}' publicada en /page/{$slug}.";
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/pages');
        exit;
    }

    public function pageUpdate(string $id): void {
        \ShopCMS\Core\ContentManager::ensureSchema();
        $pageId = (int)$id;
        $existing = Database::fetch("SELECT * FROM " . Database::table('pages') . " WHERE id = ?", [$pageId]);
        if (!$existing) {
            $_SESSION['flash_error'] = 'Página no encontrada.';
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/pages');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $rawSlug = trim($_POST['slug'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $isPublished = !empty($_POST['is_published']) ? 1 : 0;
        $showInFooter = !empty($_POST['show_in_footer']) ? 1 : 0;

        if ($title === '' || $content === '') {
            $_SESSION['flash_error'] = 'El título y contenido son obligatorios.';
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/pages?edit=' . $pageId);
            exit;
        }

        $slug = \ShopCMS\Core\ContentManager::slugify($rawSlug !== '' ? $rawSlug : $title);
        $dup = Database::fetch("SELECT id FROM " . Database::table('pages') . " WHERE slug = ? AND id != ?", [$slug, $pageId]);
        if ($dup) {
            $slug .= '-' . $pageId;
        }

        Database::update('pages', [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'is_published' => $isPublished,
            'show_in_footer' => $showInFooter,
        ], 'id = :id', ['id' => $pageId]);

        $_SESSION['flash_success'] = "Página '{$title}' actualizada correctamente.";
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/pages');
        exit;
    }

    public function pageDelete(string $id): void {
        \ShopCMS\Core\ContentManager::ensureSchema();
        Database::delete('pages', 'id = ?', [(int)$id]);
        $_SESSION['flash_success'] = 'Página eliminada correctamente.';
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/pages');
        exit;
    }

    public function pageSeedLegal(): void {
        $storeName = Settings::get('store_name', 'Nuestra Tienda');
        $created = \ShopCMS\Core\ContentManager::seedRecommendedLegalPages($storeName);
        $_SESSION['flash_success'] = $created > 0
            ? "Se generaron {$created} páginas legales recomendadas y ya aparecen en el footer."
            : 'Todas las plantillas legales recomendadas ya estaban creadas.';
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/pages');
        exit;
    }

    public function newsletterExport(): void {
        $subscribers = \ShopCMS\Core\ContentManager::getSubscribers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="suscriptores-newsletter-' . date('Ymd-His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Email', 'Origen', 'Estado', 'Fecha Registro']);
        foreach ($subscribers as $s) {
            fputcsv($out, [
                $s['id'],
                $s['email'],
                $s['source'] ?? 'footer',
                $s['status'] ?? 'active',
                $s['created_at'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function newsletterDelete(string $id): void {
        \ShopCMS\Core\ContentManager::ensureSchema();
        Database::delete('newsletter_subscribers', 'id = ?', [(int)$id]);
        $_SESSION['flash_success'] = 'Suscriptor eliminado del boletín.';
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/admin/pages');
        exit;
    }
}

