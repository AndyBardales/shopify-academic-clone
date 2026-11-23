-- ========================================================
-- ShopCMS Initial Seed Data
-- ========================================================

-- Opciones por defecto
INSERT INTO `{prefix}settings` (`setting_key`, `setting_value`) VALUES
('store_name', '{store_name}'),
('store_email', '{store_email}'),
('store_currency', 'USD'),
('store_currency_symbol', '$'),
('active_theme', 'minimal-shop'),
('inventory_low_stock_notification', '1'),
('inventory_low_stock_limit', '5'),
('store_industry', 'fashion'),
('store_tagline', 'Tu tienda virtual de confianza'),
('tax_rate', '0'),
('tax_included', '1'),
('shipping_flat_rate', '0'),
('shipping_free_threshold', '0'),
('shipping_estimated_days', '2 a 4 días hábiles'),
('enable_payment_cod', '0'),
('enable_payment_bank', '0'),
('order_min_amount', '0'),
('order_prefix', '#ORD-'),
('reviews_auto_approve', '1'),
('allow_backorder', '0'),
('announcement_enabled', '0'),
('announcement_bg', '#008060'),
('whatsapp_widget_enabled', '0'),
('maintenance_mode', '0'),
('site_logo', ''),
('installed_version', '1.0.0');

-- Registro de los 4 temas requeridos
INSERT INTO `{prefix}themes` (`slug`, `name`, `version`, `description`, `author`, `is_active`) VALUES
('minimal-shop', 'Minimal Shop', '1.0.0', 'Blanco limpio con enfoque centrado en productos y diseño Shopify.', 'ShopCMS Team', 1),
('fashion', 'Fashion', '1.0.0', 'Imágenes grandes de pantalla completa, estilo editorial de alta costura.', 'ShopCMS Team', 0),
('electronics', 'Electronics', '1.0.0', 'Grilla densa con filtros laterales y especificaciones técnicas.', 'ShopCMS Team', 0),
('food-market', 'Food & Market', '1.0.0', 'Cálido con categorías destacadas en tarjetas visuales para alimentos.', 'ShopCMS Team', 0);

-- Plugins sugeridos pre-registrados (Requisitos de entrega)
INSERT INTO `{prefix}plugins` (`slug`, `name`, `version`, `description`, `author`, `is_active`) VALUES
('payment-gateway', 'Payment Gateway (Culqi & PayPal)', '1.0.0', 'Integración de pasarela de pago para aceptar tarjetas vía Culqi y PayPal.', 'Grupo 4 - ShopCMS Team', 1),
('wishlist', 'Wishlist - Lista de Deseos', '1.0.0', 'Añade botón para guardar favoritos y compartir lista.', 'Grupo 4 - ShopCMS Team', 1),
('product-comparator', 'Product Comparator', '1.0.0', 'Comparador interactivo de especificaciones de hasta 4 productos.', 'Grupo 4 - ShopCMS Team', 1),
('stock-notifier', 'Stock Notifier', '1.0.0', 'Alerta email cuando hay reposición de stock en productos agotados.', 'Grupo 4 - ShopCMS Team', 1),
('related-products', 'Related Products (Upsell & Cross-sell)', '1.0.0', 'Recomendaciones automáticas de productos para venta cruzada.', 'Grupo 4 - ShopCMS Team', 1),
('invoice-generator', 'Invoice Generator', '1.0.0', 'Factura electrónica comercial imprimible con desglose de impuestos.', 'Grupo 4 - ShopCMS Team', 1);

-- Categorías de prueba (Estructura Jerárquica con Subcategorías)
INSERT INTO `{prefix}categories` (`id`, `name`, `slug`, `description`, `parent_id`) VALUES
(1, 'Prendas Superiores', 'prendas-superiores', 'Prendas superiores, camisetas y abrigos.', NULL),
(2, 'Camisetas & Tops', 'camisetas-tops', 'Camisetas de corte regular, polo y oversize.', 1),
(3, 'Prendas Inferiores', 'prendas-inferiores', 'Pantalones, jeans, shorts y faldas.', NULL),
(4, 'Pantalones & Jeans', 'pantalones-jeans', 'Pantalones y jeans para todo estilo.', 3),
(5, 'Calzado', 'calzado', 'Calzado urbano, deportivo y formal.', NULL),
(6, 'Zapatillas Urbanas', 'zapatillas-urbanas', 'Zapatillas urbanas y calzado deportivo.', 5),
(7, 'Accesorios', 'accesorios', 'Mochilas, gorras, cinturones y complementos.', NULL),
(8, 'Bolsos & Mochilas', 'bolsos-mochilas', 'Bolsos, mochilas y accesorios de uso diario.', 7);

-- Atributos de prueba
INSERT INTO `{prefix}product_attributes` (`id`, `name`, `slug`) VALUES
(1, 'Talla', 'talla'),
(2, 'Color', 'color');

-- Valores de atributos
INSERT INTO `{prefix}product_attribute_values` (`id`, `attribute_id`, `value`) VALUES
(1, 1, 'S'),
(2, 1, 'M'),
(3, 1, 'L'),
(4, 2, 'Negro'),
(5, 2, 'Blanco'),
(6, 2, 'Azul');

-- Productos iniciales de demostración
INSERT INTO `{prefix}products` (`id`, `name`, `slug`, `short_description`, `description`, `price`, `compare_price`, `cost_price`, `sku`, `track_quantity`, `quantity`, `low_stock_threshold`, `is_active`, `has_variants`, `option1_name`, `option2_name`, `option3_name`, `category_id`, `featured_image`) VALUES
(1, 'Camiseta Oversize Premium', 'camiseta-oversize-premium', 'Camiseta de algodón orgánico con corte relajado.', 'Nuestra Camiseta Oversize Premium está confeccionada en 100% algodón peinado de alto gramaje. Diseñada para ofrecer máxima comodidad y durabilidad.', 29.99, 39.99, 12.00, 'TSHIRT-OVR-01', 1, 45, 5, 1, 1, 'Color', 'Talla', NULL, 2, 'assets/images/sample-tshirt.svg'),
(2, 'Zapatillas Urban Runner', 'zapatillas-urban-runner', 'Calzado ligero con suela amortiguada para la ciudad.', 'Diseñadas para el ritmo urbano, las Urban Runner combinan malla transpirable y suela EVA ultraligera.', 79.50, 99.00, 35.00, 'SHOE-URB-01', 1, 18, 4, 1, 0, 'Color', 'Talla', NULL, 6, 'assets/images/sample-shoes.svg'),
(3, 'Mochila Minimalista Oxford', 'mochila-minimalista-oxford', 'Mochila resistente al agua con compartimento para laptop de 15.6".', 'Elegante y funcional, ideal para el día a día, universidad o trabajo.', 49.00, 59.00, 20.00, 'BAG-MIN-01', 1, 3, 5, 1, 0, 'Color', 'Capacidad', NULL, 8, 'assets/images/sample-backpack.svg');

-- Variantes para la Camiseta (Producto ID 1)
INSERT INTO `{prefix}product_variants` (`id`, `product_id`, `title`, `sku`, `price`, `compare_price`, `quantity`, `image_url`) VALUES
(1, 1, 'Negro / S', 'TSHIRT-BLK-S', 29.99, 39.99, 15, 'assets/images/sample-tshirt.svg'),
(2, 1, 'Negro / M', 'TSHIRT-BLK-M', 29.99, 39.99, 20, 'assets/images/sample-tshirt.svg'),
(3, 1, 'Blanco / L', 'TSHIRT-WHT-L', 29.99, 39.99, 10, 'assets/images/sample-tshirt-white.svg');

-- Asociación de variantes con valores de atributo
INSERT INTO `{prefix}variant_attribute_values` (`variant_id`, `attribute_value_id`) VALUES
(1, 1), -- S
(1, 4), -- Negro
(2, 2), -- M
(2, 4), -- Negro
(3, 3), -- L
(3, 5); -- Blanco

-- Cupón de prueba
INSERT INTO `{prefix}coupons` (`code`, `type`, `value`, `min_spend`, `usage_limit`, `usage_count`, `expires_at`, `is_active`) VALUES
('BIENVENIDO10', 'percent', 10.00, 20.00, 100, 0, DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY), 1);

-- Usuario Cliente de demostración
INSERT INTO `{prefix}users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`) VALUES
(2, 'Carlos Mendoza', 'carlos@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+51 987 654 321', 'Av. Principal 450, Miraflores, Lima', 'customer');

-- Pedido de demostración para el cliente
INSERT INTO `{prefix}orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `shipping_address`, `subtotal`, `discount_amount`, `shipping_amount`, `tax_amount`, `total`, `status`, `payment_method`, `payment_status`) VALUES
(1, '#ORD-1001', 2, 'Carlos Mendoza', 'carlos@example.com', '+51 987 654 321', 'Av. Principal 450, Miraflores, Lima', 29.99, 0.00, 0.00, 0.00, 29.99, 'completed', 'cod', 'paid');

-- Artículos del pedido de demostración
INSERT INTO `{prefix}order_items` (`id`, `order_id`, `product_id`, `variant_id`, `product_name`, `variant_title`, `sku`, `price`, `quantity`, `total`) VALUES
(1, 1, 1, 1, 'Camiseta Oversize Premium', 'Negro / S', 'TSHIRT-BLK-S', 29.99, 1, 29.99);

-- Reseña de prueba (Compra verificada)
INSERT INTO `{prefix}reviews` (`id`, `product_id`, `customer_name`, `customer_email`, `rating`, `title`, `comment`, `status`) VALUES
(1, 1, 'Carlos Mendoza', 'carlos@example.com', 5, 'Excelente calidad de tela', 'La tela se siente súper suave y el corte oversize queda perfecto. Muy recomendado.', 'approved');