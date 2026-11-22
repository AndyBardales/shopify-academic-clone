<?php
/**
 * Plugin Name: Product Comparator
 * Description: Comparador interactivo de hasta 4 productos simultáneos
 */

use ShopCMS\Core\PluginManager;
use ShopCMS\Core\Settings;
use ShopCMS\Core\ThemeCustomizer;

PluginManager::addAction('product_page_buttons', function ($product) {
    $baseUrl = defined('APP_URL') ? APP_URL : '';
    $sym = Settings::get('store_currency_symbol', '$');
    $img = !empty($product['featured_image']) ? ($baseUrl . '/' . ltrim($product['featured_image'], '/')) : '';
    $prodJson = htmlspecialchars(json_encode([
        'id' => (int)$product['id'],
        'name' => $product['name'],
        'price' => (float)$product['price'],
        'currency' => $sym,
        'category' => $product['category_name'] ?? 'General',
        'sku' => $product['sku'] ?: 'N/A',
        'stock' => (int)$product['quantity'],
        'short_desc' => $product['short_description'] ?? '',
        'image' => $img,
        'url' => $baseUrl . '/product/' . $product['slug']
    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

    echo '
    <div style="margin-top: 8px;">
        <button type="button" onclick="addToComparator(' . $prodJson . ')" class="btn-store btn-store-secondary" style="width: 100%; padding: 9px; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 8px; border-color: #D1D5DB;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <span>Comparar con otros productos (hasta 4)</span>
        </button>
        <div id="comparator_inline_status" style="display: none; margin-top: 6px; padding: 6px 10px; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 6px; font-size: 12px; color: #166534; justify-content: space-between; align-items: center;">
            <span id="comparator_inline_text">0/4 seleccionados</span>
            <span style="display: inline-flex; gap: 10px; align-items: center;">
                <button type="button" onclick="openComparatorModal()" style="background: none; border: none; color: #008060; font-weight: 700; cursor: pointer; text-decoration: underline; font-size: 12px;">Ver tabla</button>
                <button type="button" onclick="clearComparator()" style="background: none; border: none; color: #6B7280; cursor: pointer; font-size: 11.5px;">Vaciar</button>
            </span>
        </div>
    </div>';
});

PluginManager::addAction('theme_footer', function () {
    if (ThemeCustomizer::isEditorPreview()) {
        return;
    }

    echo '
    <!-- Pill discreta de respaldo (solo visible si el tema no tiene .header-actions) -->
    <div id="comparator_bar" style="display: none; position: fixed; top: 82px; right: 20px; z-index: 9980; background: #FFFFFF; color: #111827; padding: 7px 12px; border-radius: 999px; border: 1px solid #D1D5DB; box-shadow: 0 4px 14px rgba(0,0,0,0.09); align-items: center; gap: 10px;">
        <button type="button" onclick="openComparatorModal()" style="background: none; border: none; color: #111827; font-size: 12.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2.2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            <span id="comparator_count">Comparar (0/4)</span>
        </button>
        <button type="button" onclick="clearComparator()" title="Vaciar comparador" style="background: none; border: none; color: #9CA3AF; cursor: pointer; font-size: 14px; line-height: 1;">&times;</button>
    </div>

    <div id="comparator_toast" style="display: none; position: fixed; top: 82px; left: 50%; transform: translateX(-50%); z-index: 10010; background: #111827; color: #FFFFFF; padding: 9px 16px; border-radius: 8px; font-size: 12.5px; font-weight: 600; box-shadow: 0 6px 16px rgba(0,0,0,0.2);"></div>

    <!-- Modal del comparador -->
    <div id="comparator_modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 10030; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #FFFFFF; max-width: 900px; width: 100%; border-radius: 14px; padding: 26px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid #E5E7EB; padding-bottom: 12px;">
                <div>
                    <h3 style="font-size: 18px; font-weight: 700; margin: 0; color: #111827;">Comparador de Productos (Máx. 4)</h3>
                    <div style="font-size: 12px; color: #6B7280; margin-top: 2px;">Compara características, precio y disponibilidad en tiempo real.</div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" onclick="clearComparator()" style="background: #F3F4F6; color: #374151; border: 1px solid #D1D5DB; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">Vaciar todo</button>
                    <button type="button" onclick="closeComparatorModal()" style="border: none; background: transparent; font-size: 22px; cursor: pointer; color: #6B7280;">&times;</button>
                </div>
            </div>
            <div id="comparator_content" style="overflow-x: auto;"></div>
        </div>
    </div>

    <script>
    (function() {
        function esc(s) {
            return String(s || "").replace(/[&<>"\x27]/g, function(m) {
                return {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","\x27":"&#39;"}[m];
            });
        }
        function showCompToast(msg) {
            var el = document.getElementById("comparator_toast");
            if (!el) return;
            el.textContent = msg;
            el.style.display = "block";
            clearTimeout(el._t);
            el._t = setTimeout(function() { el.style.display = "none"; }, 2600);
        }
        function ensureHeaderCompareBtn() {
            var actions = document.querySelector(".site-header .header-actions");
            if (!actions) return null;
            var btn = document.getElementById("scms-header-compare-btn");
            if (!btn) {
                btn = document.createElement("button");
                btn.id = "scms-header-compare-btn";
                btn.type = "button";
                btn.onclick = window.openComparatorModal;
                btn.style.cssText = "display:none;align-items:center;gap:6px;background:#F3F4F6;border:1px solid #E5E7EB;color:#111827;border-radius:999px;padding:6px 12px;font-size:12.5px;font-weight:600;cursor:pointer;";
                btn.innerHTML = "<svg width=\'14\' height=\'14\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'#008060\' stroke-width=\'2.2\'><line x1=\'18\' y1=\'20\' x2=\'18\' y2=\'10\'/><line x1=\'12\' y1=\'20\' x2=\'12\' y2=\'4\'/><line x1=\'6\' y1=\'20\' x2=\'6\' y2=\'14\'/></svg><span>Comparar</span><span id=\'scms-header-compare-badge\' style=\'background:#008060;color:#FFF;border-radius:999px;padding:1px 7px;font-size:11px;font-weight:700;\'>0/4</span>";
                var cartBtn = actions.querySelector(".cart-button");
                if (cartBtn) {
                    actions.insertBefore(btn, cartBtn);
                } else {
                    actions.appendChild(btn);
                }
            }
            return btn;
        }
        window.getComparatorList = function() {
            try { return JSON.parse(localStorage.getItem("shopcms_comparator") || "[]"); }
            catch (e) { return []; }
        };
        window.addToComparator = function(product) {
            var list = getComparatorList();
            if (list.some(function(p) { return p.id === product.id; })) {
                showCompToast("Este producto ya está en el comparador.");
                openComparatorModal();
                return;
            }
            if (list.length >= 4) {
                showCompToast("Máximo 4 productos en comparación simultánea.");
                openComparatorModal();
                return;
            }
            list.push(product);
            localStorage.setItem("shopcms_comparator", JSON.stringify(list));
            updateComparatorUI();
            showCompToast("Agregado al comparador (" + list.length + "/4).");
        };
        window.removeFromComparator = function(id) {
            var list = getComparatorList().filter(function(p) { return Number(p.id) !== Number(id); });
            localStorage.setItem("shopcms_comparator", JSON.stringify(list));
            updateComparatorUI();
            openComparatorModal();
        };
        window.updateComparatorUI = function() {
            var list = getComparatorList();
            var headerBtn = ensureHeaderCompareBtn();
            var bar = document.getElementById("comparator_bar");
            var count = document.getElementById("comparator_count");
            var inlineStatus = document.getElementById("comparator_inline_status");
            var inlineText = document.getElementById("comparator_inline_text");

            if (inlineStatus && inlineText) {
                inlineStatus.style.display = list.length > 0 ? "flex" : "none";
                inlineText.textContent = list.length + "/4 producto(s) listos para comparar";
            }

            if (headerBtn) {
                var badge = document.getElementById("scms-header-compare-badge");
                if (badge) badge.textContent = list.length + "/4";
                headerBtn.style.display = list.length > 0 ? "inline-flex" : "none";
                if (bar) bar.style.display = "none";
            } else if (bar && count) {
                bar.style.display = list.length > 0 ? "flex" : "none";
                count.innerText = "Comparar (" + list.length + "/4)";
            }
        };
        window.openComparatorModal = function() {
            var list = getComparatorList();
            var modal = document.getElementById("comparator_modal");
            var content = document.getElementById("comparator_content");
            if (!modal || !content) return;

            if (list.length === 0) {
                content.innerHTML = "<p style=\'text-align:center;color:#6B7280;padding:24px;\'>No hay productos seleccionados para comparar.</p>";
            } else {
                var html = "<table style=\'width:100%;border-collapse:collapse;text-align:left;font-size:13.5px;\'>";
                html += "<thead><tr style=\'background:#F9FAFB;border-bottom:2px solid #E5E7EB;\'><th style=\'padding:10px;min-width:120px;\'>Atributo</th>";
                list.forEach(function(p) {
                    html += "<th style=\'padding:10px;min-width:155px;vertical-align:top;\'>"
                        + (p.image ? "<img src=\'" + esc(p.image) + "\' style=\'width:64px;height:64px;object-fit:cover;border-radius:8px;display:block;margin-bottom:6px;border:1px solid #E5E7EB;\'>" : "")
                        + "<div style=\'font-weight:700;color:#111827;\'>" + esc(p.name) + "</div>"
                        + "</th>";
                });
                html += "</tr></thead><tbody>";

                html += "<tr style=\'border-bottom:1px solid #E5E7EB;\'><td style=\'padding:10px;font-weight:600;color:#4B5563;\'>Categoría</td>";
                list.forEach(function(p) { html += "<td style=\'padding:10px;\'>" + esc(p.category) + "</td>"; });
                html += "</tr>";

                html += "<tr style=\'border-bottom:1px solid #E5E7EB;\'><td style=\'padding:10px;font-weight:600;color:#4B5563;\'>SKU</td>";
                list.forEach(function(p) { html += "<td style=\'padding:10px;\'><code>" + esc(p.sku || "N/A") + "</code></td>"; });
                html += "</tr>";

                html += "<tr style=\'border-bottom:1px solid #E5E7EB;\'><td style=\'padding:10px;font-weight:600;color:#4B5563;\'>Precio</td>";
                list.forEach(function(p) {
                    html += "<td style=\'padding:10px;font-weight:700;color:#008060;\'>" + esc(p.currency || "$") + Number(p.price || 0).toFixed(2) + "</td>";
                });
                html += "</tr>";

                html += "<tr style=\'border-bottom:1px solid #E5E7EB;\'><td style=\'padding:10px;font-weight:600;color:#4B5563;\'>Disponibilidad</td>";
                list.forEach(function(p) {
                    var badge = Number(p.stock) > 0
                        ? "<span style=\'color:#059669;font-weight:600;\'>En stock (" + Number(p.stock) + ")</span>"
                        : "<span style=\'color:#DC2626;font-weight:600;\'>Agotado</span>";
                    html += "<td style=\'padding:10px;\'>" + badge + "</td>";
                });
                html += "</tr>";

                html += "<tr style=\'border-bottom:1px solid #E5E7EB;\'><td style=\'padding:10px;font-weight:600;color:#4B5563;\'>Resumen</td>";
                list.forEach(function(p) {
                    html += "<td style=\'padding:10px;font-size:12px;color:#4B5563;\'>" + esc(p.short_desc || "—") + "</td>";
                });
                html += "</tr>";

                html += "<tr><td style=\'padding:10px;font-weight:600;color:#4B5563;\'>Acciones</td>";
                list.forEach(function(p) {
                    html += "<td style=\'padding:10px;\'><div style=\'display:flex;gap:6px;flex-wrap:wrap;\'>"
                        + "<a href=\'" + esc(p.url) + "\' style=\'background:#008060;color:#FFF;padding:6px 10px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;\'>Ver ficha</a>"
                        + "<button type=\'button\' onclick=\'removeFromComparator(" + Number(p.id) + ")\' style=\'background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;padding:5px 9px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;\'>Quitar</button>"
                        + "</div></td>";
                });
                html += "</tr></tbody></table>";
                content.innerHTML = html;
            }
            modal.style.display = "flex";
        };
        window.closeComparatorModal = function() {
            var modal = document.getElementById("comparator_modal");
            if (modal) modal.style.display = "none";
        };
        window.clearComparator = function() {
            localStorage.removeItem("shopcms_comparator");
            updateComparatorUI();
            closeComparatorModal();
        };
        document.addEventListener("DOMContentLoaded", updateComparatorUI);
    })();
    </script>';
});
