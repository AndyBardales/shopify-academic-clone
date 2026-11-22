<?php
$baseUrl = defined('APP_URL') ? APP_URL : '';
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}
$storeTitle = $storeName ?? 'ShopCMS Store';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $storeTitle) ?></title>
    <link rel="stylesheet" href="<?= \ShopCMS\Core\ThemeManager::getThemeAssetUrl('css/style.css') ?>">
    <?php \ShopCMS\Core\PluginManager::doAction('theme_head'); ?>
</head>
<body>

<?php
$logoUrl = trim((string)($themeConfig['global']['logo_url'] ?? ''));
$resolvedLogo = $logoUrl !== '' ? ((preg_match('#^https?://#i', $logoUrl) || str_starts_with($logoUrl, '/')) ? $logoUrl : $baseUrl . '/' . ltrim($logoUrl, '/')) : '';
?>
<header class="site-header" data-theme-section="header" data-section-label="Encabezado">
    <div class="container header-inner">
        <a href="<?= $baseUrl ?>/" class="site-logo">
            <?php if ($resolvedLogo !== ''): ?>
                <img src="<?= htmlspecialchars($resolvedLogo) ?>" alt="<?= htmlspecialchars($storeTitle) ?>" class="scms-custom-logo">
            <?php endif; ?>
            <svg viewBox="0 0 24 24" style="<?= $resolvedLogo !== '' ? 'display:none;' : '' ?>">
                <path d="M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm7 17H5V8h14v12z"/>
            </svg>
            <span><?= htmlspecialchars($storeTitle) ?></span>
        </a>

        <nav class="main-nav">
            <a href="<?= $baseUrl ?>/">Inicio</a>
            <a href="<?= $baseUrl ?>/catalog">Catálogo Completo</a>
        </nav>

        <div class="header-actions" style="display: flex; align-items: center; gap: 14px;">
            <?php if (\ShopCMS\Core\Auth::isCustomer()): ?>
                <?php $currentCustomer = \ShopCMS\Core\Auth::customer(); ?>
                <a href="<?= $baseUrl ?>/account" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--color-text); text-decoration: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span><?= htmlspecialchars(explode(' ', $currentCustomer['name'])[0]) ?></span>
                </a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>/login" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--color-text); text-decoration: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    <span>Iniciar Sesión</span>
                </a>
            <?php endif; ?>

            <a href="<?= $baseUrl ?>/cart" class="cart-button">
                <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                </svg>
                <span>Carrito</span>
                <span class="cart-badge"><?= $cartCount ?></span>
            </a>
        </div>
    </div>
</header>

