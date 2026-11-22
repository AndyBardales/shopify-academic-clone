<?php
$baseUrl = defined('APP_URL') ? APP_URL : '';
\ShopCMS\Core\Auth::ensureIsolatedCartSession();
$cartCount = 0;
if (\ShopCMS\Core\Auth::isCustomer() && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}
$storeTitle = $storeName ?? 'Tech & Electronics';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $storeTitle) ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/themes/electronics/assets/css/style.css">
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
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="<?= $resolvedLogo !== '' ? 'display:none;' : '' ?>">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
            <span><?= htmlspecialchars($storeTitle) ?></span>
        </a>

        <nav class="main-nav">
            <a href="<?= $baseUrl ?>/">Inicio</a>
            <a href="<?= $baseUrl ?>/catalog">Catálogo & Especificaciones</a>
        </nav>

        <div class="header-actions" style="display: flex; align-items: center; gap: 14px;">
            <?php if (\ShopCMS\Core\Auth::isCustomer()): ?>
                <?php $currentCustomer = \ShopCMS\Core\Auth::customer(); ?>
                <a href="<?= $baseUrl ?>/account" style="display: inline-flex; align-items: center; gap: 6px; font-family: var(--font-mono); font-size: 11px; font-weight: 600; color: #0F172A; text-decoration: none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span><?= htmlspecialchars(explode(' ', $currentCustomer['name'])[0]) ?></span>
                </a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>/login" style="display: inline-flex; align-items: center; gap: 6px; font-family: var(--font-mono); font-size: 11px; font-weight: 600; color: #0F172A; text-decoration: none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    <span>Login</span>
                </a>
            <?php endif; ?>

            <a href="<?= $baseUrl ?>/cart" class="cart-button">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span>Carrito</span>
                <span class="cart-badge"><?= $cartCount ?></span>
            </a>
        </div>
    </div>
</header>
