<div class="page-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 class="page-title">Temas y Plantillas</h1>
        <p class="page-subtitle">Gestiona la apariencia visual de la tienda. Puedes activar temas existentes o subir nuevos paquetes en <code>.zip</code>.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button type="button" onclick="toggleThemeGuide()" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span>¿Cómo usar?</span>
        </button>
        <a href="<?= $baseUrl ?>/" target="_blank" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                <polyline points="15 3 21 3 21 9"></polyline>
                <line x1="10" y1="14" x2="21" y2="3"></line>
            </svg>
            Ver Tienda en Vivo
        </a>
    </div>
</div>

<!-- GUÍA RÁPIDA EN 3 PASOS -->
<div id="theme-quick-guide" class="polaris-card" style="background: #F9FAFB; border-color: #E5E7EB; margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <strong style="font-size: 13px; color: #111827;">Guía rápida de diseño y cambio de plantilla</strong>
            </div>
            <button type="button" onclick="toggleThemeGuide()" style="background: none; border: none; color: #6B7280; font-size: 12px; cursor: pointer; text-decoration: underline;">Ocultar</button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">1</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Activa una plantilla al instante</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Elige entre los temas instalados y pulsa <strong>Activar este Tema</strong>; el storefront cambiará inmediatamente sin afectar tus datos.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">2</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Importa temas personalizados</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Sube un paquete <code>.zip</code> con su archivo <code>theme.json</code> y vistas compatibles para agregar nuevos diseños a tu biblioteca.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">3</span>
                <div>
                    <div style="font-weight: 600; font-size: 12.5px; color: #111827; margin-bottom: 2px;">Verifica en la tienda en vivo</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Usa <strong>Ver Tienda en Vivo</strong> para comprobar cómo lucen portada, catálogo y carrito antes de compartir tu sitio.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SUBIDA DE PLANTILLAS .ZIP (REQUISITO ESPECIFICADO) -->
<div class="polaris-card" style="margin-bottom: 24px; border-left: 4px solid var(--color-shopify-green);">
    <div class="card-header"><h2 class="card-title">Instalar Nueva Plantilla desde Archivo .ZIP</h2></div>
    <div class="card-body">
        <form method="POST" action="<?= $baseUrl ?>/admin/themes/upload" enctype="multipart/form-data" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 260px; margin-bottom: 0;">
                <label for="theme_zip">Selecciona un archivo comprimido (.zip) con <code>theme.json</code></label>
                <input type="file" id="theme_zip" name="theme_zip" class="form-control" accept=".zip" required>
            </div>
            <button type="submit" class="btn btn-primary" style="white-space: nowrap; height: 38px; display: inline-flex; align-items: center; gap: 6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Instalar Plantilla
            </button>
        </form>

        <div style="margin-top: 14px; font-size: 12px; color: var(--color-text-secondary); background: #FAFBFB; padding: 10px 14px; border-radius: 6px; border: 1px dashed var(--color-border);">
            <strong>Flujo automático de instalación:</strong> 1. Validación PHP de integridad &rarr; 2. Extracción con <code>ZipArchive</code> a <code>/themes/{slug}/</code> &rarr; 3. Lectura de metadatos desde <code>theme.json</code> &rarr; 4. Registro en la tabla <code>shop_themes</code> &rarr; 5. Carga dinámica en <code>ThemeManager</code>.
        </div>
    </div>
</div>

<!-- GRILLA DE PLANTILLAS Y TEMAS DISPONIBLES -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
    <?php foreach ($themes as $th): ?>
        <?php 
        $isActive = ($th['slug'] === $activeTheme); 
        $slug = $th['slug'];
        
        // Estilo visual del banner según el tema
        $bannerBg = '#202223';
        $bannerTextColor = '#FFFFFF';
        $badgeSub = 'Tema General';
        if ($slug === 'minimal-shop') {
            $bannerBg = 'linear-gradient(135deg, #1C1917 0%, #2D3748 100%)';
            $badgeSub = 'Blanco Limpio con Producto Centrado';
        } elseif ($slug === 'fashion') {
            $bannerBg = 'linear-gradient(135deg, #09090B 0%, #7F1D1D 100%)';
            $badgeSub = 'Imágenes Grandes a Pantalla Completa';
        } elseif ($slug === 'electronics') {
            $bannerBg = 'linear-gradient(135deg, #0F172A 0%, #1E3A8A 100%)';
            $badgeSub = 'Grilla Densa con Filtros Laterales';
        } elseif ($slug === 'food-market') {
            $bannerBg = 'linear-gradient(135deg, #14532D 0%, #B45309 100%)';
            $badgeSub = 'Cálido con Categorías en Tarjetas';
        }
        ?>
        <div class="polaris-card" style="<?= $isActive ? 'border: 2px solid var(--color-shopify-green); box-shadow: 0 4px 16px rgba(0, 128, 96, 0.15);' : '' ?> display: flex; flex-direction: column;">
            <div style="height: 130px; background: <?= $bannerBg ?>; padding: 20px; display: flex; flex-direction: column; justify-content: flex-end; color: <?= $bannerTextColor ?>; border-radius: 8px 8px 0 0; position: relative;">
                <?php if ($isActive): ?>
                    <span style="position: absolute; top: 12px; right: 12px; background: #008060; color: #FFFFFF; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Activo
                    </span>
                <?php endif; ?>
                <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.85;"><?= htmlspecialchars($badgeSub) ?></span>
                <h3 style="font-size: 20px; font-weight: 700; margin: 4px 0 0; color: #FFFFFF;"><?= htmlspecialchars($th['name']) ?></h3>
            </div>
            
            <div class="card-body" style="flex: 1; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <code style="font-size: 11px; color: var(--color-text-secondary);"><?= htmlspecialchars($slug) ?></code>
                    <span class="badge <?= $isActive ? 'badge-success' : 'badge-draft' ?>">
                        <?= $isActive ? 'En uso' : 'Inactivo' ?>
                    </span>
                </div>

                <p style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 16px; flex: 1; line-height: 1.45;">
                    <?= htmlspecialchars($th['description'] ?? 'Tema para la tienda online.') ?>
                </p>

                <div style="font-size: 11px; color: var(--color-text-muted); margin-bottom: 14px; padding-top: 8px; border-top: 1px solid var(--color-border); display: flex; justify-content: space-between;">
                    <span>Versión: <?= htmlspecialchars($th['version'] ?? '1.0.0') ?></span>
                    <span><?= htmlspecialchars($th['author'] ?? 'ShopCMS Team') ?></span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="<?= $baseUrl ?>/admin/themes/customize/<?= htmlspecialchars($th['slug']) ?>" class="btn btn-secondary btn-sm" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-weight: 600; border-color: #008060; color: #008060; background: #F0FDF4;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                        </svg>
                        Personalizar en Vivo
                    </a>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <?php if ($isActive): ?>
                            <button class="btn btn-secondary btn-sm" disabled style="flex: 1; cursor: default; opacity: 0.9; display: flex; align-items: center; justify-content: center; gap: 6px; font-weight: 600;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                Tema Actual en Tienda
                            </button>
                        <?php else: ?>
                            <form method="POST" action="<?= $baseUrl ?>/admin/themes/activate/<?= $th['slug'] ?>" style="display: flex; flex: 1;">
                                <?= \ShopCMS\Core\Security::csrfField() ?>
                                <button type="submit" class="btn btn-primary btn-sm" style="flex: 1; text-align: center; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                    Activar este Tema
                                </button>
                            </form>
                            <?php if ($slug !== 'minimal-shop'): ?>
                                <form method="POST" action="<?= $baseUrl ?>/admin/themes/delete/<?= $th['slug'] ?>" style="display: inline;" onsubmit="return confirm('¿Eliminar permanentemente este tema y sus archivos?');">
                                    <?= \ShopCMS\Core\Security::csrfField() ?>
                                    <button type="submit" class="btn btn-danger btn-sm" title="Eliminar plantilla" style="padding: 6px 10px;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
function toggleThemeGuide() {
    const guide = document.getElementById('theme-quick-guide');
    if (!guide) return;
    const isHidden = guide.style.display === 'none';
    guide.style.display = isHidden ? 'block' : 'none';
    try {
        localStorage.setItem('shopcms_hide_theme_guide', isHidden ? '0' : '1');
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        if (localStorage.getItem('shopcms_hide_theme_guide') === '1') {
            const guide = document.getElementById('theme-quick-guide');
            if (guide) guide.style.display = 'none';
        }
    } catch (e) {}
});
</script>
