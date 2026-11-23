-- ========================================================
-- ShopCMS Database Schema (Grupo 4)
-- Compatible con MySQL 5.7+ y MariaDB 10.3+ (XAMPP)
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tabla de Usuarios (Administradores y Clientes)
CREATE TABLE IF NOT EXISTS `{prefix}users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `address` TEXT NULL,
    `role` ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Opciones / Ajustes del Sistema
CREATE TABLE IF NOT EXISTS `{prefix}settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` LONGTEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Categorías de Productos
CREATE TABLE IF NOT EXISTS `{prefix}categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `parent_id` INT NULL,
    `image_url` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `{prefix}categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Catálogo de Productos
CREATE TABLE IF NOT EXISTS `{prefix}products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `short_description` TEXT NULL,
    `description` LONGTEXT NULL,
    `specs` TEXT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `compare_price` DECIMAL(10,2) NULL,
    `cost_price` DECIMAL(10,2) NULL,
    `sku` VARCHAR(100) NULL,
    `barcode` VARCHAR(100) NULL,
    `track_quantity` TINYINT(1) DEFAULT 1,
    `quantity` INT DEFAULT 0,
    `low_stock_threshold` INT DEFAULT 5,
    `is_active` TINYINT(1) DEFAULT 1,
    `has_variants` TINYINT(1) DEFAULT 0,
    `option1_name` VARCHAR(100) NULL DEFAULT 'Color',
    `option2_name` VARCHAR(100) NULL DEFAULT 'Talla',
    `option3_name` VARCHAR(100) NULL DEFAULT NULL,
    `category_id` INT NULL,
    `featured_image` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `{prefix}categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Atributos de Producto (Ej: Talla, Color)
CREATE TABLE IF NOT EXISTS `{prefix}product_attributes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Valores de Atributos (Ej: Rojo, Azul, S, M, L)
CREATE TABLE IF NOT EXISTS `{prefix}product_attribute_values` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `attribute_id` INT NOT NULL,
    `value` VARCHAR(100) NOT NULL,
    FOREIGN KEY (`attribute_id`) REFERENCES `{prefix}product_attributes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Variantes de Producto
CREATE TABLE IF NOT EXISTS `{prefix}product_variants` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `sku` VARCHAR(100) NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `compare_price` DECIMAL(10,2) NULL,
    `quantity` INT DEFAULT 0,
    `image_url` VARCHAR(255) NULL,
    `short_description` TEXT NULL,
    `description` TEXT NULL,
    `specs` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `{prefix}products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Relación Variante - Valores de Atributo
CREATE TABLE IF NOT EXISTS `{prefix}variant_attribute_values` (
    `variant_id` INT NOT NULL,
    `attribute_value_id` INT NOT NULL,
    PRIMARY KEY (`variant_id`, `attribute_value_id`),
    FOREIGN KEY (`variant_id`) REFERENCES `{prefix}product_variants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`attribute_value_id`) REFERENCES `{prefix}product_attribute_values`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Galería de Imágenes de Producto
CREATE TABLE IF NOT EXISTS `{prefix}product_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `image_url` VARCHAR(255) NOT NULL,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`product_id`) REFERENCES `{prefix}products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9b. Tokens de Recuperación de Contraseña
CREATE TABLE IF NOT EXISTS `{prefix}password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(150) NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_reset_token_hash` (`token_hash`),
    INDEX `idx_reset_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Pedidos
CREATE TABLE IF NOT EXISTS `{prefix}orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) NOT NULL UNIQUE,
    `customer_id` INT NULL,
    `customer_name` VARCHAR(150) NOT NULL,
    `customer_email` VARCHAR(150) NOT NULL,
    `customer_phone` VARCHAR(50) NULL,
    `shipping_address` TEXT NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `coupon_code` VARCHAR(50) NULL,
    `shipping_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending', 'processing', 'packing', 'shipped', 'out_for_delivery', 'delivered', 'completed', 'cancelled', 'returned') DEFAULT 'pending',
    `payment_method` VARCHAR(100) DEFAULT 'cod',
    `payment_status` ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
    `payment_proof` VARCHAR(255) NULL,
    `payment_reference` VARCHAR(120) NULL,
    `carrier` VARCHAR(100) NULL,
    `tracking_number` VARCHAR(100) NULL,
    `estimated_delivery` DATE NULL,
    `cancel_reason` VARCHAR(255) NULL,
    `stock_restored` TINYINT(1) NOT NULL DEFAULT 0,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `{prefix}users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Líneas de Pedido (Order Items)
CREATE TABLE IF NOT EXISTS `{prefix}order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NULL,
    `variant_id` INT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `variant_title` VARCHAR(255) NULL,
    `sku` VARCHAR(100) NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `total` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `{prefix}orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `{prefix}products`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`variant_id`) REFERENCES `{prefix}product_variants`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Historial de Estados del Pedido
CREATE TABLE IF NOT EXISTS `{prefix}order_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `comment` TEXT NULL,
    `actor` VARCHAR(50) NOT NULL DEFAULT 'system',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `{prefix}orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Cupones de Descuento
CREATE TABLE IF NOT EXISTS `{prefix}coupons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `type` ENUM('percent', 'fixed') DEFAULT 'percent',
    `value` DECIMAL(10,2) NOT NULL,
    `min_spend` DECIMAL(10,2) DEFAULT 0.00,
    `spend_rule` ENUM('min', 'exact', 'range') NOT NULL DEFAULT 'min',
    `max_spend` DECIMAL(10,2) NULL DEFAULT NULL,
    `usage_limit` INT DEFAULT NULL,
    `usage_count` INT DEFAULT 0,
    `single_use_per_user` TINYINT(1) NOT NULL DEFAULT 1,
    `expires_at` DATE NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Reseñas y Calificaciones
CREATE TABLE IF NOT EXISTS `{prefix}reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `customer_name` VARCHAR(150) NOT NULL,
    `customer_email` VARCHAR(150) NOT NULL,
    `rating` TINYINT(1) NOT NULL,
    `title` VARCHAR(255) NULL,
    `comment` TEXT NOT NULL,
    `admin_reply` TEXT NULL,
    `admin_replied_at` DATETIME NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'hidden') DEFAULT 'pending',
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `{prefix}products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Plugins Instalados
CREATE TABLE IF NOT EXISTS `{prefix}plugins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `version` VARCHAR(20) DEFAULT '1.0.0',
    `description` TEXT NULL,
    `author` VARCHAR(150) NULL,
    `is_active` TINYINT(1) DEFAULT 0,
    `settings` LONGTEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Temas Instalados
CREATE TABLE IF NOT EXISTS `{prefix}themes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `version` VARCHAR(20) DEFAULT '1.0.0',
    `description` TEXT NULL,
    `author` VARCHAR(150) NULL,
    `is_active` TINYINT(1) DEFAULT 0,
    `settings` LONGTEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Kardex / Historial de Movimientos de Inventario
CREATE TABLE IF NOT EXISTS `{prefix}inventory_movements` (
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
    FOREIGN KEY (`product_id`) REFERENCES `{prefix}products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Páginas Informativas / Legales CMS
CREATE TABLE IF NOT EXISTS `{prefix}pages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(180) NOT NULL,
    `slug` VARCHAR(180) NOT NULL UNIQUE,
    `content` LONGTEXT NOT NULL,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `show_in_footer` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Suscriptores del Boletín (Newsletter)
CREATE TABLE IF NOT EXISTS `{prefix}newsletter_subscribers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(180) NOT NULL UNIQUE,
    `customer_id` INT NULL,
    `source` VARCHAR(60) NOT NULL DEFAULT 'footer',
    `status` ENUM('active', 'unsubscribed') NOT NULL DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

