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

// 1. MARKET HERO
ob_start(); ?>
<section class="market-hero" data-theme-section="hero" data-section-label="Banner Principal (Hero)">
    <div class="container">
        <div class="market-hero-content">
            <span class="market-hero-tag">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 14 14"></polyline>
                </svg>
                Selección de Calidad Garantizada
            </span>
            <h1 data-theme-field="title"><?= htmlspecialchars($hero['title'] ?? 'Calidad, confianza y variedad directo a tu puerta') ?></h1>
            <p data-theme-field="subtitle"><?= htmlspecialchars($hero['subtitle'] ?? 'Descubre nuestra cuidada selección de productos con atención personalizada, garantía oficial y entrega rápida a tu domicilio.') ?></p>
            <a href="<?= htmlspecialchars($ctaHref) ?>" class="market-cta-btn">
                <span data-theme-field="cta_text"><?= htmlspecialchars($hero['cta_text'] ?? 'Explorar Catálogo Completo') ?></span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </a>
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
<section data-theme-section="categories" data-section-label="Categorías del Mercado" style="padding: <?= (int)($catSec['padding_y'] ?? 24) ?>px 0;">
    <div class="container">
        <div class="section-title-wrap">
            <h2 class="section-title" style="font-size: <?= (int)($catSec['title_size'] ?? 22) ?>px;" data-theme-field="title"><?= htmlspecialchars($catSec['title'] ?? 'Explorar por Categoría') ?></h2>
        </div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;">
            <?php foreach ($renderCats as $cat):
                $isCatVis = in_array((int)$cat['id'], $visibleCatIds, true);
            ?>
                <a href="<?= $baseUrl ?>/catalog?category=<?= htmlspecialchars($cat['slug']) ?>"
                   data-cat-id="<?= (int)$cat['id'] ?>"
                   data-parent-id="<?= (int)($cat['parent_id'] ?? 0) ?>"
                   data-demand-score="<?= (int)($cat['demand_score'] ?? 0) ?>"
                   data-cat-display="inline-flex"
                   style="display: <?= $isCatVis ? 'inline-flex' : 'none' ?>; align-items: center; background: var(--color-surface); border: 1px solid var(--color-border); padding: 10px 18px; border-radius: 999px; font-weight: 600; font-size: 13.5px; color: var(--color-text); text-decoration: none;">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif;
$builtInSections['categories'] = ob_get_clean();

// 3. PRODUCTOS DESTACADOS
ob_start(); ?>
<section data-theme-section="featured_products" data-section-label="Productos Destacados" style="padding: <?= (int)($featSec['padding_y'] ?? 28) ?>px 0;">
    <div class="container">
        <div class="section-title-wrap">
            <span class="section-tag">Selección Especial</span>
            <h2 class="section-title" style="font-size: <?= (int)($featSec['title_size'] ?? 24) ?>px;" data-theme-field="title"><?= htmlspecialchars($featSec['title'] ?? 'Productos Destacados') ?></h2>
        </div>

        <?php if (empty($featProducts)): ?>
            <div style="text-align: center; padding: 48px; background: #FFFFFF; border-radius: 14px; border: 1px solid var(--color-card-border);">
                <p style="color: var(--color-text-muted);">No hay productos registrados en el catálogo en este momento.</p>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($featProducts as $prod): ?>
                    <?php $img = $prod['featured_image'] ?? $prod['image_url'] ?? ''; ?>
                    <article class="market-product-card">
                        <div class="card-img-wrap">
                            <span class="product-badge-fresh">Garantizado</span>
                            <?php if (!empty($img)): ?>
                                <img src="<?= $baseUrl ?>/<?= htmlspecialchars(ltrim($img, '/')) ?>" alt="<?= htmlspecialchars($prod['name']) ?>">
                            <?php else: ?>
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <span class="product-category-name"><?= htmlspecialchars($prod['category_name'] ?? 'Catálogo') ?></span>
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
<?php $builtInSections['featured_products'] = ob_get_clean();

// 4. PILARES DE CONFIANZA
ob_start(); ?>
<section data-theme-section="trust_pillars" data-section-label="Pilares de Confianza" style="padding: <?= (int)($trustSec['padding_y'] ?? 32) ?>px 0;">
    <div class="container">
        <div class="section-title-wrap">
            <span class="section-tag">Compromiso &amp; Calidad</span>
            <h2 class="section-title" data-theme-field="title"><?= htmlspecialchars($trustSec['title'] ?? 'Nuestros Pilares de Confianza') ?></h2>
        </div>

        <div class="category-cards-grid">
            <div class="category-card" style="cursor: default;">
                <div class="category-icon-box" style="background: #DCFCE7; color: #166534;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <h3>Calidad Seleccionada</h3>
                <span>Control riguroso de origen</span>
            </div>
            <div class="category-card" style="cursor: default;">
                <div class="category-icon-box" style="background: #FEF3C7; color: #B45309;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                </div>
                <h3>Despacho Rápido</h3>
                <span>Entrega cuidada y segura</span>
            </div>
            <div class="category-card" style="cursor: default;">
                <div class="category-icon-box" style="background: #E0E7FF; color: #3730A3;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <h3>Precios Claros</h3>
                <span>Transparencia sin recargos</span>
            </div>
            <div class="category-card" style="cursor: default;">
                <div class="category-icon-box" style="background: #FCE7F3; color: #9D174D;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                    </svg>
                </div>
                <h3>Atención Continua</h3>
                <span>Acompañamiento en tu compra</span>
            </div>
        </div>
    </div>
</section>
<?php $builtInSections['trust_pillars'] = ob_get_clean();
?>

<main class="scms-template-sections">
    <?php ThemeCustomizer::renderOrderedSections($themeSlug ?? 'food-market', 'home', $builtInSections, $baseUrl); ?>
</main>

<?php ThemeManager::renderFooter(get_defined_vars()); ?>
