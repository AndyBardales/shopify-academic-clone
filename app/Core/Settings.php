<?php
namespace ShopCMS\Core;

use Throwable;

/**
 * Gestor centralizado de configuración global de la tienda.
 */
class Settings {
    private static ?array $cache = null;

    /**
     * Valores predeterminados para todos los ajustes elementales de ShopCMS.
     */
    public static function defaults(): array {
        return [
            'store_name' => 'ShopCMS Store',
            'store_tagline' => 'Tu tienda virtual de confianza',
            'store_email' => 'contacto@tienda.com',
            'store_phone' => '',
            'store_address' => '',
            'store_currency' => 'USD',
            'store_currency_symbol' => '$',
            'store_industry' => 'fashion',
            'tax_rate' => '0',
            'tax_included' => '1',
            'shipping_flat_rate' => '0',
            'shipping_free_threshold' => '0',
            'shipping_estimated_days' => '2 a 4 días hábiles',
            'enable_payment_cod' => '1',
            'enable_payment_bank' => '1',
            'bank_transfer_instructions' => '',
            'order_min_amount' => '0',
            'order_prefix' => '#ORD-',
            'reviews_auto_approve' => '1',
            'inventory_low_stock_notification' => '1',
            'inventory_low_stock_limit' => '5',
            'allow_backorder' => '0',
            'announcement_enabled' => '0',
            'announcement_text' => 'Envíos a todo el país con seguimiento en vivo de tu pedido.',
            'announcement_bg' => '#008060',
            'whatsapp_widget_enabled' => '0',
            'social_instagram' => '',
            'social_facebook' => '',
            'social_tiktok' => '',
            'maintenance_mode' => '0',
            'maintenance_message' => 'Estamos realizando mejoras en nuestra tienda para atenderte mejor. Volvemos en breve.',
        ];
    }

    /**
     * Retorna todos los ajustes combinados con los valores predeterminados.
     */
    public static function all(bool $forceRefresh = false): array {
        if (self::$cache !== null && !$forceRefresh) {
            return self::$cache;
        }

        $settings = self::defaults();
        try {
            $rows = Database::fetchAll("SELECT setting_key, setting_value FROM " . Database::table('settings'));
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = (string)($row['setting_value'] ?? '');
            }
        } catch (Throwable $e) {
            // Retorna defaults si BD aún no está disponible
        }

        self::$cache = $settings;
        return $settings;
    }

    public static function get(string $key, $default = null): string {
        $all = self::all();
        if (array_key_exists($key, $all) && $all[$key] !== '') {
            return (string)$all[$key];
        }
        return $default !== null ? (string)$default : (self::defaults()[$key] ?? '');
    }

    public static function getRaw(string $key, string $default = ''): string {
        $all = self::all();
        return array_key_exists($key, $all) ? (string)$all[$key] : $default;
    }

    public static function getBool(string $key, bool $default = false): bool {
        $all = self::all();
        if (!array_key_exists($key, $all)) {
            return $default;
        }
        return in_array((string)$all[$key], ['1', 'true', 'on', 'yes'], true);
    }

    public static function getFloat(string $key, float $default = 0.0): float {
        $val = self::getRaw($key, (string)$default);
        return is_numeric($val) ? max(0.0, (float)$val) : $default;
    }

    public static function getInt(string $key, int $default = 0): int {
        $val = self::getRaw($key, (string)$default);
        return is_numeric($val) ? max(0, (int)$val) : $default;
    }

    public static function clearCache(): void {
        self::$cache = null;
    }

    /**
     * Guarda múltiples claves en shop_settings de forma segura.
     */
    public static function saveMany(array $pairs): void {
        foreach ($pairs as $key => $value) {
            $val = (string)$value;
            $exists = Database::fetch("SELECT id FROM " . Database::table('settings') . " WHERE setting_key = ?", [$key]);
            if ($exists) {
                Database::update('settings', ['setting_value' => $val], "setting_key = :k", ['k' => $key]);
            } else {
                Database::insert('settings', ['setting_key' => $key, 'setting_value' => $val]);
            }
        }
        self::clearCache();
    }

    /**
     * Calcula envío, impuestos y total final para carrito/checkout según reglas activas.
     */
    public static function calculateCheckoutTotals(float $subtotal, float $discount = 0.0): array {
        $subtotal = max(0.0, round($subtotal, 2));
        $discount = min($subtotal, max(0.0, round($discount, 2)));
        $netSubtotal = max(0.0, round($subtotal - $discount, 2));

        $flatRate = self::getFloat('shipping_flat_rate', 0.0);
        $freeThreshold = self::getFloat('shipping_free_threshold', 0.0);
        $taxRate = min(100.0, self::getFloat('tax_rate', 0.0));
        $taxIncluded = self::getBool('tax_included', true);

        $shippingAmount = 0.0;
        $freeShippingUnlocked = ($flatRate <= 0.0);
        $freeShippingRemaining = 0.0;

        if ($flatRate > 0.0 && $subtotal > 0.0) {
            if ($freeThreshold > 0.0 && $subtotal >= $freeThreshold) {
                $shippingAmount = 0.0;
                $freeShippingUnlocked = true;
            } else {
                $shippingAmount = round($flatRate, 2);
                $freeShippingUnlocked = false;
                if ($freeThreshold > 0.0) {
                    $freeShippingRemaining = max(0.0, round($freeThreshold - $subtotal, 2));
                }
            }
        }

        $taxAmount = 0.0;
        if ($taxRate > 0.0 && $netSubtotal > 0.0) {
            if ($taxIncluded) {
                $taxAmount = round($netSubtotal * ($taxRate / (100.0 + $taxRate)), 2);
                $total = round($netSubtotal + $shippingAmount, 2);
            } else {
                $taxAmount = round($netSubtotal * ($taxRate / 100.0), 2);
                $total = round($netSubtotal + $taxAmount + $shippingAmount, 2);
            }
        } else {
            $total = round($netSubtotal + $shippingAmount, 2);
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'net_subtotal' => $netSubtotal,
            'shipping_amount' => $shippingAmount,
            'shipping_flat_rate' => $flatRate,
            'free_shipping_threshold' => $freeThreshold,
            'free_shipping_unlocked' => $freeShippingUnlocked,
            'free_shipping_remaining' => $freeShippingRemaining,
            'tax_rate' => $taxRate,
            'tax_included' => $taxIncluded,
            'tax_amount' => $taxAmount,
            'total' => max(0.0, $total),
        ];
    }
}
