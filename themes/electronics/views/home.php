<?php
use ShopCMS\Core\ThemeCustomizer;
use ShopCMS\Core\ThemeManager;

ThemeManager::renderHeader(get_defined_vars());

$homeCfg = $themeConfig['templates']['home']['sections'] ?? [];
$hero = $homeCfg['hero'] ?? [];
$catSec = $homeCfg['categories'] ?? [];
$featSec = $homeCfg['featured_products'] ?? [];
$trustSec = $homeCfg['trust_pillars'] ?? [];

$ctaHref = trim((string)($hero['cta_link'] ?? '/catalog'));
$ctaHref = str_starts_with($ctaHref, 'http') ? $ctaHref : $baseUrl . '/' . ltrim($ctaHref, '/');
$maxFeat = max(1, min(24, (int)($featSec['max_items'] ?? 8)));
$featProducts = array_slice($products ?? [], 0, $maxFeat);

$builtInSections = [];

// 1. HERO BANNER TECH
ob_start(); ?>
<section data-theme-section="hero" data-section-label="Cabecera Principal (Hero)" style="padding: <?= (int)($hero['padding_y'] ?? 24) ?>px 0;">
    <div class="container">
        <div style="padding: 20px 24px; background: rgba(255,255,255,0.06); border-radius: 8px; border: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-size: <?= (int)($hero['title_size'] ?? 24) ?>px; font-weight: 700; margin: 0 0 4px;" data-theme-field="title"><?= htmlspecialchars($hero['title'] ?? 'Catálogo de Componentes & Gadgets') ?></h1>
                <p style="font-size: <?= (int)($hero['subtitle_size'] ?? 14) ?>px; margin: 0; opacity: 0.85;" data-theme-field="subtitle"><?= htmlspecialchars($hero['subtitle'] ?? 'Productos disponibles con entrega inmediata y garantía oficial.') ?></p>
            </div>
            <a href="<?= htmlspecialchars($ctaHref) ?>" class="btn-tech" data-theme-field="cta_text"><?= htmlspecialchars($hero['cta_text'] ?? 'Ver Todo el Inventario') ?></a>
        </div>
    </div>
</section>
<?php $builtInSections['hero'] = ob_get_clean();

// 2. CATEGORÍAS RÁPIDAS
ob_start();
if (!empty($categories)):
    $filteredCats = ThemeCustomizer::filterCategoriesForHome($categories, $catSec);
    $visibleCatIds = array_map('intval', array_column($filteredCats, 'id'));
    $renderCats = ThemeCustomizer::isEditorPreview() ? $categories : $filteredCats;
?>
<section data-theme-section="categories" data-section-label="Familias de Productos" style="padding: <?= (int)($catSec['padding_y'] ?? 14) ?>px 0;">
    <div class="container">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <strong style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;" data-theme-field="title"><?= htmlspecialchars($catSec['title'] ?? 'Categorías:') ?></strong>
            <?php foreach ($renderCats as $cat):
                $isCatVis = in_array((int)$cat['id'], $visibleCatIds, true);
            ?>
                <a href="<?= $baseUrl ?>/catalog?category=<?= htmlspecialchars($cat['slug']) ?>"
                   data-cat-id="<?= (int)$cat['id'] ?>"
                   data-parent-id="<?= (int)($cat['parent_id'] ?? 0) ?>"
                   data-demand-score="<?= (int)($cat['demand_score'] ?? 0) ?>"
                   data-cat-display="inline-flex"
                   style="display: <?= $isCatVis ? 'inline-flex' : 'none' ?>; align-items: center; background: var(--color-surface); border: 1px solid var(--color-border); padding: 6px 12px; border-radius: 6px; font-size: 12.5px; font-weight: 600; color: var(--color-text); text-decoration: none;">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif;
$builtInSections['categories'] = ob_get_clean();

// 3. GRILLA DENSA DE PRODUCTOS
ob_start(); ?>
<section data-theme-section="featured_products" data-section-label="Grilla de Productos" style="padding: <?= (int)($featSec['padding_y'] ?? 24) ?>px 0;">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: <?= (int)($featSec['title_size'] ?? 22) ?>px; font-weight: 700;" data-theme-field="title"><?= htmlspecialchars($featSec['title'] ?? 'Productos Destacados') ?></h2>
            <a href="<?= $baseUrl ?>/catalog" style="color: var(--color-primary); text-decoration: none; font-weight: 600; font-size: 13px;" data-theme-field="cta_text"><?= htmlspecialchars($featSec['cta_text'] ?? 'Ver todos') ?></a>
        </div>
        <div class="dense-grid products-grid">
            <?php foreach ($featProducts as $p): ?>
                <div class="dense-card tech-card">
                    <div class="dense-card-image">
                        <?php if (!empty($p['featured_image'])): ?>
                            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($p['featured_image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                        <?php else: ?>
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="1.5">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                <line x1="12" y1="17" x2="12" y2="21"></line>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="dense-sku">SKU: <?= htmlspecialchars($p['sku'] ?: 'TECH-ITEM') ?></div>
                    <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($p['slug']) ?>" class="dense-card-title">
                        <?= htmlspecialchars($p['name']) ?>
                    </a>
                    <div class="dense-price">
                        <?= $currency['symbol'] ?><?= number_format($p['price'], 2) ?>
                    </div>
                    <div class="dense-stock-tag">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span><?= $p['quantity'] ?> disp.</span>
                    </div>
                    <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($p['slug']) ?>" class="btn-tech" style="text-align: center;">
                        Ver Ficha
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php $builtInSections['featured_products'] = ob_get_clean();

// 4. GARANTÍAS TÉCNICAS
ob_start(); ?>
<section data-theme-section="trust_pillars" data-section-label="Garantías Técnicas" style="padding: <?= (int)($trustSec['padding_y'] ?? 24) ?>px 0;">
    <div class="container">
        <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 8px; padding: 18px 22px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div style="display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span>Garantía Oficial de Hardware</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                <span>Embalaje Antiestático Seguro</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                <span>Despacho Rápido en 24/48h</span>
            </div>
        </div>
    </div>
</section>
<?php $builtInSections['trust_pillars'] = ob_get_clean();
?>

<main class="scms-template-sections">
    <?php ThemeCustomizer::renderOrderedSections($themeSlug ?? 'electronics', 'home', $builtInSections, $baseUrl); ?>
</main>

<?php ThemeManager::renderFooter(get_defined_vars()); ?>
