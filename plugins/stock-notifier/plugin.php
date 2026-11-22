<?php
/**
 * Plugin Name: Stock Notifier
 * Description: Alerta por correo cuando hay reposición de existencias
 */

use ShopCMS\Core\Database;
use ShopCMS\Core\PluginManager;
use ShopCMS\Core\Settings;

if (!function_exists('scms_stock_notifier_get_queue')) {
    function scms_stock_notifier_get_queue(): array {
        $raw = Settings::getRaw('stock_notifier_queue', '[]');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    function scms_stock_notifier_save_queue(array $queue): void {
        // Conservar últimos 100 registros para evitar crecimiento ilimitado
        if (count($queue) > 100) {
            $queue = array_slice($queue, -100);
        }
        Settings::saveMany([
            'stock_notifier_queue' => json_encode(array_values($queue), JSON_UNESCAPED_UNICODE)
        ]);
    }
}

// 1. Formulario real de suscripción en producto agotado (solo clientes autenticados)
PluginManager::addAction('product_page_buttons', function ($product) {
    if ((int)$product['quantity'] <= 0) {
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        $pid = (int)$product['id'];
        $customer = \ShopCMS\Core\Auth::customer();
        if (!$customer) {
            $loginHref = $baseUrl . '/login?auth_msg=stock_notify&redirect=' . urlencode('/product/' . ($product['slug'] ?? ''));
            echo '
            <div style="margin-top: 16px; background: #FFFBEB; border: 1px solid #FCD34D; border-radius: 8px; padding: 14px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; color: #92400E; font-weight: 700; font-size: 13.5px;">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <span>Producto temporalmente agotado</span>
                </div>
                <p style="font-size: 12px; color: #78350F; margin-bottom: 10px; line-height: 1.4;">Inicia sesión con tu cuenta de cliente para activar avisos de reposición de stock.</p>
                <a href="' . htmlspecialchars($loginHref) . '" style="display: inline-block; background: #D97706; color: #FFF; padding: 8px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600; text-decoration: none;">Iniciar sesión para avisarme</a>
            </div>';
            return;
        }
        $custEmail = htmlspecialchars($customer['email'] ?? '');
        echo '
        <div style="margin-top: 16px; background: #FFFBEB; border: 1px solid #FCD34D; border-radius: 8px; padding: 16px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; color: #92400E; font-weight: 700; font-size: 14px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span>Avisarme cuando haya existencias</span>
            </div>
            <p style="font-size: 12px; color: #78350F; margin-bottom: 12px; line-height: 1.4;">
                Recibirás un aviso prioritario en tu correo apenas repongamos stock en almacén.
            </p>
            <div id="stock_notify_box_' . $pid . '" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <input type="email" id="stock_notify_email_' . $pid . '" value="' . $custEmail . '" placeholder="tu-correo@ejemplo.com" style="flex: 1; min-width: 180px; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px;">
                <button type="button" onclick="submitStockNotifier(' . $pid . ')" id="stock_notify_btn_' . $pid . '" style="background: #D97706; color: #FFF; border: none; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;">
                    Notificarme
                </button>
            </div>
            <div id="stock_notify_msg_' . $pid . '" style="display: none; margin-top: 8px; font-size: 12px; font-weight: 600;"></div>
        </div>

        <script>
        async function submitStockNotifier(prodId) {
            var emailInp = document.getElementById("stock_notify_email_" + prodId);
            var btn = document.getElementById("stock_notify_btn_" + prodId);
            var msg = document.getElementById("stock_notify_msg_" + prodId);
            var email = (emailInp ? emailInp.value : "").trim();
            if (!email || email.indexOf("@") === -1) {
                msg.style.color = "#B91C1C";
                msg.textContent = "Por favor ingresa un correo electrónico válido.";
                msg.style.display = "block";
                return;
            }
            btn.disabled = true;
            btn.textContent = "Registrando...";
            try {
                var res = await fetch(' . json_encode($baseUrl) . ' + "/plugin/stock-notifier/subscribe", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ product_id: prodId, email: email })
                });
                var data = await res.json();
                if (!res.ok || !data.ok) throw new Error(data.error || "No se pudo registrar");
                msg.style.color = "#047857";
                msg.textContent = data.message;
                msg.style.display = "block";
                emailInp.disabled = true;
                btn.textContent = "Registrado";
            } catch (err) {
                msg.style.color = "#B91C1C";
                msg.textContent = err.message;
                msg.style.display = "block";
                btn.disabled = false;
                btn.textContent = "Notificarme";
            }
        }
        </script>';
    }
});

// 2. Guardar suscripción en BD (shop_settings -> stock_notifier_queue)
PluginManager::addAction('stock_notifier_subscribe', function ($product, $email) {
    $queue = scms_stock_notifier_get_queue();
    $prodId = (int)$product['id'];
    foreach ($queue as $item) {
        if ((int)($item['product_id'] ?? 0) === $prodId
            && strtolower((string)($item['email'] ?? '')) === strtolower($email)
            && ($item['status'] ?? '') === 'pending') {
            return;
        }
    }

    $queue[] = [
        'product_id' => $prodId,
        'product_name' => (string)($product['name'] ?? 'Producto'),
        'email' => $email,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'notified_at' => null,
    ];
    scms_stock_notifier_save_queue($queue);
});

// 3. Al reponer stock en Inventario/Producto/Devolución, notificar a clientes en espera
PluginManager::addAction('stock_replenished', function ($productId, $newQty) {
    if ((int)$newQty <= 0) return;

    $queue = scms_stock_notifier_get_queue();
    $changed = false;
    $notifiedCount = 0;
    $prodName = '';
    $storeName = Settings::get('store_name', 'ShopCMS Store');
    $storeEmail = Settings::get('store_email', 'no-reply@localhost');

    foreach ($queue as &$entry) {
        if ((int)($entry['product_id'] ?? 0) === (int)$productId && ($entry['status'] ?? '') === 'pending') {
            $entry['status'] = 'notified';
            $entry['notified_at'] = date('Y-m-d H:i:s');
            $prodName = (string)($entry['product_name'] ?? 'Producto');
            $to = (string)$entry['email'];
            $subject = "[{$storeName}] ¡{$prodName} vuelve a estar disponible!";
            $body = "Hola,\n\nTe avisamos que el producto '{$prodName}' ya cuenta con {$newQty} unidad(es) en stock en {$storeName}.\n\nVisítanos para completar tu compra antes de que se agote.";
            @mail($to, $subject, $body, "From: {$storeEmail}");
            $changed = true;
            $notifiedCount++;
        }
    }
    unset($entry);

    if ($changed) {
        scms_stock_notifier_save_queue($queue);
        $_SESSION['flash_plugin_notice'] = "Stock Notifier: se envió alerta de reposición a {$notifiedCount} cliente(s) en espera por '{$prodName}'.";
    }
});

// 4. Panel visible en Admin Inventario para monitorear alertas pendientes/enviadas
PluginManager::addAction('admin_inventory_bottom', function () {
    $queue = array_reverse(scms_stock_notifier_get_queue());
    ?>
    <div class="polaris-card" style="margin-top: 24px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 class="card-title">Stock Notifier — Solicitudes de Aviso por Email</h2>
                <div style="font-size: 12px; color: var(--color-text-secondary);">Se notifican automáticamente al reponer stock desde 0 a &gt; 0 unidades.</div>
            </div>
            <span class="badge badge-info"><?= count($queue) ?> registro(s)</span>
        </div>
        <div style="overflow-x: auto;">
            <table class="polaris-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Correo del Cliente</th>
                        <th>Estado</th>
                        <th>Fecha Registro</th>
                        <th>Fecha Notificación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($queue)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: var(--color-text-muted); font-size: 13px;">
                                Aún no hay solicitudes de alerta de reposición registradas.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_slice($queue, 0, 15) as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['product_name'] ?? '') ?></strong></td>
                                <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                                <td>
                                    <?php if (($row['status'] ?? '') === 'notified'): ?>
                                        <span class="badge badge-success">Notificado</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">En espera</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 12px; color: var(--color-text-secondary);"><?= htmlspecialchars($row['created_at'] ?? '') ?></td>
                                <td style="font-size: 12px; color: var(--color-text-secondary);"><?= htmlspecialchars($row['notified_at'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
});
