<?php
use ShopCMS\Core\ThemeCustomizer;
use ShopCMS\Core\ThemeManager;

ThemeManager::renderHeader(get_defined_vars());

$catCfg = $themeConfig['templates']['catalog']['sections'] ?? [];
$hdrSec = $catCfg['catalog_header'] ?? [];
$fltSec = $catCfg['catalog_filters'] ?? [];

$builtInSections = [];

ob_start(); ?>
<section data-theme-section="catalog_header" data-section-label="Cabecera del Mercado" style="padding: <?= (int)($hdrSec['padding_y'] ?? 16) ?>px 0;">
    <div class="container" style="text-align: center;">
        <span class="section-tag">Despacho Directo</span>
        <h1 class="section-title" style="font-size: <?= (int)($hdrSec['title_size'] ?? 28) ?>px;" data-theme-field="title"><?= htmlspecialchars($hdrSec['title'] ?? 'Catálogo Completo de Productos') ?></h1>
        <p style="color: var(--color-text-muted); font-size: 14px; margin-top: 6px;" data-theme-field="subtitle"><?= htmlspecialchars($hdrSec['subtitle'] ?? 'Selección de artículos con garantía y entrega rápida a domicilio.') ?></p>
    </div>
</section>
<?php $builtInSections['catalog_header'] = ob_get_clean();

ob_start(); ?>
<section data-theme-section="catalog_filters" data-section-label="Buscador y Filtros" style="margin-bottom: 30px;">
    <div class="container">
        <div style="background: #FFFFFF; border: 1px solid var(--color-card-border); border-radius: var(--radius-md); padding: 18px 24px; box-shadow: var(--shadow-soft);">
            <form method="GET" action="<?= $baseUrl ?>/catalog" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center;">
                <?php if (($fltSec['show_search'] ?? true) !== false): ?>
                    <input type="text" name="q" placeholder="Buscar por palabra clave..." value="<?= htmlspecialchars($search) ?>" style="flex: 2; min-width: 220px; padding: 11px 16px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 14px;">
                <?php endif; ?>
                <?php if (($fltSec['show_categories'] ?? true) !== false): ?>
                    <select name="category" style="flex: 1; min-width: 180px; padding: 11px 16px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 14px;">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $activeCategory === $cat['slug'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <button type="submit" class="market-cta-btn" style="padding: 11px 24px; font-size: 14px;">Filtrar Catálogo</button>
                <?php if (!empty($search) || !empty($activeCategory)): ?>
                    <a href="<?= $baseUrl ?>/catalog" class="btn-store btn-store-secondary" style="padding: 11px 18px;">Ver Todo</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</section>
<?php $builtInSections['catalog_filters'] = ob_get_clean();

ob_start(); ?>
<section data-theme-section="catalog_grid" data-section-label="Grilla del Catálogo" style="padding-bottom: 40px;">
    <div class="container">
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 60px 20px; background: #FFFFFF; border-radius: var(--radius-md); border: 1px solid var(--color-card-border);">
                <h3>No se encontraron productos en este criterio</h3>
                <p style="color: var(--color-text-muted); margin-top: 8px;">Intenta seleccionando otra categoría o limpiando la búsqueda.</p>
                <a href="<?= $baseUrl ?>/catalog" class="market-cta-btn" style="margin-top: 16px;">Ver Catálogo Completo</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $prod): ?>
                    <article class="market-product-card">
                        <div class="card-img-wrap">
                            <span class="product-badge-fresh">Garantizado</span>
                            <?php if (!empty($prod['featured_image'])): ?>
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($prod['featured_image']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>">
                            <?php else: ?>
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <span class="product-category-name"><?= htmlspecialchars($prod['category_name'] ?? 'Productos') ?></span>
                            <h3 class="product-title"><?= htmlspecialchars($prod['name']) ?></h3>
                            <div class="product-price-row">
                                <span class="price-main"><?= $currency['symbol'] ?><?= number_format((float)$prod['price'], 2) ?></span>
                                <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($prod['slug']) ?>" class="btn-view-product">
                                    <span>Ver Detalle</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php $builtInSections['catalog_grid'] = ob_get_clean();
?>

<main class="scms-template-sections" style="padding-top: 24px; padding-bottom: 50px;">
    <?php ThemeCustomizer::renderOrderedSections($themeSlug ?? 'food-market', 'catalog', $builtInSections, $baseUrl); ?>
</main>

<?php ThemeManager::renderFooter(get_defined_vars()); ?>
