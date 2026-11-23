<?php
use ShopCMS\Core\ThemeCustomizer;
use ShopCMS\Core\ThemeManager;
use ShopCMS\Core\CatalogHelper;

ThemeManager::renderHeader(get_defined_vars());

$catCfg = $themeConfig['templates']['catalog']['sections'] ?? [];
$hdrSec = $catCfg['catalog_header'] ?? [];
$fltSec = $catCfg['catalog_filters'] ?? [];

$filters = $filters ?? CatalogHelper::sanitizeFilters(['q' => $search ?? '', 'category' => $activeCategory ?? '']);
$totalProducts = (int)($totalProducts ?? count($products ?? []));
$totalPages = (int)($totalPages ?? 1);
$currentPage = (int)($currentPage ?? 1);
$showingFrom = (int)($showingFrom ?? ($totalProducts > 0 ? 1 : 0));
$showingTo = (int)($showingTo ?? count($products ?? []));

$builtInSections = [];

ob_start(); ?>
<section data-theme-section="catalog_header" data-section-label="Cabecera de Colección" style="padding: <?= (int)($hdrSec['padding_y'] ?? 24) ?>px 0;">
    <div class="container" style="text-align: center;">
        <h1 style="font-family: var(--font-serif); font-size: <?= (int)($hdrSec['title_size'] ?? 36) ?>px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 8px;" data-theme-field="title"><?= htmlspecialchars($hdrSec['title'] ?? 'Catálogo de Colecciones') ?></h1>
        <p style="font-size: 13px; text-transform: uppercase; letter-spacing: 2px; color: var(--color-text-light);" data-theme-field="subtitle"><?= htmlspecialchars($hdrSec['subtitle'] ?? 'Prendas selectas con confección artesanal de alta costura') ?></p>
    </div>
</section>
<?php $builtInSections['catalog_header'] = ob_get_clean();

ob_start(); ?>
<section data-theme-section="catalog_filters" data-section-label="Filtros" style="margin-bottom: 36px;">
    <div class="container" style="max-width: 920px;">
        <div style="background: #FFFFFF; border: 1px solid #E5E5E5; padding: 18px 20px;">
            <?= CatalogHelper::renderFilterFields($filters, $categories ?? [], $fltSec, $currency ?? ['symbol' => '$'], [
                'base_url' => $baseUrl,
                'btn_class' => 'btn-fashion',
                'btn_style' => 'padding: 10px 24px;',
                'radius' => '0px',
                'border' => '1px solid #D4D4D4',
                'search_placeholder' => 'Buscar por prenda, material o SKU...',
                'category_placeholder' => 'Todas las colecciones',
                'total_products' => $totalProducts,
                'showing_from' => $showingFrom,
                'showing_to' => $showingTo,
                'btn_label' => 'Filtrar',
            ]) ?>
        </div>
    </div>
</section>
<?php $builtInSections['catalog_filters'] = ob_get_clean();

ob_start(); ?>
<section data-theme-section="catalog_grid" data-section-label="Grilla de Productos" style="padding-bottom: 50px;">
    <div class="container">
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 80px 20px; background: #FAFAFA; border: 1px solid #E5E5E5;">
                <h3 style="font-family: var(--font-serif); font-size: 18px; text-transform: uppercase; letter-spacing: 1px;">No se encontraron piezas en este criterio</h3>
                <p style="color: #737373; margin-top: 8px; font-size: 13px;">Explora otras colecciones o restablece la búsqueda.</p>
                <a href="<?= $baseUrl ?>/catalog" class="btn-fashion" style="margin-top: 20px;">Restablecer Catálogo</a>
            </div>
        <?php else: ?>
            <div class="fashion-grid">
                <?php foreach ($products as $p): ?>
                    <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($p['slug']) ?>" class="fashion-card">
                        <div class="fashion-card-image">
                            <?php if (!empty($p['featured_image'])): ?>
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($p['featured_image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                            <?php else: ?>
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#D4D4D4" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="fashion-card-info">
                            <div class="fashion-card-category"><?= htmlspecialchars($p['category_name'] ?? 'Colección') ?></div>
                            <div class="fashion-card-title"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="fashion-card-price"><?= $currency['symbol'] ?><?= number_format($p['price'], 2) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?= CatalogHelper::renderPagination($baseUrl, $filters, $currentPage, $totalPages, ['radius' => '0px']) ?>
        <?php endif; ?>
    </div>
</section>
<?php $builtInSections['catalog_grid'] = ob_get_clean();
?>

<main class="scms-template-sections" style="padding-top: 24px; padding-bottom: 50px;">
    <?php ThemeCustomizer::renderOrderedSections($themeSlug ?? 'fashion', 'catalog', $builtInSections, $baseUrl); ?>
</main>

<?php ThemeManager::renderFooter(get_defined_vars()); ?>
