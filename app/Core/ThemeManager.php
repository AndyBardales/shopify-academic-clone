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
        $headerFile = dirname(__DIR__, 2) . '/themes/' . $theme . '/views/header.php';
        if (!file_exists($headerFile)) {
            $headerFile = dirname(__DIR__, 2) . '/themes/minimal-shop/views/header.php';
        }
        require $headerFile;
        echo '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/shopcms-guard.css?v=20261122b">' . PHP_EOL;
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

        echo '<script src="' . $baseUrl . '/assets/js/shopcms-guard.js?v=20261122b"></script>' . PHP_EOL;
        echo ThemeCustomizer::renderEditorBridgeScript($theme) . PHP_EOL;
        require $footerFile;
    }

    public static function getThemeAssetUrl(string $asset): string {
        $theme = self::getActiveTheme();
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        return $baseUrl . '/themes/' . $theme . '/assets/' . ltrim($asset, '/');
    }
}
