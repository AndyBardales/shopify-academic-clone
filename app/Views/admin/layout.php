<?php
$baseUrl = defined('APP_URL') ? APP_URL : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Panel de Control') ?> — <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/polaris.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/shopcms-guard.css?v=20261122b">
</head>
<body>

<!-- SIDEBAR POLARIS -->
<aside class="polaris-sidebar">
    <div class="sidebar-header">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="#008060">
            <path d="M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm7 17H5V8h14v12z"/>
        </svg>
        <h2><?= htmlspecialchars($storeName) ?></h2>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Principal</div>
        <a href="<?= $baseUrl ?>/admin" class="nav-item <?= $currentRoute === 'admin' ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            Inicio
        </a>
        <a href="<?= $baseUrl ?>/admin/orders" class="nav-item <?= str_starts_with($currentRoute, 'admin/orders') ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
            Pedidos
        </a>
        <a href="<?= $baseUrl ?>/admin/products" class="nav-item <?= str_starts_with($currentRoute, 'admin/products') ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg>
            Productos & Variantes
        </a>
        <a href="<?= $baseUrl ?>/admin/categories" class="nav-item <?= str_starts_with($currentRoute, 'admin/categories') ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm14 1a1 1 0 100-2 1 1 0 000 2zM2 13a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2zm14 1a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
            Categorías
        </a>
        <a href="<?= $baseUrl ?>/admin/inventory" class="nav-item <?= str_starts_with($currentRoute, 'admin/inventory') ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20"><path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/></svg>
            Inventario & Alertas
        </a>

        <div class="nav-section-title">Marketing & Clientes</div>
        <a href="<?= $baseUrl ?>/admin/customers" class="nav-item <?= str_starts_with($currentRoute, 'admin/customers') ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
            Clientes
        </a>
        <a href="<?= $baseUrl ?>/admin/coupons" class="nav-item <?= str_starts_with($currentRoute, 'admin/coupons') ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm2 5a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1zm0 4a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
            Cupones & Descuentos
        </a>
        <a href="<?= $baseUrl ?>/admin/reviews" class="nav-item <?= str_starts_with($currentRoute, 'admin/reviews') ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            Reseñas & Valoraciones
        </a>

        <div class="nav-section-title">Canales & Extensibilidad</div>
        <a href="<?= $baseUrl ?>/admin/plugins" class="nav-item <?= str_starts_with($currentRoute, 'admin/plugins') ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
            Plugins (Carga .ZIP)
        </a>
        <a href="<?= $baseUrl ?>/admin/themes" class="nav-item <?= str_starts_with($currentRoute, 'admin/themes') ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v11a3 3 0 106 0V4a2 2 0 00-2-2H4zm1 14a1 1 0 100-2 1 1 0 000 2zm5-1.757l4.9-4.9a2 2 0 000-2.828L13.414 5.03a2 2 0 00-2.828 0L9.172 6.444l2.828 2.829-1 1-2.828-2.829-1.414 1.415 4.242 4.242z" clip-rule="evenodd"/></svg>
            Temas & Plantillas
        </a>
        <a href="<?= $baseUrl ?>/admin/settings" class="nav-item <?= str_starts_with($currentRoute, 'admin/settings') ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
            Ajustes
        </a>
    </nav>

    <div class="sidebar-footer">
        <div>
            <div style="font-weight: 600; color: #FFF;"><?= htmlspecialchars($user['name'] ?? 'Admin') ?></div>
            <div style="font-size: 11px; color: #8C9196;"><?= htmlspecialchars($user['email'] ?? '') ?></div>
        </div>
        <a href="<?= $baseUrl ?>/admin/logout" title="Cerrar sesión">Salir</a>
    </div>
</aside>

<!-- CONTENIDO PRINCIPAL -->
<div class="polaris-layout">
    <!-- BARRA DE GUARDADO CONTEXTUAL (POLARIS CONTEXTUAL SAVE BAR) -->
    <div id="shopcms-contextual-savebar" class="shopcms-savebar" role="region" aria-label="Barra de guardado contextual">
        <div class="shopcms-savebar-content">
            <div class="shopcms-savebar-indicator">
                <span class="shopcms-savebar-dot"></span>
                <span class="shopcms-savebar-text" id="shopcms-savebar-text">Cambios no guardados</span>
            </div>
            <div class="shopcms-savebar-actions">
                <button type="button" class="shopcms-btn-discard" id="shopcms-savebar-discard">
                    Descartar
                </button>
                <button type="button" class="shopcms-btn-save" id="shopcms-savebar-save">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>Guardar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- TOPBAR -->
    <header class="polaris-topbar">
        <div class="topbar-left">
            <a href="<?= $baseUrl ?>/" target="_blank" class="btn btn-secondary btn-sm" style="font-size: 12px;">
                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/><path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/></svg>
                Ver Tienda
            </a>
        </div>
        <div class="topbar-right">
            <span style="font-size: 13px; color: var(--color-text-secondary);">ShopCMS v1.0.0</span>
        </div>
    </header>

    <!-- CONTENT BODY -->
    <main class="polaris-content">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert-message success">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert-message error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?= htmlspecialchars($_SESSION['flash_error']) ?></span>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <?php
        $viewPath = __DIR__ . '/' . $view . '.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "<p>Vista no encontrada: " . htmlspecialchars($view) . "</p>";
        }
        ?>
    </main>
</div>

<script src="<?= $baseUrl ?>/assets/js/shopcms-guard.js?v=20261122b"></script>
</body>
</html>

