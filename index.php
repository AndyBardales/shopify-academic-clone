<?php
/**
 * ShopCMS - Punto de Entrada Principal
 */

// 1. Verificar si el sistema ya fue instalado o tiene configuraciÃ³n
if (!file_exists(__DIR__ . '/config.php')) {
    header('Location: install/');
    exit;
}

require_once __DIR__ . '/config.php';

// 2. Autoloader PSR-4 para clases del CMS
spl_autoload_register(function ($class) {
    $prefix = 'ShopCMS\\';
    $baseDir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// 2.5 Verificar Salud de la Base de Datos (Evitar Fatal Errors si MySQL estÃ¡ iniciando o faltan tablas)
try {
    $pdo = \ShopCMS\Core\Database::getInstance();
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'shop_';
    $checkTable = $pdo->query("SHOW TABLES LIKE '{$prefix}settings'")->fetch();
    if (!$checkTable) {
        // La base de datos existe pero estÃ¡ vacÃ­a o faltan tablas
        header('Location: install/?action=repair&reason=missing_tables');
        exit;
    }
} catch (\Throwable $e) {
    // Si la conexiÃ³n fallÃ³ (ej. MySQL en XAMPP no ha iniciado todavÃ­a al encender el equipo)
    // NO redirigir intempestivamente al instalador. Mostrar pantalla amigable de espera y reintento.
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ShopCMS â€” Esperando ConexiÃ³n a Base de Datos</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #F6F6F7; color: #202223; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
            .card { background: #FFFFFF; max-width: 520px; width: 100%; padding: 36px; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); text-align: center; }
            .icon { width: 54px; height: 54px; background: #FEF3C7; color: #B45309; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; }
            h1 { font-size: 20px; font-weight: 700; margin-bottom: 12px; color: #111827; }
            p { font-size: 14px; color: #6B7280; line-height: 1.6; margin-bottom: 24px; }
            .btn-primary { background: #008060; color: #FFF; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; display: inline-block; cursor: pointer; border: none; }
            .btn-primary:hover { background: #006e52; }
            .btn-secondary { color: #6B7280; text-decoration: underline; font-size: 13px; display: block; margin-top: 16px; }
            .error-box { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 6px; padding: 10px; font-family: monospace; font-size: 12px; color: #DC2626; margin-bottom: 20px; text-align: left; overflow-x: auto; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                </svg>
            </div>
            <h1>Iniciando ConexiÃ³n con XAMPP MySQL</h1>
            <p>
                No se pudo establecer conexiÃ³n inmediata con el servidor de base de datos MySQL (puerto 3306). Si acabas de encender XAMPP, asegÃºrate de que el mÃ³dulo <strong>MySQL</strong> tenga el botÃ³n <strong>"Start"</strong> activado.
            </p>
            <div class="error-box"><?= htmlspecialchars($e->getMessage()) ?></div>
            <button class="btn-primary" onclick="window.location.reload();">Reintentar ConexiÃ³n</button>
            <a href="install/?action=repair&reason=db_error" class="btn-secondary">Ir al Asistente de ReparaciÃ³n / Instalador</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 3. Inicializar SesiÃ³n y Plugins
\ShopCMS\Core\Auth::init();
\ShopCMS\Core\Database::ensureSoftDeleteSchema();
\ShopCMS\Core\PluginManager::loadActivePlugins();
\ShopCMS\Core\PluginManager::doAction('init');

// 4. Configurar Rutas con el Router
use ShopCMS\Core\Router;
use ShopCMS\Controllers\AdminController;
use ShopCMS\Controllers\StoreController;

// --- Rutas del Panel de AdministraciÃ³n (Polaris) ---
Router::get('/admin', [AdminController::class, 'dashboard']);
Router::get('/admin/login', [AdminController::class, 'loginForm']);
Router::post('/admin/login', [AdminController::class, 'loginSubmit']);
Router::get('/admin/logout', [AdminController::class, 'logout']);

// MÃ³dulo 1: CatÃ¡logo de Productos y Variantes
Router::get('/admin/products', [AdminController::class, 'products']);
Router::get('/admin/products/create', [AdminController::class, 'productCreate']);
Router::post('/admin/products/store', [AdminController::class, 'productStore']);
Router::get('/admin/products/edit/{id}', [AdminController::class, 'productEdit']);
Router::post('/admin/products/update/{id}', [AdminController::class, 'productUpdate']);
Router::get('/admin/products/toggle/{id}', [AdminController::class, 'productToggle']);
Router::get('/admin/products/delete/{id}', [AdminController::class, 'productDelete']);

// GestiÃ³n de CategorÃ­as JerÃ¡rquicas y Presets
Router::get('/admin/categories', [AdminController::class, 'categories']);
Router::post('/admin/categories/store', [AdminController::class, 'categoryStore']);
Router::post('/admin/categories/update/{id}', [AdminController::class, 'categoryUpdate']);
Router::get('/admin/categories/toggle/{id}', [AdminController::class, 'categoryToggle']);
Router::get('/admin/categories/delete/{id}', [AdminController::class, 'categoryDelete']);
Router::get('/admin/categories/seed-preset', [AdminController::class, 'categorySeedPreset']);

// MÃ³dulo 3: GestiÃ³n de Pedidos y Estados
Router::get('/admin/orders', [AdminController::class, 'orders']);
Router::get('/admin/orders/view/{id}', [AdminController::class, 'orderView']);
Router::get('/admin/orders/receipt/{id}', [AdminController::class, 'orderReceipt']);
Router::post('/admin/orders/update-status/{id}', [AdminController::class, 'orderUpdateStatus']);

// MÃ³dulo 4: Cupones y Descuentos
Router::get('/admin/coupons', [AdminController::class, 'coupons']);
Router::post('/admin/coupons/store', [AdminController::class, 'couponStore']);
Router::post('/admin/coupons/update/{id}', [AdminController::class, 'couponUpdate']);
Router::get('/admin/coupons/toggle/{id}', [AdminController::class, 'couponToggle']);
Router::get('/admin/coupons/delete/{id}', [AdminController::class, 'couponDelete']);

// GestiÃ³n de Clientes (Personas que compran en la tienda)
Router::get('/admin/customers', [AdminController::class, 'customers']);
Router::post('/admin/customers/store', [AdminController::class, 'customerStore']);
Router::get('/admin/customers/view/{id}', [AdminController::class, 'customerView']);
Router::post('/admin/customers/update/{id}', [AdminController::class, 'customerUpdate']);
Router::get('/admin/customers/toggle/{id}', [AdminController::class, 'customerToggle']);
Router::get('/admin/customers/delete/{id}', [AdminController::class, 'customerDelete']);

// MÃ³dulo 5: Inventario y Alertas de Stock
Router::get('/admin/inventory', [AdminController::class, 'inventory']);
Router::post('/admin/inventory/update', [AdminController::class, 'inventoryUpdate']);

// MÃ³dulo 6: ReseÃ±as y Calificaciones
Router::get('/admin/reviews', [AdminController::class, 'reviews']);
Router::post('/admin/reviews/update-status/{id}', [AdminController::class, 'reviewUpdateStatus']);
Router::post('/admin/reviews/reply/{id}', [AdminController::class, 'reviewReply']);
Router::get('/admin/reviews/toggle-featured/{id}', [AdminController::class, 'reviewToggleFeatured']);

// GestiÃ³n de Plugins (Subida ZIP y activaciÃ³n)
Router::get('/admin/plugins', [AdminController::class, 'plugins']);
Router::post('/admin/plugins/upload', [AdminController::class, 'pluginUpload']);
Router::get('/admin/plugins/toggle/{slug}', [AdminController::class, 'pluginToggle']);
Router::get('/admin/plugins/delete/{slug}', [AdminController::class, 'pluginDelete']);

// GestiÃ³n de Temas
Router::get('/admin/themes', [AdminController::class, 'themes']);
Router::post('/admin/themes/upload', [AdminController::class, 'themeUpload']);
Router::get('/admin/themes/activate/{slug}', [AdminController::class, 'themeActivate']);
Router::get('/admin/themes/delete/{slug}', [AdminController::class, 'themeDelete']);
Router::get('/admin/themes/customize/{slug}', [AdminController::class, 'themeCustomize']);
Router::post('/admin/themes/customize/{slug}/save', [AdminController::class, 'themeCustomizeSave']);
Router::post('/admin/themes/customize/{slug}/reset', [AdminController::class, 'themeCustomizeReset']);
Router::post('/admin/themes/customize/{slug}/upload-asset', [AdminController::class, 'themeCustomizeUploadAsset']);

// Ajustes Generales
Router::get('/admin/settings', [AdminController::class, 'settings']);
Router::post('/admin/settings/save', [AdminController::class, 'settingsSave']);

// Páginas Legales / CMS y Suscriptores Newsletter
Router::get('/admin/pages', [AdminController::class, 'pages']);
Router::post('/admin/pages/store', [AdminController::class, 'pageStore']);
Router::post('/admin/pages/update/{id}', [AdminController::class, 'pageUpdate']);
Router::get('/admin/pages/delete/{id}', [AdminController::class, 'pageDelete']);
Router::get('/admin/pages/seed-legal', [AdminController::class, 'pageSeedLegal']);
Router::get('/admin/newsletter/export', [AdminController::class, 'newsletterExport']);
Router::get('/admin/newsletter/delete/{id}', [AdminController::class, 'newsletterDelete']);

// --- Rutas de la Tienda (Storefront / Minimal Shop) ---
Router::get('/', [StoreController::class, 'home']);
Router::get('/catalog', [StoreController::class, 'catalog']);
Router::get('/product/{slug}', [StoreController::class, 'product']);
Router::get('/page/{slug}', [StoreController::class, 'page']);
Router::post('/newsletter/subscribe', [StoreController::class, 'newsletterSubscribe']);

// MÃ³dulo 2: Carrito y Checkout
Router::get('/cart', [StoreController::class, 'cart']);
Router::post('/cart/add', [StoreController::class, 'cartAdd']);
Router::post('/cart/update', [StoreController::class, 'cartUpdate']);
Router::get('/cart/remove/{itemKey}', [StoreController::class, 'cartRemove']);
Router::post('/cart/apply-coupon', [StoreController::class, 'applyCoupon']);
Router::get('/cart/remove-coupon', [StoreController::class, 'removeCoupon']);

Router::get('/checkout', [StoreController::class, 'checkout']);
Router::post('/checkout/process', [StoreController::class, 'processOrder']);
Router::get('/order/success/{order_number}', [StoreController::class, 'orderSuccess']);
Router::get('/order/receipt/{order_number}', [StoreController::class, 'orderReceipt']);

// AutenticaciÃ³n de Clientes y Portal de Compras
Router::get('/login', [StoreController::class, 'customerLogin']);
Router::post('/login', [StoreController::class, 'customerLoginProcess']);
Router::get('/register', [StoreController::class, 'customerRegister']);
Router::post('/register', [StoreController::class, 'customerRegisterProcess']);
Router::get('/logout', [StoreController::class, 'customerLogout']);
Router::get('/account', [StoreController::class, 'customerAccount']);
Router::post('/account/profile/update', [StoreController::class, 'customerProfileUpdate']);
Router::post('/account/order-action/{id}', [StoreController::class, 'customerOrderAction']);
Router::post('/account/order-voucher/{id}', [StoreController::class, 'uploadOrderVoucher']);

// EnvÃ­o de ReseÃ±as Verificadas de Clientes
Router::post('/account/review', [StoreController::class, 'submitVerifiedReview']);
Router::post('/product/{slug}/review', [StoreController::class, 'submitVerifiedReview']);
Router::post('/review/submit', [StoreController::class, 'submitVerifiedReview']);

// Endpoints ligeros de Plugins Activos (Wishlist compartir & Stock Notifier)
Router::get('/plugin/wishlist/products', [StoreController::class, 'pluginWishlistProducts']);
Router::post('/plugin/wishlist/sync', [StoreController::class, 'pluginWishlistSync']);
Router::post('/plugin/comparator/sync', [StoreController::class, 'pluginComparatorSync']);
Router::post('/plugin/stock-notifier/subscribe', [StoreController::class, 'pluginStockNotifierSubscribe']);

// Despachar la peticiÃ³n
Router::dispatch();

