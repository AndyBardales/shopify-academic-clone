<?php
namespace ShopCMS\Core;

class OrderWorkflow {
    /**
     * Fases completas del ciclo de vida del pedido (inspirado en Temu / AliExpress)
     */
    public static function statuses(): array {
        return [
            'pending' => [
                'label' => 'Pendiente de confirmación',
                'short' => 'Pendiente',
                'step'  => 1,
                'badge' => 'badge-warning',
                'bg'    => '#FEF3C7',
                'color' => '#92400E',
                'desc'  => 'Pedido recibido. Verificando pago y disponibilidad de stock.',
                'next'  => 'processing',
                'next_label' => 'Confirmar y Validar Pedido',
            ],
            'processing' => [
                'label' => 'Confirmado / En procesamiento',
                'short' => 'Confirmado',
                'step'  => 2,
                'badge' => 'badge-info',
                'bg'    => '#E0E7FF',
                'color' => '#3730A3',
                'desc'  => 'Pago o modalidad validada. Orden asignada a fila de almacén.',
                'next'  => 'packing',
                'next_label' => 'Pasar a Empaquetado (Picking)',
            ],
            'packing' => [
                'label' => 'Preparando y empacando',
                'short' => 'Empacando',
                'step'  => 3,
                'badge' => 'badge-info',
                'bg'    => '#DDD6FE',
                'color' => '#5B21B6',
                'desc'  => 'El almacén está recolectando los artículos, embalando y etiquetando tu paquete.',
                'next'  => 'shipped',
                'next_label' => 'Despachar con Courier',
            ],
            'shipped' => [
                'label' => 'Enviado / Despachado',
                'short' => 'Enviado',
                'step'  => 4,
                'badge' => 'badge-info',
                'bg'    => '#DBEAFE',
                'color' => '#1E40AF',
                'desc'  => 'El paquete salió del almacén y fue entregado a la empresa transportista.',
                'next'  => 'out_for_delivery',
                'next_label' => 'Marcar En Reparto Local',
            ],
            'out_for_delivery' => [
                'label' => 'En camino / En reparto',
                'short' => 'En reparto',
                'step'  => 5,
                'badge' => 'badge-info',
                'bg'    => '#CCFBF1',
                'color' => '#115E59',
                'desc'  => 'El repartidor se encuentra en ruta local para entregar en tu dirección.',
                'next'  => 'delivered',
                'next_label' => 'Confirmar Entrega Final',
            ],
            'delivered' => [
                'label' => 'Entregado',
                'short' => 'Entregado',
                'step'  => 6,
                'badge' => 'badge-success',
                'bg'    => '#DCFCE7',
                'color' => '#166534',
                'desc'  => 'Paquete entregado en destino final. Venta completada satisfactoriamente.',
                'next'  => null,
                'next_label' => null,
            ],
            'completed' => [
                'label' => 'Entregado / Completado',
                'short' => 'Entregado',
                'step'  => 6,
                'badge' => 'badge-success',
                'bg'    => '#DCFCE7',
                'color' => '#166534',
                'desc'  => 'Paquete entregado en destino final. Venta completada satisfactoriamente.',
                'next'  => null,
                'next_label' => null,
            ],
            'cancelled' => [
                'label' => 'Cancelado',
                'short' => 'Cancelado',
                'step'  => 0,
                'badge' => 'badge-danger',
                'bg'    => '#FEE2E2',
                'color' => '#991B1B',
                'desc'  => 'El pedido fue anulado y el stock reservado fue liberado al catálogo.',
                'next'  => null,
                'next_label' => null,
            ],
            'returned' => [
                'label' => 'Devuelto / Reembolsado',
                'short' => 'Devuelto',
                'step'  => 0,
                'badge' => 'badge-draft',
                'bg'    => '#F3F4F6',
                'color' => '#374151',
                'desc'  => 'Devolución o reembolso procesado con retorno de inventario.',
                'next'  => null,
                'next_label' => null,
            ],
        ];
    }

    public static function getStatusMeta(string $status): array {
        $all = self::statuses();
        return $all[$status] ?? $all['pending'];
    }

    public static function paymentStatuses(): array {
        return [
            'unpaid'   => ['label' => 'Pendiente de pago', 'badge' => 'badge-warning', 'bg' => '#FEF3C7', 'color' => '#92400E'],
            'paid'     => ['label' => 'Pagado',            'badge' => 'badge-success', 'bg' => '#DCFCE7', 'color' => '#166534'],
            'refunded' => ['label' => 'Reembolsado',       'badge' => 'badge-draft',   'bg' => '#F3F4F6', 'color' => '#374151'],
        ];
    }

    public static function getPaymentMeta(string $paymentStatus): array {
        $all = self::paymentStatuses();
        return $all[$paymentStatus] ?? $all['unpaid'];
    }

    public static function paymentMethodLabel(string $method): string {
        $map = [
            'cod'           => 'Contra Entrega (Efectivo)',
            'bank_transfer' => 'Transferencia / Yape / Plin',
            'card'          => 'Tarjeta de Crédito / Débito',
            'culqi_card'    => 'Tarjeta de Crédito / Débito (Culqi)',
            'paypal'        => 'PayPal Express Checkout',
            'culqi'         => 'Culqi',
        ];
        return $map[strtolower($method)] ?? strtoupper($method);
    }

    /**
     * Los 6 hitos secuenciales que ve el cliente en el rastreador tipo Temu / AliExpress
     */
    public static function trackingSteps(): array {
        return [
            1 => ['key' => 'pending',          'title' => 'Pedido recibido', 'sub' => 'Orden registrada'],
            2 => ['key' => 'processing',       'title' => 'Confirmado',      'sub' => 'Validado en sistema'],
            3 => ['key' => 'packing',          'title' => 'Empacando',       'sub' => 'Picking en almacén'],
            4 => ['key' => 'shipped',          'title' => 'Despachado',      'sub' => 'Entregado a courier'],
            5 => ['key' => 'out_for_delivery', 'title' => 'En reparto',      'sub' => 'En ruta de entrega'],
            6 => ['key' => 'delivered',        'title' => 'Entregado',       'sub' => 'Recibido en destino'],
        ];
    }

    /**
     * Registra un evento en la línea de tiempo del pedido
     */
    public static function recordHistory(int $orderId, string $status, string $comment, string $actor = 'system'): void {
        Database::insert('order_history', [
            'order_id' => $orderId,
            'status'   => $status,
            'comment'  => $comment,
            'actor'    => $actor,
        ]);
    }

    /**
     * Sincroniza de forma idempotente el inventario y uso de cupón al cancelar o devolver un pedido,
     * o si un pedido cancelado se reactiva.
     */
    public static function syncInventoryForStatus(int $orderId, string $newStatus): bool {
        $order = Database::fetch("SELECT id, order_number, status, coupon_code, stock_restored FROM " . Database::table('orders') . " WHERE id = ?", [$orderId]);
        if (!$order) {
            return false;
        }

        $isTerminalReturn = in_array($newStatus, ['cancelled', 'returned'], true);
        $wasRestored = !empty($order['stock_restored']);
        $items = Database::fetchAll("SELECT product_id, variant_id, quantity FROM " . Database::table('order_items') . " WHERE order_id = ?", [$orderId]);

        if ($isTerminalReturn && !$wasRestored) {
            foreach ($items as $item) {
                $qty = max(0, (int)$item['quantity']);
                $prodId = (int)$item['product_id'];
                $varId = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
                if ($qty <= 0 || $prodId <= 0) continue;

                if ($varId) {
                    $curParent = Database::fetch("SELECT quantity FROM " . Database::table('products') . " WHERE id = ?", [$prodId]);
                    $prevParent = (int)($curParent['quantity'] ?? 0);
                    $curVar = Database::fetch("SELECT quantity FROM " . Database::table('product_variants') . " WHERE id = ?", [$varId]);
                    $prev = (int)($curVar['quantity'] ?? 0);
                    Database::query("UPDATE " . Database::table('product_variants') . " SET quantity = quantity + ? WHERE id = ?", [$qty, $varId]);
                    $newParent = InventoryManager::syncParentProductStock($prodId);
                    InventoryManager::logMovement($prodId, $varId, $prev, $prev + $qty, 'order_restore', $order['order_number'], "Reposición automática por estado '{$newStatus}'");
                    if ($prevParent <= 0 && $newParent > 0) {
                        PluginManager::doAction('stock_replenished', $prodId, $newParent);
                    }
                } else {
                    $curProd = Database::fetch("SELECT quantity FROM " . Database::table('products') . " WHERE id = ?", [$prodId]);
                    $prev = (int)($curProd['quantity'] ?? 0);
                    Database::query("UPDATE " . Database::table('products') . " SET quantity = quantity + ? WHERE id = ?", [$qty, $prodId]);
                    InventoryManager::logMovement($prodId, null, $prev, $prev + $qty, 'order_restore', $order['order_number'], "Reposición automática por estado '{$newStatus}'");
                    if ($prev <= 0 && ($prev + $qty) > 0) {
                        PluginManager::doAction('stock_replenished', $prodId, $prev + $qty);
                    }
                }
            }
            if (!empty($order['coupon_code'])) {
                Database::query("UPDATE " . Database::table('coupons') . " SET usage_count = GREATEST(0, usage_count - 1) WHERE code = ?", [$order['coupon_code']]);
            }
            Database::update('orders', ['stock_restored' => 1], "id = :id", ['id' => $orderId]);
            return true;
        }

        if (!$isTerminalReturn && $wasRestored) {
            foreach ($items as $item) {
                $qty = max(0, (int)$item['quantity']);
                $prodId = (int)$item['product_id'];
                $varId = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
                if ($qty <= 0 || $prodId <= 0) continue;

                if ($varId) {
                    $curVar = Database::fetch("SELECT quantity FROM " . Database::table('product_variants') . " WHERE id = ?", [$varId]);
                    $prev = (int)($curVar['quantity'] ?? 0);
                    $next = max(0, $prev - $qty);
                    Database::query("UPDATE " . Database::table('product_variants') . " SET quantity = ? WHERE id = ?", [$next, $varId]);
                    InventoryManager::syncParentProductStock($prodId);
                    InventoryManager::logMovement($prodId, $varId, $prev, $next, 'order_reserve', $order['order_number'], "Reserva por reactivación de pedido");
                } else {
                    $curProd = Database::fetch("SELECT quantity FROM " . Database::table('products') . " WHERE id = ?", [$prodId]);
                    $prev = (int)($curProd['quantity'] ?? 0);
                    $next = max(0, $prev - $qty);
                    Database::query("UPDATE " . Database::table('products') . " SET quantity = ? WHERE id = ?", [$next, $prodId]);
                    InventoryManager::logMovement($prodId, null, $prev, $next, 'order_reserve', $order['order_number'], "Reserva por reactivación de pedido");
                }
            }
            if (!empty($order['coupon_code'])) {
                Database::query("UPDATE " . Database::table('coupons') . " SET usage_count = usage_count + 1 WHERE code = ?", [$order['coupon_code']]);
            }
            Database::update('orders', ['stock_restored' => 0], "id = :id", ['id' => $orderId]);
            return true;
        }

        return false;
    }

    private static bool $proofSchemaChecked = false;

    public static function ensurePaymentProofSchema(): void {
        if (self::$proofSchemaChecked) {
            return;
        }
        self::$proofSchemaChecked = true;
        try {
            $pdo = Database::getInstance();
            $table = Database::table('orders');
            $hasProof = $pdo->query("SHOW COLUMNS FROM {$table} LIKE 'payment_proof'")->fetch();
            if (!$hasProof) {
                $pdo->exec("ALTER TABLE {$table} ADD COLUMN `payment_proof` VARCHAR(255) NULL AFTER `payment_status`");
            }
            $hasRef = $pdo->query("SHOW COLUMNS FROM {$table} LIKE 'payment_reference'")->fetch();
            if (!$hasRef) {
                $pdo->exec("ALTER TABLE {$table} ADD COLUMN `payment_reference` VARCHAR(120) NULL AFTER `payment_proof`");
            }
        } catch (\Throwable $e) {
            // Ignorar si BD no responde en pruebas aisladas
        }
    }

    /**
     * Valida y guarda un archivo de voucher/comprobante de pago (JPG, PNG, WebP o PDF hasta 5MB).
     */
    public static function storeVoucherUpload(array $file): string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            throw new \RuntimeException('No se pudo recibir el comprobante adjunto. Intenta nuevamente.');
        }

        $maxBytes = 5 * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxBytes) {
            throw new \RuntimeException('El comprobante supera el límite máximo permitido de 5 MB.');
        }

        $allowed = [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'application/pdf' => 'pdf',
        ];

        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
            }
        }
        if ($mime === '' && function_exists('mime_content_type')) {
            $mime = (string)@mime_content_type($file['tmp_name']);
        }

        if (!isset($allowed[$mime])) {
            throw new \RuntimeException('Formato de comprobante no válido. Usa JPG, PNG, WebP o PDF.');
        }

        $ext = $allowed[$mime];
        $rootDir = dirname(__DIR__, 2);
        $relDir = 'uploads/vouchers';
        $absDir = $rootDir . '/' . $relDir;
        if (!is_dir($absDir) && !@mkdir($absDir, 0775, true) && !is_dir($absDir)) {
            throw new \RuntimeException('No se pudo preparar la carpeta de almacenamiento para comprobantes.');
        }

        $filename = 'voucher_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $destPath = $absDir . '/' . $filename;

        $moved = is_uploaded_file($file['tmp_name'])
            ? @move_uploaded_file($file['tmp_name'], $destPath)
            : @copy($file['tmp_name'], $destPath);

        if (!$moved) {
            throw new \RuntimeException('Error al guardar el comprobante en el servidor.');
        }

        return $relDir . '/' . $filename;
    }
}
