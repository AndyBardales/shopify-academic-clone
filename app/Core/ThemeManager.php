<?php
namespace ShopCMS\Core;

use ZipArchive;
use Exception;

class ThemeManager {
    private static ?string $activeTheme = null;

    public static function getActiveTheme(): string {
        if (!empty($_GET['preview_theme']) && Auth::isAdmin()) {
            $preview = preg_replace('/[^a-zA-Z0-9\-_]/', '', (string)$_GET['preview_theme']);
            if ($preview !== '' && is_dir(dirname(__DIR__, 2) . '/themes/' . $preview)) {
                return $preview;
            }
        }

        if (self::$activeTheme === null) {
            try {
                $setting = Database::fetch("SELECT setting_value FROM " . Database::table('settings') . " WHERE setting_key = 'active_theme'");
                self::$activeTheme = $setting['setting_value'] ?? 'minimal-shop';
            } catch (Exception $e) {
                self::$activeTheme = 'minimal-shop';
            }
        }
        return self::$activeTheme;
    }

    public static function setActiveTheme(string $themeSlug): bool {
        Database::update('settings', ['setting_value' => $themeSlug], "setting_key = 'active_theme'");
        Database::update('themes', ['is_active' => 0], "1=1");
        Database::update('themes', ['is_active' => 1], "slug = :slug", ['slug' => $themeSlug]);
        self::$activeTheme = $themeSlug;
        return true;
    }

    /**
     * Retorna todos los temas registrados, auto-descubriendo temas en disco
     */
    public static function all(): array {
        $themesDir = dirname(__DIR__, 2) . '/themes';
        if (is_dir($themesDir)) {
            $folders = array_diff(scandir($themesDir), ['.', '..']);
            $activeSlug = self::getActiveTheme();

            foreach ($folders as $folder) {
                $themePath = $themesDir . '/' . $folder;
                if (!is_dir($themePath)) continue;

                $metaFile = $themePath . '/theme.json';
                if (file_exists($metaFile)) {
                    $json = @json_decode(file_get_contents($metaFile), true);
                    if ($json && !empty($json['name'])) {
                        $slug = $json['slug'] ?? $folder;
                        try {
                            $existing = Database::fetch("SELECT id FROM " . Database::table('themes') . " WHERE slug = ?", [$slug]);
                            if (!$existing) {
                                Database::insert('themes', [
                                    'slug' => $slug,
                                    'name' => $json['name'],
                                    'version' => $json['version'] ?? '1.0.0',
                                    'description' => $json['description'] ?? '',
                                    'author' => $json['author'] ?? '',
                                    'is_active' => ($slug === $activeSlug) ? 1 : 0
                                ]);
                            } else {
                                Database::update('themes', [
                                    'name' => $json['name'],
                                    'version' => $json['version'] ?? '1.0.0',
                                    'description' => $json['description'] ?? '',
                                    'author' => $json['author'] ?? ''
                                ], "slug = :slug", ['slug' => $slug]);
                            }
                        } catch (Exception $e) {
                            // Ignorar si BD aún no está disponible
                        }
                    }
                }
            }
        }

        try {
            return Database::fetchAll("SELECT * FROM " . Database::table('themes') . " ORDER BY (slug = 'minimal-shop') DESC, name ASC");
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Flujo de instalación de una plantilla/tema desde un archivo .zip (según especificación)
     * 1. Subida desde el panel
     * 2. PHP valida archivo (tipo, tamaño, estructura)
     * 3. Se extrae con ZipArchive a /themes/nombre/
     * 4. Se lee theme.json para obtener la metadata
     * 5. Se registra en la BD como tema instalado
     */
    public static function installZip(array $file): array {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error al subir el archivo ZIP de la plantilla.");
        }

        $fileName = $file['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($fileExt !== 'zip') {
            throw new Exception("El archivo debe ser un paquete comprimido en formato .zip.");
        }

        if ($file['size'] > 25 * 1024 * 1024) { // 25MB max
            throw new Exception("El archivo excede el tamaño máximo permitido (25 MB).");
        }

        if (!class_exists('ZipArchive')) {
            throw new Exception("La extensión PHP ZipArchive no está habilitada en el servidor.");
        }

        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            throw new Exception("No se pudo abrir ni procesar el archivo ZIP de la plantilla.");
        }

        // Buscar theme.json dentro del archivo zip
        $themeJsonIndex = -1;
        $themeSlug = '';
        $hasSubfolder = false;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $entryName = $stat['name'];

            if (basename($entryName) === 'theme.json') {
                $themeJsonIndex = $i;
                $parts = explode('/', trim($entryName, '/'));
                if (count($parts) > 1) {
                    $themeSlug = $parts[0];
                    $hasSubfolder = true;
                }
                break;
            }
        }

        if ($themeJsonIndex === -1) {
            $zip->close();
            throw new Exception("El paquete ZIP no contiene el archivo requerido de metadatos 'theme.json'.");
        }

        // Leer metadata de theme.json
        $jsonContent = $zip->getFromIndex($themeJsonIndex);
        $metadata = json_decode($jsonContent, true);

        if (!$metadata || !isset($metadata['name'])) {
            $zip->close();
            throw new Exception("El archivo 'theme.json' no contiene un formato JSON válido o carece del campo 'name'.");
        }

        if (empty($themeSlug)) {
            $themeSlug = $metadata['slug'] ?? preg_replace('/[^a-z0-9_-]/', '', strtolower($metadata['name']));
        }

        $themesBaseDir = dirname(__DIR__, 2) . '/themes';
        $targetDir = $themesBaseDir . '/' . $themeSlug;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Extraer archivos
        if ($hasSubfolder) {
            $zip->extractTo($themesBaseDir);
        } else {
            $zip->extractTo($targetDir);
        }
        $zip->close();

        // Registrar o actualizar en la BD
        $existing = Database::fetch("SELECT id FROM " . Database::table('themes') . " WHERE slug = ?", [$themeSlug]);
        if ($existing) {
            Database::update('themes', [
                'name' => $metadata['name'],
                'version' => $metadata['version'] ?? '1.0.0',
                'description' => $metadata['description'] ?? '',
                'author' => $metadata['author'] ?? ''
            ], "slug = :slug", ['slug' => $themeSlug]);
        } else {
            Database::insert('themes', [
                'slug' => $themeSlug,
                'name' => $metadata['name'],
                'version' => $metadata['version'] ?? '1.0.0',
                'description' => $metadata['description'] ?? '',
                'author' => $metadata['author'] ?? '',
                'is_active' => 0
            ]);
        }

        return [
            'slug' => $themeSlug,
            'name' => $metadata['name'],
            'version' => $metadata['version'] ?? '1.0.0',
            'message' => "Plantilla '{$metadata['name']}' instalada correctamente."
        ];
    }

    /**
     * Eliminar plantilla (no se permite borrar la activa ni la predeterminada minimal-shop)
     */
    public static function delete(string $slug): bool {
        if ($slug === 'minimal-shop') {
            throw new Exception("No es posible eliminar el tema base predeterminado (Minimal Shop).");
        }

        if ($slug === self::getActiveTheme()) {
            throw new Exception("No es posible eliminar el tema que actualmente se encuentra activo. Active otro tema antes.");
        }

        Database::delete('themes', "slug = ?", [$slug]);

        $dir = dirname(__DIR__, 2) . '/themes/' . $slug;
        if (is_dir($dir)) {
            self::deleteDirectory($dir);
        }
        return true;
    }

    private static function deleteDirectory(string $dir): void {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? self::deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public static function render(string $view, array $data = []): void {
        if (!isset($data['storeName'])) {
            $data['storeName'] = Settings::get('store_name', 'ShopCMS Store');
        }
        $theme = self::getActiveTheme();
        if (!isset($data['themeSlug'])) {
            $data['themeSlug'] = $theme;
        }
        if (!isset($data['themeConfig'])) {
            $data['themeConfig'] = ThemeCustomizer::getConfig($theme);
        }
        extract($data);
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        $themeDir = dirname(__DIR__, 2) . '/themes/' . $theme;
        $viewFile = $themeDir . '/views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            // Si no existe en el tema activo, buscar en minimal-shop
            $viewFile = dirname(__DIR__, 2) . '/themes/minimal-shop/views/' . $view . '.php';
        }

        if (!file_exists($viewFile)) {
            die("Error: No se encontró la vista '{$view}' para el tema '{$theme}'.");
        }

        // Cargar vista
        require $viewFile;
    }

    public static function renderHeader(array $data = []): void {
        if (!isset($data['storeName'])) {
            $data['storeName'] = Settings::get('store_name', 'ShopCMS Store');
        }
        $theme = self::getActiveTheme();
        if (!isset($data['themeSlug'])) {
            $data['themeSlug'] = $theme;
        }
        if (!isset($data['themeConfig'])) {
            $data['themeConfig'] = ThemeCustomizer::getConfig($theme);
        }
        extract($data);
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        $data['storeName'] = $data['storeName'] ?? Settings::get('store_name', 'ShopCMS Store');
        $data['themeSlug'] = $theme;
        $data['themeConfig'] = $themeConfig;
        SeoHelper::setContext($data);
        $headerFile = dirname(__DIR__, 2) . '/themes/' . $theme . '/views/header.php';
        if (!file_exists($headerFile)) {
            $headerFile = dirname(__DIR__, 2) . '/themes/minimal-shop/views/header.php';
        }
        require $headerFile;
        echo Security::csrfMetaTag() . PHP_EOL;
        echo '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/shopcms-guard.css?v=20261123f">' . PHP_EOL;
        echo ThemeCustomizer::renderGlobalStyleTag($theme) . PHP_EOL;

        if (Settings::getBool('maintenance_mode', false) && Auth::isAdmin() && !ThemeCustomizer::isEditorPreview()) {
            echo '<div style="background:#FEF3C7; color:#92400E; border-bottom:1px solid #FDE68A; padding:8px 16px; text-align:center; font-size:12.5px; font-weight:600;">';
            echo 'Modo Mantenimiento activo: los visitantes ven pantalla de mantenimiento, tú navegas como administrador. <a href="' . htmlspecialchars($baseUrl) . '/admin/settings" style="color:#92400E; text-decoration:underline; margin-left:6px;">Configurar</a>';
            echo '</div>' . PHP_EOL;
        }

        if (Settings::getBool('announcement_enabled', false)) {
            $annText = trim(Settings::get('announcement_text', ''));
            if ($annText !== '') {
                $annBg = Settings::get('announcement_bg', '#008060');
                if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $annBg)) {
                    $annBg = '#008060';
                }
                echo '<div style="background:' . htmlspecialchars($annBg) . '; color:#FFFFFF; padding:8px 16px; text-align:center; font-size:13px; font-weight:600; letter-spacing:0.2px;">';
                echo htmlspecialchars($annText);
                echo '</div>' . PHP_EOL;
            }
        }
    }

    public static function renderFooter(array $data = []): void {
        if (!isset($data['storeName'])) {
            $data['storeName'] = Settings::get('store_name', 'ShopCMS Store');
        }
        $theme = self::getActiveTheme();
        if (!isset($data['themeSlug'])) {
            $data['themeSlug'] = $theme;
        }
        if (!isset($data['themeConfig'])) {
            $data['themeConfig'] = ThemeCustomizer::getConfig($theme);
        }
        extract($data);
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        $footerFile = dirname(__DIR__, 2) . '/themes/' . $theme . '/views/footer.php';
        if (!file_exists($footerFile)) {
            $footerFile = dirname(__DIR__, 2) . '/themes/minimal-shop/views/footer.php';
        }

        $storePhone = trim(Settings::get('store_phone', ''));

        if (Settings::getBool('whatsapp_widget_enabled', false) && $storePhone !== '' && !ThemeCustomizer::isEditorPreview()) {
            $digits = preg_replace('/\D+/', '', $storePhone);
            if ($digits !== '') {
                $waUrl = 'https://wa.me/' . $digits . '?text=' . rawurlencode('Hola, necesito ayuda con un producto de ' . Settings::get('store_name', 'la tienda'));
                echo '<a href="' . htmlspecialchars($waUrl) . '" target="_blank" rel="noopener" aria-label="Atención por WhatsApp" style="position:fixed; bottom:20px; right:20px; z-index:990; background:#16A34A; color:#FFFFFF; border-radius:999px; padding:11px 16px; display:inline-flex; align-items:center; gap:8px; font-size:13px; font-weight:700; text-decoration:none; box-shadow:0 6px 18px rgba(22,163,74,0.32);">';
                echo '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>';
                echo '<span>WhatsApp</span></a>' . PHP_EOL;
            }
        }

        if (!empty($data['product']) && !ThemeCustomizer::isEditorPreview()) {
            echo StorefrontExperience::renderProductSideBasket($baseUrl) . PHP_EOL;
        }

        echo '<script src="' . $baseUrl . '/assets/js/shopcms-guard.js?v=20261123e"></script>' . PHP_EOL;
        echo ThemeCustomizer::renderEditorBridgeScript($theme) . PHP_EOL;
        require $footerFile;
    }

    public static function getThemeAssetUrl(string $asset): string {
        $theme = self::getActiveTheme();
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        return $baseUrl . '/themes/' . $theme . '/assets/' . ltrim($asset, '/');
    }

    public static function resolveImageUrl(string $path, string $baseUrl): string {
        $path = trim($path);
        if ($path === '') return '';
        if (preg_match('#^https?://#i', $path) || str_starts_with($path, '/')) {
            return $path;
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Renderiza la galería múltiple interactiva con miniaturas y lightbox en la ficha de producto.
     */
    public static function renderProductGallery(
        array $product,
        array $galleryImages,
        array $variants,
        ?string $initialImage,
        string $baseUrl,
        string $fallbackSvg
    ): string {
        $slides = [];
        $seen = [];
        $pushSlide = function (?string $rawPath, string $label = '') use (&$slides, &$seen, $baseUrl): void {
            $rawPath = trim((string)$rawPath);
            if ($rawPath === '') return;
            $resolved = self::resolveImageUrl($rawPath, $baseUrl);
            if ($resolved === '' || isset($seen[$resolved])) return;
            $seen[$resolved] = true;
            $slides[] = [
                'raw' => $rawPath,
                'src' => $resolved,
                'alt' => $label,
            ];
        };

        $prodName = (string)($product['name'] ?? 'Producto');
        $pushSlide($product['featured_image'] ?? '', $prodName);
        foreach ($galleryImages as $gi) {
            $pushSlide($gi['image_url'] ?? '', $prodName);
        }
        foreach ($variants as $v) {
            if (!empty($v['image_url'])) {
                $pushSlide($v['image_url'], $prodName . ' - ' . (string)($v['title'] ?? ''));
            }
        }

        $activeSrc = '';
        $initRaw = trim((string)$initialImage);
        if ($initRaw !== '') {
            $activeSrc = self::resolveImageUrl($initRaw, $baseUrl);
        } elseif (!empty($slides)) {
            $activeSrc = $slides[0]['src'];
        }

        $activeIdx = 0;
        foreach ($slides as $idx => $s) {
            if ($s['src'] === $activeSrc) {
                $activeIdx = $idx;
                break;
            }
        }

        $total = count($slides);
        ob_start();
        ?>
        <style>
            .scms-product-gallery-wrap .product-gallery-box {
                height: 440px;
                min-height: 360px;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
            }
            .scms-product-gallery-wrap .product-gallery-box img#main_product_image {
                max-width: 100%;
                max-height: 100%;
                width: auto;
                height: auto;
                object-fit: contain;
                display: block;
            }
            .scms-product-gallery-wrap .scms-gallery-thumbs {
                display: flex;
                gap: 10px;
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 4px;
                scrollbar-width: thin;
            }
            .scms-product-gallery-wrap .scms-gallery-thumb {
                flex-shrink: 0;
            }
            @media (max-width: 768px) {
                .scms-product-gallery-wrap .product-gallery-box {
                    height: 310px;
                    min-height: 250px;
                    padding: 12px;
                }
            }
        </style>
        <div class="scms-product-gallery-wrap" style="display: flex; flex-direction: column; gap: 12px;">
            <div class="product-gallery-box" style="position: relative;">
                <?php if ($activeSrc !== ''): ?>
                    <img id="main_product_image"
                         src="<?= htmlspecialchars($activeSrc) ?>"
                         alt="<?= htmlspecialchars($prodName) ?>"
                         onclick="scmsOpenGalleryLightbox()"
                         title="Haz clic para ampliar"
                         style="cursor: zoom-in; transition: opacity 0.18s ease;">
                    <button type="button" onclick="scmsOpenGalleryLightbox()" aria-label="Ampliar imagen" title="Ampliar imagen" style="position: absolute; top: 12px; right: 12px; width: 34px; height: 34px; border-radius: 999px; border: 1px solid rgba(0,0,0,0.08); background: rgba(255,255,255,0.9); color: #111827; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                    </button>
                    <?php if ($total > 1): ?>
                        <button type="button" onclick="scmsGalleryStep(-1)" aria-label="Imagen anterior" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 34px; height: 34px; border-radius: 999px; border: 1px solid rgba(0,0,0,0.08); background: rgba(255,255,255,0.92); color: #111827; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="15 18 9 12 15 6"/></svg>
                        </button>
                        <button type="button" onclick="scmsGalleryStep(1)" aria-label="Siguiente imagen" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 34px; height: 34px; border-radius: 999px; border: 1px solid rgba(0,0,0,0.08); background: rgba(255,255,255,0.92); color: #111827; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                        <span id="scms_gallery_counter" style="position: absolute; bottom: 10px; right: 12px; background: rgba(17,24,39,0.72); color: #FFFFFF; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700;">
                            <?= ($activeIdx + 1) ?> / <?= $total ?>
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <?= $fallbackSvg ?>
                <?php endif; ?>
            </div>

            <?php if ($total > 1): ?>
                <div class="scms-gallery-thumbs" id="scms_gallery_thumbs" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php foreach ($slides as $idx => $s):
                        $isAct = ($idx === $activeIdx);
                    ?>
                        <button type="button"
                                class="scms-gallery-thumb <?= $isAct ? 'is-active' : '' ?>"
                                data-idx="<?= $idx ?>"
                                data-src="<?= htmlspecialchars($s['src']) ?>"
                                onclick="scmsSelectGalleryIndex(<?= $idx ?>)"
                                aria-label="Ver imagen <?= $idx + 1 ?>"
                                style="width: 68px; height: 68px; padding: 3px; border-radius: 8px; border: 2px solid <?= $isAct ? 'var(--color-primary, #008060)' : 'var(--color-border, #E5E7EB)' ?>; background: var(--color-surface, #FFFFFF); cursor: pointer; overflow: hidden; display: inline-flex; align-items: center; justify-content: center; transition: border-color 0.15s, transform 0.15s;">
                            <img src="<?= htmlspecialchars($s['src']) ?>" alt="<?= htmlspecialchars($s['alt']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 5px;">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total > 0): ?>
            <div id="scms_gallery_lightbox" onclick="if(event.target===this)scmsCloseGalleryLightbox()" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 10050; align-items: center; justify-content: center; padding: 24px;">
                <button type="button" onclick="scmsCloseGalleryLightbox()" aria-label="Cerrar visor" style="position: fixed; top: 18px; right: 22px; width: 40px; height: 40px; border-radius: 999px; border: none; background: rgba(255,255,255,0.16); color: #FFFFFF; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
                <?php if ($total > 1): ?>
                    <button type="button" onclick="scmsGalleryStep(-1)" aria-label="Imagen anterior" style="position: fixed; left: 20px; top: 50%; transform: translateY(-50%); width: 42px; height: 42px; border-radius: 999px; border: none; background: rgba(255,255,255,0.18); color: #FFFFFF; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                <?php endif; ?>
                <img id="scms_lightbox_img" src="<?= htmlspecialchars($activeSrc) ?>" alt="<?= htmlspecialchars($prodName) ?>" style="max-width: 90vw; max-height: 85vh; object-fit: contain; border-radius: 10px; box-shadow: 0 20px 50px rgba(0,0,0,0.4);">
                <?php if ($total > 1): ?>
                    <button type="button" onclick="scmsGalleryStep(1)" aria-label="Siguiente imagen" style="position: fixed; right: 20px; top: 50%; transform: translateY(-50%); width: 42px; height: 42px; border-radius: 999px; border: none; background: rgba(255,255,255,0.18); color: #FFFFFF; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                    <div id="scms_lightbox_counter" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); color: #FFFFFF; font-size: 13px; font-weight: 700; background: rgba(0,0,0,0.55); padding: 4px 12px; border-radius: 999px;">
                        <?= ($activeIdx + 1) ?> / <?= $total ?>
                    </div>
                <?php endif; ?>
            </div>

            <script>
            (function() {
                var slides = <?= json_encode(array_column($slides, 'src'), JSON_UNESCAPED_SLASHES) ?>;
                var curIdx = <?= (int)$activeIdx ?>;

                function applyGalleryUI(idx, silent) {
                    if (!slides.length) return;
                    curIdx = ((idx % slides.length) + slides.length) % slides.length;
                    var src = slides[curIdx];
                    var mainImg = document.getElementById('main_product_image');
                    var lbImg = document.getElementById('scms_lightbox_img');
                    if (mainImg) mainImg.src = src;
                    if (lbImg) lbImg.src = src;

                    var cnt = document.getElementById('scms_gallery_counter');
                    var lbCnt = document.getElementById('scms_lightbox_counter');
                    if (cnt) cnt.textContent = (curIdx + 1) + ' / ' + slides.length;
                    if (lbCnt) lbCnt.textContent = (curIdx + 1) + ' / ' + slides.length;

                    document.querySelectorAll('#scms_gallery_thumbs .scms-gallery-thumb').forEach(function(btn, i) {
                        var active = (i === curIdx);
                        btn.classList.toggle('is-active', active);
                        btn.style.borderColor = active ? 'var(--color-primary, #008060)' : 'var(--color-border, #E5E7EB)';
                    });

                    if (!silent && typeof window.dispatchEvent === 'function') {
                        window.dispatchEvent(new CustomEvent('scms:gallery-image-changed', {
                            detail: { src: src, index: curIdx }
                        }));
                    }
                }

                window.scmsSelectGalleryIndex = function(idx) {
                    applyGalleryUI(idx);
                };
                window.scmsGalleryStep = function(delta) {
                    applyGalleryUI(curIdx + delta);
                };
                window.scmsOpenGalleryLightbox = function() {
                    var lb = document.getElementById('scms_gallery_lightbox');
                    var mainImg = document.getElementById('main_product_image');
                    var lbImg = document.getElementById('scms_lightbox_img');
                    if (mainImg && lbImg) lbImg.src = mainImg.src;
                    if (lb) lb.style.display = 'flex';
                };
                window.scmsCloseGalleryLightbox = function() {
                    var lb = document.getElementById('scms_gallery_lightbox');
                    if (lb) lb.style.display = 'none';
                };
                window.syncProductGalleryActive = function(src) {
                    var idx = slides.indexOf(src);
                    if (idx === -1 && src) {
                        try {
                            var targetPath = new URL(src, window.location.origin).pathname;
                            for (var k = 0; k < slides.length; k++) {
                                if (new URL(slides[k], window.location.origin).pathname === targetPath) {
                                    idx = k;
                                    break;
                                }
                            }
                        } catch (e) {}
                    }
                    if (idx !== -1) {
                        applyGalleryUI(idx, true);
                    }
                };
                document.addEventListener('keydown', function(e) {
                    var lb = document.getElementById('scms_gallery_lightbox');
                    if (!lb || lb.style.display !== 'flex') return;
                    if (e.key === 'Escape') window.scmsCloseGalleryLightbox();
                    if (e.key === 'ArrowRight') window.scmsGalleryStep(1);
                    if (e.key === 'ArrowLeft') window.scmsGalleryStep(-1);
                });
            })();
            </script>
        <?php endif;
        return ob_get_clean();
    }
}
