<div class="page-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 class="page-title">Gestión de Plugins & Extensiones</h1>
        <p class="page-subtitle">Sube paquetes comprimidos en <code>.zip</code> para ampliar la funcionalidad de ShopCMS.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button type="button" onclick="togglePluginGuide()" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span>¿Cómo usar?</span>
        </button>
    </div>
</div>

<!-- GUÍA RÁPIDA EN 3 PASOS -->
<div id="plugin-quick-guide" class="polaris-card" style="background: #F9FAFB; border-color: #E5E7EB; margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <strong style="font-size: 13px; color: #111827;">Guía rápida de instalación y control de plugins</strong>
            </div>
            <button type="button" onclick="togglePluginGuide()" style="background: none; border: none; color: #6B7280; font-size: 12px; cursor: pointer; text-decoration: underline;">Ocultar</button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">1</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Sube el paquete .ZIP validado</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Selecciona un archivo <code>.zip</code> que incluya su manifiesto <code>plugin.json</code> en la raíz o carpeta principal e instálalo.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">2</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Activa o pausa en un clic</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Usa <strong>Activar / Desactivar</strong> para habilitar o detener los hooks de la extensión al instante sin desinstalar sus archivos.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">3</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Depura módulos sin uso</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Si ya no necesitas una extensión, pulsa <strong>Eliminar</strong> para retirar su registro de la base de datos y limpiar <code>/plugins/</code>.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SUBIDA DE PLUGINS .ZIP -->
<div class="polaris-card" style="margin-bottom: 24px; border-left: 4px solid var(--color-shopify-green);">
    <div class="card-header"><h2 class="card-title">Instalar Nuevo Plugin desde Archivo .ZIP</h2></div>
    <div class="card-body">
        <form method="POST" action="<?= $baseUrl ?>/admin/plugins/upload" enctype="multipart/form-data" style="display: flex; gap: 16px; align-items: flex-end;">
            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                <label for="plugin_zip">Selecciona un archivo comprimido (.zip) con <code>plugin.json</code></label>
                <input type="file" id="plugin_zip" name="plugin_zip" class="form-control" accept=".zip" required>
            </div>
            <button type="submit" class="btn btn-primary" style="white-space: nowrap; height: 38px;">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                Instalar Plugin
            </button>
        </form>

        <div style="margin-top: 14px; font-size: 12px; color: var(--color-text-secondary); background: #FAFBFB; padding: 10px 14px; border-radius: 6px; border: 1px dashed var(--color-border);">
            <strong>Flujo automático:</strong> 1. Validación PHP de integridad &rarr; 2. Extracción con <code>ZipArchive</code> a <code>/plugins/{slug}/</code> &rarr; 3. Lectura de metadatos desde <code>plugin.json</code> &rarr; 4. Registro en BD &rarr; 5. Carga en <code>PluginManager</code>.
        </div>
    </div>
</div>

<!-- LISTA DE PLUGINS INSTALADOS -->
<div class="polaris-card">
    <div class="card-header"><h2 class="card-title">Plugins Instalados</h2></div>
    <div style="overflow-x: auto;">
        <table class="polaris-table">
            <thead>
                <tr>
                    <th>Plugin</th>
                    <th>Versión</th>
                    <th>Autor</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($plugins)): ?>
                    <tr><td colspan="6" style="text-align:center; padding: 24px; color: var(--color-text-muted);">No hay plugins instalados actualmente. ¡Sube tu primer .zip arriba!</td></tr>
                <?php else: ?>
                    <?php foreach ($plugins as $pl): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($pl['name']) ?></strong><br>
                                <code style="font-size: 11px; color: var(--color-text-muted);"><?= htmlspecialchars($pl['slug']) ?></code>
                            </td>
                            <td><span class="badge badge-draft">v<?= htmlspecialchars($pl['version']) ?></span></td>
                            <td><?= htmlspecialchars($pl['author'] ?: 'Desconocido') ?></td>
                            <td><p style="font-size: 13px; color: var(--color-text-secondary); max-width: 360px;"><?= htmlspecialchars($pl['description'] ?? '') ?></p></td>
                            <td>
                                <span class="badge <?= $pl['is_active'] ? 'badge-success' : 'badge-draft' ?>">
                                    <?= $pl['is_active'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <form method="POST" action="<?= $baseUrl ?>/admin/plugins/toggle/<?= $pl['slug'] ?>" style="display: inline;">
                                    <?= \ShopCMS\Core\Security::csrfField() ?>
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        <?= $pl['is_active'] ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                                <form method="POST" action="<?= $baseUrl ?>/admin/plugins/delete/<?= $pl['slug'] ?>" style="display: inline;" onsubmit="return confirm('¿Eliminar permanentemente este plugin y sus archivos?');">
                                    <?= \ShopCMS\Core\Security::csrfField() ?>
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function togglePluginGuide() {
    const guide = document.getElementById('plugin-quick-guide');
    if (!guide) return;
    const isHidden = guide.style.display === 'none';
    guide.style.display = isHidden ? 'block' : 'none';
    try {
        localStorage.setItem('shopcms_hide_plugin_guide', isHidden ? '0' : '1');
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        if (localStorage.getItem('shopcms_hide_plugin_guide') === '1') {
            const guide = document.getElementById('plugin-quick-guide');
            if (guide) guide.style.display = 'none';
        }
    } catch (e) {}
});
</script>

