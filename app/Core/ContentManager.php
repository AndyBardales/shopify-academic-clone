<?php
namespace ShopCMS\Core;

class ContentManager {
    private static bool $schemaChecked = false;

    public static function ensureSchema(): void {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;
        try {
            $pdo = Database::getInstance();
            $pagesTable = Database::table('pages');
            $nlTable = Database::table('newsletter_subscribers');

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS {$pagesTable} (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(180) NOT NULL,
                    `slug` VARCHAR(180) NOT NULL UNIQUE,
                    `content` LONGTEXT NOT NULL,
                    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
                    `show_in_footer` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS {$nlTable} (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `email` VARCHAR(180) NOT NULL UNIQUE,
                    `customer_id` INT NULL,
                    `source` VARCHAR(60) NOT NULL DEFAULT 'footer',
                    `status` ENUM('active', 'unsubscribed') NOT NULL DEFAULT 'active',
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (\Throwable $e) {
            // Ignorar si BD no responde en pruebas aisladas
        }
    }

    public static function slugify(string $text): string {
        $text = strtolower(trim($text));
        $repl = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u'];
        $text = strtr($text, $repl);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim((string)$text, '-') ?: 'pagina-' . time();
    }

    public static function getFooterPages(): array {
        self::ensureSchema();
        try {
            return Database::fetchAll(
                "SELECT id, title, slug FROM " . Database::table('pages') . " WHERE is_published = 1 AND show_in_footer = 1 ORDER BY id ASC"
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function getAllPages(): array {
        self::ensureSchema();
        try {
            return Database::fetchAll("SELECT * FROM " . Database::table('pages') . " ORDER BY id DESC");
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function getSubscribers(): array {
        self::ensureSchema();
        try {
            return Database::fetchAll("SELECT * FROM " . Database::table('newsletter_subscribers') . " ORDER BY id DESC");
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function subscribeEmail(string $email, ?int $customerId = null, string $source = 'footer'): array {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'duplicate' => false, 'message' => 'Ingresa un correo electrónico válido.'];
        }

        self::ensureSchema();
        $table = Database::table('newsletter_subscribers');
        $existing = Database::fetch("SELECT id, status FROM {$table} WHERE email = ?", [$email]);
        if ($existing) {
            if (($existing['status'] ?? 'active') !== 'active') {
                Database::update('newsletter_subscribers', ['status' => 'active'], 'id = :id', ['id' => (int)$existing['id']]);
                return ['ok' => true, 'duplicate' => false, 'message' => '¡Suscripción reactivada correctamente!'];
            }
            return ['ok' => true, 'duplicate' => true, 'message' => 'Este correo ya está suscrito a nuestras novedades.'];
        }

        Database::insert('newsletter_subscribers', [
            'email' => $email,
            'customer_id' => $customerId,
            'source' => $source,
            'status' => 'active',
        ]);
        return ['ok' => true, 'duplicate' => false, 'message' => '¡Gracias! Tu suscripción quedó registrada correctamente.'];
    }

    public static function seedRecommendedLegalPages(string $storeName): int {
        self::ensureSchema();
        $templates = [
            [
                'title' => 'Términos y Condiciones',
                'slug' => 'terminos-y-condiciones',
                'content' => "Bienvenido a {$storeName}. Al acceder y realizar compras en nuestra tienda virtual, aceptas los siguientes términos de uso:\n\n1. Precios y Disponibilidad: Todos los precios mostrados incluyen los impuestos aplicables según la configuración de la tienda y están sujetos a disponibilidad real de inventario.\n\n2. Pedidos y Pagos: Cada pedido genera un número único de seguimiento. Los pagos pueden realizarse a través de los métodos habilitados en el checkout.\n\n3. Modificaciones: {$storeName} se reserva el derecho de actualizar estas condiciones en cualquier momento.",
            ],
            [
                'title' => 'Política de Privacidad',
                'slug' => 'politica-de-privacidad',
                'content' => "En {$storeName} protegemos tus datos personales conforme a buenas prácticas de comercio electrónico.\n\n1. Datos recopilados: Nombre, correo electrónico, teléfono y dirección de envío únicamente para gestionar tus pedidos, soporte y comunicaciones autorizadas.\n\n2. Seguridad: Las contraseñas se almacenan cifradas mediante algoritmos seguros y nunca compartimos ni vendemos tus datos a terceros.",
            ],
            [
                'title' => 'Política de Envíos y Devoluciones',
                'slug' => 'envios-y-devoluciones',
                'content' => "1. Tiempos de Despacho: Los pedidos confirmados se preparan y despachan dentro del plazo estimado indicado en el checkout.\n\n2. Seguimiento: Desde la sección Mi Cuenta puedes consultar en tiempo real cada cambio de estado de tu paquete.\n\n3. Cambios y Devoluciones: Si recibiste un producto con defecto o deseas solicitar una devolución dentro de los 7 días calendario posteriores a la entrega, puedes hacerlo desde tu historial de pedidos o escribiendo a nuestro equipo.",
            ],
            [
                'title' => 'Sobre Nosotros',
                'slug' => 'sobre-nosotros',
                'content' => "{$storeName} es una tienda comprometida con ofrecer productos seleccionados de alta calidad, atención cercana y una experiencia de compra rápida y segura.",
            ],
        ];

        $inserted = 0;
        foreach ($templates as $tpl) {
            $exists = Database::fetch("SELECT id FROM " . Database::table('pages') . " WHERE slug = ?", [$tpl['slug']]);
            if (!$exists) {
                Database::insert('pages', [
                    'title' => $tpl['title'],
                    'slug' => $tpl['slug'],
                    'content' => $tpl['content'],
                    'is_published' => 1,
                    'show_in_footer' => 1,
                ]);
                $inserted++;
            }
        }
        return $inserted;
    }
}
