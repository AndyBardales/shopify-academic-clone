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

// 1. HERO FASHION
ob_start(); ?>
<section class="fashion-hero" data-theme-section="hero" data-section-label="Banner Editorial (Hero)">
    <div class="container">
        <p data-theme-field="subtitle"><?= htmlspecialchars($hero['subtitle'] ?? 'Temporada 2026 / Colección Cápsula') ?></p>
        <h1 data-theme-field="title"><?= htmlspecialchars($hero['title'] ?? 'Moda & Vanguardia') ?></h1>
        <div style="display: flex; gap: 16px; justify-content: center; margin-top: 22px;">
            <a href="<?= htmlspecialchars($ctaHref) ?>" class="btn-fashion btn-fashion-outline" data-theme-field="cta_text"><?= htmlspecialchars($hero['cta_text'] ?? 'Explorar Catálogo') ?></a>
        </div>
    </div>
</section>
<?php $builtInSections['hero'] = ob_get_clean();

// 2. CATEGORÍAS
ob_start();
if (!empty($categories)):
    $filteredCats = ThemeCustomizer::filterCategoriesForHome($categories, $catSec);
    $visibleCatIds = array_map('intval', array_column($filteredCats, 'id'));
    $renderCats = ThemeCustomizer::isEditorPreview() ? $categories : $filteredCats;
?>
<section data-theme-section="categories" data-section-label="Colecciones / Categorías" style="padding: <?= (int)($catSec['padding_y'] ?? 24) ?>px 0;">
    <div class="container" style="text-align: center;">
        <h2 style="font-family: var(--font-serif); font-size: <?= (int)($catSec['title_size'] ?? 20) ?>px; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 18px;" data-theme-field="title"><?= htmlspecialchars($catSec['title'] ?? 'Explorar por Colección') ?></h2>
        <div style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;">
            <?php foreach ($renderCats as $cat):
                $isCatVis = in_array((int)$cat['id'], $visibleCatIds, true);
            ?>
                <a href="<?= $baseUrl ?>/catalog?category=<?= htmlspecialchars($cat['slug']) ?>"
                   data-cat-id="<?= (int)$cat['id'] ?>"
                   data-parent-id="<?= (int)($cat['parent_id'] ?? 0) ?>"
                   data-demand-score="<?= (int)($cat['demand_score'] ?? 0) ?>"
                   data-cat-display="inline-flex"
                   style="display: <?= $isCatVis ? 'inline-flex' : 'none' ?>; align-items: center; border: 1px solid #D4D4D4; padding: 10px 20px; text-decoration: none; color: var(--color-text); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif;
$builtInSections['categories'] = ob_get_clean();

// 3. PIEZAS SELECCIONADAS
ob_start(); ?>
<section data-theme-section="featured_products" data-section-label="Piezas Seleccionadas" style="padding: <?= (int)($featSec['padding_y'] ?? 28) ?>px 0;">
    <div class="container">
        <div style="text-align: center; margin-bottom: 24px;">
            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 3px; color: var(--color-text-light);" data-theme-field="subtitle"><?= htmlspecialchars($featSec['subtitle'] ?? 'Lanzamientos Exclusivos') ?></span>
            <h2 style="font-family: var(--font-serif); font-size: <?= (int)($featSec['title_size'] ?? 32) ?>px; letter-spacing: 2px; text-transform: uppercase; margin-top: 8px;" data-theme-field="title"><?= htmlspecialchars($featSec['title'] ?? 'Piezas Seleccionadas') ?></h2>
        </div>

        <div class="fashion-grid">
            <?php foreach ($featProducts as $p): ?>
                <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($p['slug']) ?>" class="fashion-card">
                    <div class="fashion-card-image">
                        <?php if (!empty($p['featured_image'])): ?>
                            <img src="<?= $baseUrl ?>/<?= htmlspecialchars($p['featured_image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                        <?php else: ?>
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#D4D4D4" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="fashion-card-info">
                        <div class="fashion-card-category"><?= htmlspecialchars($p['category_name'] ?? 'Pret-a-Porter') ?></div>
                        <div class="fashion-card-title"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="fashion-card-price">
                            <?= $currency['symbol'] ?><?= number_format($p['price'], 2) ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php $builtInSections['featured_products'] = ob_get_clean();

// 4. PILARES EDITORIALES
ob_start(); ?>
<section data-theme-section="trust_pillars" data-section-label="Pilares de Confianza" style="padding: <?= (int)($trustSec['padding_y'] ?? 36) ?>px 0; border-top: 1px solid #E5E5E5; border-bottom: 1px solid #E5E5E5;">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 32px; text-align: center;">
            <div>
                <h4 style="font-family: var(--font-serif); font-size: 14px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px;">Confección de Autor</h4>
                <p style="font-size: 12px; color: var(--color-text-light); line-height: 1.5;">Atención a cada detalle y estándares de sastrería fina.</p>
            </div>
            <div>
                <h4 style="font-family: var(--font-serif); font-size: 14px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px;">Ediciones Limitadas</h4>
                <p style="font-size: 12px; color: var(--color-text-light); line-height: 1.5;">Piezas exclusivas con disponibilidad reducida.</p>
            </div>
            <div>
                <h4 style="font-family: var(--font-serif); font-size: 14px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px;">Envío Asegurado</h4>
                <p style="font-size: 12px; color: var(--color-text-light); line-height: 1.5;">Embalaje protegido y seguimiento en tiempo real.</p>
            </div>
            <div>
                <h4 style="font-family: var(--font-serif); font-size: 14px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px;">Garantía de Atelier</h4>
                <p style="font-size: 12px; color: var(--color-text-light); line-height: 1.5;">Cambios y devoluciones asistidas sin complicaciones.</p>
            </div>
        </div>
    </div>
</section>
<?php $builtInSections['trust_pillars'] = ob_get_clean();
?>

<main class="scms-template-sections">
    <?php ThemeCustomizer::renderOrderedSections($themeSlug ?? 'fashion', 'home', $builtInSections, $baseUrl); ?>
</main>

<?php ThemeManager::renderFooter(get_defined_vars()); ?>
