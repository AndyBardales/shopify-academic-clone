<?php
namespace ShopCMS\Core;

class InventoryManager {
    public static function movementTypes(): array {
        return [
            'restock'       => ['label' => 'Reposición de Stock',     'badge' => 'badge-success', 'bg' => '#DCFCE7', 'color' => '#166534'],
            'order_reserve' => ['label' => 'Venta / Reserva Pedido',  'badge' => 'badge-info',    'bg' => '#DBEAFE', 'color' => '#1E40AF'],
            'order_restore' => ['label' => 'Retorno por Cancelación', 'badge' => 'badge-success', 'bg' => '#E0E7FF', 'color' => '#3730A3'],
            'loss'          => ['label' => 'Merma / Daño / Salida',   'badge' => 'badge-danger',  'bg' => '#FEE2E2', 'color' => '#991B1B'],
            'adjustment'    => ['label' => 'Ajuste por Conteo',       'badge' => 'badge-warning', 'bg' => '#FEF3C7', 'color' => '#92400E'],
            'catalog_sync'  => ['label' => 'Edición de Catálogo',     'badge' => 'badge-draft',   'bg' => '#F3F4F6', 'color' => '#374151'],
        ];
    }

    public static function getTypeMeta(string $type): array {
        $all = self::movementTypes();
        return $all[$type] ?? $all['adjustment'];
    }

    /**
     * Registra un movimiento en el Kardex solo cuando la cantidad cambia realmente.
     */
    public static function logMovement(
        int $productId,
        ?int $variantId,
        int $previousQty,
        int $newQty,
        string $type = 'adjustment',
        ?string $reference = null,
        ?string $note = null
    ): void {
        if ($previousQty === $newQty) {
            return;
        }
        try {
            Database::insert('inventory_movements', [
                'product_id'    => $productId,
                'variant_id'    => $variantId,
                'change_amount' => $newQty - $previousQty,
                'previous_qty'  => $previousQty,
                'new_qty'       => $newQty,
                'type'          => $type,
                'reference'     => $reference,
                'note'          => $note,
            ]);
        } catch (\Throwable $e) {
            // Silencioso si la tabla aún no fue migrada
        }
    }

    /**
     * Recalcula el stock maestro del producto padre como la suma exacta de sus variantes
     * cuando el producto tiene variantes activas (has_variants = 1).
     */
    public static function syncParentProductStock(int $productId): int {
        $prod = Database::fetch("SELECT id, has_variants, quantity FROM " . Database::table('products') . " WHERE id = ?", [$productId]);
        if (!$prod) {
            return 0;
        }
        if (empty($prod['has_variants'])) {
            return (int)$prod['quantity'];
        }

        $row = Database::fetch("SELECT COALESCE(SUM(quantity), 0) as total_qty FROM " . Database::table('product_variants') . " WHERE product_id = ?", [$productId]);
        $total = max(0, (int)($row['total_qty'] ?? 0));
        Database::update('products', ['quantity' => $total], "id = :id", ['id' => $productId]);
        return $total;
    }

    /**
     * Obtiene el stock real disponible de un producto o variante y si lleva control de inventario.
     */
    public static function getAvailableStock(int $productId, ?int $variantId = null): array {
        $prod = Database::fetch("SELECT id, name, track_quantity, quantity, low_stock_threshold, is_active FROM " . Database::table('products') . " WHERE id = ?", [$productId]);
        if (!$prod) {
            return ['exists' => false, 'track' => true, 'available' => 0, 'threshold' => 0];
        }

        $track = !empty($prod['track_quantity']) && !Settings::getBool('allow_backorder', false);
        $avail = (int)$prod['quantity'];

        if ($variantId !== null && $variantId > 0) {
            $var = Database::fetch("SELECT id, quantity FROM " . Database::table('product_variants') . " WHERE id = ? AND product_id = ?", [$variantId, $productId]);
            if ($var) {
                $avail = (int)$var['quantity'];
            }
        }

        return [
            'exists'    => true,
            'track'     => $track,
            'available' => max(0, $avail),
            'threshold' => max(0, (int)$prod['low_stock_threshold']),
        ];
    }
}
