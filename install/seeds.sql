-- ========================================================
-- ShopCMS Base System Configuration (Instalación Limpia)
-- Sin cuentas de administrador ni datos maestros previos
-- ========================================================

-- Opciones base del sistema
INSERT INTO `{prefix}settings` (`setting_key`, `setting_value`) VALUES
('store_name', '{store_name}'),
('store_email', '{store_email}'),
('store_currency', 'USD'),
('store_currency_symbol', '$'),
('active_theme', 'minimal-shop'),
('inventory_low_stock_notification', '1'),
('inventory_low_stock_limit', '5'),
('store_industry', 'general'),
('store_tagline', ''),
('tax_rate', '0'),
('tax_included', '1'),
('shipping_flat_rate', '0'),
('shipping_free_threshold', '0'),
('shipping_estimated_days', '2 a 4 días hábiles'),
('enable_payment_cod', '1'),
('enable_payment_bank', '1'),
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

-- Registro de los 4 temas base incluidos en /themes/
INSERT INTO `{prefix}themes` (`slug`, `name`, `version`, `description`, `author`, `is_active`) VALUES
('minimal-shop', 'Minimal Shop', '1.0.0', 'Blanco limpio con enfoque centrado en productos y diseño Shopify.', 'ShopCMS Team', 1),
('fashion', 'Fashion', '1.0.0', 'Diseño editorial de alta costura con tipografía serif y grilla estilizada.', 'ShopCMS Team', 0),
('electronics', 'Electronics', '1.0.0', 'Grilla densa con filtros rápidos y especificaciones técnicas.', 'ShopCMS Team', 0),
('food-market', 'Food & Market', '1.0.0', 'Diseño cálido con tarjetas visuales orientadas a alimentos y mercado.', 'ShopCMS Team', 0);

-- Registro de los 6 plugins base incluidos en /plugins/
INSERT INTO `{prefix}plugins` (`slug`, `name`, `version`, `description`, `author`, `is_active`) VALUES
('payment-gateway', 'Payment Gateway (Culqi & PayPal)', '1.0.0', 'Integración de pasarela de pago para aceptar tarjetas vía Culqi y PayPal.', 'Grupo 4 - ShopCMS Team', 1),
('wishlist', 'Wishlist - Lista de Deseos', '1.0.0', 'Añade botón para guardar favoritos y compartir lista.', 'Grupo 4 - ShopCMS Team', 1),
('product-comparator', 'Product Comparator', '1.0.0', 'Comparador interactivo de especificaciones de hasta 4 productos.', 'Grupo 4 - ShopCMS Team', 1),
('stock-notifier', 'Stock Notifier', '1.0.0', 'Alerta email cuando hay reposición de stock en productos agotados.', 'Grupo 4 - ShopCMS Team', 1),
('related-products', 'Related Products (Upsell & Cross-sell)', '1.0.0', 'Recomendaciones automáticas de productos para venta cruzada.', 'Grupo 4 - ShopCMS Team', 1),
('invoice-generator', 'Invoice Generator', '1.0.0', 'Factura electrónica comercial imprimible con desglose de impuestos.', 'Grupo 4 - ShopCMS Team', 1);
