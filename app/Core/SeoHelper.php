<?php
namespace ShopCMS\Core;

class SeoHelper {
    private static array $context = [];
    private static bool $hookRegistered = false;

    public static function ensureHookRegistered(): void {
        if (self::$hookRegistered) {
            return;
        }
        self::$hookRegistered = true;
        PluginManager::addAction('theme_head', [self::class, 'echoHeadTags'], 5);
    }

    public static function setContext(array $data): void {
        self::$context = $data;
        self::ensureHookRegistered();
    }

    public static function siteOriginUrl(): string {
        $base = defined('APP_URL') ? rtrim((string)APP_URL, '/') : '';
        if ($base !== '' && preg_match('#^https?://#i', $base)) {
            return $base;
        }
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
        $scheme = $isHttps ? 'https' : 'http';
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        if ($host === '') {
            $host = 'localhost';
        }
        return $scheme . '://' . $host . ($base !== '' ? '/' . ltrim($base, '/') : '');
    }

    public static function resolveMeta(): array {
        $ctx = self::$context;
        $storeName = trim((string)($ctx['storeName'] ?? Settings::get('store_name', 'ShopCMS Store')));
        $origin = self::siteOriginUrl();
        $baseUrl = defined('APP_URL') ? rtrim((string)APP_URL, '/') : '';

        $routePath = '/' . ltrim((string)($_GET['route'] ?? ''), '/');
        if ($routePath === '/') {
            $canonical = $origin . '/';
        } else {
            $canonical = $origin . $routePath;
        }

        $title = trim((string)($ctx['seoTitle'] ?? $ctx['pageTitle'] ?? $storeName));
        if ($title === '') {
            $title = $storeName;
        }

        $desc = trim((string)($ctx['seoDescription'] ?? ''));
        $type = trim((string)($ctx['seoType'] ?? 'website'));
        $img = trim((string)($ctx['seoImage'] ?? ''));
        $jsonLd = null;

        if (!empty($ctx['product']) && is_array($ctx['product'])) {
            $p = $ctx['product'];
            $pName = trim((string)($p['name'] ?? ''));
            if ($pName !== '') {
                $title = $pName . ' — ' . $storeName;
            }
            if ($desc === '') {
                $rawDesc = strip_tags((string)($p['short_description'] ?: ($p['description'] ?? '')));
                $desc = self::truncateText($rawDesc, 155);
            }
            $type = 'product';
            if (!empty($p['slug'])) {
                $canonical = $origin . '/product/' . rawurlencode((string)$p['slug']);
            }
            if ($img === '' && !empty($p['featured_image'])) {
                $img = ThemeManager::resolveImageUrl((string)$p['featured_image'], $origin);
            }

            $inStock = empty($p['track_quantity']) || (int)($p['quantity'] ?? 0) > 0;
            $currCode = (string)($ctx['currency']['code'] ?? Settings::get('store_currency', 'USD'));
            $ldData = [
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $pName,
                'description' => $desc !== '' ? $desc : $pName,
                'url' => $canonical,
                'offers' => [
                    '@type' => 'Offer',
                    'priceCurrency' => $currCode,
                    'price' => number_format((float)($p['price'] ?? 0), 2, '.', ''),
                    'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                ],
            ];
            if (!empty($p['sku'])) {
                $ldData['sku'] = (string)$p['sku'];
            }
            if ($img !== '') {
                $ldData['image'] = [$img];
            }
            $jsonLd = $ldData;
        } elseif (!empty($ctx['page']) && is_array($ctx['page'])) {
            $pg = $ctx['page'];
            $pgTitle = trim((string)($pg['title'] ?? ''));
            if ($pgTitle !== '') {
                $title = $pgTitle . ' — ' . $storeName;
            }
            if ($desc === '') {
                $desc = self::truncateText(strip_tags((string)($pg['content'] ?? '')), 155);
            }
            $type = 'article';
            if (!empty($pg['slug'])) {
                $canonical = $origin . '/page/' . rawurlencode((string)$pg['slug']);
            }
        }

        if ($desc === '') {
            $desc = self::truncateText(
                Settings::get('store_tagline', '') ?: ($storeName . ' — Compra en línea con envíos seguros, catálogo actualizado y seguimiento en tiempo real.'),
                155
            );
        }

        if ($img === '') {
            $logoUrl = trim((string)($ctx['themeConfig']['global']['logo_url'] ?? ''));
            if ($logoUrl !== '') {
                $img = ThemeManager::resolveImageUrl($logoUrl, $origin);
            }
        } elseif (!preg_match('#^https?://#i', $img)) {
            $img = $origin . '/' . ltrim($img, '/');
        }

        return [
            'base_url'    => $baseUrl,
            'site_name'   => $storeName,
            'title'       => $title,
            'description' => $desc,
            'type'        => $type,
            'canonical'   => $canonical,
            'image'       => $img,
            'json_ld'     => $jsonLd,
        ];
    }

    public static function echoHeadTags(): void {
        $m = self::resolveMeta();
        echo "\n    <meta name=\"scms-base-url\" content=\"" . htmlspecialchars($m['base_url'], ENT_QUOTES, 'UTF-8') . "\">\n";
        echo "    <meta name=\"description\" content=\"" . htmlspecialchars($m['description'], ENT_QUOTES, 'UTF-8') . "\">\n";
        echo "    <link rel=\"canonical\" href=\"" . htmlspecialchars($m['canonical'], ENT_QUOTES, 'UTF-8') . "\">\n";
        echo "    <meta property=\"og:site_name\" content=\"" . htmlspecialchars($m['site_name'], ENT_QUOTES, 'UTF-8') . "\">\n";
        echo "    <meta property=\"og:type\" content=\"" . htmlspecialchars($m['type'], ENT_QUOTES, 'UTF-8') . "\">\n";
        echo "    <meta property=\"og:title\" content=\"" . htmlspecialchars($m['title'], ENT_QUOTES, 'UTF-8') . "\">\n";
        echo "    <meta property=\"og:description\" content=\"" . htmlspecialchars($m['description'], ENT_QUOTES, 'UTF-8') . "\">\n";
        echo "    <meta property=\"og:url\" content=\"" . htmlspecialchars($m['canonical'], ENT_QUOTES, 'UTF-8') . "\">\n";
        if ($m['image'] !== '') {
            echo "    <meta property=\"og:image\" content=\"" . htmlspecialchars($m['image'], ENT_QUOTES, 'UTF-8') . "\">\n";
            echo "    <meta name=\"twitter:card\" content=\"summary_large_image\">\n";
        } else {
            echo "    <meta name=\"twitter:card\" content=\"summary\">\n";
        }
        echo "    <meta name=\"twitter:title\" content=\"" . htmlspecialchars($m['title'], ENT_QUOTES, 'UTF-8') . "\">\n";
        echo "    <meta name=\"twitter:description\" content=\"" . htmlspecialchars($m['description'], ENT_QUOTES, 'UTF-8') . "\">\n";
        if (!empty($m['json_ld'])) {
            $json = json_encode($m['json_ld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json !== false) {
                echo "    <script type=\"application/ld+json\">{$json}</script>\n";
            }
        }
    }

    public static function buildSitemapXml(?string $originOverride = null): string {
        $origin = rtrim($originOverride ?? self::siteOriginUrl(), '/');
        $urls = [
            ['loc' => $origin . '/', 'lastmod' => date('Y-m-d'), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => $origin . '/catalog', 'lastmod' => date('Y-m-d'), 'changefreq' => 'daily', 'priority' => '0.9'],
        ];

        $categories = Database::fetchAll("SELECT slug FROM " . Database::table('categories') . " WHERE is_active = 1 ORDER BY id ASC");
        foreach ($categories as $c) {
            if (empty($c['slug'])) continue;
            $urls[] = [
                'loc' => $origin . '/catalog?category=' . rawurlencode((string)$c['slug']),
                'lastmod' => date('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        $products = Database::fetchAll("SELECT slug, updated_at, created_at FROM " . Database::table('products') . " WHERE is_active = 1 ORDER BY id DESC");
        foreach ($products as $p) {
            if (empty($p['slug'])) continue;
            $ts = strtotime((string)($p['updated_at'] ?: ($p['created_at'] ?: 'now')));
            $urls[] = [
                'loc' => $origin . '/product/' . rawurlencode((string)$p['slug']),
                'lastmod' => date('Y-m-d', $ts ?: time()),
                'changefreq' => 'weekly',
                'priority' => '0.85',
            ];
        }

        try {
            ContentManager::ensureSchema();
            $pages = Database::fetchAll("SELECT slug, updated_at, created_at FROM " . Database::table('pages') . " WHERE is_published = 1 ORDER BY id ASC");
            foreach ($pages as $pg) {
                if (empty($pg['slug'])) continue;
                $ts = strtotime((string)($pg['updated_at'] ?: ($pg['created_at'] ?: 'now')));
                $urls[] = [
                    'loc' => $origin . '/page/' . rawurlencode((string)$pg['slug']),
                    'lastmod' => date('Y-m-d', $ts ?: time()),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ];
            }
        } catch (\Throwable $e) {
            // Ignorar si tabla aún no existe
        }

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>" . htmlspecialchars($u['lastmod'], ENT_XML1, 'UTF-8') . "</lastmod>\n";
            $xml .= "    <changefreq>" . htmlspecialchars($u['changefreq'], ENT_XML1, 'UTF-8') . "</changefreq>\n";
            $xml .= "    <priority>" . htmlspecialchars($u['priority'], ENT_XML1, 'UTF-8') . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= "</urlset>\n";
        return $xml;
    }

    public static function buildRobotsTxt(?string $originOverride = null): string {
        $origin = rtrim($originOverride ?? self::siteOriginUrl(), '/');
        return "User-agent: *\n"
            . "Disallow: /admin\n"
            . "Disallow: /cart\n"
            . "Disallow: /checkout\n"
            . "Disallow: /account\n"
            . "Disallow: /install\n"
            . "Sitemap: {$origin}/sitemap.xml\n";
    }

    private static function truncateText(string $text, int $maxLen = 155): string {
        $clean = trim((string)preg_replace('/\s+/', ' ', $text));
        if ($clean === '') {
            return '';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($clean, 'UTF-8') <= $maxLen) {
                return $clean;
            }
            return rtrim(mb_substr($clean, 0, $maxLen - 3, 'UTF-8')) . '...';
        }
        if (strlen($clean) <= $maxLen) {
            return $clean;
        }
        return rtrim(substr($clean, 0, $maxLen - 3)) . '...';
    }
}
