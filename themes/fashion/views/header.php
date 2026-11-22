<?php
$baseUrl = defined('APP_URL') ? APP_URL : '';
\ShopCMS\Core\Auth::ensureIsolatedCartSession();
$cartCount = 0;
if (\ShopCMS\Core\Auth::isCustomer() && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}
$storeTitle = $storeName ?? 'Fashion Collection';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $storeTitle) ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/themes/fashion/assets/css/style.css">
    <?php \ShopCMS\Core\PluginManager::doAction('theme_head'); ?>
</head>
<body>

<?php
$logoUrl = trim((string)($themeConfig['global']['logo_url'] ?? ''));
$resolvedLogo = $logoUrl !== '' ? ((preg_match('#^https?://#i', $logoUrl) || str_starts_with($logoUrl, '/')) ? $logoUrl : $baseUrl . '/' . ltrim($logoUrl, '/')) : '';
?>
<header class="site-header" data-theme-section="header" data-section-label="Encabezado">
    <div class="container header-inner">
        <a href="<?= $baseUrl ?>/" class="site-logo" style="display:inline-flex; align-items:center; gap:10px;">
            <?php if ($resolvedLogo !== ''): ?>
                <img src="<?= htmlspecialchars($resolvedLogo) ?>" alt="<?= htmlspecialchars($storeTitle) ?>" class="scms-custom-logo">
            <?php endif; ?>
            <span><?= htmlspecialchars($storeTitle) ?></span>
        </a>

        <nav class="main-nav">
            <a href="<?= $baseUrl ?>/">Colección</a>
            <a href="<?= $baseUrl ?>/catalog">Catálogo Completo</a>
        </nav>

        <div class="header-actions" style="display: flex; align-items: center; gap: 16px;">
            <?php if (\ShopCMS\Core\Auth::isCustomer()): ?>
                <?php $currentCustomer = \ShopCMS\Core\Auth::customer(); ?>
                <a href="<?= $baseUrl ?>/account" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; font-weight: 600; color: #171717; text-decoration: none;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span><?= htmlspecialchars(explode(' ', $currentCustomer['name'])[0]) ?></span>
                </a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>/login" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; font-weight: 600; color: #171717; text-decoration: none;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    <span>Ingresar</span>
                </a>
            <?php endif; ?>

            <a href="<?= $baseUrl ?>/cart" class="cart-button">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <span>Carrito</span>
                <span class="cart-badge"><?= $cartCount ?></span>
            </a>
        </div>
    </div>
</header>
