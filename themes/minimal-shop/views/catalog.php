<?php
use ShopCMS\Core\ThemeCustomizer;
use ShopCMS\Core\ThemeManager;

ThemeManager::renderHeader(get_defined_vars());

$catCfg = $themeConfig['templates']['catalog']['sections'] ?? [];
$hdrSec = $catCfg['catalog_header'] ?? [];
$fltSec = $catCfg['catalog_filters'] ?? [];
$grdSec = $catCfg['catalog_grid'] ?? [];

$builtInSections = [];

// 1. CABECERA DE CATÁLOGO
ob_start(); ?>
<section data-theme-section="catalog_header" data-section-label="Cabecera de Catálogo" style="padding: <?= (int)($hdrSec['padding_y'] ?? 16) ?>px 0;">
    <div class="container">
        <h1 style="font-size: <?= (int)($hdrSec['title_size'] ?? 28) ?>px; font-weight: 700; margin-bottom: 8px;" data-theme-field="title"><?= htmlspecialchars($hdrSec['title'] ?? 'Catálogo de Productos') ?></h1>
        <p style="color: var(--color-text-light);" data-theme-field="subtitle"><?= htmlspecialchars($hdrSec['subtitle'] ?? 'Encuentra todo lo que buscas con nuestras opciones y filtros.') ?></p>
    </div>
</section>
<?php $builtInSections['catalog_header'] = ob_get_clean();

// 2. FILTROS
ob_start(); ?>
<section data-theme-section="catalog_filters" data-section-label="Barra de Filtros" style="margin-bottom: 24px;">
    <div class="container">
        <div style="display: flex; gap: 16px; flex-wrap: wrap; background: var(--color-surface); padding: 16px; border-radius: 10px; border: 1px solid var(--color-border);">
            <form method="GET" action="<?= $baseUrl ?>/catalog" style="display: flex; gap: 12px; flex: 1; flex-wrap: wrap;">
                <?php if (($fltSec['show_search'] ?? true) !== false): ?>
                    <input type="text" name="q" placeholder="Buscar por palabra clave..." value="<?= htmlspecialchars($search) ?>" style="flex: 2; min-width: 200px; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
                <?php endif; ?>
                <?php if (($fltSec['show_categories'] ?? true) !== false): ?>
                    <select name="category" style="flex: 1; min-width: 160px; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $activeCategory === $cat['slug'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <button type="submit" class="btn-store btn-store-primary" style="padding: 10px 20px;">Filtrar</button>
                <?php if (!empty($search) || !empty($activeCategory)): ?>
                    <a href="<?= $baseUrl ?>/catalog" class="btn-store btn-store-secondary" style="padding: 10px 16px;">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</section>
<?php $builtInSections['catalog_filters'] = ob_get_clean();

// 3. GRILLA DE PRODUCTOS
ob_start(); ?>
<section data-theme-section="catalog_grid" data-section-label="Grilla de Productos" style="padding-bottom: 40px;">
    <div class="container">
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 60px 20px; background: var(--color-surface); border-radius: 12px;">
                <h3>No se encontraron productos</h3>
                <p style="color: var(--color-text-light); margin-top: 8px;">Intenta ajustando tus términos de búsqueda o filtros.</p>
                <a href="<?= $baseUrl ?>/catalog" class="btn-store btn-store-secondary" style="margin-top: 16px;">Ver todo el catálogo</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $p): ?>
                    <div class="product-card">
                        <div class="product-card-image">
                            <?php if (!empty($p['featured_image'])): ?>
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($p['featured_image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                            <?php else: ?>
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="#CBD5E1">
                                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="product-card-content">
                            <div class="product-category"><?= htmlspecialchars($p['category_name'] ?? 'General') ?></div>
                            <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($p['slug']) ?>" class="product-title"><?= htmlspecialchars($p['name']) ?></a>

                            <div class="product-price-row">
                                <span class="product-price"><?= $currency['symbol'] ?><?= number_format($p['price'], 2) ?></span>
                                <?php if (!empty($p['compare_price'])): ?>
                                    <span class="product-compare-price"><?= $currency['symbol'] ?><?= number_format($p['compare_price'], 2) ?></span>
                                <?php endif; ?>
                            </div>

                            <div style="margin-top: 14px;">
                                <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($p['slug']) ?>" class="btn-store btn-store-secondary" style="width: 100%; font-size: 13px; padding: 8px;">
                                    Ver Opciones
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php $builtInSections['catalog_grid'] = ob_get_clean();
?>

<main class="scms-template-sections" style="padding-top: 20px; padding-bottom: 40px;">
    <?php ThemeCustomizer::renderOrderedSections($themeSlug ?? 'minimal-shop', 'catalog', $builtInSections, $baseUrl); ?>
</main>

<?php ThemeManager::renderFooter(get_defined_vars()); ?>
