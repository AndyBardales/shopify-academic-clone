<?php
namespace ShopCMS\Core;

use Throwable;

/**
 * Motor de personalización visual por plantilla (Theme Studio).
 * Administra configuración global, estructura de secciones, bloques modulares y renderizado en vivo.
 */
class ThemeCustomizer {
    private static array $configCache = [];

    /**
     * Presets de paleta global según plantilla base.
     */
    private static function themeGlobalDefaults(string $themeSlug): array {
        $presets = [
            'minimal-shop' => [
                'color_bg' => '#FFFFFF',
                'color_surface' => '#F8F9FA',
                'color_header_bg' => '#FFFFFF',
                'color_header_text' => '#111827',
                'color_primary' => '#008060',
                'color_text' => '#111827',
                'color_text_light' => '#6B7280',
                'color_footer_bg' => '#F8F9FA',
                'color_footer_text' => '#111827',
                'font_family' => 'Inter',
                'card_radius' => 10,
            ],
            'fashion' => [
                'color_bg' => '#FFFFFF',
                'color_surface' => '#FAFAFA',
                'color_header_bg' => '#FFFFFF',
                'color_header_text' => '#171717',
                'color_primary' => '#171717',
                'color_text' => '#171717',
                'color_text_light' => '#737373',
                'color_footer_bg' => '#171717',
                'color_footer_text' => '#FFFFFF',
                'font_family' => 'Georgia',
                'card_radius' => 0,
            ],
            'electronics' => [
                'color_bg' => '#F8FAFC',
                'color_surface' => '#FFFFFF',
                'color_header_bg' => '#0F172A',
                'color_header_text' => '#FFFFFF',
                'color_primary' => '#2563EB',
                'color_text' => '#0F172A',
                'color_text_light' => '#64748B',
                'color_footer_bg' => '#0F172A',
                'color_footer_text' => '#E2E8F0',
                'font_family' => 'Inter',
                'card_radius' => 8,
            ],
            'food-market' => [
                'color_bg' => '#FFFBF5',
                'color_surface' => '#FFFFFF',
                'color_header_bg' => '#FFFFFF',
                'color_header_text' => '#1C1917',
                'color_primary' => '#15803D',
                'color_text' => '#1C1917',
                'color_text_light' => '#57534E',
                'color_footer_bg' => '#14532D',
                'color_footer_text' => '#F0FDF4',
                'font_family' => 'Poppins',
                'card_radius' => 14,
            ],
        ];

        $base = $presets[$themeSlug] ?? $presets['minimal-shop'];
        return array_merge([
            'logo_url' => '',
            'logo_height' => 32,
            'base_font_size' => 15,
            'section_spacing' => 36,
        ], $base);
    }

    /**
     * Textos y colores iniciales por defecto del Hero según plantilla.
     */
    private static function themeHeroDefaults(string $themeSlug, string $storeName): array {
        $map = [
            'fashion' => [
                'title' => 'Moda & Vanguardia',
                'subtitle' => 'Temporada 2026 / Colección Cápsula',
                'cta_text' => 'Explorar Catálogo',
                'bg_color' => '#18181B',
                'text_color' => '#FFFFFF',
                'title_size' => 48,
                'subtitle_size' => 14,
                'padding_y' => 90,
            ],
            'electronics' => [
                'title' => 'Catálogo de Componentes & Gadgets',
                'subtitle' => 'Productos disponibles con entrega inmediata y garantía oficial.',
                'cta_text' => 'Ver Todo el Inventario',
                'bg_color' => '#FFFFFF',
                'text_color' => '#0F172A',
                'title_size' => 24,
                'subtitle_size' => 14,
                'padding_y' => 24,
            ],
            'food-market' => [
                'title' => 'Calidad, confianza y variedad directo a tu puerta',
                'subtitle' => 'Descubre nuestra cuidada selección de productos con atención personalizada, garantía oficial y entrega rápida a tu domicilio.',
                'cta_text' => 'Explorar Catálogo Completo',
                'bg_color' => '#14532D',
                'text_color' => '#FFFFFF',
                'title_size' => 38,
                'subtitle_size' => 16,
                'padding_y' => 64,
            ],
        ];

        $d = $map[$themeSlug] ?? [
            'title' => 'Bienvenido a ' . $storeName,
            'subtitle' => 'Explora nuestras colecciones exclusivas y los artículos más destacados de la temporada.',
            'cta_text' => 'Ver Catálogo Completo',
            'bg_color' => '#F8F9FA',
            'text_color' => '#111827',
            'title_size' => 38,
            'subtitle_size' => 16,
            'padding_y' => 56,
        ];

        return array_merge([
            'enabled' => true,
            'cta_link' => '/catalog',
            'bg_image' => '',
            'align' => 'center',
        ], $d);
    }

    /**
     * Esquema completo de configuración por defecto para un tema.
     */
    public static function getDefaults(string $themeSlug): array {
        $storeName = Settings::get('store_name', 'ShopCMS Store');
        $global = self::themeGlobalDefaults($themeSlug);
        $hero = self::themeHeroDefaults($themeSlug, $storeName);

        return [
            'global' => $global,
            'templates' => [
                'home' => [
                    'section_order' => ['hero', 'categories', 'featured_products', 'trust_pillars'],
                    'sections' => [
                        'hero' => $hero,
                        'categories' => [
                            'enabled' => true,
                            'title' => 'Explorar por Categoría',
                            'display_mode' => 'top_popular',
                            'limit' => 4,
                            'selected_ids' => [],
                            'title_size' => 20,
                            'bg_color' => 'transparent',
                            'padding_y' => 24,
                        ],
                        'featured_products' => [
                            'enabled' => true,
                            'title' => $themeSlug === 'fashion' ? 'Piezas Seleccionadas' : 'Productos Destacados',
                            'subtitle' => $themeSlug === 'fashion' ? 'Lanzamientos Exclusivos' : '',
                            'cta_text' => 'Ver todos',
                            'title_size' => 24,
                            'columns_desktop' => 4,
                            'columns_mobile' => 2,
                            'max_items' => 8,
                            'bg_color' => 'transparent',
                            'padding_y' => 28,
                        ],
                        'trust_pillars' => [
                            'enabled' => true,
                            'title' => 'Nuestros Pilares de Confianza',
                            'bg_color' => 'transparent',
                            'padding_y' => 32,
                        ],
                    ],
                    'custom_sections' => [],
                ],
                'catalog' => [
                    'section_order' => ['catalog_header', 'catalog_filters', 'catalog_grid'],
                    'sections' => [
                        'catalog_header' => [
                            'enabled' => true,
                            'title' => 'Catálogo de Productos',
                            'subtitle' => 'Encuentra todo lo que buscas con nuestras opciones y filtros.',
                            'title_size' => 28,
                            'bg_color' => 'transparent',
                            'padding_y' => 16,
                        ],
                        'catalog_filters' => [
                            'enabled' => true,
                            'show_search' => true,
                            'show_categories' => true,
                        ],
                        'catalog_grid' => [
                            'enabled' => true,
                            'columns_desktop' => 4,
                            'columns_mobile' => 2,
                        ],
                    ],
                    'custom_sections' => [],
                ],
                'product' => [
                    'section_order' => ['product_main', 'product_reviews', 'product_related'],
                    'sections' => [
                        'product_main' => [
                            'enabled' => true,
                            'title_size' => 28,
                            'price_size' => 24,
                            'buy_btn_text' => 'Agregar al Carrito',
                            'show_sku' => true,
                            'show_stock_badge' => true,
                        ],
                        'product_reviews' => [
                            'enabled' => true,
                            'title' => 'Opiniones de Clientes',
                        ],
                        'product_related' => [
                            'enabled' => true,
                            'title' => 'También te puede interesar',
                        ],
                    ],
                    'custom_sections' => [],
                ],
                'cart' => [
                    'section_order' => ['cart_header', 'cart_items', 'cart_summary'],
                    'sections' => [
                        'cart_header' => [
                            'enabled' => true,
                            'title' => 'Tu Carrito de Compras',
                            'title_size' => 28,
                        ],
                        'cart_items' => [
                            'enabled' => true,
                        ],
                        'cart_summary' => [
                            'enabled' => true,
                            'show_coupon_box' => true,
                            'checkout_btn_text' => 'Proceder al Pago',
                        ],
                    ],
                    'custom_sections' => [],
                ],
            ],
            'footer' => [
                'enabled' => true,
                'brand_title' => '',
                'description' => $themeSlug === 'fashion'
                    ? 'Línea de moda y diseño contemporáneo con envíos asegurados.'
                    : ($themeSlug === 'electronics'
                        ? 'Hardware, dispositivos y tecnología con garantía oficial.'
                        : 'Tienda virtual construida con arquitectura modular y envíos seguros.'),
                'show_logo' => true,
                'col1_enabled' => true,
                'col1_title' => 'Tienda',
                'col1_links' => "Inicio | /\nCatálogo Completo | /catalog\nMi Cuenta | /account\nCarrito de Compras | /cart",
                'col2_enabled' => true,
                'col2_title' => 'Atención y Ayuda',
                'col2_links' => "Seguimiento de Pedidos | /account\nFinalizar Compra | /checkout\nCrear Cuenta | /register\nIniciar Sesión | /login",
                'show_newsletter' => true,
                'newsletter_title' => 'Novedades y Ofertas',
                'newsletter_subtitle' => 'Suscríbete para recibir promociones y lanzamientos exclusivos.',
                'newsletter_placeholder' => 'Tu correo electrónico',
                'newsletter_btn_text' => 'Suscribirme',
                'show_contact' => true,
                'show_social' => true,
                'show_payment_badges' => true,
                'show_back_to_top' => true,
                'copyright_text' => '© {year} {store}. Todos los derechos reservados.',
                'bottom_right_text' => 'Compra 100% segura y verificada',
                'padding_y' => 42,
                'font_size' => 13,
                'accent_color' => $global['color_primary'] ?? '#008060',
            ],
        ];
    }

    /**
     * Catálogo de bloques modulares agregables por el usuario en el editor.
     */
    public static function availableBlockTypes(): array {
        return [
            'promo_banner' => [
                'label' => 'Banner Promocional / Cupón',
                'desc' => 'Franja destacada con oferta, código promocional y botón directo.',
                'defaults' => [
                    'type' => 'promo_banner',
                    'enabled' => true,
                    'tag' => 'OFERTA ESPECIAL',
                    'title' => 'Aprovecha 10% de descuento en toda la tienda',
                    'body' => 'Usa el cupón BIENVENIDO10 al finalizar tu compra.',
                    'cta_text' => 'Ir a Comprar',
                    'cta_link' => '/catalog',
                    'bg_color' => '#008060',
                    'text_color' => '#FFFFFF',
                    'padding_y' => 36,
                ],
            ],
            'testimonials' => [
                'label' => 'Testimonios de Clientes',
                'desc' => 'Tarjetas con valoraciones reales para generar confianza.',
                'defaults' => [
                    'type' => 'testimonials',
                    'enabled' => true,
                    'title' => 'Lo que dicen nuestros clientes',
                    'quote_1' => 'Excelente calidad y entrega puntual. Superó mis expectativas.',
                    'author_1' => 'María Fernández',
                    'quote_2' => 'El seguimiento en vivo del pedido me dio mucha tranquilidad.',
                    'author_2' => 'Carlos Mendoza',
                    'quote_3' => 'Muy fácil comprar desde el celular y atención rápida.',
                    'author_3' => 'Lucía Paredes',
                    'bg_color' => '#F8F9FA',
                    'text_color' => '#111827',
                    'padding_y' => 40,
                ],
            ],
            'newsletter' => [
                'label' => 'Formulario de Suscripción',
                'desc' => 'Captura correos de visitantes para campañas y lanzamientos.',
                'defaults' => [
                    'type' => 'newsletter',
                    'enabled' => true,
                    'title' => 'Suscríbete y entérate primero',
                    'body' => 'Recibe novedades de temporada, lanzamientos y ofertas exclusivas.',
                    'placeholder' => 'Ingresa tu correo electrónico',
                    'cta_text' => 'Suscribirme',
                    'bg_color' => '#111827',
                    'text_color' => '#FFFFFF',
                    'padding_y' => 40,
                ],
            ],
            'media_text' => [
                'label' => 'Imagen o Video + Historia',
                'desc' => 'Bloque visual de dos columnas ideal para contar tu marca.',
                'defaults' => [
                    'type' => 'media_text',
                    'enabled' => true,
                    'title' => 'Diseñado para durar contigo',
                    'body' => 'Seleccionamos materiales de primera calidad y cuidamos cada detalle antes de despachar tu paquete.',
                    'cta_text' => 'Ver Colección',
                    'cta_link' => '/catalog',
                    'media_url' => '',
                    'bg_color' => '#FFFFFF',
                    'text_color' => '#111827',
                    'padding_y' => 40,
                ],
            ],
            'store_map' => [
                'label' => 'Ubicación y Horarios',
                'desc' => 'Muestra tu tienda física, horario de atención y contacto.',
                'defaults' => [
                    'type' => 'store_map',
                    'enabled' => true,
                    'title' => 'Visítanos o retira tu pedido',
                    'address' => Settings::get('store_address', 'Av. Principal 450, Lima'),
                    'hours' => 'Lunes a Sábado de 9:00 a.m. a 7:00 p.m.',
                    'phone' => Settings::get('store_phone', '+51 987 654 321'),
                    'bg_color' => '#F3F4F6',
                    'text_color' => '#111827',
                    'padding_y' => 36,
                ],
            ],
        ];
    }

    /**
     * Fusión recursiva preservando arrays secuenciales (como section_order).
     */
    private static function mergeConfig(array $defaults, array $saved): array {
        $result = $defaults;
        foreach ($saved as $k => $v) {
            if ($k === 'section_order' && is_array($v)) {
                $result[$k] = array_values(array_unique(array_map('strval', $v)));
            } elseif ($k === 'selected_ids' && is_array($v)) {
                $result[$k] = array_values(array_unique(array_filter(array_map('intval', $v), fn($id) => $id > 0)));
            } elseif ($k === 'custom_sections' && is_array($v)) {
                $result[$k] = $v;
            } elseif (isset($result[$k]) && is_array($result[$k]) && is_array($v)) {
                $result[$k] = self::mergeConfig($result[$k], $v);
            } else {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    public static function getConfig(string $themeSlug, bool $forceRefresh = false): array {
        if (isset(self::$configCache[$themeSlug]) && !$forceRefresh) {
            return self::$configCache[$themeSlug];
        }

        $defaults = self::getDefaults($themeSlug);
        $key = 'theme_custom_' . $themeSlug;
        try {
            $row = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = ?", [$key]);
            if ($row && !empty($row['setting_value'])) {
                $decoded = json_decode($row['setting_value'], true);
                if (is_array($decoded)) {
                    $defaults = self::mergeConfig($defaults, $decoded);
                }
            }
        } catch (Throwable $e) {
            // Usar defaults si BD no responde
        }

        self::$configCache[$themeSlug] = $defaults;
        return $defaults;
    }

    public static function saveConfig(string $themeSlug, array $input): array {
        $merged = self::mergeConfig(self::getDefaults($themeSlug), $input);
        $key = 'theme_custom_' . $themeSlug;
        $json = json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $exists = Database::fetch("SELECT id FROM " . Database::table('settings') . " WHERE setting_key = ?", [$key]);
        if ($exists) {
            Database::update('settings', ['setting_value' => $json], "setting_key = :k", ['k' => $key]);
        } else {
            Database::insert('settings', ['setting_key' => $key, 'setting_value' => $json]);
        }

        self::$configCache[$themeSlug] = $merged;
        return $merged;
    }

    public static function resetConfig(string $themeSlug): array {
        $key = 'theme_custom_' . $themeSlug;
        Database::delete('settings', "setting_key = ?", [$key]);
        unset(self::$configCache[$themeSlug]);
        return self::getDefaults($themeSlug);
    }

    /**
     * Filtra y ordena categorías para la sección "Explorar por Categoría" en la portada.
     */
    public static function filterCategoriesForHome(array $categories, array $catSec): array {
        $mode = (string)($catSec['display_mode'] ?? 'top_popular');
        $limit = max(1, min(12, (int)($catSec['limit'] ?? 4)));
        $selectedIds = array_map('intval', is_array($catSec['selected_ids'] ?? null) ? $catSec['selected_ids'] : []);

        if ($mode === 'custom' && !empty($selectedIds)) {
            $byId = [];
            foreach ($categories as $cat) {
                $byId[(int)$cat['id']] = $cat;
            }
            $picked = [];
            foreach ($selectedIds as $id) {
                if (isset($byId[$id])) {
                    $picked[] = $byId[$id];
                }
            }
            return $picked;
        }

        $pool = $categories;
        if ($mode === 'only_parents') {
            $pool = array_values(array_filter($pool, fn($c) => empty($c['parent_id'])));
        }

        usort($pool, function ($a, $b) {
            $cmp = ((int)($b['demand_score'] ?? 0)) <=> ((int)($a['demand_score'] ?? 0));
            if ($cmp !== 0) return $cmp;
            return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
        });

        return array_slice($pool, 0, $limit);
    }

    public static function isEditorPreview(): bool {
        return Auth::isAdmin() && !empty($_GET['theme_editor']);
    }

    private static function fontCssValue(string $family): string {
        $map = [
            'Inter' => "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
            'Poppins' => "'Poppins', 'Inter', sans-serif",
            'Roboto' => "'Roboto', -apple-system, BlinkMacSystemFont, sans-serif",
            'Georgia' => "Georgia, 'Playfair Display', serif",
            'JetBrains Mono' => "'JetBrains Mono', monospace",
            'System' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
        ];
        return $map[$family] ?? $map['Inter'];
    }

    /**
     * Genera el CSS dinámico global y responsive según la personalización del tema.
     */
    public static function renderGlobalStyleTag(string $themeSlug): string {
        $cfg = self::getConfig($themeSlug);
        $g = $cfg['global'];
        $homeSecs = $cfg['templates']['home']['sections'] ?? [];
        $catGrid = $cfg['templates']['catalog']['sections']['catalog_grid'] ?? [];
        $featColsDesktop = max(2, min(5, (int)($homeSecs['featured_products']['columns_desktop'] ?? 4)));
        $featColsMobile = max(1, min(3, (int)($homeSecs['featured_products']['columns_mobile'] ?? 2)));
        $catColsDesktop = max(2, min(5, (int)($catGrid['columns_desktop'] ?? 4)));
        $catColsMobile = max(1, min(3, (int)($catGrid['columns_mobile'] ?? 2)));

        $heroSec = $homeSecs['hero'] ?? [];
        $heroTitleSize = max(18, min(72, (int)($heroSec['title_size'] ?? 38)));
        $heroSubSize = max(12, min(30, (int)($heroSec['subtitle_size'] ?? 16)));
        $heroPadY = max(16, min(140, (int)($heroSec['padding_y'] ?? 56)));
        $heroBg = (string)($heroSec['bg_color'] ?? '#F8F9FA');
        $heroText = (string)($heroSec['text_color'] ?? '#111827');
        $heroAlign = (($heroSec['align'] ?? 'center') === 'left') ? 'left' : 'center';
        $heroBgImg = trim((string)($heroSec['bg_image'] ?? ''));

        $fontCss = self::fontCssValue((string)($g['font_family'] ?? 'Inter'));
        $baseFont = max(12, min(20, (int)($g['base_font_size'] ?? 15)));
        $radius = max(0, min(24, (int)($g['card_radius'] ?? 10)));
        $logoHeight = max(20, min(80, (int)($g['logo_height'] ?? 32)));

        $baseUrl = defined('APP_URL') ? APP_URL : '';
        $resolvedHeroImg = '';
        if ($heroBgImg !== '') {
            $resolvedHeroImg = (preg_match('#^https?://#i', $heroBgImg) || str_starts_with($heroBgImg, '/'))
                ? $heroBgImg
                : $baseUrl . '/' . ltrim($heroBgImg, '/');
        }

        ob_start();
        ?>
        <style id="shopcms-theme-custom-css">
            :root {
                --color-bg: <?= htmlspecialchars($g['color_bg']) ?>;
                --color-surface: <?= htmlspecialchars($g['color_surface']) ?>;
                --color-primary: <?= htmlspecialchars($g['color_primary']) ?>;
                --color-primary-hover: <?= htmlspecialchars($g['color_primary']) ?>;
                --color-text: <?= htmlspecialchars($g['color_text']) ?>;
                --color-text-light: <?= htmlspecialchars($g['color_text_light']) ?>;
                --color-text-muted: <?= htmlspecialchars($g['color_text_light']) ?>;
                --font-sans: <?= $fontCss ?>;
                --scms-base-size: <?= $baseFont ?>px;
                --scms-radius: <?= $radius ?>px;
                --scms-header-bg: <?= htmlspecialchars($g['color_header_bg']) ?>;
                --scms-header-text: <?= htmlspecialchars($g['color_header_text']) ?>;
                --scms-footer-bg: <?= htmlspecialchars($g['color_footer_bg']) ?>;
                --scms-footer-text: <?= htmlspecialchars($g['color_footer_text']) ?>;
                --scms-hero-bg: <?= htmlspecialchars($heroBg) ?>;
                --scms-hero-text: <?= htmlspecialchars($heroText) ?>;
                --scms-hero-title-size: <?= $heroTitleSize ?>px;
                --scms-hero-sub-size: <?= $heroSubSize ?>px;
                --scms-hero-pad-y: <?= $heroPadY ?>px;
                --scms-hero-align: <?= $heroAlign ?>;
                --scms-feat-cols-desktop: <?= $featColsDesktop ?>;
                --scms-feat-cols-mobile: <?= $featColsMobile ?>;
                --scms-cat-cols-desktop: <?= $catColsDesktop ?>;
                --scms-cat-cols-mobile: <?= $catColsMobile ?>;
            }
            body {
                font-family: var(--font-sans);
                font-size: var(--scms-base-size);
                background-color: var(--color-bg);
                color: var(--color-text);
            }
            .site-header {
                background-color: var(--scms-header-bg) !important;
                color: var(--scms-header-text) !important;
            }
            .site-header a, .site-header .site-logo, .site-header .main-nav a {
                color: var(--scms-header-text);
            }
            .site-logo img.scms-custom-logo {
                height: <?= $logoHeight ?>px;
                width: auto;
                max-width: 190px;
                object-fit: contain;
                display: block;
            }
            .site-footer {
                background-color: var(--scms-footer-bg) !important;
                color: var(--scms-footer-text) !important;
            }
            .btn-store-primary, .market-cta-btn, .btn-tech {
                background-color: var(--color-primary) !important;
                border-color: var(--color-primary) !important;
            }
            .product-card, .fashion-card, .market-product-card, .tech-card {
                border-radius: var(--scms-radius) !important;
            }
            [data-theme-section="hero"], .hero-banner, .fashion-hero, .market-hero {
                background-color: var(--scms-hero-bg) !important;
                color: var(--scms-hero-text) !important;
                padding-top: var(--scms-hero-pad-y) !important;
                padding-bottom: var(--scms-hero-pad-y) !important;
                text-align: var(--scms-hero-align) !important;
                <?php if ($resolvedHeroImg !== ''): ?>
                background-image: linear-gradient(rgba(0,0,0,0.42), rgba(0,0,0,0.42)), url('<?= htmlspecialchars($resolvedHeroImg) ?>') !important;
                background-size: cover !important;
                background-position: center !important;
                <?php endif; ?>
            }
            [data-theme-section="hero"] h1, .hero-banner h1, .fashion-hero h1, .market-hero h1 {
                font-size: var(--scms-hero-title-size) !important;
                color: var(--scms-hero-text) !important;
            }
            [data-theme-section="hero"] p, .hero-banner p, .fashion-hero p, .market-hero p {
                font-size: var(--scms-hero-sub-size) !important;
                color: var(--scms-hero-text) !important;
            }
            [data-theme-section="featured_products"] .products-grid,
            [data-theme-section="featured_products"] .fashion-grid,
            [data-theme-section="featured_products"] .tech-grid {
                display: grid !important;
                grid-template-columns: repeat(var(--scms-feat-cols-desktop), minmax(0, 1fr)) !important;
            }
            [data-theme-section="catalog_grid"] .products-grid,
            [data-theme-section="catalog_grid"] .fashion-grid,
            [data-theme-section="catalog_grid"] .tech-grid {
                display: grid !important;
                grid-template-columns: repeat(var(--scms-cat-cols-desktop), minmax(0, 1fr)) !important;
            }
            @media (max-width: 768px) {
                .site-header .header-inner {
                    height: auto !important;
                    min-height: 56px;
                    padding: 10px 0 !important;
                    flex-wrap: wrap !important;
                    row-gap: 8px !important;
                    column-gap: 10px !important;
                }
                .site-header .site-logo {
                    font-size: 16px !important;
                    white-space: nowrap !important;
                    flex-shrink: 0 !important;
                    max-width: 55vw;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .site-header .site-logo img.scms-custom-logo {
                    max-height: 28px !important;
                    max-width: 130px !important;
                }
                .site-header .header-actions {
                    order: 2 !important;
                    margin-left: auto !important;
                    gap: 8px !important;
                    flex-wrap: nowrap !important;
                    flex-shrink: 0 !important;
                }
                .site-header .header-actions .cart-button {
                    padding: 6px 10px !important;
                    font-size: 12px !important;
                    white-space: nowrap !important;
                }
                .site-header #scms-header-compare-btn {
                    padding: 5px 8px !important;
                    font-size: 11.5px !important;
                    white-space: nowrap !important;
                }
                .site-header .main-nav {
                    order: 3 !important;
                    flex-basis: 100% !important;
                    width: 100% !important;
                    display: flex !important;
                    align-items: center !important;
                    gap: 16px !important;
                    padding-top: 8px !important;
                    border-top: 1px solid rgba(148, 163, 184, 0.25) !important;
                    overflow-x: auto !important;
                    white-space: nowrap !important;
                    font-size: 13px !important;
                }
                .site-header .main-nav a {
                    white-space: nowrap !important;
                }
                [data-theme-section="featured_products"] .products-grid,
                [data-theme-section="featured_products"] .fashion-grid,
                [data-theme-section="featured_products"] .tech-grid {
                    grid-template-columns: repeat(var(--scms-feat-cols-mobile), minmax(0, 1fr)) !important;
                }
                [data-theme-section="catalog_grid"] .products-grid,
                [data-theme-section="catalog_grid"] .fashion-grid,
                [data-theme-section="catalog_grid"] .tech-grid {
                    grid-template-columns: repeat(var(--scms-cat-cols-mobile), minmax(0, 1fr)) !important;
                }
            }
            <?php if (self::isEditorPreview()): ?>
            [data-theme-section] {
                position: relative;
                transition: outline 0.15s ease;
                cursor: pointer;
            }
            [data-theme-section]:hover {
                outline: 2px dashed #2563EB;
                outline-offset: -2px;
            }
            [data-theme-section].scms-editor-selected {
                outline: 2.5px solid #2563EB !important;
                outline-offset: -2px;
            }
            .scms-section-pill {
                display: none;
                position: absolute;
                top: 8px;
                left: 8px;
                z-index: 60;
                background: #2563EB;
                color: #FFFFFF;
                font-family: -apple-system, BlinkMacSystemFont, sans-serif;
                font-size: 11px;
                font-weight: 700;
                padding: 3px 9px;
                border-radius: 5px;
                pointer-events: none;
                box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);
            }
            [data-theme-section]:hover > .scms-section-pill,
            [data-theme-section].scms-editor-selected > .scms-section-pill {
                display: inline-block;
            }
            <?php endif; ?>
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza una sección modular agregada dinámicamente por el administrador.
     */
    public static function renderCustomSection(string $id, array $sec, string $baseUrl): string {
        if (empty($sec['enabled'])) {
            return '';
        }
        $type = $sec['type'] ?? 'promo_banner';
        $bg = htmlspecialchars((string)($sec['bg_color'] ?? '#F8F9FA'));
        $fg = htmlspecialchars((string)($sec['text_color'] ?? '#111827'));
        $padY = max(12, min(100, (int)($sec['padding_y'] ?? 36)));
        $attr = 'data-theme-section="' . htmlspecialchars($id) . '" data-section-label="' . htmlspecialchars($sec['title'] ?? 'Bloque Personalizado') . '"';

        ob_start();
        if ($type === 'promo_banner') {
            $link = trim((string)($sec['cta_link'] ?? '/catalog'));
            $href = str_starts_with($link, 'http') ? $link : $baseUrl . '/' . ltrim($link, '/');
            ?>
            <section <?= $attr ?> style="background: <?= $bg ?>; color: <?= $fg ?>; padding: <?= $padY ?>px 20px; margin: 24px 0; border-radius: var(--scms-radius, 10px);">
                <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 18px;">
                    <div>
                        <?php if (!empty($sec['tag'])): ?>
                            <div style="font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; opacity: 0.85; margin-bottom: 4px;" data-theme-field="tag"><?= htmlspecialchars($sec['tag']) ?></div>
                        <?php endif; ?>
                        <h3 style="font-size: 22px; font-weight: 700; margin: 0 0 4px;" data-theme-field="title"><?= htmlspecialchars($sec['title'] ?? '') ?></h3>
                        <p style="font-size: 14px; opacity: 0.9; margin: 0;" data-theme-field="body"><?= htmlspecialchars($sec['body'] ?? '') ?></p>
                    </div>
                    <?php if (!empty($sec['cta_text'])): ?>
                        <a href="<?= htmlspecialchars($href) ?>" style="background: <?= $fg ?>; color: <?= $bg ?>; padding: 11px 22px; border-radius: 8px; font-weight: 700; font-size: 13.5px; text-decoration: none;" data-theme-field="cta_text"><?= htmlspecialchars($sec['cta_text']) ?></a>
                    <?php endif; ?>
                </div>
            </section>
            <?php
        } elseif ($type === 'testimonials') {
            ?>
            <section <?= $attr ?> style="background: <?= $bg ?>; color: <?= $fg ?>; padding: <?= $padY ?>px 20px; margin: 24px 0; border-radius: var(--scms-radius, 10px);">
                <div class="container">
                    <h2 style="text-align: center; font-size: 22px; font-weight: 700; margin-bottom: 24px;" data-theme-field="title"><?= htmlspecialchars($sec['title'] ?? 'Testimonios') ?></h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px;">
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                            <div style="background: #FFFFFF; color: #111827; padding: 20px; border-radius: 10px; border: 1px solid #E5E7EB;">
                                <div style="display: flex; gap: 3px; color: #F59E0B; margin-bottom: 10px;">
                                    <?php for ($s = 0; $s < 5; $s++): ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                    <?php endfor; ?>
                                </div>
                                <p style="font-size: 13.5px; line-height: 1.5; margin-bottom: 10px;" data-theme-field="quote_<?= $i ?>">"<?= htmlspecialchars($sec["quote_{$i}"] ?? '') ?>"</p>
                                <strong style="font-size: 12.5px; color: #4B5563;" data-theme-field="author_<?= $i ?>"><?= htmlspecialchars($sec["author_{$i}"] ?? '') ?></strong>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>
            <?php
        } elseif ($type === 'newsletter') {
            ?>
            <section <?= $attr ?> style="background: <?= $bg ?>; color: <?= $fg ?>; padding: <?= $padY ?>px 20px; margin: 24px 0; border-radius: var(--scms-radius, 10px); text-align: center;">
                <div class="container" style="max-width: 620px;">
                    <h3 style="font-size: 22px; font-weight: 700; margin-bottom: 6px;" data-theme-field="title"><?= htmlspecialchars($sec['title'] ?? '') ?></h3>
                    <p style="font-size: 14px; opacity: 0.88; margin-bottom: 18px;" data-theme-field="body"><?= htmlspecialchars($sec['body'] ?? '') ?></p>
                    <form onsubmit="event.preventDefault(); this.querySelector('.nl-ok').style.display='block';" style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                        <input type="email" required placeholder="<?= htmlspecialchars($sec['placeholder'] ?? 'Tu correo') ?>" style="flex: 1; min-width: 220px; padding: 11px 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.25); font-size: 14px;">
                        <button type="submit" style="background: var(--color-primary, #008060); color: #FFFFFF; border: none; padding: 11px 22px; border-radius: 8px; font-weight: 700; cursor: pointer;" data-theme-field="cta_text"><?= htmlspecialchars($sec['cta_text'] ?? 'Suscribirme') ?></button>
                        <div class="nl-ok" style="display: none; width: 100%; font-size: 12.5px; margin-top: 8px; color: #A7F3D0;">¡Gracias por suscribirte!</div>
                    </form>
                </div>
            </section>
            <?php
        } elseif ($type === 'media_text') {
            $link = trim((string)($sec['cta_link'] ?? '/catalog'));
            $href = str_starts_with($link, 'http') ? $link : $baseUrl . '/' . ltrim($link, '/');
            $media = trim((string)($sec['media_url'] ?? ''));
            $mediaSrc = $media !== '' ? ((preg_match('#^https?://#i', $media) || str_starts_with($media, '/')) ? $media : $baseUrl . '/' . ltrim($media, '/')) : '';
            ?>
            <section <?= $attr ?> style="background: <?= $bg ?>; color: <?= $fg ?>; padding: <?= $padY ?>px 20px; margin: 24px 0; border-radius: var(--scms-radius, 10px);">
                <div class="container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 28px; align-items: center;">
                    <div style="background: #E5E7EB; border-radius: 10px; min-height: 220px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <?php if ($mediaSrc !== ''): ?>
                            <img src="<?= htmlspecialchars($mediaSrc) ?>" alt="<?= htmlspecialchars($sec['title'] ?? '') ?>" style="width: 100%; height: 100%; max-height: 300px; object-fit: cover;">
                        <?php else: ?>
                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3 style="font-size: 24px; font-weight: 700; margin-bottom: 10px;" data-theme-field="title"><?= htmlspecialchars($sec['title'] ?? '') ?></h3>
                        <p style="font-size: 14.5px; line-height: 1.6; opacity: 0.9; margin-bottom: 18px;" data-theme-field="body"><?= htmlspecialchars($sec['body'] ?? '') ?></p>
                        <?php if (!empty($sec['cta_text'])): ?>
                            <a href="<?= htmlspecialchars($href) ?>" class="btn-store btn-store-primary" style="display: inline-block; padding: 10px 20px; border-radius: 8px; color: #FFF; text-decoration: none; font-weight: 600;" data-theme-field="cta_text"><?= htmlspecialchars($sec['cta_text']) ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <?php
        } elseif ($type === 'store_map') {
            ?>
            <section <?= $attr ?> style="background: <?= $bg ?>; color: <?= $fg ?>; padding: <?= $padY ?>px 20px; margin: 24px 0; border-radius: var(--scms-radius, 10px);">
                <div class="container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; align-items: center;">
                    <div>
                        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 8px;" data-theme-field="title"><?= htmlspecialchars($sec['title'] ?? '') ?></h3>
                        <p style="font-size: 13.5px; margin-bottom: 6px;"><strong>Dirección:</strong> <span data-theme-field="address"><?= htmlspecialchars($sec['address'] ?? '') ?></span></p>
                        <p style="font-size: 13.5px; margin-bottom: 6px;"><strong>Horario:</strong> <span data-theme-field="hours"><?= htmlspecialchars($sec['hours'] ?? '') ?></span></p>
                        <p style="font-size: 13.5px; margin-bottom: 0;"><strong>Teléfono:</strong> <span data-theme-field="phone"><?= htmlspecialchars($sec['phone'] ?? '') ?></span></p>
                    </div>
                    <div style="background: #FFFFFF; border: 1px solid #D1D5DB; border-radius: 10px; padding: 24px; text-align: center; color: #6B7280;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="margin-bottom: 8px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <div style="font-size: 13px; font-weight: 600;">Punto de Atención y Recojo</div>
                    </div>
                </div>
            </section>
            <?php
        }
        return ob_get_clean();
    }

    /**
     * Renderiza las secciones de una página respetando orden exacto y bloques personalizados.
     */
    public static function renderOrderedSections(string $themeSlug, string $templateName, array $builtInHtmlMap, string $baseUrl): void {
        $cfg = self::getConfig($themeSlug);
        $tpl = $cfg['templates'][$templateName] ?? [];
        $order = $tpl['section_order'] ?? array_keys($builtInHtmlMap);
        $sectionsCfg = $tpl['sections'] ?? [];
        $customSecs = $tpl['custom_sections'] ?? [];
        $rendered = [];

        $wrapBuiltIn = function (string $id, string $html) use ($sectionsCfg) {
            if (isset($sectionsCfg[$id]['enabled']) && !$sectionsCfg[$id]['enabled']) {
                return '';
            }
            return $html;
        };

        foreach ($order as $id) {
            if (isset($builtInHtmlMap[$id])) {
                echo $wrapBuiltIn($id, $builtInHtmlMap[$id]);
                $rendered[$id] = true;
            } elseif (isset($customSecs[$id])) {
                echo self::renderCustomSection($id, $customSecs[$id], $baseUrl);
                $rendered[$id] = true;
            }
        }

        // Asegurar cualquier sección base faltante en el orden
        foreach ($builtInHtmlMap as $id => $html) {
            if (empty($rendered[$id])) {
                echo $wrapBuiltIn($id, $html);
            }
        }
    }

    /**
     * Convierte líneas "Texto | /url" en pares seguros para columnas del footer.
     */
    public static function parseFooterLinks(string $raw, string $baseUrl, bool $hideGuestAuthLinks = false): array {
        $links = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($raw));
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $parts = explode('|', $line, 2);
            $label = trim($parts[0]);
            if ($label === '') continue;
            $hrefRaw = isset($parts[1]) ? trim($parts[1]) : '#';
            if ($hideGuestAuthLinks) {
                $path = strtolower(trim((string)(parse_url($hrefRaw, PHP_URL_PATH) ?: ''), '/'));
                if ($path === 'login' || $path === 'register') {
                    continue;
                }
            }
            $href = ($hrefRaw === '#' || str_starts_with($hrefRaw, 'http') || str_starts_with($hrefRaw, 'mailto:') || str_starts_with($hrefRaw, 'tel:'))
                ? $hrefRaw
                : rtrim($baseUrl, '/') . '/' . ltrim($hrefRaw, '/');
            $links[] = ['label' => $label, 'href' => $href];
        }
        return $links;
    }

    /**
     * Renderiza el pie de página modular editable en todos los temas.
     */
    public static function renderRichFooter(string $themeSlug, string $storeName, string $baseUrl): string {
        $cfg = self::getConfig($themeSlug);
        $f = $cfg['footer'] ?? self::getDefaults($themeSlug)['footer'];
        $g = $cfg['global'] ?? [];

        $enabled = ($f['enabled'] ?? true) !== false;
        if (!$enabled && !self::isEditorPreview()) {
            return '';
        }

        $brandName = trim((string)($f['brand_title'] ?? '')) ?: $storeName;
        $desc = (string)($f['description'] ?? '');
        $padY = max(16, min(100, (int)($f['padding_y'] ?? 42)));
        $fontSize = max(11, min(16, (int)($f['font_size'] ?? 13)));
        $accent = (string)($f['accent_color'] ?? ($g['color_primary'] ?? '#008060'));
        $bg = (string)($g['color_footer_bg'] ?? '#F8F9FA');
        $fg = (string)($g['color_footer_text'] ?? '#111827');

        $logoUrl = trim((string)($g['logo_url'] ?? ''));
        $resolvedLogo = $logoUrl !== ''
            ? ((preg_match('#^https?://#i', $logoUrl) || str_starts_with($logoUrl, '/')) ? $logoUrl : rtrim($baseUrl, '/') . '/' . ltrim($logoUrl, '/'))
            : '';

        $hideGuestAuthLinks = !self::isEditorPreview() && Auth::isCustomer();
        $col1Links = self::parseFooterLinks((string)($f['col1_links'] ?? ''), $baseUrl, $hideGuestAuthLinks);
        $col2Links = self::parseFooterLinks((string)($f['col2_links'] ?? ''), $baseUrl, $hideGuestAuthLinks);

        $storePhone = trim(Settings::get('store_phone', ''));
        $storeEmail = trim(Settings::get('store_email', ''));
        $storeAddress = trim(Settings::get('store_address', ''));
        $ig = trim(Settings::get('social_instagram', ''));
        $fb = trim(Settings::get('social_facebook', ''));
        $tt = trim(Settings::get('social_tiktok', ''));

        $rawCopy = (string)($f['copyright_text'] ?? '© {year} {store}. Todos los derechos reservados.');
        $copyrightRendered = str_replace(['{year}', '{store}'], [date('Y'), $brandName], $rawCopy);

        ob_start();
        ?>
        <footer class="site-footer scms-rich-footer"
                data-theme-section="footer"
                data-section-label="Pie de Página"
                style="background-color: var(--scms-footer-bg, <?= htmlspecialchars($bg) ?>); color: var(--scms-footer-text, <?= htmlspecialchars($fg) ?>); padding: <?= $padY ?>px 0 24px; font-size: <?= $fontSize ?>px; border-top: 3px solid <?= htmlspecialchars($accent) ?>; margin-top: auto; <?= !$enabled ? 'display:none;' : '' ?>">
            <div class="container">
                <div class="scms-footer-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 28px; margin-bottom: 28px;">
                    <!-- BLOQUE MARCA Y CONTACTO -->
                    <div>
                        <div class="scms-footer-logo-wrap" style="margin-bottom: 10px; <?= (!empty($f['show_logo']) && $resolvedLogo !== '') ? '' : 'display:none;' ?>">
                            <?php if ($resolvedLogo !== ''): ?>
                                <img src="<?= htmlspecialchars($resolvedLogo) ?>" alt="<?= htmlspecialchars($brandName) ?>" style="max-height: 36px; width: auto; object-fit: contain;">
                            <?php endif; ?>
                        </div>
                        <h4 style="font-size: 16px; font-weight: 700; margin: 0 0 8px; color: inherit;" data-footer-field="brand_title"><?= htmlspecialchars($brandName) ?></h4>
                        <p style="opacity: 0.85; line-height: 1.55; margin: 0 0 12px;" data-footer-field="description"><?= htmlspecialchars($desc) ?></p>

                        <div class="scms-footer-contact" style="display: <?= !empty($f['show_contact']) ? 'flex' : 'none' ?>; flex-direction: column; gap: 4px; opacity: 0.85; font-size: 12px; margin-bottom: 12px;">
                            <?php if ($storeAddress !== ''): ?><span><strong>Dir:</strong> <?= htmlspecialchars($storeAddress) ?></span><?php endif; ?>
                            <?php if ($storePhone !== ''): ?><span><strong>Tel:</strong> <?= htmlspecialchars($storePhone) ?></span><?php endif; ?>
                            <?php if ($storeEmail !== ''): ?><span><strong>Email:</strong> <?= htmlspecialchars($storeEmail) ?></span><?php endif; ?>
                        </div>

                        <div class="scms-footer-social" style="display: <?= !empty($f['show_social']) ? 'flex' : 'none' ?>; gap: 10px; align-items: center;">
                            <?php if ($ig !== ''): ?>
                                <a href="<?= htmlspecialchars($ig) ?>" target="_blank" rel="noopener" title="Instagram" style="color: inherit; opacity: 0.85; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; font-weight: 600; font-size: 12px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                                    <span>Instagram</span>
                                </a>
                            <?php endif; ?>
                            <?php if ($fb !== ''): ?>
                                <a href="<?= htmlspecialchars($fb) ?>" target="_blank" rel="noopener" title="Facebook" style="color: inherit; opacity: 0.85; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; font-weight: 600; font-size: 12px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                                    <span>Facebook</span>
                                </a>
                            <?php endif; ?>
                            <?php if ($tt !== ''): ?>
                                <a href="<?= htmlspecialchars($tt) ?>" target="_blank" rel="noopener" title="TikTok" style="color: inherit; opacity: 0.85; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; font-weight: 600; font-size: 12px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>
                                    <span>TikTok</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- COLUMNA DE ENLACES 1 -->
                    <div class="scms-footer-col1" style="<?= !empty($f['col1_enabled']) ? '' : 'display:none;' ?>">
                        <h5 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 700; margin: 0 0 10px; color: <?= htmlspecialchars($accent) ?>;" data-footer-field="col1_title"><?= htmlspecialchars($f['col1_title'] ?? 'Tienda') ?></h5>
                        <ul class="scms-footer-col1-list" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 7px;">
                            <?php foreach ($col1Links as $lnk): ?>
                                <li><a href="<?= htmlspecialchars($lnk['href']) ?>" style="color: inherit; opacity: 0.85; text-decoration: none;"><?= htmlspecialchars($lnk['label']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- COLUMNA DE ENLACES 2 -->
                    <div class="scms-footer-col2" style="<?= !empty($f['col2_enabled']) ? '' : 'display:none;' ?>">
                        <h5 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 700; margin: 0 0 10px; color: <?= htmlspecialchars($accent) ?>;" data-footer-field="col2_title"><?= htmlspecialchars($f['col2_title'] ?? 'Atención y Ayuda') ?></h5>
                        <ul class="scms-footer-col2-list" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 7px;">
                            <?php foreach ($col2Links as $lnk): ?>
                                <li><a href="<?= htmlspecialchars($lnk['href']) ?>" style="color: inherit; opacity: 0.85; text-decoration: none;"><?= htmlspecialchars($lnk['label']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- NEWSLETTER EN FOOTER -->
                    <div class="scms-footer-newsletter" style="<?= !empty($f['show_newsletter']) ? '' : 'display:none;' ?>">
                        <h5 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 700; margin: 0 0 8px; color: <?= htmlspecialchars($accent) ?>;" data-footer-field="newsletter_title"><?= htmlspecialchars($f['newsletter_title'] ?? 'Novedades y Ofertas') ?></h5>
                        <p style="opacity: 0.85; line-height: 1.45; margin: 0 0 10px; font-size: 12.5px;" data-footer-field="newsletter_subtitle"><?= htmlspecialchars($f['newsletter_subtitle'] ?? '') ?></p>
                        <form onsubmit="event.preventDefault(); this.querySelector('.scms-fnl-msg').style.display='block'; this.reset();" style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <input type="email" required class="scms-footer-nl-input" placeholder="<?= htmlspecialchars($f['newsletter_placeholder'] ?? 'Tu correo') ?>" style="flex: 1; min-width: 140px; padding: 8px 10px; border-radius: 6px; border: 1px solid rgba(148, 163, 184, 0.45); font-size: 12.5px; background: rgba(255,255,255,0.92); color: #111827;">
                            <button type="submit" class="scms-footer-nl-btn" style="background: <?= htmlspecialchars($accent) ?>; color: #FFFFFF; border: none; border-radius: 6px; padding: 8px 14px; font-size: 12.5px; font-weight: 700; cursor: pointer;" data-footer-field="newsletter_btn_text"><?= htmlspecialchars($f['newsletter_btn_text'] ?? 'Suscribirme') ?></button>
                            <div class="scms-fnl-msg" style="display: none; width: 100%; font-size: 11.5px; color: #10B981; margin-top: 4px; font-weight: 600;">¡Suscripción registrada!</div>
                        </form>
                    </div>
                </div>

                <!-- BARRA DE PAGOS Y SUBIR -->
                <div class="scms-footer-bottom" style="padding-top: 18px; border-top: 1px solid rgba(148, 163, 184, 0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <div style="font-weight: 500; opacity: 0.9;" data-footer-field="copyright_rendered"><?= htmlspecialchars($copyrightRendered) ?></div>
                        <div style="font-size: 11.5px; opacity: 0.72;" data-footer-field="bottom_right_text"><?= htmlspecialchars($f['bottom_right_text'] ?? '') ?></div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <div class="scms-footer-payments" style="display: <?= !empty($f['show_payment_badges']) ? 'flex' : 'none' ?>; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <span style="padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(148,163,184,0.4); font-size: 11px; font-weight: 700; opacity: 0.9;">Efectivo / COD</span>
                            <span style="padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(148,163,184,0.4); font-size: 11px; font-weight: 700; opacity: 0.9;">Transferencia / Yape</span>
                            <span style="padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(148,163,184,0.4); font-size: 11px; font-weight: 700; opacity: 0.9;">Visa / Mastercard</span>
                            <span style="padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(148,163,184,0.4); font-size: 11px; font-weight: 700; opacity: 0.9;">PayPal</span>
                        </div>

                        <button type="button" class="scms-footer-top-btn" onclick="window.scrollTo({top:0, behavior:'smooth'});" style="display: <?= !empty($f['show_back_to_top']) ? 'inline-flex' : 'none' ?>; align-items: center; gap: 5px; background: rgba(148,163,184,0.16); color: inherit; border: 1px solid rgba(148,163,184,0.35); border-radius: 6px; padding: 6px 10px; font-size: 11.5px; font-weight: 600; cursor: pointer;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="18 15 12 9 6 15"/></svg>
                            <span>Subir</span>
                        </button>
                    </div>
                </div>
            </div>
        </footer>
        <?php
        return ob_get_clean();
    }

    /**
     * Script puente de vista previa en vivo dentro del iframe del Theme Studio.
     */
    public static function renderEditorBridgeScript(string $themeSlug): string {
        if (!self::isEditorPreview()) {
            return '';
        }
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        ob_start();
        ?>
        <script id="shopcms-editor-bridge">
        (function() {
            function labelSections() {
                document.querySelectorAll('[data-theme-section]').forEach(function(el) {
                    if (!el.querySelector(':scope > .scms-section-pill')) {
                        var pill = document.createElement('span');
                        pill.className = 'scms-section-pill';
                        pill.textContent = el.getAttribute('data-section-label') || el.getAttribute('data-theme-section');
                        el.insertBefore(pill, el.firstChild);
                    }
                });
            }
            labelSections();
            document.addEventListener('DOMContentLoaded', labelSections);

            // Interceptar clics para seleccionar sección en lugar de navegar accidentalmente
            document.addEventListener('click', function(e) {
                var sec = e.target.closest('[data-theme-section]');
                var anchor = e.target.closest('a');
                if (anchor) {
                    e.preventDefault();
                }
                if (sec) {
                    e.stopPropagation();
                    document.querySelectorAll('[data-theme-section].scms-editor-selected').forEach(function(item) {
                        item.classList.remove('scms-editor-selected');
                    });
                    sec.classList.add('scms-editor-selected');
                    window.parent.postMessage({
                        type: 'shopcms_select_section',
                        sectionId: sec.getAttribute('data-theme-section')
                    }, '*');
                }
            }, true);

            var fontMap = {
                'Inter': "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
                'Poppins': "'Poppins', 'Inter', sans-serif",
                'Roboto': "'Roboto', -apple-system, BlinkMacSystemFont, sans-serif",
                'Georgia': "Georgia, 'Playfair Display', serif",
                'JetBrains Mono': "'JetBrains Mono', monospace",
                'System': "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
            };

            window.addEventListener('message', function(e) {
                var msg = e.data;
                if (!msg || msg.type !== 'shopcms_apply_config') return;
                var cfg = msg.config || {};
                var g = cfg.global || {};
                var root = document.documentElement.style;

                if (g.color_bg) root.setProperty('--color-bg', g.color_bg);
                if (g.color_surface) root.setProperty('--color-surface', g.color_surface);
                if (g.color_primary) {
                    root.setProperty('--color-primary', g.color_primary);
                    root.setProperty('--color-primary-hover', g.color_primary);
                }
                if (g.color_text) root.setProperty('--color-text', g.color_text);
                if (g.color_text_light) {
                    root.setProperty('--color-text-light', g.color_text_light);
                    root.setProperty('--color-text-muted', g.color_text_light);
                }
                if (g.color_header_bg) root.setProperty('--scms-header-bg', g.color_header_bg);
                if (g.color_header_text) root.setProperty('--scms-header-text', g.color_header_text);
                if (g.color_footer_bg) root.setProperty('--scms-footer-bg', g.color_footer_bg);
                if (g.color_footer_text) root.setProperty('--scms-footer-text', g.color_footer_text);
                if (g.font_family && fontMap[g.font_family]) root.setProperty('--font-sans', fontMap[g.font_family]);
                if (g.base_font_size) root.setProperty('--scms-base-size', g.base_font_size + 'px');
                if (g.card_radius !== undefined) root.setProperty('--scms-radius', g.card_radius + 'px');

                // Actualizar logo en cabecera si existe
                var logoEl = document.querySelector('.site-logo');
                if (logoEl && g.logo_url !== undefined) {
                    var img = logoEl.querySelector('img.scms-custom-logo');
                    var svg = logoEl.querySelector('svg');
                    if (g.logo_url && g.logo_url.trim() !== '') {
                        var src = g.logo_url.trim();
                        if (!/^https?:\/\//i.test(src) && src.indexOf('/') !== 0) {
                            src = '<?= htmlspecialchars($baseUrl) ?>/' + src;
                        }
                        if (!img) {
                            img = document.createElement('img');
                            img.className = 'scms-custom-logo';
                            logoEl.insertBefore(img, logoEl.firstChild);
                        }
                        img.src = src;
                        if (g.logo_height) img.style.height = g.logo_height + 'px';
                        img.style.display = 'block';
                        if (svg) svg.style.display = 'none';
                    } else {
                        if (img) img.style.display = 'none';
                        if (svg) svg.style.display = '';
                    }
                }

                // Actualizar plantilla actual
                var tplName = msg.template || 'home';
                var tpl = (cfg.templates && cfg.templates[tplName]) ? cfg.templates[tplName] : null;
                if (!tpl) return;

                if (tplName === 'home' && tpl.sections && tpl.sections.hero) {
                    var h = tpl.sections.hero;
                    if (h.bg_color) root.setProperty('--scms-hero-bg', h.bg_color);
                    if (h.text_color) root.setProperty('--scms-hero-text', h.text_color);
                    if (h.title_size) root.setProperty('--scms-hero-title-size', h.title_size + 'px');
                    if (h.subtitle_size) root.setProperty('--scms-hero-sub-size', h.subtitle_size + 'px');
                    if (h.padding_y) root.setProperty('--scms-hero-pad-y', h.padding_y + 'px');
                    if (h.align) root.setProperty('--scms-hero-align', h.align);
                    var heroEl = document.querySelector('[data-theme-section="hero"]');
                    if (heroEl && h.bg_image !== undefined) {
                        var hImg = (h.bg_image || '').trim();
                        if (hImg !== '') {
                            var resolved = (/^https?:\/\//i.test(hImg) || hImg.indexOf('/') === 0) ? hImg : '<?= htmlspecialchars($baseUrl) ?>/' + hImg;
                            heroEl.style.backgroundImage = "linear-gradient(rgba(0,0,0,0.42), rgba(0,0,0,0.42)), url('" + resolved + "')";
                            heroEl.style.backgroundSize = 'cover';
                            heroEl.style.backgroundPosition = 'center';
                        } else {
                            heroEl.style.backgroundImage = 'none';
                        }
                    }
                }

                if (tplName === 'home' && tpl.sections && tpl.sections.featured_products) {
                    var fp = tpl.sections.featured_products;
                    if (fp.columns_desktop) root.setProperty('--scms-feat-cols-desktop', fp.columns_desktop);
                    if (fp.columns_mobile) root.setProperty('--scms-feat-cols-mobile', fp.columns_mobile);
                }

                if (tplName === 'home' && tpl.sections && tpl.sections.categories) {
                    var catCfg = tpl.sections.categories;
                    var catSectionEl = document.querySelector('[data-theme-section="categories"]');
                    if (catSectionEl) {
                        var catNodes = Array.from(catSectionEl.querySelectorAll('[data-cat-id]'));
                        var mode = catCfg.display_mode || 'top_popular';
                        var limit = Math.max(1, parseInt(catCfg.limit || 4, 10));
                        var selectedIds = Array.isArray(catCfg.selected_ids) ? catCfg.selected_ids.map(Number) : [];

                        if (mode === 'custom' && selectedIds.length > 0) {
                            catNodes.forEach(function(n) {
                                var cid = Number(n.getAttribute('data-cat-id'));
                                var disp = n.getAttribute('data-cat-display') || 'inline-flex';
                                n.style.display = selectedIds.indexOf(cid) !== -1 ? disp : 'none';
                            });
                        } else {
                            var sorted = catNodes.slice().sort(function(a, b) {
                                var da = Number(a.getAttribute('data-demand-score') || 0);
                                var db = Number(b.getAttribute('data-demand-score') || 0);
                                if (db !== da) return db - da;
                                return (a.textContent || '').localeCompare(b.textContent || '');
                            });
                            var shownCount = 0;
                            sorted.forEach(function(n) {
                                var isParent = Number(n.getAttribute('data-parent-id') || 0) === 0;
                                if (mode === 'only_parents' && !isParent) {
                                    n.style.display = 'none';
                                    return;
                                }
                                if (shownCount < limit) {
                                    n.style.display = n.getAttribute('data-cat-display') || 'inline-flex';
                                    shownCount++;
                                } else {
                                    n.style.display = 'none';
                                }
                            });
                        }
                    }
                }

                if (tplName === 'catalog' && tpl.sections && tpl.sections.catalog_grid) {
                    var cg = tpl.sections.catalog_grid;
                    if (cg.columns_desktop) root.setProperty('--scms-cat-cols-desktop', cg.columns_desktop);
                    if (cg.columns_mobile) root.setProperty('--scms-cat-cols-mobile', cg.columns_mobile);
                }

                var allSecs = Object.assign({}, tpl.sections || {}, tpl.custom_sections || {});
                Object.keys(allSecs).forEach(function(secId) {
                    var secData = allSecs[secId];
                    var el = document.querySelector('[data-theme-section="' + secId + '"]');
                    if (!el) return;
                    el.style.display = (secData.enabled === false) ? 'none' : '';
                    if (secData.bg_color && secId !== 'hero' && secData.bg_color !== 'transparent') {
                        el.style.backgroundColor = secData.bg_color;
                    } else if (secData.bg_color === 'transparent' && secId !== 'hero') {
                        el.style.backgroundColor = '';
                    }
                    if (secData.text_color && secId !== 'hero') {
                        el.style.color = secData.text_color;
                    }
                    if (secData.padding_y && secId !== 'hero') {
                        el.style.paddingTop = secData.padding_y + 'px';
                        el.style.paddingBottom = secData.padding_y + 'px';
                    }

                    // Campos de texto en tiempo real
                    Object.keys(secData).forEach(function(fieldKey) {
                        var target = el.querySelector('[data-theme-field="' + fieldKey + '"]');
                        if (target && typeof secData[fieldKey] === 'string') {
                            target.textContent = secData[fieldKey];
                        }
                    });
                    if (secData.title_size && secId !== 'hero') {
                        var heading = el.querySelector('h1, h2, h3');
                        if (heading) heading.style.fontSize = secData.title_size + 'px';
                    }
                });

                // Reordenar DOM de secciones modulares dentro de .scms-template-sections en tiempo real
                var sectionsContainer = document.querySelector('.scms-template-sections');
                if (sectionsContainer && Array.isArray(tpl.section_order)) {
                    tpl.section_order.forEach(function(secId) {
                        var el = sectionsContainer.querySelector(':scope > [data-theme-section="' + secId + '"]');
                        if (el) {
                            sectionsContainer.appendChild(el);
                        }
                    });
                }

                // Resaltado activo
                if (msg.selectedSection) {
                    document.querySelectorAll('[data-theme-section].scms-editor-selected').forEach(function(item) {
                        item.classList.remove('scms-editor-selected');
                    });
                    var activeEl = document.querySelector('[data-theme-section="' + msg.selectedSection + '"]');
                    if (activeEl) activeEl.classList.add('scms-editor-selected');
                }

                // Actualización en tiempo real del Footer modular
                if (cfg.footer) {
                    var f = cfg.footer;
                    var fEl = document.querySelector('footer[data-theme-section="footer"]');
                    if (fEl) {
                        fEl.style.display = (f.enabled === false) ? 'none' : '';
                        if (f.padding_y) fEl.style.paddingTop = f.padding_y + 'px';
                        if (f.font_size) fEl.style.fontSize = f.font_size + 'px';
                        if (f.accent_color) {
                            fEl.style.borderTopColor = f.accent_color;
                            fEl.querySelectorAll('h5').forEach(function(h) { h.style.color = f.accent_color; });
                            var nlBtn = fEl.querySelector('.scms-footer-nl-btn');
                            if (nlBtn) nlBtn.style.backgroundColor = f.accent_color;
                        }

                        var setF = function(field, val) {
                            var target = fEl.querySelector('[data-footer-field="' + field + '"]');
                            if (target && val !== undefined) target.textContent = val;
                        };
                        var resolvedBrand = (f.brand_title && f.brand_title.trim() !== '') ? f.brand_title.trim() : <?= json_encode(Settings::get('store_name', 'ShopCMS Store')) ?>;
                        setF('brand_title', resolvedBrand);
                        setF('description', f.description || '');
                        setF('col1_title', f.col1_title || 'Tienda');
                        setF('col2_title', f.col2_title || 'Atención y Ayuda');
                        setF('newsletter_title', f.newsletter_title || '');
                        setF('newsletter_subtitle', f.newsletter_subtitle || '');
                        setF('newsletter_btn_text', f.newsletter_btn_text || 'Suscribirme');
                        setF('bottom_right_text', f.bottom_right_text || '');

                        var rawCopy = f.copyright_text || '';
                        var renderedCopy = rawCopy.replace(/\{year\}/g, String(new Date().getFullYear())).replace(/\{store\}/g, resolvedBrand);
                        setF('copyright_rendered', renderedCopy);

                        var nlInp = fEl.querySelector('.scms-footer-nl-input');
                        if (nlInp && f.newsletter_placeholder !== undefined) nlInp.placeholder = f.newsletter_placeholder;

                        var tog = function(sel, show) {
                            var node = fEl.querySelector(sel);
                            if (node) node.style.display = show ? '' : 'none';
                        };
                        var fLogoWrap = fEl.querySelector('.scms-footer-logo-wrap');
                        if (fLogoWrap) {
                            var fLogoSrc = (g.logo_url || '').trim();
                            if (f.show_logo !== false && fLogoSrc !== '') {
                                var fResolved = (/^https?:\/\//i.test(fLogoSrc) || fLogoSrc.indexOf('/') === 0) ? fLogoSrc : '<?= htmlspecialchars($baseUrl) ?>/' + fLogoSrc;
                                var fImg = fLogoWrap.querySelector('img');
                                if (!fImg) {
                                    fImg = document.createElement('img');
                                    fImg.style.cssText = 'max-height:36px;width:auto;object-fit:contain;';
                                    fLogoWrap.appendChild(fImg);
                                }
                                fImg.src = fResolved;
                                fLogoWrap.style.display = '';
                            } else {
                                fLogoWrap.style.display = 'none';
                            }
                        }
                        tog('.scms-footer-col1', f.col1_enabled !== false);
                        tog('.scms-footer-col2', f.col2_enabled !== false);
                        tog('.scms-footer-newsletter', f.show_newsletter !== false);
                        tog('.scms-footer-contact', f.show_contact !== false);
                        tog('.scms-footer-social', f.show_social !== false);
                        tog('.scms-footer-payments', f.show_payment_badges !== false);
                        tog('.scms-footer-top-btn', f.show_back_to_top !== false);

                        var renderLinksList = function(sel, raw) {
                            var ul = fEl.querySelector(sel);
                            if (!ul || raw === undefined) return;
                            ul.innerHTML = '';
                            String(raw).split(/\r?\n/).forEach(function(line) {
                                line = line.trim();
                                if (!line) return;
                                var parts = line.split('|');
                                var label = (parts[0] || '').trim();
                                if (!label) return;
                                var li = document.createElement('li');
                                var a = document.createElement('a');
                                a.href = '#';
                                a.style.cssText = 'color:inherit;opacity:0.85;text-decoration:none;';
                                a.textContent = label;
                                li.appendChild(a);
                                ul.appendChild(li);
                            });
                        };
                        renderLinksList('.scms-footer-col1-list', f.col1_links);
                        renderLinksList('.scms-footer-col2-list', f.col2_links);
                    }
                }
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}
