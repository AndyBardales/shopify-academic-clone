<?php
namespace ShopCMS\Core;

use ZipArchive;
use Exception;

class PluginManager {
    private static array $actions = [];
    private static array $filters = [];
    private static array $loadedPlugins = [];

    /**
     * Registra un hook de acción
     */
    public static function addAction(string $tag, callable $callback, int $priority = 10): void {
        self::$actions[$tag][$priority][] = $callback;
    }

    /**
     * Ejecuta las funciones asociadas a una acción
     */
    public static function doAction(string $tag, ...$args): void {
        if (!isset(self::$actions[$tag])) {
            return;
        }

        ksort(self::$actions[$tag]);
        foreach (self::$actions[$tag] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                call_user_func_array($callback, $args);
            }
        }
    }

    /**
     * Registra un filtro para modificar datos
     */
    public static function addFilter(string $tag, callable $callback, int $priority = 10): void {
        self::$filters[$tag][$priority][] = $callback;
    }

    /**
     * Aplica los filtros sobre un valor y lo retorna
     */
    public static function applyFilter(string $tag, $value, ...$args) {
        if (!isset(self::$filters[$tag])) {
            return $value;
        }

        ksort(self::$filters[$tag]);
        foreach (self::$filters[$tag] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $value = call_user_func_array($callback, array_merge([$value], $args));
            }
        }
        return $value;
    }

    /**
     * Retorna todos los plugins registrados, auto-descubriendo plugins en disco
     */
    public static function all(): array {
        $pluginsDir = dirname(__DIR__, 2) . '/plugins';
        if (is_dir($pluginsDir)) {
            $folders = array_diff(scandir($pluginsDir), ['.', '..']);
            foreach ($folders as $folder) {
                $pluginPath = $pluginsDir . '/' . $folder;
                if (!is_dir($pluginPath)) continue;

                $metaFile = $pluginPath . '/plugin.json';
                if (file_exists($metaFile)) {
                    $json = @json_decode(file_get_contents($metaFile), true);
                    if ($json && !empty($json['name'])) {
                        $slug = $json['slug'] ?? $folder;
                        try {
                            $existing = Database::fetch("SELECT id FROM " . Database::table('plugins') . " WHERE slug = ?", [$slug]);
                            if (!$existing) {
                                Database::insert('plugins', [
                                    'slug' => $slug,
                                    'name' => $json['name'],
                                    'version' => $json['version'] ?? '1.0.0',
                                    'description' => $json['description'] ?? '',
                                    'author' => $json['author'] ?? '',
                                    'is_active' => 0
                                ]);
                            } else {
                                Database::update('plugins', [
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
            return Database::fetchAll("SELECT * FROM " . Database::table('plugins') . " ORDER BY name ASC");
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Carga todos los plugins activos desde la base de datos
     */
    public static function loadActivePlugins(): void {
        try {
            $plugins = Database::fetchAll("SELECT * FROM " . Database::table('plugins') . " WHERE is_active = 1");
            $pluginsDir = dirname(__DIR__, 2) . '/plugins';

            foreach ($plugins as $plugin) {
                $slug = $plugin['slug'];
                $mainFile = $pluginsDir . '/' . $slug . '/plugin.php';

                if (file_exists($mainFile)) {
                    require_once $mainFile;
                    self::$loadedPlugins[$slug] = $plugin;
                }
            }
        } catch (Exception $e) {
            // Si la base de datos aún no está lista o tabla no existe, omitir
        }
    }

    /**
     * Flujo de instalación de un plugin desde un archivo .zip (según diagrama de requisitos)
     * 1. Validación (tipo, tamaño, estructura)
     * 2. Extracción con ZipArchive a /plugins/nombre/
     * 3. Lectura de plugin.json para obtener metadata
     * 4. Registro en la BD como plugin instalado
     */
    public static function installZip(array $file): array {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error al subir el archivo ZIP del plugin.");
        }

        // 1. Validar extensión y tipo MIME
        $fileName = $file['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($fileExt !== 'zip') {
            throw new Exception("El archivo debe ser un paquete comprimido en formato .zip.");
        }

        if ($file['size'] > 15 * 1024 * 1024) { // 15MB max
            throw new Exception("El archivo excede el tamaño máximo permitido (15 MB).");
        }

        if (!class_exists('ZipArchive')) {
            throw new Exception("La extensión PHP ZipArchive no está habilitada en el servidor.");
        }

        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            throw new Exception("No se pudo abrir ni procesar el archivo ZIP.");
        }

        // Buscar plugin.json dentro del archivo zip
        $pluginJsonIndex = -1;
        $pluginSlug = '';

        $hasSubfolder = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $entryName = $stat['name'];

            if (basename($entryName) === 'plugin.json') {
                $pluginJsonIndex = $i;
                // Si está en una subcarpeta (ej. mi-plugin/plugin.json), obtener el nombre
                $parts = explode('/', trim($entryName, '/'));
                if (count($parts) > 1) {
                    $pluginSlug = $parts[0];
                    $hasSubfolder = true;
                }
                break;
            }
        }

        if ($pluginJsonIndex === -1) {
            $zip->close();
            throw new Exception("El paquete ZIP no contiene el archivo requerido de metadatos 'plugin.json'.");
        }

        // Leer metadata de plugin.json
        $jsonContent = $zip->getFromIndex($pluginJsonIndex);
        $metadata = json_decode($jsonContent, true);

        if (!$metadata || !isset($metadata['name'])) {
            $zip->close();
            throw new Exception("El archivo 'plugin.json' no contiene un formato JSON válido o carece del campo 'name'.");
        }

        if (empty($pluginSlug)) {
            $pluginSlug = $metadata['slug'] ?? preg_replace('/[^a-z0-9_-]/', '', strtolower($metadata['name']));
        }
        $pluginSlug = strtolower(trim((string)$pluginSlug));
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $pluginSlug)) {
            $zip->close();
            throw new Exception("El identificador (slug) del plugin contiene caracteres inválidos.");
        }

        // Validación anti-Zip-Slip: comprobar todas las rutas antes de extraer
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = str_replace('\\', '/', (string)$zip->getNameIndex($i));
            if ($entry === '' || str_contains($entry, "\0") || str_starts_with($entry, '/') || preg_match('/^[a-zA-Z]:/', $entry) || preg_match('#(^|/)\.\.(/|$)#', $entry)) {
                $zip->close();
                throw new Exception("El archivo ZIP contiene rutas no seguras y fue rechazado por seguridad.");
            }
            if ($hasSubfolder) {
                $topSeg = explode('/', ltrim($entry, '/'))[0] ?? '';
                if ($topSeg !== '' && $topSeg !== $pluginSlug) {
                    $zip->close();
                    throw new Exception("El archivo ZIP intenta extraer archivos fuera de la carpeta del plugin '{$pluginSlug}'.");
                }
            }
        }

        $pluginsBaseDir = dirname(__DIR__, 2) . '/plugins';
        $targetDir = $pluginsBaseDir . '/' . $pluginSlug;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Extraer archivos validados al destino correcto
        if ($hasSubfolder) {
            $zip->extractTo($pluginsBaseDir);
        } else {
            $zip->extractTo($targetDir);
        }
        $zip->close();

        // Registrar o actualizar en la BD
        $existing = Database::fetch("SELECT id FROM " . Database::table('plugins') . " WHERE slug = ?", [$pluginSlug]);
        if ($existing) {
            Database::update('plugins', [
                'name' => $metadata['name'],
                'version' => $metadata['version'] ?? '1.0.0',
                'description' => $metadata['description'] ?? '',
                'author' => $metadata['author'] ?? ''
            ], "slug = :slug", ['slug' => $pluginSlug]);
        } else {
            Database::insert('plugins', [
                'slug' => $pluginSlug,
                'name' => $metadata['name'],
                'version' => $metadata['version'] ?? '1.0.0',
                'description' => $metadata['description'] ?? '',
                'author' => $metadata['author'] ?? '',
                'is_active' => 0
            ]);
        }

        return [
            'slug' => $pluginSlug,
            'name' => $metadata['name'],
            'version' => $metadata['version'] ?? '1.0.0',
            'message' => "Plugin '{$metadata['name']}' instalado correctamente."
        ];
    }

    /**
     * Alternar estado (activar / desactivar)
     */
    public static function toggle(string $slug, bool $activate): bool {
        $status = $activate ? 1 : 0;
        Database::update('plugins', ['is_active' => $status], "slug = :slug", ['slug' => $slug]);
        return true;
    }

    /**
     * Eliminar plugin y sus archivos
     */
    public static function delete(string $slug): bool {
        // Desactivar y eliminar registro de BD
        Database::delete('plugins', "slug = ?", [$slug]);

        // Eliminar directorio físico
        $dir = dirname(__DIR__, 2) . '/plugins/' . $slug;
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
}