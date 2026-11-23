<?php
$baseUrl = defined('APP_URL') ? APP_URL : '';
$slug = $themeMeta['slug'] ?? 'minimal-shop';
$themeName = $themeMeta['name'] ?? $slug;
$isCurrentActive = ($slug === ($activeTheme ?? 'minimal-shop'));
$previewProductPath = !empty($sampleProductSlug) ? '/product/' . $sampleProductSlug : '/catalog';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= \ShopCMS\Core\Security::csrfMetaTag() ?>
    <title>Editor Visual: <?= htmlspecialchars($themeName) ?> — ShopCMS Studio</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Inter, sans-serif; background: #F1F2F4; color: #1F2937; }

        /* TOPBAR */
        .studio-topbar {
            height: 54px;
            background: #1A1A1A;
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 14px;
            gap: 12px;
            border-bottom: 1px solid #2E2E2E;
        }
        .topbar-group { display: flex; align-items: center; gap: 10px; }
        .studio-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #3F3F46;
            background: #27272A;
            color: #F4F4F5;
            padding: 6px 11px;
            border-radius: 7px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s ease, border-color 0.15s ease;
        }
        .studio-btn:hover:not(:disabled) { background: #3F3F46; }
        .studio-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .studio-btn.active { background: #2563EB; border-color: #2563EB; color: #FFFFFF; }
        .studio-btn-save {
            background: #008060;
            border-color: #008060;
            color: #FFFFFF;
            padding: 7px 14px;
        }
        .studio-btn-save:hover:not(:disabled) { background: #006E52; }
        .studio-select {
            background: #27272A;
            color: #FFFFFF;
            border: 1px solid #3F3F46;
            border-radius: 7px;
            padding: 6px 10px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
        }
        .theme-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 999px;
            font-weight: 700;
        }
        .theme-badge-live { background: #064E3B; color: #6EE7B7; }
        .theme-badge-draft { background: #78350F; color: #FDE68A; }

        /* MAIN 3-COLUMN WORKSPACE */
        .studio-workspace {
            display: grid;
            grid-template-columns: 275px 1fr 315px;
            height: calc(100vh - 54px);
            overflow: hidden;
        }
        @media (max-width: 1100px) {
            .studio-workspace { grid-template-columns: 235px 1fr 275px; }
        }

        /* SIDEBARS */
        .studio-sidebar {
            background: #FFFFFF;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: 100%;
        }
        .studio-left { border-right: 1px solid #E5E7EB; }
        .studio-right { border-left: 1px solid #E5E7EB; }

        .sidebar-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-bottom: 1px solid #E5E7EB;
            background: #F9FAFB;
        }
        .sidebar-tab-btn {
            border: none;
            background: transparent;
            padding: 11px 8px;
            font-size: 12.5px;
            font-weight: 600;
            color: #6B7280;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-bottom: 2px solid transparent;
        }
        .sidebar-tab-btn.active {
            color: #008060;
            background: #FFFFFF;
            border-bottom-color: #008060;
        }

        .sidebar-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
        }

        /* SECTIONS TREE */
        .tree-group-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9CA3AF;
            margin: 10px 4px 6px;
        }
        .tree-item {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 8px 9px;
            border-radius: 7px;
            border: 1px solid transparent;
            cursor: pointer;
            font-size: 12.5px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 3px;
            background: #FFFFFF;
            transition: background 0.12s ease, border-color 0.12s ease;
        }
        .tree-item:hover { background: #F3F4F6; }
        .tree-item.selected {
            background: #ECFDF5;
            border-color: #008060;
            color: #065F46;
            font-weight: 600;
        }
        .tree-item.hidden-sec { opacity: 0.52; }
        .tree-item.drag-over { border-top: 2.5px solid #2563EB; }
        .tree-drag-handle {
            color: #9CA3AF;
            cursor: grab;
            display: inline-flex;
            flex-shrink: 0;
        }
        .tree-label { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tree-icon-btn {
            background: none;
            border: none;
            color: #6B7280;
            padding: 3px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
        }
        .tree-icon-btn:hover { background: #E5E7EB; color: #111827; }

        .btn-add-section {
            width: 100%;
            margin-top: 8px;
            padding: 9px 12px;
            border: 1px dashed #008060;
            background: #F0FDF4;
            color: #008060;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-add-section:hover { background: #DCFCE7; }

        .block-picker {
            margin-top: 10px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            background: #F9FAFB;
            padding: 8px;
            display: none;
        }
        .block-picker.open { display: block; }
        .block-option {
            width: 100%;
            text-align: left;
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 6px;
            cursor: pointer;
        }
        .block-option:last-child { margin-bottom: 0; }
        .block-option:hover { border-color: #008060; background: #ECFDF5; }
        .block-option-title { font-size: 12.5px; font-weight: 700; color: #111827; }
        .block-option-desc { font-size: 11px; color: #6B7280; margin-top: 2px; line-height: 1.35; }

        /* PREVIEW CANVAS */
        .studio-canvas {
            background: #E5E7EB;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 14px;
            overflow: hidden;
            position: relative;
        }
        .preview-frame-shell {
            width: 100%;
            height: 100%;
            background: #FFFFFF;
            border-radius: 10px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.12);
            overflow: hidden;
            transition: width 0.25s ease;
            border: 1px solid #D1D5DB;
            position: relative;
        }
        .preview-frame-shell.viewport-desktop { width: 100%; }
        .preview-frame-shell.viewport-tablet { width: 768px; max-width: 100%; }
        .preview-frame-shell.viewport-mobile { width: 390px; max-width: 100%; border-radius: 20px; border: 6px solid #1F2937; }
        .preview-iframe { width: 100%; height: 100%; border: none; background: #FFFFFF; }

        /* RIGHT INSPECTOR */
        .inspector-header {
            padding: 12px 14px;
            border-bottom: 1px solid #E5E7EB;
            background: #F9FAFB;
        }
        .inspector-title { font-size: 13.5px; font-weight: 700; color: #111827; }
        .inspector-sub { font-size: 11.5px; color: #6B7280; margin-top: 2px; }

        .ctrl-group { margin-bottom: 14px; }
        .ctrl-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
        }
        .ctrl-val-tag { font-size: 11px; color: #008060; font-weight: 700; }
        .ctrl-input, .ctrl-textarea, .ctrl-select {
            width: 100%;
            padding: 7px 10px;
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            font-size: 12.5px;
            font-family: inherit;
            color: #111827;
            background: #FFFFFF;
        }
        .ctrl-input:focus, .ctrl-textarea:focus, .ctrl-select:focus {
            outline: none;
            border-color: #008060;
        }
        .ctrl-color-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .ctrl-color-picker {
            width: 34px;
            height: 32px;
            padding: 2px;
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            cursor: pointer;
            background: #FFFFFF;
        }
        .ctrl-range { width: 100%; accent-color: #008060; cursor: pointer; }
        .ctrl-switch-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 10px;
            background: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 7px;
            margin-bottom: 10px;
            font-size: 12.5px;
            font-weight: 600;
        }
        .palette-chip {
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            padding: 6px 8px;
            background: #FFFFFF;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .palette-chip:hover { border-color: #008060; }
        .palette-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }

        .studio-toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #111827;
            color: #FFFFFF;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(0,0,0,0.22);
            z-index: 9999;
            display: none;
        }
        .studio-toast.show { display: block; }
    </style>
</head>
<body>

<!-- BARRA SUPERIOR DE NAVEGACIÓN -->
<header class="studio-topbar">
    <div class="topbar-group">
        <a href="<?= $baseUrl ?>/admin/themes" class="studio-btn" title="Volver a Temas">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="15 18 9 12 15 6"></polyline></svg>
            <span>Salir</span>
        </a>
        <div style="display: flex; align-items: center; gap: 8px;">
            <strong style="font-size: 13.5px;"><?= htmlspecialchars($themeName) ?></strong>
            <span class="theme-badge <?= $isCurrentActive ? 'theme-badge-live' : 'theme-badge-draft' ?>">
                <?= $isCurrentActive ? 'Tema Activo' : 'Vista Previa' ?>
            </span>
            <span id="unsaved-dot" style="display: none; font-size: 11px; color: #FBBF24; font-weight: 600;">Cambios sin guardar</span>
        </div>
    </div>

    <div class="topbar-group">
        <label for="template-selector" style="font-size: 11.5px; color: #A1A1AA;">Plantilla:</label>
        <select id="template-selector" class="studio-select" onchange="switchTemplatePage(this.value)">
            <option value="home">Página de Inicio</option>
            <option value="catalog">Catálogo / Colecciones</option>
            <option value="product">Página de Producto</option>
            <option value="cart">Carrito de Compras</option>
        </select>

        <div style="display: flex; gap: 4px; background: #27272A; padding: 3px; border-radius: 8px;">
            <button type="button" class="studio-btn active" id="vp-desktop" onclick="setViewport('desktop')" title="Vista Escritorio" style="border: none; padding: 5px 8px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            </button>
            <button type="button" class="studio-btn" id="vp-tablet" onclick="setViewport('tablet')" title="Vista Tablet" style="border: none; padding: 5px 8px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
            </button>
            <button type="button" class="studio-btn" id="vp-mobile" onclick="setViewport('mobile')" title="Vista Móvil" style="border: none; padding: 5px 8px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="2" width="12" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
            </button>
        </div>
    </div>

    <div class="topbar-group">
        <button type="button" class="studio-btn" id="btn-undo" onclick="undoChange()" disabled title="Deshacer">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
        </button>
        <button type="button" class="studio-btn" id="btn-redo" onclick="redoChange()" disabled title="Rehacer">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/></svg>
        </button>
        <button type="button" class="studio-btn" onclick="resetThemeConfig()" title="Restablecer valores originales">Restablecer</button>
        <button type="button" class="studio-btn studio-btn-save" id="btn-save" onclick="saveThemeConfig()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <span>Guardar</span>
        </button>
    </div>
</header>

<!-- ÁREA DE 3 PANELES -->
<div class="studio-workspace">

    <!-- 1. BARRA LATERAL IZQUIERDA (ESTRUCTURA Y CONFIG. GLOBAL) -->
    <aside class="studio-sidebar studio-left">
        <div class="sidebar-tabs">
            <button type="button" class="sidebar-tab-btn active" id="left-tab-sections" onclick="setLeftMode('sections')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                <span>Secciones</span>
            </button>
            <button type="button" class="sidebar-tab-btn" id="left-tab-global" onclick="setLeftMode('global')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span>Tema Global</span>
            </button>
        </div>

        <div class="sidebar-scroll" id="left-sections-pane">
            <div class="tree-group-title">Cabecera del Sitio</div>
            <div class="tree-item" id="tree-header" onclick="selectSection('header')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="4" rx="1"/><line x1="3" y1="14" x2="21" y2="14"/></svg>
                <span class="tree-label">Encabezado y Logo</span>
            </div>

            <div class="tree-group-title" id="tree-page-label">Bloques de Inicio (Arrastrables)</div>
            <div id="sections-tree-list"></div>

            <button type="button" class="btn-add-section" onclick="toggleBlockPicker()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>Agregar Sección</span>
            </button>

            <div class="block-picker" id="block-picker">
                <?php foreach ($blockTypes as $bType => $bInfo): ?>
                    <button type="button" class="block-option" onclick="addCustomSection('<?= htmlspecialchars($bType) ?>')">
                        <div class="block-option-title"><?= htmlspecialchars($bInfo['label']) ?></div>
                        <div class="block-option-desc"><?= htmlspecialchars($bInfo['desc']) ?></div>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="tree-group-title" style="margin-top: 14px;">Pie del Sitio</div>
            <div class="tree-item" id="tree-footer" onclick="selectSection('footer')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="16" width="18" height="4" rx="1"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span class="tree-label">Pie de Página</span>
            </div>
        </div>

        <div class="sidebar-scroll" id="left-global-pane" style="display: none;">
            <div style="font-size: 12px; color: #6B7280; line-height: 1.5; margin-bottom: 14px;">
                Los ajustes globales aplican a <strong>todas las páginas</strong> de la plantilla <?= htmlspecialchars($themeName) ?>. Edítalos directamente en el panel derecho.
            </div>
            <div class="tree-item selected" onclick="openGlobalInspector('brand')">
                <span class="tree-label">Logotipo de Marca</span>
            </div>
            <div class="tree-item" onclick="openGlobalInspector('colors')">
                <span class="tree-label">Esquema de Colores</span>
            </div>
            <div class="tree-item" onclick="openGlobalInspector('typography')">
                <span class="tree-label">Tipografía y Espaciado</span>
            </div>
        </div>
    </aside>

    <!-- 2. VENTANA CENTRAL DE VISTA PREVIA -->
    <main class="studio-canvas">
        <div class="preview-frame-shell viewport-desktop" id="preview-shell">
            <iframe
                id="preview-iframe"
                class="preview-iframe"
                src="<?= $baseUrl ?>/?preview_theme=<?= urlencode($slug) ?>&theme_editor=1"
                title="Vista previa en tiempo real">
            </iframe>
        </div>
    </main>

    <!-- 3. PANEL DERECHO (INSPECTOR CONTEXTUAL) -->
    <aside class="studio-sidebar studio-right">
        <div class="inspector-header">
            <div class="inspector-title" id="inspector-title">Banner Principal (Hero)</div>
            <div class="inspector-sub" id="inspector-sub">Haz clic en cualquier elemento o sección para editarlo.</div>
        </div>
        <div class="sidebar-scroll" id="inspector-body"></div>
    </aside>
</div>

<div class="studio-toast" id="studio-toast"></div>

<script>
const BASE_URL = <?= json_encode($baseUrl) ?>;
const CSRF_TOKEN = <?= json_encode(\ShopCMS\Core\Security::csrfToken()) ?>;
const THEME_SLUG = <?= json_encode($slug) ?>;
const SAMPLE_PRODUCT_PATH = <?= json_encode($previewProductPath) ?>;
const BLOCK_TYPES = <?= json_encode($blockTypes, JSON_UNESCAPED_UNICODE) ?>;
const ALL_CATEGORIES = <?= json_encode($allCategories ?? [], JSON_UNESCAPED_UNICODE) ?>;

let themeConfig = <?= json_encode($themeConfig, JSON_UNESCAPED_UNICODE) ?>;
let currentTemplate = 'home';
let selectedSectionId = 'hero';
let leftMode = 'sections';
let undoStack = [];
let redoStack = [];
let hasUnsaved = false;
let draggedSectionId = null;

const SECTION_LABELS = {
    header: 'Encabezado y Logotipo',
    footer: 'Pie de Página',
    hero: 'Banner Principal (Hero)',
    categories: 'Categorías',
    featured_products: 'Productos Destacados',
    trust_pillars: 'Pilares de Confianza',
    catalog_header: 'Cabecera de Catálogo',
    catalog_filters: 'Barra de Filtros',
    catalog_grid: 'Grilla de Productos',
    product_main: 'Ficha de Producto',
    product_reviews: 'Reseñas de Clientes',
    product_related: 'Productos Relacionados',
    cart_header: 'Cabecera del Carrito',
    cart_items: 'Lista de Artículos',
    cart_summary: 'Cupones y Totales'
};

function deepClone(obj) {
    return JSON.parse(JSON.stringify(obj));
}

function showToast(msg) {
    const el = document.getElementById('studio-toast');
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(el._timer);
    el._timer = setTimeout(() => el.classList.remove('show'), 2600);
}

function markDirty() {
    hasUnsaved = true;
    document.getElementById('unsaved-dot').style.display = 'inline';
    updateHistoryButtons();
}

function pushUndoSnapshot() {
    undoStack.push(deepClone(themeConfig));
    if (undoStack.length > 40) undoStack.shift();
    redoStack = [];
    markDirty();
}

function updateHistoryButtons() {
    document.getElementById('btn-undo').disabled = undoStack.length === 0;
    document.getElementById('btn-redo').disabled = redoStack.length === 0;
}

function undoChange() {
    if (!undoStack.length) return;
    redoStack.push(deepClone(themeConfig));
    themeConfig = undoStack.pop();
    hasUnsaved = true;
    renderTree();
    renderInspector();
    syncPreview();
    updateHistoryButtons();
}

function redoChange() {
    if (!redoStack.length) return;
    undoStack.push(deepClone(themeConfig));
    themeConfig = redoStack.pop();
    hasUnsaved = true;
    renderTree();
    renderInspector();
    syncPreview();
    updateHistoryButtons();
}

function syncPreview() {
    const iframe = document.getElementById('preview-iframe');
    if (iframe && iframe.contentWindow) {
        iframe.contentWindow.postMessage({
            type: 'shopcms_apply_config',
            template: currentTemplate,
            selectedSection: selectedSectionId,
            config: themeConfig
        }, '*');
    }
}

document.getElementById('preview-iframe').addEventListener('load', function() {
    setTimeout(syncPreview, 60);
});

window.addEventListener('message', function(e) {
    if (!e.data || e.data.type !== 'shopcms_select_section') return;
    if (e.data.sectionId) {
        setLeftMode('sections');
        selectSection(e.data.sectionId);
    }
});

function setViewport(mode) {
    const shell = document.getElementById('preview-shell');
    shell.className = 'preview-frame-shell viewport-' + mode;
    ['desktop', 'tablet', 'mobile'].forEach(v => {
        document.getElementById('vp-' + v).classList.toggle('active', v === mode);
    });
}

function switchTemplatePage(tpl) {
    currentTemplate = tpl;
    const labels = {
        home: 'Bloques de Inicio (Arrastrables)',
        catalog: 'Bloques de Catálogo',
        product: 'Bloques de Producto',
        cart: 'Bloques de Carrito'
    };
    document.getElementById('tree-page-label').textContent = labels[tpl] || 'Bloques';

    const pathMap = {
        home: '/',
        catalog: '/catalog',
        product: SAMPLE_PRODUCT_PATH,
        cart: '/cart'
    };
    const path = pathMap[tpl] || '/';
    const sep = path.includes('?') ? '&' : '?';
    document.getElementById('preview-iframe').src = BASE_URL + path + sep + 'preview_theme=' + encodeURIComponent(THEME_SLUG) + '&theme_editor=1';

    const order = (themeConfig.templates[tpl] && themeConfig.templates[tpl].section_order) || [];
    selectedSectionId = order[0] || 'header';
    renderTree();
    renderInspector();
}

function setLeftMode(mode) {
    leftMode = mode;
    document.getElementById('left-tab-sections').classList.toggle('active', mode === 'sections');
    document.getElementById('left-tab-global').classList.toggle('active', mode === 'global');
    document.getElementById('left-sections-pane').style.display = mode === 'sections' ? 'block' : 'none';
    document.getElementById('left-global-pane').style.display = mode === 'global' ? 'block' : 'none';
    if (mode === 'global') {
        selectedSectionId = 'global_all';
        renderInspector();
    } else {
        const order = themeConfig.templates[currentTemplate].section_order || [];
        if (selectedSectionId === 'global_all') selectedSectionId = order[0] || 'header';
        renderTree();
        renderInspector();
    }
}

function openGlobalInspector(part) {
    selectedSectionId = 'global_all';
    renderInspector(part);
}

function selectSection(secId) {
    selectedSectionId = secId;
    renderTree();
    renderInspector();
    syncPreview();
}

function getSectionObj(secId) {
    const tpl = themeConfig.templates[currentTemplate];
    if (tpl.sections && tpl.sections[secId]) return { kind: 'builtin', obj: tpl.sections[secId] };
    if (tpl.custom_sections && tpl.custom_sections[secId]) return { kind: 'custom', obj: tpl.custom_sections[secId] };
    return null;
}

function renderTree() {
    const list = document.getElementById('sections-tree-list');
    const tpl = themeConfig.templates[currentTemplate];
    const order = tpl.section_order || [];

    document.getElementById('tree-header').classList.toggle('selected', selectedSectionId === 'header');
    document.getElementById('tree-footer').classList.toggle('selected', selectedSectionId === 'footer');

    list.innerHTML = '';
    order.forEach((secId) => {
        const found = getSectionObj(secId);
        if (!found) return;
        const sec = found.obj;
        const isHidden = sec.enabled === false;
        const label = found.kind === 'custom'
            ? (sec.title || (BLOCK_TYPES[sec.type] ? BLOCK_TYPES[sec.type].label : 'Bloque'))
            : (SECTION_LABELS[secId] || secId);

        const row = document.createElement('div');
        row.className = 'tree-item' + (selectedSectionId === secId ? ' selected' : '') + (isHidden ? ' hidden-sec' : '');
        row.draggable = true;
        row.dataset.secId = secId;

        row.addEventListener('dragstart', (e) => {
            draggedSectionId = secId;
            e.dataTransfer.effectAllowed = 'move';
        });
        row.addEventListener('dragover', (e) => {
            e.preventDefault();
            row.classList.add('drag-over');
        });
        row.addEventListener('dragleave', () => row.classList.remove('drag-over'));
        row.addEventListener('drop', (e) => {
            e.preventDefault();
            row.classList.remove('drag-over');
            if (!draggedSectionId || draggedSectionId === secId) return;
            pushUndoSnapshot();
            const arr = tpl.section_order;
            const fromIdx = arr.indexOf(draggedSectionId);
            const toIdx = arr.indexOf(secId);
            if (fromIdx !== -1 && toIdx !== -1) {
                arr.splice(fromIdx, 1);
                arr.splice(toIdx, 0, draggedSectionId);
                renderTree();
                syncPreview();
            }
            draggedSectionId = null;
        });

        row.onclick = (e) => {
            if (e.target.closest('.tree-icon-btn')) return;
            selectSection(secId);
        };

        const dragIcon = '<span class="tree-drag-handle" title="Arrastrar para reordenar"><svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg></span>';
        const eyeSvg = isHidden
            ? '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
            : '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        const delBtn = found.kind === 'custom'
            ? `<button type="button" class="tree-icon-btn" title="Eliminar bloque" onclick="deleteCustomSection('${secId}')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg></button>`
            : '';

        row.innerHTML = `
            ${dragIcon}
            <span class="tree-label">${escapeHtml(label)}</span>
            <button type="button" class="tree-icon-btn" title="${isHidden ? 'Mostrar' : 'Ocultar'}" onclick="toggleSectionVisibility('${secId}')">${eyeSvg}</button>
            ${delBtn}
        `;
        list.appendChild(row);
    });
}

function toggleSectionVisibility(secId) {
    const found = getSectionObj(secId);
    if (!found) return;
    pushUndoSnapshot();
    found.obj.enabled = (found.obj.enabled === false) ? true : false;
    renderTree();
    renderInspector();
    syncPreview();
}

function toggleBlockPicker() {
    document.getElementById('block-picker').classList.toggle('open');
}

function addCustomSection(blockType) {
    const tplDef = BLOCK_TYPES[blockType];
    if (!tplDef) return;
    pushUndoSnapshot();

    const newId = 'custom_' + Date.now();
    const secData = deepClone(tplDef.defaults);
    const tpl = themeConfig.templates[currentTemplate];
    if (!tpl.custom_sections || Array.isArray(tpl.custom_sections)) tpl.custom_sections = {};
    tpl.custom_sections[newId] = secData;
    tpl.section_order.push(newId);

    document.getElementById('block-picker').classList.remove('open');
    injectCustomSectionIntoPreview(newId, secData);
    selectSection(newId);
    showToast('Bloque "' + tplDef.label + '" agregado. Puedes arrastrarlo para reordenar.');
}

function deleteCustomSection(secId) {
    pushUndoSnapshot();
    const tpl = themeConfig.templates[currentTemplate];
    if (tpl.custom_sections) delete tpl.custom_sections[secId];
    tpl.section_order = tpl.section_order.filter(id => id !== secId);
    const iframe = document.getElementById('preview-iframe');
    if (iframe && iframe.contentDocument) {
        const el = iframe.contentDocument.querySelector('[data-theme-section="' + secId + '"]');
        if (el) el.remove();
    }
    selectedSectionId = tpl.section_order[0] || 'header';
    renderTree();
    renderInspector();
}

function injectCustomSectionIntoPreview(id, sec) {
    const iframe = document.getElementById('preview-iframe');
    if (!iframe || !iframe.contentDocument) return;
    const container = iframe.contentDocument.querySelector('.scms-template-sections');
    if (!container) return;

    const sectionEl = iframe.contentDocument.createElement('section');
    sectionEl.setAttribute('data-theme-section', id);
    sectionEl.setAttribute('data-section-label', sec.title || 'Bloque Personalizado');
    sectionEl.style.backgroundColor = sec.bg_color || '#F8F9FA';
    sectionEl.style.color = sec.text_color || '#111827';
    sectionEl.style.padding = (sec.padding_y || 36) + 'px 20px';
    sectionEl.style.margin = '24px 0';
    sectionEl.style.borderRadius = 'var(--scms-radius, 10px)';

    if (sec.type === 'promo_banner') {
        sectionEl.innerHTML = `<div class="container" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:18px;"><div><div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;opacity:0.85;margin-bottom:4px;" data-theme-field="tag">${escapeHtml(sec.tag || '')}</div><h3 style="font-size:22px;font-weight:700;margin:0 0 4px;" data-theme-field="title">${escapeHtml(sec.title || '')}</h3><p style="font-size:14px;opacity:0.9;margin:0;" data-theme-field="body">${escapeHtml(sec.body || '')}</p></div><a href="#" style="background:${escapeHtml(sec.text_color || '#FFF')};color:${escapeHtml(sec.bg_color || '#008060')};padding:11px 22px;border-radius:8px;font-weight:700;font-size:13.5px;text-decoration:none;" data-theme-field="cta_text">${escapeHtml(sec.cta_text || '')}</a></div>`;
    } else if (sec.type === 'testimonials') {
        sectionEl.innerHTML = `<div class="container"><h2 style="text-align:center;font-size:22px;font-weight:700;margin-bottom:24px;" data-theme-field="title">${escapeHtml(sec.title || '')}</h2><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;">${[1,2,3].map(i => `<div style="background:#FFF;color:#111827;padding:20px;border-radius:10px;border:1px solid #E5E7EB;"><p style="font-size:13.5px;line-height:1.5;margin-bottom:10px;" data-theme-field="quote_${i}">"${escapeHtml(sec['quote_'+i] || '')}"</p><strong style="font-size:12.5px;color:#4B5563;" data-theme-field="author_${i}">${escapeHtml(sec['author_'+i] || '')}</strong></div>`).join('')}</div></div>`;
    } else if (sec.type === 'newsletter') {
        sectionEl.style.textAlign = 'center';
        sectionEl.innerHTML = `<div class="container" style="max-width:620px;"><h3 style="font-size:22px;font-weight:700;margin-bottom:6px;" data-theme-field="title">${escapeHtml(sec.title || '')}</h3><p style="font-size:14px;opacity:0.88;margin-bottom:18px;" data-theme-field="body">${escapeHtml(sec.body || '')}</p><div style="display:flex;gap:10px;justify-content:center;"><input type="email" placeholder="${escapeHtml(sec.placeholder || '')}" style="flex:1;padding:11px 14px;border-radius:8px;border:1px solid rgba(255,255,255,0.25);"><button type="button" style="background:var(--color-primary,#008060);color:#FFF;border:none;padding:11px 22px;border-radius:8px;font-weight:700;" data-theme-field="cta_text">${escapeHtml(sec.cta_text || '')}</button></div></div>`;
    } else if (sec.type === 'media_text') {
        sectionEl.innerHTML = `<div class="container" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;align-items:center;"><div style="background:#E5E7EB;border-radius:10px;min-height:180px;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:12px;">Vista previa de imagen</div><div><h3 style="font-size:22px;font-weight:700;margin-bottom:10px;" data-theme-field="title">${escapeHtml(sec.title || '')}</h3><p style="font-size:14px;line-height:1.6;margin-bottom:16px;" data-theme-field="body">${escapeHtml(sec.body || '')}</p><a href="#" class="btn-store btn-store-primary" style="display:inline-block;padding:10px 20px;border-radius:8px;color:#FFF;text-decoration:none;font-weight:600;" data-theme-field="cta_text">${escapeHtml(sec.cta_text || '')}</a></div></div>`;
    } else {
        sectionEl.innerHTML = `<div class="container"><h3 style="font-size:20px;font-weight:700;margin-bottom:8px;" data-theme-field="title">${escapeHtml(sec.title || '')}</h3><p style="font-size:13.5px;margin-bottom:4px;"><strong>Dirección:</strong> <span data-theme-field="address">${escapeHtml(sec.address || '')}</span></p><p style="font-size:13.5px;margin-bottom:4px;"><strong>Horario:</strong> <span data-theme-field="hours">${escapeHtml(sec.hours || '')}</span></p><p style="font-size:13.5px;"><strong>Teléfono:</strong> <span data-theme-field="phone">${escapeHtml(sec.phone || '')}</span></p></div>`;
    }

    const pill = iframe.contentDocument.createElement('span');
    pill.className = 'scms-section-pill';
    pill.textContent = sec.title || 'Bloque';
    sectionEl.insertBefore(pill, sectionEl.firstChild);
    container.appendChild(sectionEl);
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
}

/* ========================================================
   INSPECTOR CONTEXTUAL (PANEL DERECHO)
   ======================================================== */
function makeTextCtrl(label, value, onChange) {
    const wrap = document.createElement('div');
    wrap.className = 'ctrl-group';
    wrap.innerHTML = `<label class="ctrl-label"><span>${label}</span></label>`;
    const inp = document.createElement('input');
    inp.type = 'text';
    inp.className = 'ctrl-input';
    inp.value = value ?? '';
    let snapshotTaken = false;
    inp.addEventListener('focus', () => { snapshotTaken = false; });
    inp.addEventListener('input', () => {
        if (!snapshotTaken) { pushUndoSnapshot(); snapshotTaken = true; }
        onChange(inp.value);
        syncPreview();
    });
    wrap.appendChild(inp);
    return wrap;
}

function makeTextareaCtrl(label, value, onChange) {
    const wrap = document.createElement('div');
    wrap.className = 'ctrl-group';
    wrap.innerHTML = `<label class="ctrl-label"><span>${label}</span></label>`;
    const ta = document.createElement('textarea');
    ta.rows = 3;
    ta.className = 'ctrl-textarea';
    ta.value = value ?? '';
    let snapshotTaken = false;
    ta.addEventListener('focus', () => { snapshotTaken = false; });
    ta.addEventListener('input', () => {
        if (!snapshotTaken) { pushUndoSnapshot(); snapshotTaken = true; }
        onChange(ta.value);
        syncPreview();
    });
    wrap.appendChild(ta);
    return wrap;
}

function makeRangeCtrl(label, value, min, max, unit, onChange) {
    const wrap = document.createElement('div');
    wrap.className = 'ctrl-group';
    const valTag = document.createElement('span');
    valTag.className = 'ctrl-val-tag';
    valTag.textContent = (value ?? min) + unit;

    const lbl = document.createElement('label');
    lbl.className = 'ctrl-label';
    lbl.innerHTML = `<span>${label}</span>`;
    lbl.appendChild(valTag);

    const rng = document.createElement('input');
    rng.type = 'range';
    rng.className = 'ctrl-range';
    rng.min = min;
    rng.max = max;
    rng.value = value ?? min;
    let snapshotTaken = false;
    rng.addEventListener('mousedown', () => { snapshotTaken = false; });
    rng.addEventListener('input', () => {
        if (!snapshotTaken) { pushUndoSnapshot(); snapshotTaken = true; }
        const num = parseInt(rng.value, 10);
        valTag.textContent = num + unit;
        onChange(num);
        syncPreview();
    });

    wrap.appendChild(lbl);
    wrap.appendChild(rng);
    return wrap;
}

function makeColorCtrl(label, value, onChange) {
    const wrap = document.createElement('div');
    wrap.className = 'ctrl-group';
    wrap.innerHTML = `<label class="ctrl-label"><span>${label}</span></label>`;
    const row = document.createElement('div');
    row.className = 'ctrl-color-row';

    const picker = document.createElement('input');
    picker.type = 'color';
    picker.className = 'ctrl-color-picker';
    picker.value = /^#[0-9A-Fa-f]{6}$/.test(value || '') ? value : '#FFFFFF';

    const txt = document.createElement('input');
    txt.type = 'text';
    txt.className = 'ctrl-input';
    txt.value = value || '#FFFFFF';

    let snapshotTaken = false;
    const commit = (val) => {
        if (!snapshotTaken) { pushUndoSnapshot(); snapshotTaken = true; }
        onChange(val);
        syncPreview();
    };
    picker.addEventListener('click', () => { snapshotTaken = false; });
    picker.addEventListener('input', () => { txt.value = picker.value; commit(picker.value); });
    txt.addEventListener('focus', () => { snapshotTaken = false; });
    txt.addEventListener('input', () => {
        if (/^#[0-9A-Fa-f]{6}$/.test(txt.value)) picker.value = txt.value;
        commit(txt.value);
    });

    row.appendChild(picker);
    row.appendChild(txt);
    wrap.appendChild(row);
    return wrap;
}

function makeSelectCtrl(label, value, options, onChange) {
    const wrap = document.createElement('div');
    wrap.className = 'ctrl-group';
    wrap.innerHTML = `<label class="ctrl-label"><span>${label}</span></label>`;
    const sel = document.createElement('select');
    sel.className = 'ctrl-select';
    options.forEach(opt => {
        const o = document.createElement('option');
        o.value = opt.value;
        o.textContent = opt.label;
        if (String(opt.value) === String(value)) o.selected = true;
        sel.appendChild(o);
    });
    sel.addEventListener('change', () => {
        pushUndoSnapshot();
        onChange(sel.value);
        syncPreview();
    });
    wrap.appendChild(sel);
    return wrap;
}

function makeSwitchCtrl(label, checked, onChange) {
    const row = document.createElement('label');
    row.className = 'ctrl-switch-row';
    row.style.cursor = 'pointer';
    row.innerHTML = `<span>${label}</span>`;
    const chk = document.createElement('input');
    chk.type = 'checkbox';
    chk.checked = !!checked;
    chk.style.width = '16px';
    chk.style.height = '16px';
    chk.style.accentColor = '#008060';
    chk.addEventListener('change', () => {
        pushUndoSnapshot();
        onChange(chk.checked);
        renderTree();
        syncPreview();
    });
    row.appendChild(chk);
    return row;
}

function makeImageUploadCtrl(label, currentVal, onChange, reloadIframeOnUpload) {
    const wrap = document.createElement('div');
    wrap.className = 'ctrl-group';
    wrap.innerHTML = `<label class="ctrl-label"><span>${label}</span></label>`;

    const txt = document.createElement('input');
    txt.type = 'text';
    txt.className = 'ctrl-input';
    txt.placeholder = 'URL o sube una imagen...';
    txt.value = currentVal || '';
    txt.style.marginBottom = '6px';
    txt.addEventListener('change', () => {
        pushUndoSnapshot();
        onChange(txt.value.trim());
        syncPreview();
    });

    const row = document.createElement('div');
    row.style.display = 'flex';
    row.style.gap = '6px';

    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = '.png,.jpg,.jpeg,.webp,.svg';
    fileInput.style.display = 'none';

    const upBtn = document.createElement('button');
    upBtn.type = 'button';
    upBtn.className = 'studio-btn';
    upBtn.style.cssText = 'flex:1;justify-content:center;background:#F3F4F6;color:#111827;border-color:#D1D5DB;';
    upBtn.textContent = 'Subir Imagen';
    upBtn.onclick = () => fileInput.click();

    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.className = 'studio-btn';
    clearBtn.style.cssText = 'background:#FEF2F2;color:#DC2626;border-color:#FECACA;';
    clearBtn.textContent = 'Quitar';
    clearBtn.onclick = () => {
        pushUndoSnapshot();
        txt.value = '';
        onChange('');
        syncPreview();
    };

    fileInput.onchange = async () => {
        if (!fileInput.files.length) return;
        const fd = new FormData();
        fd.append('asset_file', fileInput.files[0]);
        upBtn.textContent = 'Subiendo...';
        try {
            const res = await fetch(BASE_URL + '/admin/themes/customize/' + encodeURIComponent(THEME_SLUG) + '/upload-asset', {
                method: 'POST',
                headers: { 'X-CSRF-Token': CSRF_TOKEN },
                body: fd
            });
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.error || 'Error al subir');
            pushUndoSnapshot();
            txt.value = data.path;
            onChange(data.path);
            syncPreview();
            if (reloadIframeOnUpload) showToast('Imagen subida. Pulsa Guardar si deseas fijarla en el CSS.');
        } catch (err) {
            showToast(err.message);
        } finally {
            upBtn.textContent = 'Subir Imagen';
        }
    };

    row.appendChild(upBtn);
    row.appendChild(clearBtn);
    wrap.appendChild(txt);
    wrap.appendChild(row);
    wrap.appendChild(fileInput);
    return wrap;
}

function renderInspector(focusGlobalPart) {
    const titleEl = document.getElementById('inspector-title');
    const subEl = document.getElementById('inspector-sub');
    const body = document.getElementById('inspector-body');
    body.innerHTML = '';

    // 1. CONFIGURACIÓN GLOBAL DEL TEMA
    if (selectedSectionId === 'global_all') {
        const g = themeConfig.global;
        titleEl.textContent = 'Configuración Global del Tema';
        subEl.textContent = 'Aplica identidad, esquema de colores y tipografía en todo el sitio.';

        // Paletas rápidas
        const palWrap = document.createElement('div');
        palWrap.className = 'ctrl-group';
        palWrap.innerHTML = '<div class="ctrl-label"><span>Esquemas de Color Rápidos</span></div>';
        const palGrid = document.createElement('div');
        palGrid.style.cssText = 'display:grid;grid-template-columns:1fr 1fr;gap:6px;';
        const palettes = [
            { name: 'Shopify Verde', p: '#008060', bg: '#FFFFFF', hbg: '#FFFFFF', htxt: '#111827', txt: '#111827' },
            { name: 'Moda Editorial', p: '#171717', bg: '#FFFFFF', hbg: '#FFFFFF', htxt: '#171717', txt: '#171717' },
            { name: 'Azul Tech', p: '#2563EB', bg: '#F8FAFC', hbg: '#0F172A', htxt: '#FFFFFF', txt: '#0F172A' },
            { name: 'Orgánico Cálido', p: '#15803D', bg: '#FFFBF5', hbg: '#FFFFFF', htxt: '#1C1917', txt: '#1C1917' },
        ];
        palettes.forEach(pal => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'palette-chip';
            btn.innerHTML = `<span class="palette-dot" style="background:${pal.p};"></span><span>${pal.name}</span>`;
            btn.onclick = () => {
                pushUndoSnapshot();
                g.color_primary = pal.p;
                g.color_bg = pal.bg;
                g.color_header_bg = pal.hbg;
                g.color_header_text = pal.htxt;
                g.color_text = pal.txt;
                renderInspector();
                syncPreview();
            };
            palGrid.appendChild(btn);
        });
        palWrap.appendChild(palGrid);
        body.appendChild(palWrap);

        body.appendChild(makeImageUploadCtrl('Logotipo Personalizado', g.logo_url, v => g.logo_url = v, false));
        body.appendChild(makeRangeCtrl('Altura del Logotipo', g.logo_height, 20, 72, 'px', v => g.logo_height = v));
        body.appendChild(makeSelectCtrl('Tipografía Principal', g.font_family, [
            { value: 'Inter', label: 'Inter (Moderna)' },
            { value: 'Poppins', label: 'Poppins (Amigable)' },
            { value: 'Roboto', label: 'Roboto (Corporativa)' },
            { value: 'Georgia', label: 'Georgia (Editorial Serif)' },
            { value: 'JetBrains Mono', label: 'JetBrains Mono (Tech)' },
            { value: 'System', label: 'Sistema Nativo' }
        ], v => g.font_family = v));
        body.appendChild(makeRangeCtrl('Tamaño Base de Texto', g.base_font_size, 12, 19, 'px', v => g.base_font_size = v));
        body.appendChild(makeRangeCtrl('Bordes Redondeados de Tarjetas', g.card_radius, 0, 20, 'px', v => g.card_radius = v));
        body.appendChild(makeColorCtrl('Color Principal / Botones', g.color_primary, v => g.color_primary = v));
        body.appendChild(makeColorCtrl('Fondo General de la Tienda', g.color_bg, v => g.color_bg = v));
        body.appendChild(makeColorCtrl('Fondo Secundario / Superficies', g.color_surface, v => g.color_surface = v));
        body.appendChild(makeColorCtrl('Texto Principal', g.color_text, v => g.color_text = v));
        body.appendChild(makeColorCtrl('Fondo de Cabecera', g.color_header_bg, v => g.color_header_bg = v));
        body.appendChild(makeColorCtrl('Texto de Cabecera', g.color_header_text, v => g.color_header_text = v));
        body.appendChild(makeColorCtrl('Fondo de Pie de Página', g.color_footer_bg, v => g.color_footer_bg = v));
        body.appendChild(makeColorCtrl('Texto de Pie de Página', g.color_footer_text, v => g.color_footer_text = v));
        return;
    }

    // 2. CABECERA / PIE DE PÁGINA
    if (selectedSectionId === 'header') {
        const g = themeConfig.global;
        titleEl.textContent = 'Encabezado y Logotipo';
        subEl.textContent = 'Personaliza la marca y colores de la barra superior.';
        body.appendChild(makeImageUploadCtrl('Logotipo de la Tienda', g.logo_url, v => g.logo_url = v, false));
        body.appendChild(makeRangeCtrl('Altura del Logotipo', g.logo_height, 20, 72, 'px', v => g.logo_height = v));
        body.appendChild(makeColorCtrl('Fondo de Cabecera', g.color_header_bg, v => g.color_header_bg = v));
        body.appendChild(makeColorCtrl('Color de Enlaces y Texto', g.color_header_text, v => g.color_header_text = v));
        return;
    }

    if (selectedSectionId === 'footer') {
        const g = themeConfig.global;
        if (!themeConfig.footer) themeConfig.footer = {};
        const f = themeConfig.footer;
        titleEl.textContent = 'Pie de Página (Footer)';
        subEl.textContent = 'Personaliza marca, columnas de enlaces, boletín, insignias de pago y derechos de autor en vivo.';

        const makeHeading = (text) => {
            const h = document.createElement('div');
            h.style.cssText = 'font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#6B7280;margin:14px 0 8px;padding-top:10px;border-top:1px solid #E5E7EB;';
            h.textContent = text;
            return h;
        };

        body.appendChild(makeSwitchCtrl('Mostrar Pie de Página', f.enabled !== false, v => f.enabled = v));

        body.appendChild(makeHeading('Marca y Descripción'));
        body.appendChild(makeTextCtrl('Título de Marca (vacío = nombre tienda)', f.brand_title || '', v => f.brand_title = v));
        body.appendChild(makeTextareaCtrl('Descripción de la Tienda', f.description || '', v => f.description = v));
        body.appendChild(makeSwitchCtrl('Mostrar Logotipo en Footer', f.show_logo !== false, v => f.show_logo = v));

        body.appendChild(makeHeading('Columna 1 de Enlaces'));
        body.appendChild(makeSwitchCtrl('Mostrar Columna 1', f.col1_enabled !== false, v => f.col1_enabled = v));
        body.appendChild(makeTextCtrl('Título Columna 1', f.col1_title || 'Tienda', v => f.col1_title = v));
        body.appendChild(makeTextareaCtrl('Enlaces Columna 1 (Texto | /ruta)', f.col1_links || '', v => f.col1_links = v));

        body.appendChild(makeHeading('Columna 2 de Enlaces'));
        body.appendChild(makeSwitchCtrl('Mostrar Columna 2', f.col2_enabled !== false, v => f.col2_enabled = v));
        body.appendChild(makeTextCtrl('Título Columna 2', f.col2_title || 'Atención y Ayuda', v => f.col2_title = v));
        body.appendChild(makeTextareaCtrl('Enlaces Columna 2 (Texto | /ruta)', f.col2_links || '', v => f.col2_links = v));

        body.appendChild(makeHeading('Suscripción / Boletín (Newsletter)'));
        body.appendChild(makeSwitchCtrl('Mostrar Formulario Newsletter', f.show_newsletter !== false, v => f.show_newsletter = v));
        body.appendChild(makeTextCtrl('Título Newsletter', f.newsletter_title || '', v => f.newsletter_title = v));
        body.appendChild(makeTextareaCtrl('Subtítulo Newsletter', f.newsletter_subtitle || '', v => f.newsletter_subtitle = v));
        body.appendChild(makeTextCtrl('Placeholder del Campo', f.newsletter_placeholder || '', v => f.newsletter_placeholder = v));
        body.appendChild(makeTextCtrl('Texto Botón Suscripción', f.newsletter_btn_text || 'Suscribirme', v => f.newsletter_btn_text = v));

        body.appendChild(makeHeading('Contacto, Pagos y Utilidades'));
        body.appendChild(makeSwitchCtrl('Mostrar Dirección, Teléfono y Correo', f.show_contact !== false, v => f.show_contact = v));
        body.appendChild(makeSwitchCtrl('Mostrar Redes Sociales', f.show_social !== false, v => f.show_social = v));
        body.appendChild(makeSwitchCtrl('Mostrar Sellos de Métodos de Pago', f.show_payment_badges !== false, v => f.show_payment_badges = v));
        body.appendChild(makeSwitchCtrl('Mostrar Botón "Subir" (Back to top)', f.show_back_to_top !== false, v => f.show_back_to_top = v));

        body.appendChild(makeHeading('Franja Legal / Copyright'));
        body.appendChild(makeTextCtrl('Texto Copyright ({year}, {store})', f.copyright_text || '', v => f.copyright_text = v));
        body.appendChild(makeTextCtrl('Texto Secundario Inferior', f.bottom_right_text || '', v => f.bottom_right_text = v));

        body.appendChild(makeHeading('Colores y Espaciado'));
        body.appendChild(makeColorCtrl('Fondo del Footer', g.color_footer_bg, v => g.color_footer_bg = v));
        body.appendChild(makeColorCtrl('Texto del Footer', g.color_footer_text, v => g.color_footer_text = v));
        body.appendChild(makeColorCtrl('Color Acento (Títulos / Botón)', f.accent_color || g.color_primary, v => f.accent_color = v));
        body.appendChild(makeRangeCtrl('Espaciado Vertical Superior', f.padding_y || 42, 16, 100, 'px', v => f.padding_y = v));
        body.appendChild(makeRangeCtrl('Tamaño de Texto', f.font_size || 13, 11, 16, 'px', v => f.font_size = v));
        return;
    }

    // 3. SECCIONES DE PLANTILLA O BLOQUES MODULARES
    const found = getSectionObj(selectedSectionId);
    if (!found) {
        titleEl.textContent = 'Selecciona un elemento';
        subEl.textContent = 'Haz clic en una sección del árbol o de la vista previa.';
        return;
    }

    const sec = found.obj;
    titleEl.textContent = found.kind === 'custom'
        ? (BLOCK_TYPES[sec.type] ? BLOCK_TYPES[sec.type].label : 'Bloque Personalizado')
        : (SECTION_LABELS[selectedSectionId] || selectedSectionId);
    subEl.textContent = 'Los cambios se reflejan en vivo a la izquierda.';

    body.appendChild(makeSwitchCtrl('Mostrar esta sección', sec.enabled !== false, v => sec.enabled = v));

    if (found.kind === 'builtIn' || found.kind === 'builtin') {
        if ('title' in sec) body.appendChild(makeTextCtrl('Título', sec.title, v => sec.title = v));
        if ('subtitle' in sec) body.appendChild(makeTextareaCtrl('Subtítulo / Descripción', sec.subtitle, v => sec.subtitle = v));
        if ('cta_text' in sec) body.appendChild(makeTextCtrl('Texto del Botón', sec.cta_text, v => sec.cta_text = v));
        if ('cta_link' in sec) body.appendChild(makeTextCtrl('Enlace del Botón', sec.cta_link, v => sec.cta_link = v));
        if ('buy_btn_text' in sec) body.appendChild(makeTextCtrl('Texto Botón de Compra', sec.buy_btn_text, v => sec.buy_btn_text = v));
        if ('checkout_btn_text' in sec) body.appendChild(makeTextCtrl('Texto Botón Pagar', sec.checkout_btn_text, v => sec.checkout_btn_text = v));
        if ('show_coupon_box' in sec) body.appendChild(makeSwitchCtrl('Mostrar caja de cupón', sec.show_coupon_box !== false, v => sec.show_coupon_box = v));
        if ('title_size' in sec) body.appendChild(makeRangeCtrl('Tamaño de Título', sec.title_size, 16, 64, 'px', v => sec.title_size = v));
        if ('subtitle_size' in sec) body.appendChild(makeRangeCtrl('Tamaño de Subtítulo', sec.subtitle_size, 12, 26, 'px', v => sec.subtitle_size = v));
        if ('price_size' in sec) body.appendChild(makeRangeCtrl('Tamaño de Precio', sec.price_size, 16, 40, 'px', v => sec.price_size = v));
        if ('align' in sec) body.appendChild(makeSelectCtrl('Alineación de Texto', sec.align, [
            { value: 'center', label: 'Centrado' },
            { value: 'left', label: 'Izquierda' }
        ], v => sec.align = v));
        if ('columns_desktop' in sec) body.appendChild(makeSelectCtrl('Columnas en Escritorio', sec.columns_desktop, [
            { value: 2, label: '2 columnas' },
            { value: 3, label: '3 columnas' },
            { value: 4, label: '4 columnas' },
            { value: 5, label: '5 columnas' }
        ], v => sec.columns_desktop = parseInt(v, 10)));
        if ('columns_mobile' in sec) body.appendChild(makeSelectCtrl('Columnas en Móvil', sec.columns_mobile, [
            { value: 1, label: '1 columna' },
            { value: 2, label: '2 columnas' }
        ], v => sec.columns_mobile = parseInt(v, 10)));
        if ('max_items' in sec) body.appendChild(makeRangeCtrl('Máximo de Productos', sec.max_items, 2, 16, ' u.', v => sec.max_items = v));
        if (selectedSectionId === 'categories') {
            const curMode = sec.display_mode || 'top_popular';
            body.appendChild(makeSelectCtrl('Modo de Visualización', curMode, [
                { value: 'top_popular', label: 'Las N más solicitadas (por ventas/catálogo)' },
                { value: 'only_parents', label: 'Solo categorías principales' },
                { value: 'custom', label: 'Seleccionar categorías manualmente' }
            ], v => {
                sec.display_mode = v;
                if (v === 'custom' && (!Array.isArray(sec.selected_ids) || sec.selected_ids.length === 0)) {
                    sec.selected_ids = ALL_CATEGORIES.slice(0, sec.limit || 4).map(c => Number(c.id));
                }
                renderInspector();
            }));
            if (curMode !== 'custom') {
                body.appendChild(makeRangeCtrl('Cantidad máxima a mostrar', sec.limit || 4, 2, 12, ' cat.', v => sec.limit = v));
            } else {
                const pickWrap = document.createElement('div');
                pickWrap.className = 'ctrl-group';
                pickWrap.innerHTML = '<label class="ctrl-label"><span>Categorías visibles en portada</span></label>';
                const listBox = document.createElement('div');
                listBox.style.cssText = 'max-height:210px;overflow-y:auto;border:1px solid #D1D5DB;border-radius:6px;background:#FFFFFF;padding:6px;display:flex;flex-direction:column;gap:4px;';
                const selSet = new Set(Array.isArray(sec.selected_ids) ? sec.selected_ids.map(Number) : []);
                if (!ALL_CATEGORIES.length) {
                    listBox.innerHTML = '<span style="font-size:12px;color:#6B7280;padding:6px;">No hay categorías registradas aún.</span>';
                } else {
                    ALL_CATEGORIES.forEach(cat => {
                        const cid = Number(cat.id);
                        const lbl = document.createElement('label');
                        lbl.style.cssText = 'display:flex;align-items:center;gap:8px;font-size:12.5px;padding:4px 6px;border-radius:4px;cursor:pointer;color:#111827;';
                        const cb = document.createElement('input');
                        cb.type = 'checkbox';
                        cb.checked = selSet.has(cid);
                        cb.style.accentColor = '#008060';
                        cb.addEventListener('change', () => {
                            pushUndoSnapshot();
                            const cur = new Set(Array.isArray(sec.selected_ids) ? sec.selected_ids.map(Number) : []);
                            if (cb.checked) cur.add(cid); else cur.delete(cid);
                            sec.selected_ids = Array.from(cur);
                            syncPreview();
                        });
                        const span = document.createElement('span');
                        span.textContent = cat.parent_id ? ('  -- ' + cat.name) : cat.name;
                        lbl.appendChild(cb);
                        lbl.appendChild(span);
                        listBox.appendChild(lbl);
                    });
                }
                pickWrap.appendChild(listBox);
                const hint = document.createElement('div');
                hint.style.cssText = 'font-size:11px;color:#6B7280;margin-top:4px;';
                hint.textContent = 'Si dejas todas desmarcadas, se mostrarán automáticamente las más populares.';
                pickWrap.appendChild(hint);
                body.appendChild(pickWrap);
            }
        }
        if ('bg_color' in sec && sec.bg_color !== 'transparent') body.appendChild(makeColorCtrl('Color de Fondo', sec.bg_color, v => sec.bg_color = v));
        if ('text_color' in sec) body.appendChild(makeColorCtrl('Color de Texto', sec.text_color, v => sec.text_color = v));
        if ('bg_image' in sec) body.appendChild(makeImageUploadCtrl('Imagen de Fondo del Hero', sec.bg_image, v => sec.bg_image = v, true));
        if ('padding_y' in sec) body.appendChild(makeRangeCtrl('Espaciado Vertical', sec.padding_y, 12, 120, 'px', v => sec.padding_y = v));
    } else {
        // Bloques personalizados modulares
        if ('tag' in sec) body.appendChild(makeTextCtrl('Etiqueta Superior', sec.tag, v => sec.tag = v));
        if ('title' in sec) body.appendChild(makeTextCtrl('Título', sec.title, v => sec.title = v));
        if ('body' in sec) body.appendChild(makeTextareaCtrl('Contenido / Texto', sec.body, v => sec.body = v));
        if ('placeholder' in sec) body.appendChild(makeTextCtrl('Placeholder del Campo', sec.placeholder, v => sec.placeholder = v));
        if ('cta_text' in sec) body.appendChild(makeTextCtrl('Texto del Botón', sec.cta_text, v => sec.cta_text = v));
        if ('cta_link' in sec) body.appendChild(makeTextCtrl('Enlace del Botón', sec.cta_link, v => sec.cta_link = v));
        if ('media_url' in sec) body.appendChild(makeImageUploadCtrl('Imagen del Bloque', sec.media_url, v => sec.media_url = v, true));
        if ('quote_1' in sec) {
            for (let i = 1; i <= 3; i++) {
                body.appendChild(makeTextareaCtrl('Testimonio ' + i, sec['quote_' + i], v => sec['quote_' + i] = v));
                body.appendChild(makeTextCtrl('Autor ' + i, sec['author_' + i], v => sec['author_' + i] = v));
            }
        }
        if ('address' in sec) body.appendChild(makeTextCtrl('Dirección Física', sec.address, v => sec.address = v));
        if ('hours' in sec) body.appendChild(makeTextCtrl('Horario de Atención', sec.hours, v => sec.hours = v));
        if ('phone' in sec) body.appendChild(makeTextCtrl('Teléfono', sec.phone, v => sec.phone = v));
        if ('bg_color' in sec) body.appendChild(makeColorCtrl('Color de Fondo', sec.bg_color, v => sec.bg_color = v));
        if ('text_color' in sec) body.appendChild(makeColorCtrl('Color de Texto', sec.text_color, v => sec.text_color = v));
        if ('padding_y' in sec) body.appendChild(makeRangeCtrl('Espaciado Vertical', sec.padding_y, 12, 90, 'px', v => sec.padding_y = v));
    }
}

async function saveThemeConfig() {
    const btn = document.getElementById('btn-save');
    btn.disabled = true;
    try {
        const res = await fetch(BASE_URL + '/admin/themes/customize/' + encodeURIComponent(THEME_SLUG) + '/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
            body: JSON.stringify(themeConfig)
        });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.error || 'Error al guardar');
        themeConfig = data.config;
        hasUnsaved = false;
        document.getElementById('unsaved-dot').style.display = 'none';
        document.getElementById('preview-iframe').contentWindow.location.reload();
        showToast('Cambios guardados y publicados en la plantilla.');
    } catch (err) {
        showToast('Error: ' + err.message);
    } finally {
        btn.disabled = false;
    }
}

async function resetThemeConfig() {
    if (!confirm('¿Restablecer los colores, textos y secciones de esta plantilla a su diseño original?')) return;
    try {
        const res = await fetch(BASE_URL + '/admin/themes/customize/' + encodeURIComponent(THEME_SLUG) + '/reset', {
            method: 'POST',
            headers: { 'X-CSRF-Token': CSRF_TOKEN }
        });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.error || 'No se pudo restablecer');
        themeConfig = data.config;
        undoStack = [];
        redoStack = [];
        hasUnsaved = false;
        document.getElementById('unsaved-dot').style.display = 'none';
        renderTree();
        renderInspector();
        document.getElementById('preview-iframe').contentWindow.location.reload();
        updateHistoryButtons();
        showToast('Plantilla restablecida a valores originales.');
    } catch (err) {
        showToast('Error: ' + err.message);
    }
}

window.addEventListener('beforeunload', function(e) {
    if (hasUnsaved) {
        e.preventDefault();
        e.returnValue = '';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    renderTree();
    renderInspector();
    updateHistoryButtons();
});
</script>
</body>
</html>
