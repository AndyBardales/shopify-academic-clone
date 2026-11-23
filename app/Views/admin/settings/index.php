<?php
$baseUrl = defined('APP_URL') ? APP_URL : '';
$currSymbol = $settings['store_currency_symbol'] ?? '$';
$currCode = $settings['store_currency'] ?? 'USD';
$taxRate = (float)($settings['tax_rate'] ?? 0);
$taxIncluded = ($settings['tax_included'] ?? '1') === '1';
$flatRate = (float)($settings['shipping_flat_rate'] ?? 0);
$freeThreshold = (float)($settings['shipping_free_threshold'] ?? 0);
$isMaint = ($settings['maintenance_mode'] ?? '0') === '1';
$lowStockOn = ($settings['inventory_low_stock_notification'] ?? '1') === '1';
$lowStockLimit = (int)($settings['inventory_low_stock_limit'] ?? 5);
$hasPaymentPlugin = $hasPaymentPlugin ?? false;
?>

<style>
.settings-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.settings-kpi-card {
    background: #FFFFFF;
    border: 1px solid var(--color-border);
    border-radius: 10px;
    padding: 14px 16px;
    cursor: pointer;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    text-align: left;
}
.settings-kpi-card:hover {
    border-color: #008060;
    box-shadow: 0 2px 8px rgba(0, 128, 96, 0.08);
}
.settings-kpi-label {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--color-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 4px;
}
.settings-kpi-value {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 2px;
}
.settings-kpi-sub {
    font-size: 12px;
    color: var(--color-text-muted);
}

.settings-layout {
    display: grid;
    grid-template-columns: 245px 1fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 860px) {
    .settings-layout {
        grid-template-columns: 1fr;
    }
}

.settings-nav {
    background: #FFFFFF;
    border: 1px solid var(--color-border);
    border-radius: 10px;
    padding: 8px;
    position: sticky;
    top: 76px;
}
.settings-tab-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border: none;
    background: transparent;
    border-radius: 7px;
    color: #374151;
    font-size: 13.5px;
    font-weight: 500;
    cursor: pointer;
    text-align: left;
    transition: background 0.15s ease, color 0.15s ease;
    margin-bottom: 2px;
}
.settings-tab-btn:hover {
    background: #F3F4F6;
}
.settings-tab-btn.active {
    background: #ECFDF5;
    color: #008060;
    font-weight: 600;
}
.settings-tab-btn svg {
    flex-shrink: 0;
}

.settings-panel {
    display: none;
}
.settings-panel.active {
    display: block;
}

.switch-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 14px 16px;
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    margin-bottom: 12px;
}
.switch-row-title {
    font-size: 13.5px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 2px;
}
.switch-row-desc {
    font-size: 12px;
    color: #6B7280;
    line-height: 1.4;
}

.polaris-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}
.polaris-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.polaris-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background-color: #CBD5E1;
    transition: 0.2s;
    border-radius: 999px;
}
.polaris-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.2s;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.polaris-switch input:checked + .polaris-slider {
    background-color: #008060;
}
.polaris-switch input:checked + .polaris-slider:before {
    transform: translateX(20px);
}

.currency-chip {
    border: 1px solid #D1D5DB;
    background: #FFFFFF;
    color: #374151;
    padding: 6px 11px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}
.currency-chip:hover {
    border-color: #008060;
    color: #008060;
    background: #ECFDF5;
}
.preview-box {
    background: #F9FAFB;
    border: 1px dashed #D1D5DB;
    border-radius: 8px;
    padding: 12px 14px;
    font-size: 12.5px;
    color: #374151;
    margin-top: 10px;
}
</style>

<!-- CABECERA -->
<div class="page-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 class="page-title">Ajustes de la Tienda</h1>
        <p class="page-subtitle">Configura tu marca, moneda, impuestos, envíos, métodos de pago, alertas y estado de la tienda desde un solo lugar.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <button type="button" onclick="toggleSettingsGuide()" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span>¿Cómo usar?</span>
        </button>
        <a href="<?= $baseUrl ?>/" target="_blank" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                <polyline points="15 3 21 3 21 9"></polyline>
                <line x1="10" y1="14" x2="21" y2="3"></line>
            </svg>
            <span>Ver Tienda</span>
        </a>
        <button type="submit" form="settings-form" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <span>Guardar Cambios</span>
        </button>
    </div>
</div>

<!-- GUÍA RÁPIDA EN 3 PASOS -->
<div id="settings-quick-guide" class="polaris-card" style="background: #F9FAFB; border-color: #E5E7EB; margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <strong style="font-size: 13px; color: #111827;">Guía rápida para configurar tu tienda en minutos</strong>
            </div>
            <button type="button" onclick="toggleSettingsGuide()" style="background: none; border: none; color: #6B7280; font-size: 12px; cursor: pointer; text-decoration: underline;">Ocultar</button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">1</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Identidad, divisa e impuestos</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Personaliza tu nombre comercial, contacto, rubro y elige moneda + tasa de IGV/IVA en la sección <strong>Moneda &amp; Impuestos</strong>.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">2</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Reglas de envío y pagos</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Define tarifa de envío o envío gratis desde cierto monto, habilita métodos de pago y añade tus datos para transferencia.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">3</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Un solo guardado para todo</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Puedes navegar entre pestañas sin perder datos; al terminar pulsa <strong>Guardar Cambios</strong> y todo se aplica en vivo en la tienda.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RESUMEN RÁPIDO DE CONFIGURACIÓN ACTUAL -->
<div class="settings-kpi-grid">
    <button type="button" class="settings-kpi-card" onclick="switchSettingsTab('storefront')">
        <div class="settings-kpi-label">Estado de la Tienda</div>
        <div class="settings-kpi-value" style="color: <?= $isMaint ? '#B45309' : '#008060' ?>;">
            <?= $isMaint ? 'Modo Mantenimiento' : 'Activa y Pública' ?>
        </div>
        <div class="settings-kpi-sub">Clic para gestionar visibilidad</div>
    </button>

    <button type="button" class="settings-kpi-card" onclick="switchSettingsTab('currency')">
        <div class="settings-kpi-label">Moneda &amp; Impuesto</div>
        <div class="settings-kpi-value"><?= htmlspecialchars($currCode) ?> (<?= htmlspecialchars($currSymbol) ?>)</div>
        <div class="settings-kpi-sub">
            <?= $taxRate > 0 ? number_format($taxRate, 2) . '% (' . ($taxIncluded ? 'incluido' : 'adicional') . ')' : 'Sin impuesto adicional (0%)' ?>
        </div>
    </button>

    <button type="button" class="settings-kpi-card" onclick="switchSettingsTab('shipping')">
        <div class="settings-kpi-label">Política de Envío</div>
        <div class="settings-kpi-value">
            <?= $flatRate > 0 ? htmlspecialchars($currSymbol) . number_format($flatRate, 2) : 'Envío Gratis' ?>
        </div>
        <div class="settings-kpi-sub">
            <?= ($flatRate > 0 && $freeThreshold > 0) ? 'Gratis desde ' . htmlspecialchars($currSymbol) . number_format($freeThreshold, 2) : htmlspecialchars($settings['shipping_estimated_days'] ?? '2 a 4 días hábiles') ?>
        </div>
    </button>

    <button type="button" class="settings-kpi-card" onclick="switchSettingsTab('inventory')">
        <div class="settings-kpi-label">Alertas de Stock</div>
        <div class="settings-kpi-value">Umbral &le; <?= $lowStockLimit ?> unid.</div>
        <div class="settings-kpi-sub"><?= $lowStockOn ? 'Notificaciones activas' : 'Notificaciones pausadas' ?></div>
    </button>
</div>

<form id="settings-form" method="POST" action="<?= $baseUrl ?>/admin/settings/save">
    <div class="settings-layout">
        <!-- NAVEGACIÓN LATERAL POR CATEGORÍA -->
        <aside class="settings-nav" role="tablist" aria-label="Categorías de ajustes">
            <button type="button" class="settings-tab-btn active" data-tab="general" onclick="switchSettingsTab('general')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>General &amp; Identidad</span>
            </button>

            <button type="button" class="settings-tab-btn" data-tab="currency" onclick="switchSettingsTab('currency')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <span>Moneda &amp; Impuestos</span>
            </button>

            <button type="button" class="settings-tab-btn" data-tab="shipping" onclick="switchSettingsTab('shipping')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="3" width="15" height="13"></rect>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                </svg>
                <span>Envíos &amp; Entregas</span>
            </button>

            <button type="button" class="settings-tab-btn" data-tab="checkout" onclick="switchSettingsTab('checkout')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                <span>Pagos &amp; Pedidos</span>
            </button>

            <button type="button" class="settings-tab-btn" data-tab="inventory" onclick="switchSettingsTab('inventory')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                <span>Inventario &amp; Stock</span>
            </button>

            <button type="button" class="settings-tab-btn" data-tab="storefront" onclick="switchSettingsTab('storefront')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                </svg>
                <span>Tienda Online &amp; Avisos</span>
            </button>
        </aside>

        <!-- PANELES DE CONFIGURACIÓN -->
        <div>
            <!-- 1. GENERAL & IDENTIDAD -->
            <div id="panel-general" class="settings-panel active">
                <div class="polaris-card">
                    <div class="card-header">
                        <h2 class="card-title">Identidad y Contacto Comercial</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="store_name">Nombre de la Tienda *</label>
                                <input type="text" id="store_name" name="store_name" class="form-control" value="<?= htmlspecialchars($settings['store_name'] ?? '') ?>" required>
                                <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">Se muestra en cabecera, pestañas del navegador y comprobantes.</div>
                            </div>
                            <div class="form-group">
                                <label for="store_tagline">Eslogan o Subtítulo</label>
                                <input type="text" id="store_tagline" name="store_tagline" class="form-control" value="<?= htmlspecialchars($settings['store_tagline'] ?? '') ?>" placeholder="Ej: Moda urbana y sostenible">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="store_email">Correo de Contacto *</label>
                                <input type="email" id="store_email" name="store_email" class="form-control" value="<?= htmlspecialchars($settings['store_email'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="store_phone">Teléfono / WhatsApp de Atención</label>
                                <input type="text" id="store_phone" name="store_phone" class="form-control" value="<?= htmlspecialchars($settings['store_phone'] ?? '') ?>" placeholder="Ej: +51 987 654 321">
                                <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">Usado en el pie de página y el botón flotante de WhatsApp.</div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="store_address">Dirección Comercial / Ciudad</label>
                            <input type="text" id="store_address" name="store_address" class="form-control" value="<?= htmlspecialchars($settings['store_address'] ?? '') ?>" placeholder="Ej: Av. Larco 450, Miraflores, Lima">
                        </div>
                    </div>
                </div>

                <div class="polaris-card">
                    <div class="card-header">
                        <h2 class="card-title">Especialización del Catálogo (Rubro)</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="store_industry">Rubro / Giro Comercial</label>
                            <select id="store_industry" name="store_industry" class="form-control" style="font-weight: 600;">
                                <option value="fashion" <?= ($settings['store_industry'] ?? 'fashion') === 'fashion' ? 'selected' : '' ?>>
                                    Moda, Ropa y Calzado (Sugerencias: Color / Talla)
                                </option>
                                <option value="electronics" <?= ($settings['store_industry'] ?? '') === 'electronics' ? 'selected' : '' ?>>
                                    Tecnología y Gadgets (Sugerencias: Almacenamiento / Color)
                                </option>
                                <option value="food" <?= ($settings['store_industry'] ?? '') === 'food' ? 'selected' : '' ?>>
                                    Alimentos, Bebidas y Gourmet (Sugerencias: Presentación / Peso)
                                </option>
                                <option value="general" <?= ($settings['store_industry'] ?? '') === 'general' ? 'selected' : '' ?>>
                                    Comercio General / Multirubro (Sugerencias: Opción / Variante)
                                </option>
                            </select>
                            <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 6px; line-height: 1.45;">
                                Adapta automáticamente las etiquetas de variantes, sugerencias al crear productos y plantillas de categorías.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. MONEDA & IMPUESTOS -->
            <div id="panel-currency" class="settings-panel">
                <div class="polaris-card">
                    <div class="card-header">
                        <h2 class="card-title">Divisa y Formato de Precios</h2>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 14px;">
                            <div style="font-size: 12px; font-weight: 600; color: #4B5563; margin-bottom: 8px;">Selección rápida de moneda:</div>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                <button type="button" class="currency-chip" onclick="applyCurrencyPreset('USD', '$')">USD ($)</button>
                                <button type="button" class="currency-chip" onclick="applyCurrencyPreset('PEN', 'S/')">PEN (S/)</button>
                                <button type="button" class="currency-chip" onclick="applyCurrencyPreset('EUR', '€')">EUR (€)</button>
                                <button type="button" class="currency-chip" onclick="applyCurrencyPreset('MXN', '$')">MXN ($)</button>
                                <button type="button" class="currency-chip" onclick="applyCurrencyPreset('COP', '$')">COP ($)</button>
                                <button type="button" class="currency-chip" onclick="applyCurrencyPreset('ARS', '$')">ARS ($)</button>
                                <button type="button" class="currency-chip" onclick="applyCurrencyPreset('CLP', '$')">CLP ($)</button>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="store_currency">Código ISO de Moneda *</label>
                                <input type="text" id="store_currency" name="store_currency" class="form-control" maxlength="5" value="<?= htmlspecialchars($currCode) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="store_currency_symbol">Símbolo Monetario *</label>
                                <input type="text" id="store_currency_symbol" name="store_currency_symbol" class="form-control" maxlength="6" value="<?= htmlspecialchars($currSymbol) ?>" oninput="updateTaxSimulator()" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="polaris-card">
                    <div class="card-header">
                        <h2 class="card-title">Impuestos (IGV / IVA)</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="tax_rate">Tasa de Impuesto (%)</label>
                                <input type="number" id="tax_rate" name="tax_rate" class="form-control" step="0.01" min="0" max="100" value="<?= htmlspecialchars($settings['tax_rate'] ?? '0') ?>" oninput="updateTaxSimulator()">
                                <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">Deja <strong>0</strong> si vendes sin cálculo de impuestos.</div>
                            </div>
                            <div class="form-group">
                                <label>Modo de Aplicación</label>
                                <div class="switch-row" style="margin-bottom: 0; padding: 10px 12px;">
                                    <div>
                                        <div class="switch-row-title" style="font-size: 13px;">Impuesto incluido en precios</div>
                                        <div class="switch-row-desc" style="font-size: 11.5px;">Si se desactiva, el impuesto se suma al total en el checkout.</div>
                                    </div>
                                    <label class="polaris-switch">
                                        <input type="checkbox" id="tax_included" name="tax_included" value="1" <?= $taxIncluded ? 'checked' : '' ?> onchange="updateTaxSimulator()">
                                        <span class="polaris-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="preview-box" id="tax-simulator-preview"></div>
                    </div>
                </div>
            </div>

            <!-- 3. ENVÍOS & ENTREGAS -->
            <div id="panel-shipping" class="settings-panel">
                <div class="polaris-card">
                    <div class="card-header">
                        <h2 class="card-title">Tarifas y Promociones de Envío</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_flat_rate">Tarifa Plana de Envío (<?= htmlspecialchars($currSymbol) ?>)</label>
                                <input type="number" id="shipping_flat_rate" name="shipping_flat_rate" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($settings['shipping_flat_rate'] ?? '0') ?>" oninput="updateShippingPreview()">
                                <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">Usa <strong>0.00</strong> para ofrecer envío gratis en toda la tienda.</div>
                            </div>
                            <div class="form-group">
                                <label for="shipping_free_threshold">Envío Gratis por Compra Desde (<?= htmlspecialchars($currSymbol) ?>)</label>
                                <input type="number" id="shipping_free_threshold" name="shipping_free_threshold" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($settings['shipping_free_threshold'] ?? '0') ?>" oninput="updateShippingPreview()">
                                <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">Si supera este subtotal en el carrito, el envío pasa a 0. Usa <strong>0</strong> para desactivar.</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="shipping_estimated_days">Tiempo Estimado de Entrega</label>
                            <input type="text" id="shipping_estimated_days" name="shipping_estimated_days" class="form-control" value="<?= htmlspecialchars($settings['shipping_estimated_days'] ?? '2 a 4 días hábiles') ?>" placeholder="Ej: 24 a 48 horas">
                            <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">Se muestra al comprador en el resumen del checkout.</div>
                        </div>

                        <div class="preview-box" id="shipping-preview-box"></div>
                    </div>
                </div>
            </div>

            <!-- 4. PAGOS & PEDIDOS -->
            <div id="panel-checkout" class="settings-panel">
                <div class="polaris-card">
                    <div class="card-header">
                        <h2 class="card-title">Métodos de Pago Nativos en Checkout</h2>
                    </div>
                    <div class="card-body">
                        <div class="switch-row">
                            <div>
                                <div class="switch-row-title">Pago Contra Entrega / Efectivo (COD)</div>
                                <div class="switch-row-desc">Permite que el cliente pague al recibir el pedido en su dirección.</div>
                            </div>
                            <label class="polaris-switch">
                                <input type="checkbox" name="enable_payment_cod" value="1" <?= ($settings['enable_payment_cod'] ?? '0') === '1' ? 'checked' : '' ?>>
                                <span class="polaris-slider"></span>
                            </label>
                        </div>

                        <div class="switch-row">
                            <div>
                                <div class="switch-row-title">Transferencia Bancaria / Yape / Plin</div>
                                <div class="switch-row-desc">Habilita pago por depósito o billetera móvil mostrando tus instrucciones abajo.</div>
                            </div>
                            <label class="polaris-switch">
                                <input type="checkbox" name="enable_payment_bank" value="1" <?= ($settings['enable_payment_bank'] ?? '0') === '1' ? 'checked' : '' ?>>
                                <span class="polaris-slider"></span>
                            </label>
                        </div>

                        <div class="form-group" style="margin-top: 12px; margin-bottom: 10px;">
                            <label for="bank_transfer_instructions">Instrucciones y Cuentas para Transferencia</label>
                            <textarea id="bank_transfer_instructions" name="bank_transfer_instructions" rows="3" class="form-control" placeholder="Ej: BCP Cta. 191-00000000-0-00 / Yape al 987 654 321 a nombre de Tienda SAC. Envía el comprobante por WhatsApp."><?= htmlspecialchars($settings['bank_transfer_instructions'] ?? '') ?></textarea>
                            <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">Visible en el checkout y en la pantalla de confirmación del pedido.</div>
                        </div>

                        <div class="preview-box">
                            <strong>Pasarelas adicionales (Plugins):</strong>
                            <?php if ($hasPaymentPlugin): ?>
                                El plugin <strong>Payment Gateway (Culqi &amp; PayPal)</strong> está activo y agrega tarjetas y PayPal al checkout.
                            <?php else: ?>
                                Puedes activar pasarelas como Culqi y PayPal desde <a href="<?= $baseUrl ?>/admin/plugins">Plugins</a>.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="polaris-card">
                    <div class="card-header">
                        <h2 class="card-title">Reglas de Pedidos y Reseñas</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="order_min_amount">Monto Mínimo de Compra (<?= htmlspecialchars($currSymbol) ?>)</label>
                                <input type="number" id="order_min_amount" name="order_min_amount" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($settings['order_min_amount'] ?? '0') ?>">
                                <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">Usa <strong>0</strong> para permitir pedidos de cualquier importe.</div>
                            </div>
                            <div class="form-group">
                                <label for="order_prefix">Prefijo de Código de Pedido</label>
                                <input type="text" id="order_prefix" name="order_prefix" class="form-control" maxlength="12" value="<?= htmlspecialchars($settings['order_prefix'] ?? '#ORD-') ?>" oninput="updateOrderPrefixPreview()">
                                <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">Vista previa: <strong id="order-prefix-preview"><?= htmlspecialchars(($settings['order_prefix'] ?? '#ORD-') . '8F3A12') ?></strong></div>
                            </div>
                        </div>

                        <div class="switch-row" style="margin-bottom: 0;">
                            <div>
                                <div class="switch-row-title">Publicar automáticamente reseñas verificadas</div>
                                <div class="switch-row-desc">Si se desactiva, las nuevas opiniones de clientes pasan por aprobación manual en <a href="<?= $baseUrl ?>/admin/reviews">Reseñas</a>.</div>
                            </div>
                            <label class="polaris-switch">
                                <input type="checkbox" name="reviews_auto_approve" value="1" <?= ($settings['reviews_auto_approve'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <span class="polaris-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. INVENTARIO & STOCK -->
            <div id="panel-inventory" class="settings-panel">
                <div class="polaris-card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h2 class="card-title">Parámetros de Almacén y Alertas</h2>
                        <a href="<?= $baseUrl ?>/admin/inventory" class="btn btn-secondary btn-sm">Ir a Inventario &amp; Kardex</a>
                    </div>
                    <div class="card-body">
                        <div class="switch-row">
                            <div>
                                <div class="switch-row-title">Notificaciones de stock bajo activas</div>
                                <div class="switch-row-desc">Muestra alertas en el Dashboard y en el módulo de Inventario cuando un SKU cae por debajo del umbral.</div>
                            </div>
                            <label class="polaris-switch">
                                <input type="checkbox" name="inventory_low_stock_notification" value="1" <?= $lowStockOn ? 'checked' : '' ?>>
                                <span class="polaris-slider"></span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="inventory_low_stock_limit">Umbral Global de Stock Bajo (Unidades)</label>
                            <input type="number" id="inventory_low_stock_limit" name="inventory_low_stock_limit" class="form-control" min="0" value="<?= $lowStockLimit ?>">
                            <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">Aplica por defecto a los productos que no tengan un umbral personalizado.</div>
                        </div>

                        <div class="switch-row" style="margin-bottom: 0;">
                            <div>
                                <div class="switch-row-title">Permitir compra sin control estricto (Modo Pre-venta global)</div>
                                <div class="switch-row-desc">Si se desactiva (recomendado), el carrito impide comprar más unidades de las disponibles en almacén.</div>
                            </div>
                            <label class="polaris-switch">
                                <input type="checkbox" name="allow_backorder" value="1" <?= ($settings['allow_backorder'] ?? '0') === '1' ? 'checked' : '' ?>>
                                <span class="polaris-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. TIENDA ONLINE & AVISOS -->
            <div id="panel-storefront" class="settings-panel">
                <div class="polaris-card">
                    <div class="card-header">
                        <h2 class="card-title">Barra Promocional y WhatsApp en la Tienda</h2>
                    </div>
                    <div class="card-body">
                        <div class="switch-row">
                            <div>
                                <div class="switch-row-title">Mostrar barra de anuncio superior</div>
                                <div class="switch-row-desc">Destaca promociones, cupones o avisos de despacho en la parte superior de la tienda pública.</div>
                            </div>
                            <label class="polaris-switch">
                                <input type="checkbox" id="announcement_enabled" name="announcement_enabled" value="1" <?= ($settings['announcement_enabled'] ?? '0') === '1' ? 'checked' : '' ?> onchange="updateAnnouncementPreview()">
                                <span class="polaris-slider"></span>
                            </label>
                        </div>

                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label for="announcement_text">Texto del Anuncio</label>
                                <input type="text" id="announcement_text" name="announcement_text" class="form-control" value="<?= htmlspecialchars($settings['announcement_text'] ?? '') ?>" oninput="updateAnnouncementPreview()" placeholder="Ej: 10% OFF en tu primera compra con el código BIENVENIDO10">
                            </div>
                            <div class="form-group">
                                <label for="announcement_bg">Color de Fondo</label>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="color" id="announcement_bg" name="announcement_bg" value="<?= htmlspecialchars($settings['announcement_bg'] ?? '#008060') ?>" oninput="updateAnnouncementPreview()" style="width: 44px; height: 38px; border: 1px solid #D1D5DB; border-radius: 6px; padding: 2px; cursor: pointer;">
                                    <button type="button" class="currency-chip" onclick="setAnnouncementColor('#008060')">Verde</button>
                                    <button type="button" class="currency-chip" onclick="setAnnouncementColor('#111827')">Oscuro</button>
                                    <button type="button" class="currency-chip" onclick="setAnnouncementColor('#B45309')">Ámbar</button>
                                </div>
                            </div>
                        </div>

                        <div id="announcement-live-preview" style="border-radius: 6px; padding: 9px 14px; text-align: center; color: #FFFFFF; font-size: 12.5px; font-weight: 600; margin-bottom: 16px;"></div>

                        <div class="switch-row">
                            <div>
                                <div class="switch-row-title">Botón flotante de contacto rápido por WhatsApp</div>
                                <div class="switch-row-desc">Muestra un botón de ayuda en la tienda usando el campo <strong>Teléfono / WhatsApp</strong> de General &amp; Identidad.</div>
                            </div>
                            <label class="polaris-switch">
                                <input type="checkbox" name="whatsapp_widget_enabled" value="1" <?= ($settings['whatsapp_widget_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                                <span class="polaris-slider"></span>
                            </label>
                        </div>

                        <div class="form-row" style="margin-top: 12px;">
                            <div class="form-group">
                                <label for="social_instagram">Instagram (URL)</label>
                                <input type="url" id="social_instagram" name="social_instagram" class="form-control" value="<?= htmlspecialchars($settings['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/tutienda">
                            </div>
                            <div class="form-group">
                                <label for="social_facebook">Facebook (URL)</label>
                                <input type="url" id="social_facebook" name="social_facebook" class="form-control" value="<?= htmlspecialchars($settings['social_facebook'] ?? '') ?>" placeholder="https://facebook.com/tutienda">
                            </div>
                            <div class="form-group">
                                <label for="social_tiktok">TikTok (URL)</label>
                                <input type="url" id="social_tiktok" name="social_tiktok" class="form-control" value="<?= htmlspecialchars($settings['social_tiktok'] ?? '') ?>" placeholder="https://tiktok.com/@tutienda">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="polaris-card" style="border-left: 4px solid <?= $isMaint ? '#D97706' : '#E5E7EB' ?>;">
                    <div class="card-header">
                        <h2 class="card-title">Modo Mantenimiento</h2>
                    </div>
                    <div class="card-body">
                        <div class="switch-row">
                            <div>
                                <div class="switch-row-title">Activar pantalla de mantenimiento en la tienda pública</div>
                                <div class="switch-row-desc">Los clientes verán un aviso temporal. El panel <code>/admin</code> sigue 100% operativo para tu equipo.</div>
                            </div>
                            <label class="polaris-switch">
                                <input type="checkbox" name="maintenance_mode" value="1" <?= $isMaint ? 'checked' : '' ?>>
                                <span class="polaris-slider"></span>
                            </label>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="maintenance_message">Mensaje para los Visitantes</label>
                            <textarea id="maintenance_message" name="maintenance_message" rows="2" class="form-control"><?= htmlspecialchars($settings['maintenance_message'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PIE DE GUARDADO SIEMPRE VISIBLE -->
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px;">
                <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>Guardar Toda la Configuración</span>
                </button>
            </div>
        </div>
    </div>
</form>

<script>
function toggleSettingsGuide() {
    const guide = document.getElementById('settings-quick-guide');
    if (!guide) return;
    const isHidden = guide.style.display === 'none';
    guide.style.display = isHidden ? 'block' : 'none';
    try {
        localStorage.setItem('shopcms_hide_settings_guide', isHidden ? '0' : '1');
    } catch (e) {}
}

function switchSettingsTab(tabName) {
    document.querySelectorAll('.settings-tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tabName);
    });
    document.querySelectorAll('.settings-panel').forEach(panel => {
        panel.classList.toggle('active', panel.id === 'panel-' + tabName);
    });
    try {
        sessionStorage.setItem('shopcms_settings_active_tab', tabName);
    } catch (e) {}
}

function triggerDirty(el) {
    if (!el) return;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
}

function applyCurrencyPreset(code, symbol) {
    const codeEl = document.getElementById('store_currency');
    const symEl = document.getElementById('store_currency_symbol');
    if (codeEl) {
        codeEl.value = code;
        triggerDirty(codeEl);
    }
    if (symEl) {
        symEl.value = symbol;
        triggerDirty(symEl);
    }
    updateTaxSimulator();
    updateShippingPreview();
}

function updateTaxSimulator() {
    const sym = (document.getElementById('store_currency_symbol')?.value || '$').trim() || '$';
    const rate = Math.max(0, Math.min(100, parseFloat(document.getElementById('tax_rate')?.value || '0') || 0));
    const included = !!document.getElementById('tax_included')?.checked;
    const box = document.getElementById('tax-simulator-preview');
    if (!box) return;

    const sample = 100;
    if (rate <= 0) {
        box.innerHTML = '<strong>Simulación (compra de ' + sym + '100.00):</strong> Sin impuesto aplicado &rarr; Total final: <strong>' + sym + '100.00</strong>.';
        return;
    }

    if (included) {
        const taxPart = sample * (rate / (100 + rate));
        box.innerHTML = '<strong>Simulación (producto marcado a ' + sym + '100.00):</strong> El precio ya incluye <strong>' + sym + taxPart.toFixed(2) + '</strong> de impuesto (' + rate.toFixed(2) + '%) &rarr; Cliente paga <strong>' + sym + '100.00</strong>.';
    } else {
        const taxAdded = sample * (rate / 100);
        const total = sample + taxAdded;
        box.innerHTML = '<strong>Simulación (subtotal de ' + sym + '100.00):</strong> Se suma +' + sym + taxAdded.toFixed(2) + ' (' + rate.toFixed(2) + '%) en checkout &rarr; Cliente paga <strong>' + sym + total.toFixed(2) + '</strong>.';
    }
}

function updateShippingPreview() {
    const sym = (document.getElementById('store_currency_symbol')?.value || '$').trim() || '$';
    const flat = Math.max(0, parseFloat(document.getElementById('shipping_flat_rate')?.value || '0') || 0);
    const threshold = Math.max(0, parseFloat(document.getElementById('shipping_free_threshold')?.value || '0') || 0);
    const box = document.getElementById('shipping-preview-box');
    if (!box) return;

    if (flat <= 0) {
        box.innerHTML = '<strong>Vista previa en carrito/checkout:</strong> Todos los pedidos tendrán <strong>Envío Gratis</strong>.';
    } else if (threshold > 0) {
        box.innerHTML = '<strong>Vista previa en carrito/checkout:</strong> Envío de <strong>' + sym + flat.toFixed(2) + '</strong>, o <strong>Gratis</strong> al superar <strong>' + sym + threshold.toFixed(2) + '</strong> (con barra de progreso automática en el carrito).';
    } else {
        box.innerHTML = '<strong>Vista previa en carrito/checkout:</strong> Se cobrará una tarifa fija de <strong>' + sym + flat.toFixed(2) + '</strong> por pedido.';
    }
}

function updateOrderPrefixPreview() {
    const prefix = (document.getElementById('order_prefix')?.value || '#ORD-').trim() || '#ORD-';
    const preview = document.getElementById('order-prefix-preview');
    if (preview) preview.textContent = prefix + '8F3A12';
}

function setAnnouncementColor(hex) {
    const el = document.getElementById('announcement_bg');
    if (el) {
        el.value = hex;
        triggerDirty(el);
        updateAnnouncementPreview();
    }
}

function updateAnnouncementPreview() {
    const enabled = !!document.getElementById('announcement_enabled')?.checked;
    const text = (document.getElementById('announcement_text')?.value || '').trim();
    const bg = document.getElementById('announcement_bg')?.value || '#008060';
    const preview = document.getElementById('announcement-live-preview');
    if (!preview) return;

    preview.style.backgroundColor = bg;
    if (!enabled) {
        preview.style.opacity = '0.5';
        preview.textContent = '[Desactivado] ' + (text || 'Tu anuncio aparecerá aquí arriba en la tienda');
    } else {
        preview.style.opacity = '1';
        preview.textContent = text || 'Escribe un mensaje promocional para mostrar en la tienda';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        if (localStorage.getItem('shopcms_hide_settings_guide') === '1') {
            const guide = document.getElementById('settings-quick-guide');
            if (guide) guide.style.display = 'none';
        }
        const savedTab = sessionStorage.getItem('shopcms_settings_active_tab');
        if (savedTab && document.getElementById('panel-' + savedTab)) {
            switchSettingsTab(savedTab);
        }
    } catch (e) {}

    updateTaxSimulator();
    updateShippingPreview();
    updateOrderPrefixPreview();
    updateAnnouncementPreview();
});
</script>
