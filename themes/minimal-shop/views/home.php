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

// 1. HERO BANNER
ob_start(); ?>
<section class="hero-banner" data-theme-section="hero" data-section-label="Banner Principal (Hero)">
    <div class="container">
        <h1 data-theme-field="title"><?= htmlspecialchars($hero['title'] ?? ('Bienvenido a ' . $storeName)) ?></h1>
        <p data-theme-field="subtitle"><?= htmlspecialchars($hero['subtitle'] ?? 'Explora nuestras colecciones exclusivas y los artículos más destacados de la temporada.') ?></p>
        <div style="margin-top: 24px;">
            <a href="<?= htmlspecialchars($ctaHref) ?>" class="btn-store btn-store-primary" data-theme-field="cta_text"><?= htmlspecialchars($hero['cta_text'] ?? 'Ver Catálogo Completo') ?></a>
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
<section data-theme-section="categories" data-section-label="Categorías" style="padding: <?= (int)($catSec['padding_y'] ?? 24) ?>px 0; <?= !empty($catSec['bg_color']) && $catSec['bg_color'] !== 'transparent' ? 'background-color:' . htmlspecialchars($catSec['bg_color']) . ';' : '' ?>">
    <div class="container">
        <h2 style="font-size: <?= (int)($catSec['title_size'] ?? 20) ?>px; font-weight: 700; margin-bottom: 16px;" data-theme-field="title"><?= htmlspecialchars($catSec['title'] ?? 'Explorar por Categoría') ?></h2>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <?php foreach ($renderCats as $cat):
                $isCatVis = in_array((int)$cat['id'], $visibleCatIds, true);
            ?>
                <a href="<?= $baseUrl ?>/catalog?category=<?= htmlspecialchars($cat['slug']) ?>"
                   data-cat-id="<?= (int)$cat['id'] ?>"
                   data-parent-id="<?= (int)($cat['parent_id'] ?? 0) ?>"
                   data-demand-score="<?= (int)($cat['demand_score'] ?? 0) ?>"
                   data-cat-display="inline-flex"
                   style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 10px 18px; border-radius: 8px; text-decoration: none; color: var(--color-text); font-weight: 500; font-size: 14px; display: <?= $isCatVis ? 'inline-flex' : 'none' ?>; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span><?= htmlspecialchars($cat['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif;
$builtInSections['categories'] = ob_get_clean();

// 3. PRODUCTOS DESTACADOS
ob_start(); ?>
<section data-theme-section="featured_products" data-section-label="Productos Destacados" style="padding: <?= (int)($featSec['padding_y'] ?? 28) ?>px 0; <?= !empty($featSec['bg_color']) && $featSec['bg_color'] !== 'transparent' ? 'background-color:' . htmlspecialchars($featSec['bg_color']) . ';' : '' ?>">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h2 style="font-size: <?= (int)($featSec['title_size'] ?? 22) ?>px; font-weight: 700;" data-theme-field="title"><?= htmlspecialchars($featSec['title'] ?? 'Productos Destacados') ?></h2>
            <a href="<?= $baseUrl ?>/catalog" style="color: var(--color-primary); text-decoration: none; font-weight: 600; font-size: 14px;" data-theme-field="cta_text"><?= htmlspecialchars($featSec['cta_text'] ?? 'Ver todos') ?></a>
        </div>

        <div class="products-grid">
            <?php foreach ($featProducts as $p): ?>
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
                                Ver Detalles
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php $builtInSections['featured_products'] = ob_get_clean();

// 4. PILARES DE CONFIANZA
ob_start(); ?>
<section data-theme-section="trust_pillars" data-section-label="Pilares de Confianza" style="padding: <?= (int)($trustSec['padding_y'] ?? 32) ?>px 0; border-top: 1px solid var(--color-border); <?= !empty($trustSec['bg_color']) && $trustSec['bg_color'] !== 'transparent' ? 'background-color:' . htmlspecialchars($trustSec['bg_color']) . ';' : '' ?>">
    <div class="container">
        <?php if (!empty($trustSec['title'])): ?>
            <h3 style="text-align: center; font-size: 18px; font-weight: 700; margin-bottom: 20px;" data-theme-field="title"><?= htmlspecialchars($trustSec['title']) ?></h3>
        <?php endif; ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; text-align: center;">
            <div style="padding: 20px; background: var(--color-surface); border-radius: 8px; border: 1px solid var(--color-border);">
                <div style="color: var(--color-primary); margin-bottom: 8px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                </div>
                <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Envíos Protegidos</h4>
                <p style="font-size: 12px; color: var(--color-text-light);">Despacho seguro directo a tu puerta.</p>
            </div>
            <div style="padding: 20px; background: var(--color-surface); border-radius: 8px; border: 1px solid var(--color-border);">
                <div style="color: var(--color-primary); margin-bottom: 8px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Compra Confiable</h4>
                <p style="font-size: 12px; color: var(--color-text-light);">Transacciones verificadas y seguras.</p>
            </div>
            <div style="padding: 20px; background: var(--color-surface); border-radius: 8px; border: 1px solid var(--color-border);">
                <div style="color: var(--color-primary); margin-bottom: 8px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                </div>
                <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Garantía de Calidad</h4>
                <p style="font-size: 12px; color: var(--color-text-light);">Compromiso total de satisfacción.</p>
            </div>
            <div style="padding: 20px; background: var(--color-surface); border-radius: 8px; border: 1px solid var(--color-border);">
                <div style="color: var(--color-primary); margin-bottom: 8px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                    </svg>
                </div>
                <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 4px;">Soporte Continuo</h4>
                <p style="font-size: 12px; color: var(--color-text-light);">Acompañamiento antes y después.</p>
            </div>
        </div>
    </div>
</section>
<?php $builtInSections['trust_pillars'] = ob_get_clean();
?>

<main class="scms-template-sections">
    <?php ThemeCustomizer::renderOrderedSections($themeSlug ?? 'minimal-shop', 'home', $builtInSections, $baseUrl); ?>
</main>

<?php ThemeManager::renderFooter(get_defined_vars()); ?>
