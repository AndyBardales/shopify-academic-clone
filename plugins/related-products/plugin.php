<?php
/**
 * Plugin Name: Related Products (Upsell & Cross-sell)
 * Description: Muestra productos recomendados en ficha de producto (Upsell) y carrito (Cross-sell)
 */

use ShopCMS\Core\Database;
use ShopCMS\Core\PluginManager;
use ShopCMS\Core\Settings;

// 1. UPSELL en página de producto
PluginManager::addAction('product_page_bottom', function ($product) {
    try {
        $catId = (int)($product['category_id'] ?? 0);
        $prodId = (int)$product['id'];

        $related = Database::fetchAll("
            SELECT p.*, c.name as category_name
            FROM " . Database::table('products') . " p
            LEFT JOIN " . Database::table('categories') . " c ON p.category_id = c.id
            WHERE p.is_active = 1 AND p.id != {$prodId}
            ORDER BY (p.category_id = {$catId}) DESC, p.created_at DESC
            LIMIT 4
        ");

        if (empty($related)) {
            return;
        }

        $baseUrl = defined('APP_URL') ? APP_URL : '';
        $sym = Settings::get('store_currency_symbol', '$');

        echo '
        <section style="margin-top: 30px; padding-top: 28px; border-top: 1px solid #E5E7EB;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                    <h2 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0;">Productos Recomendados (Upsell)</h2>
                </div>
                <span style="font-size: 13px; color: #6B7280;">Sugerencias basadas en tu selección actual</span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">';

        foreach ($related as $item) {
            $priceFormatted = $sym . number_format((float)$item['price'], 2);
            $url = $baseUrl . '/product/' . htmlspecialchars($item['slug']);
            $name = htmlspecialchars($item['name']);
            $cat = htmlspecialchars($item['category_name'] ?? 'General');

            echo '
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 10px; padding: 14px; display: flex; flex-direction: column;">
                <div style="height: 140px; background: #F3F4F6; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; overflow: hidden;">';

            if (!empty($item['featured_image'])) {
                echo '<img src="' . $baseUrl . '/' . htmlspecialchars(ltrim($item['featured_image'], '/')) . '" alt="' . $name . '" style="max-height: 100%; max-width: 100%; object-fit: cover;">';
            } else {
                echo '<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
            }

            echo '
                </div>
                <span style="font-size: 11px; text-transform: uppercase; color: #6B7280; font-weight: 600; margin-bottom: 4px;">' . $cat . '</span>
                <h4 style="font-size: 14px; font-weight: 700; color: #111827; margin: 0 0 8px 0; line-height: 1.3;">' . $name . '</h4>
                <div style="margin-top: auto; display: flex; align-items: center; justify-content: space-between; padding-top: 8px;">
                    <span style="font-size: 16px; font-weight: 800; color: #008060;">' . $priceFormatted . '</span>
                    <a href="' . $url . '" style="display: inline-flex; align-items: center; gap: 4px; background: #F3F4F6; color: #1F2937; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none;">
                        <span>Ver</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                </div>
            </div>';
        }

        echo '</div></section>';
    } catch (\Throwable $e) {}
});

// 2. CROSS-SELL AUTOMÁTICO en carrito
PluginManager::addAction('cart_page_bottom', function ($cart) {
    try {
        if (empty($cart)) return;
        $inCartIds = array_values(array_unique(array_filter(array_map(fn($it) => (int)($it['product_id'] ?? 0), $cart))));
        $notInSql = !empty($inCartIds) ? (' AND p.id NOT IN (' . implode(',', $inCartIds) . ')') : '';

        $cross = Database::fetchAll("
            SELECT p.*, c.name as category_name
            FROM " . Database::table('products') . " p
            LEFT JOIN " . Database::table('categories') . " c ON p.category_id = c.id
            WHERE p.is_active = 1 AND p.quantity > 0 {$notInSql}
            ORDER BY p.id DESC
            LIMIT 4
        ");

        if (empty($cross)) return;

        $baseUrl = defined('APP_URL') ? APP_URL : '';
        $sym = Settings::get('store_currency_symbol', '$');

        echo '
        <section style="margin-top: 44px; padding-top: 30px; border-top: 1px solid #E5E7EB;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 8px;">
                <h2 style="font-size: 19px; font-weight: 700; color: #111827; margin: 0;">Completa tu Compra (Cross-sell)</h2>
                <span style="font-size: 12.5px; color: #6B7280;">Artículos complementarios recomendados para tu carrito</span>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 18px;">';

        foreach ($cross as $item) {
            $name = htmlspecialchars($item['name']);
            $url = $baseUrl . '/product/' . htmlspecialchars($item['slug']);
            $price = $sym . number_format((float)$item['price'], 2);

            echo '
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 10px; padding: 14px; display: flex; flex-direction: column;">
                <a href="' . $url . '" style="font-weight: 700; font-size: 14px; color: #111827; text-decoration: none; margin-bottom: 6px;">' . $name . '</a>
                <div style="font-size: 15px; font-weight: 800; color: #008060; margin-bottom: 12px;">' . $price . '</div>
                <form method="POST" action="' . $baseUrl . '/cart/add" style="margin-top: auto;">
                    <input type="hidden" name="product_id" value="' . (int)$item['id'] . '">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="btn-store btn-store-secondary" style="width: 100%; padding: 8px 10px; font-size: 12.5px;">+ Agregar al Carrito</button>
                </form>
            </div>';
        }

        echo '</div></section>';
    } catch (\Throwable $e) {}
});
