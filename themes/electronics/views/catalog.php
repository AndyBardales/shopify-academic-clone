<?php
use ShopCMS\Core\ThemeCustomizer;
use ShopCMS\Core\ThemeManager;

ThemeManager::renderHeader(get_defined_vars());

$catCfg = $themeConfig['templates']['catalog']['sections'] ?? [];
$hdrSec = $catCfg['catalog_header'] ?? [];
$fltSec = $catCfg['catalog_filters'] ?? [];

$builtInSections = [];

ob_start(); ?>
<section data-theme-section="catalog_header" data-section-label="Cabecera Tech" style="padding: <?= (int)($hdrSec['padding_y'] ?? 16) ?>px 0;">
    <div class="container">
        <div style="padding-bottom: 16px; border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-family: var(--font-mono); font-size: <?= (int)($hdrSec['title_size'] ?? 24) ?>px; font-weight: 700; text-transform: uppercase;" data-theme-field="title"><?= htmlspecialchars($hdrSec['title'] ?? 'Hardware & Dispositivos (Catálogo)') ?></h1>
                <p style="font-size: 13px; color: var(--color-text-light);" data-theme-field="subtitle"><?= htmlspecialchars($hdrSec['subtitle'] ?? 'Arquitectura técnica, componentes y periféricos de alto rendimiento.') ?></p>
            </div>
            <div style="font-family: var(--font-mono); font-size: 12px; color: var(--color-primary); font-weight: 700;">
                TOTAL ITEMS: <?= count($products) ?> REGISTRADOS
            </div>
        </div>
    </div>
</section>
<?php $builtInSections['catalog_header'] = ob_get_clean();

ob_start(); ?>
<section data-theme-section="catalog_filters" data-section-label="Filtros de Búsqueda" style="margin-bottom: 20px;">
    <div class="container">
        <form method="GET" action="<?= $baseUrl ?>/catalog" style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: 8px; padding: 14px 18px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <?php if (($fltSec['show_search'] ?? true) !== false): ?>
                <input type="text" name="q" placeholder="Buscar modelo (Pro, Max, Cable...)" value="<?= htmlspecialchars($search) ?>" style="flex: 2; min-width: 200px; padding: 9px 12px; border: 1px solid var(--color-border); border-radius: 4px; font-size: 13px;">
            <?php endif; ?>
            <?php if (($fltSec['show_categories'] ?? true) !== false): ?>
                <select name="category" style="flex: 1; min-width: 170px; padding: 9px 12px; border: 1px solid var(--color-border); border-radius: 4px; font-size: 13px; background: #FFF;">
                    <option value="">Todas las familias</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $activeCategory === $cat['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <button type="submit" class="btn-tech" style="padding: 9px 18px;">Aplicar Filtros</button>
            <?php if (!empty($search) || !empty($activeCategory)): ?>
                <a href="<?= $baseUrl ?>/catalog" class="btn-store btn-store-secondary" style="padding: 9px 14px; font-size: 12px;">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</section>
<?php $builtInSections['catalog_filters'] = ob_get_clean();

ob_start(); ?>
<section data-theme-section="catalog_grid" data-section-label="Grilla Densa Tech" style="padding-bottom: 40px;">
    <div class="container">
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 60px 20px; background: #FFFFFF; border: 1px solid var(--color-border); border-radius: 8px;">
                <h3 style="font-family: var(--font-mono); font-size: 16px;">NO SE REGISTRAN COMPONENTES CON ESTE FILTRO</h3>
                <p style="font-size: 13px; color: var(--color-text-light); margin-top: 8px;">Modifica los criterios para ver hardware disponible.</p>
                <a href="<?= $baseUrl ?>/catalog" class="btn-tech" style="margin-top: 16px;">Ver Catálogo Completo</a>
            </div>
        <?php else: ?>
            <div class="tech-grid">
                <?php foreach ($products as $p): ?>
                    <div class="tech-card">
                        <div class="tech-card-image">
                            <?php if (!empty($p['featured_image'])): ?>
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars($p['featured_image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                            <?php else: ?>
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="1.5">
                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                    <line x1="8" y1="21" x2="16" y2="21"></line>
                                    <line x1="12" y1="17" x2="12" y2="21"></line>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="tech-card-body">
                            <span class="tech-badge"><?= htmlspecialchars($p['category_name'] ?? 'HARDWARE') ?></span>
                            <h3 class="tech-title">
                                <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($p['slug']) ?>"><?= htmlspecialchars($p['name']) ?></a>
                            </h3>
                            <div style="margin-top: auto;">
                                <div class="tech-specs">
                                    <span>STOCK: <?= (int)$p['quantity'] ?>U</span>
                                    <span>SKU: <?= htmlspecialchars($p['sku'] ?? 'N/A') ?></span>
                                </div>
                                <div class="tech-price"><?= $currency['symbol'] ?><?= number_format($p['price'], 2) ?></div>
                                <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($p['slug']) ?>" class="btn-tech" style="width: 100%; text-align: center;">Ver Especificaciones</a>
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

<main class="scms-template-sections" style="padding-top: 24px; padding-bottom: 50px;">
    <?php ThemeCustomizer::renderOrderedSections($themeSlug ?? 'electronics', 'catalog', $builtInSections, $baseUrl); ?>
</main>

<?php ThemeManager::renderFooter(get_defined_vars()); ?>
