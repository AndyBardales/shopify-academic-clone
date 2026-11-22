<?php
namespace ShopCMS\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            if (!defined('DB_HOST') || !defined('DB_NAME')) {
                throw new \Exception("La base de datos no está configurada.");
            }

            $host = DB_HOST;
            $port = defined('DB_PORT') ? DB_PORT : '3306';
            $db   = DB_NAME;
            $user = DB_USER;
            $pass = DB_PASS;
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            self::$instance = new PDO($dsn, $user, $pass, $options);
        }

        return self::$instance;
    }

    public static function table(string $name): string {
        $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'shop_';
        return "`{$prefix}{$name}`";
    }

    public static function query(string $sql, array $params = []): \PDOStatement {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetch(string $sql, array $params = []): ?array {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    public static function insert(string $table, array $data): int {
        $pdo = self::getInstance();
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ":{$f}", $fields);

        $sql = "INSERT INTO " . self::table($table) . " (" . implode(', ', array_map(fn($f) => "`{$f}`", $fields)) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        return (int)$pdo->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $pdo = self::getInstance();
        $setClauses = [];
        $params = [];

        foreach ($data as $key => $val) {
            $setClauses[] = "`{$key}` = :set_{$key}";
            $params["set_{$key}"] = $val;
        }

        $sql = "UPDATE " . self::table($table) . " SET " . implode(', ', $setClauses) . " WHERE {$where}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, $whereParams));

        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM " . self::table($table) . " WHERE {$where}";
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Garantiza de forma idempotente las columnas necesarias para borrado lógico,
     * reglas avanzadas de cupones y respuestas/destacado de reseñas.
     */
    public static function ensureSoftDeleteSchema(): void {
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['_soft_delete_schema_v6'])) {
            return;
        }
        try {
            $pdo = self::getInstance();
            $catTable = self::table('categories');
            $userTable = self::table('users');
            $couponTable = self::table('coupons');
            $revTable = self::table('reviews');
            $orderTable = self::table('orders');
            $historyTable = self::table('order_history');
            $movTable = self::table('inventory_movements');
            $prodTable = self::table('products');

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS {$movTable} (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `product_id` INT NOT NULL,
                    `variant_id` INT NULL,
                    `change_amount` INT NOT NULL,
                    `previous_qty` INT NOT NULL,
                    `new_qty` INT NOT NULL,
                    `type` VARCHAR(50) NOT NULL DEFAULT 'adjustment',
                    `reference` VARCHAR(100) NULL,
                    `note` VARCHAR(255) NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`product_id`) REFERENCES {$prodTable}(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $columnAdditions = [
                $catTable => [
                    'is_active' => "ALTER TABLE {$catTable} ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `image_url`",
                ],
                $userTable => [
                    'is_active' => "ALTER TABLE {$userTable} ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `role`",
                ],
                $couponTable => [
                    'description'         => "ALTER TABLE {$couponTable} ADD COLUMN `description` VARCHAR(255) NULL AFTER `code`",
                    'spend_rule'          => "ALTER TABLE {$couponTable} ADD COLUMN `spend_rule` ENUM('min', 'exact', 'range') NOT NULL DEFAULT 'min' AFTER `min_spend`",
                    'max_spend'           => "ALTER TABLE {$couponTable} ADD COLUMN `max_spend` DECIMAL(10,2) NULL DEFAULT NULL AFTER `spend_rule`",
                    'single_use_per_user' => "ALTER TABLE {$couponTable} ADD COLUMN `single_use_per_user` TINYINT(1) NOT NULL DEFAULT 1 AFTER `usage_count`",
                ],
                $revTable => [
                    'admin_reply'      => "ALTER TABLE {$revTable} ADD COLUMN `admin_reply` TEXT NULL AFTER `comment`",
                    'admin_replied_at' => "ALTER TABLE {$revTable} ADD COLUMN `admin_replied_at` DATETIME NULL AFTER `admin_reply`",
                    'is_featured'      => "ALTER TABLE {$revTable} ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`",
                ],
                $orderTable => [
                    'carrier'            => "ALTER TABLE {$orderTable} ADD COLUMN `carrier` VARCHAR(100) NULL AFTER `payment_status`",
                    'tracking_number'    => "ALTER TABLE {$orderTable} ADD COLUMN `tracking_number` VARCHAR(100) NULL AFTER `carrier`",
                    'estimated_delivery' => "ALTER TABLE {$orderTable} ADD COLUMN `estimated_delivery` DATE NULL AFTER `tracking_number`",
                    'cancel_reason'      => "ALTER TABLE {$orderTable} ADD COLUMN `cancel_reason` VARCHAR(255) NULL AFTER `estimated_delivery`",
                    'stock_restored'     => "ALTER TABLE {$orderTable} ADD COLUMN `stock_restored` TINYINT(1) NOT NULL DEFAULT 0 AFTER `cancel_reason`",
                ],
                $historyTable => [
                    'actor' => "ALTER TABLE {$historyTable} ADD COLUMN `actor` VARCHAR(50) NOT NULL DEFAULT 'system' AFTER `comment`",
                ],
            ];

            foreach ($columnAdditions as $table => $columns) {
                foreach ($columns as $colName => $ddl) {
                    if (!$pdo->query("SHOW COLUMNS FROM {$table} LIKE '{$colName}'")->fetch()) {
                        $pdo->exec($ddl);
                    }
                }
            }

            $revStatus = $pdo->query("SHOW COLUMNS FROM {$revTable} LIKE 'status'")->fetch();
            if ($revStatus && isset($revStatus['Type']) && stripos($revStatus['Type'], "'hidden'") === false) {
                $pdo->exec("ALTER TABLE {$revTable} MODIFY COLUMN `status` ENUM('pending', 'approved', 'rejected', 'hidden') DEFAULT 'pending'");
            }

            $orderStatus = $pdo->query("SHOW COLUMNS FROM {$orderTable} LIKE 'status'")->fetch();
            if ($orderStatus && isset($orderStatus['Type']) && stripos($orderStatus['Type'], "'packing'") === false) {
                $pdo->exec("ALTER TABLE {$orderTable} MODIFY COLUMN `status` ENUM('pending', 'processing', 'packing', 'shipped', 'out_for_delivery', 'delivered', 'completed', 'cancelled', 'returned') DEFAULT 'pending'");
            }

            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['_soft_delete_schema_v6'] = true;
            }
        } catch (\Throwable $e) {
            // No bloquear la petición si la tabla aún no existe o falta permiso DDL
        }
    }
}

