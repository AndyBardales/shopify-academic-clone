<?php
/**
 * Plugin Name: Wishlist - Lista de Deseos
 * Description: Añade botón para guardar favoritos y compartir enlace público de deseos
 */

use ShopCMS\Core\PluginManager;
use ShopCMS\Core\Settings;
use ShopCMS\Core\ThemeCustomizer;

PluginManager::addAction('product_page_buttons', function ($product) {
    $baseUrl = defined('APP_URL') ? APP_URL : '';
    $sym = Settings::get('store_currency_symbol', '$');
    $prodId = (int)$product['id'];
    $img = !empty($product['featured_image']) ? ($baseUrl . '/' . ltrim($product['featured_image'], '/')) : '';
    $itemJson = htmlspecialchars(json_encode([
        'id' => $prodId,
        'name' => $product['name'],
        'slug' => $product['slug'],
        'price' => (float)$product['price'],
        'currency' => $sym,
        'image' => $img,
        'url' => $baseUrl . '/product/' . $product['slug'],
    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

    echo '
    <div style="margin-top: 12px; margin-bottom: 10px;">
        <button type="button" onclick="toggleWishlist(' . $itemJson . ')" id="wishlist_btn_' . $prodId . '" class="btn-store btn-store-secondary" style="width: 100%; padding: 10px; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <svg id="wishlist_icon_' . $prodId . '" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            <span id="wishlist_text_' . $prodId . '">Guardar en Lista de Deseos</span>
        </button>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof syncWishlistButtonState === "function") {
            syncWishlistButtonState(' . $prodId . ');
        }
    });
    </script>';
});

PluginManager::addAction('theme_footer', function () {
    if (ThemeCustomizer::isEditorPreview()) {
        return;
    }
    $baseUrl = defined('APP_URL') ? APP_URL : '';
    $waBottom = (Settings::getBool('whatsapp_widget_enabled', false) && trim(Settings::get('store_phone', '')) !== '') ? '72px' : '20px';

    echo '
    <!-- BOTÓN FLOTANTE DE WISHLIST -->
    <button type="button" id="scms-wishlist-fab" onclick="openWishlistModal(false)" style="display: none; position: fixed; bottom: ' . $waBottom . '; right: 20px; z-index: 985; background: #FFFFFF; color: #111827; border: 1px solid #E5E7EB; border-radius: 999px; padding: 10px 15px; align-items: center; gap: 7px; font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 6px 18px rgba(0,0,0,0.12);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="#DC2626" stroke="#DC2626" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        <span>Favoritos (<span id="scms-wishlist-count">0</span>)</span>
    </button>

    <!-- MODAL DE LISTA DE DESEOS Y COMPARTIR -->
    <div id="scms-wishlist-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 10020; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #FFFFFF; max-width: 620px; width: 100%; border-radius: 14px; padding: 24px; box-shadow: 0 20px 44px rgba(0,0,0,0.25); max-height: 88vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #E5E7EB; padding-bottom: 12px;">
                <div>
                    <h3 id="scms-wishlist-modal-title" style="font-size: 18px; font-weight: 700; margin: 0; color: #111827;">Mi Lista de Deseos</h3>
                    <div id="scms-wishlist-modal-sub" style="font-size: 12px; color: #6B7280; margin-top: 2px;">Guarda tus productos favoritos o comparte tu lista mediante un enlace.</div>
                </div>
                <button type="button" onclick="closeWishlistModal()" style="border: none; background: transparent; font-size: 22px; cursor: pointer; color: #6B7280;">&times;</button>
            </div>

            <div id="scms-wishlist-items"></div>

            <div id="scms-wishlist-share-bar" style="margin-top: 18px; padding-top: 14px; border-top: 1px solid #E5E7EB; display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <button type="button" onclick="copyWishlistShareLink()" style="flex: 1; background: #008060; color: #FFFFFF; border: none; border-radius: 7px; padding: 9px 14px; font-size: 12.5px; font-weight: 700; cursor: pointer;">
                    Copiar enlace para compartir
                </button>
                <a id="scms-wishlist-wa-share" href="#" target="_blank" rel="noopener" style="background: #16A34A; color: #FFFFFF; border-radius: 7px; padding: 9px 14px; font-size: 12.5px; font-weight: 700; text-decoration: none;">
                    Compartir por WhatsApp
                </a>
                <button type="button" onclick="clearWishlistAll()" style="background: #F3F4F6; color: #374151; border: 1px solid #D1D5DB; border-radius: 7px; padding: 9px 12px; font-size: 12.5px; font-weight: 600; cursor: pointer;">
                    Vaciar
                </button>
            </div>
            <div id="scms-wishlist-feedback" style="display: none; margin-top: 10px; font-size: 12px; color: #047857; font-weight: 600; background: #ECFDF5; padding: 8px 10px; border-radius: 6px;"></div>
        </div>
    </div>

    <script>
    (function() {
        var BASE = ' . json_encode($baseUrl) . ';

        function getIds() {
            try { return JSON.parse(localStorage.getItem("shopcms_wishlist") || "[]").map(Number).filter(Boolean); }
            catch (e) { return []; }
        }
        function getItemsMap() {
            try { return JSON.parse(localStorage.getItem("shopcms_wishlist_items") || "{}"); }
            catch (e) { return {}; }
        }
        function saveStore(ids, map) {
            localStorage.setItem("shopcms_wishlist", JSON.stringify(ids));
            localStorage.setItem("shopcms_wishlist_items", JSON.stringify(map));
        }

        window.syncWishlistButtonState = function(prodId) {
            var ids = getIds();
            var btnText = document.getElementById("wishlist_text_" + prodId);
            var btnIcon = document.getElementById("wishlist_icon_" + prodId);
            if (!btnText || !btnIcon) return;
            if (ids.indexOf(Number(prodId)) !== -1) {
                btnText.innerText = "Guardado en tus Favoritos";
                btnIcon.setAttribute("fill", "#D72C0D");
                btnIcon.setAttribute("stroke", "#D72C0D");
            } else {
                btnText.innerText = "Guardar en Lista de Deseos";
                btnIcon.setAttribute("fill", "none");
                btnIcon.setAttribute("stroke", "currentColor");
            }
        };

        function updateFab() {
            var ids = getIds();
            var fab = document.getElementById("scms-wishlist-fab");
            var countEl = document.getElementById("scms-wishlist-count");
            if (countEl) countEl.textContent = ids.length;
            if (fab) fab.style.display = ids.length > 0 ? "inline-flex" : "none";
        }

        window.toggleWishlist = function(itemOrId) {
            var isObj = (typeof itemOrId === "object" && itemOrId !== null);
            var prodId = Number(isObj ? itemOrId.id : itemOrId);
            var ids = getIds();
            var map = getItemsMap();
            var idx = ids.indexOf(prodId);

            if (idx === -1) {
                ids.push(prodId);
                if (isObj) map[prodId] = itemOrId;
            } else {
                ids.splice(idx, 1);
                delete map[prodId];
            }
            saveStore(ids, map);
            window.syncWishlistButtonState(prodId);
            updateFab();
        };

        function renderModalList(products, isSharedMode) {
            var box = document.getElementById("scms-wishlist-items");
            var shareBar = document.getElementById("scms-wishlist-share-bar");
            if (!box) return;
            if (!products.length) {
                box.innerHTML = "<p style=\'text-align:center;color:#6B7280;padding:24px 10px;font-size:13.5px;\'>Aún no tienes productos guardados en tu lista de deseos.</p>";
                if (shareBar) shareBar.style.display = "none";
                return;
            }
            if (shareBar) shareBar.style.display = isSharedMode ? "none" : "flex";
            var ids = products.map(function(p) { return p.id; });
            var shareUrl = window.location.origin + BASE + "/catalog?wishlist=" + ids.join(",");
            var waEl = document.getElementById("scms-wishlist-wa-share");
            if (waEl) {
                waEl.href = "https://wa.me/?text=" + encodeURIComponent("Mira mi lista de deseos: " + shareUrl);
            }

            box.innerHTML = products.map(function(p) {
                var imgHtml = p.image
                    ? "<img src=\'" + p.image + "\' style=\'width:54px;height:54px;object-fit:cover;border-radius:8px;border:1px solid #E5E7EB;\'>"
                    : "<div style=\'width:54px;height:54px;border-radius:8px;background:#F3F4F6;\'></div>";
                var removeBtn = isSharedMode
                    ? "<button type=\'button\' onclick=\'saveSharedItem(" + JSON.stringify(p).replace(/\x27/g, "&#39;") + ")\' style=\'background:#F3F4F6;border:1px solid #D1D5DB;border-radius:6px;padding:6px 10px;font-size:12px;font-weight:600;cursor:pointer;\'>Guardar</button>"
                    : "<button type=\'button\' onclick=\'removeWishlistItem(" + p.id + ")\' style=\'background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;border-radius:6px;padding:6px 10px;font-size:12px;font-weight:600;cursor:pointer;\'>Quitar</button>";

                return "<div style=\'display:flex;align-items:center;gap:12px;padding:10px;border:1px solid #E5E7EB;border-radius:10px;margin-bottom:10px;\'>"
                    + imgHtml
                    + "<div style=\'flex:1;\'>"
                    + "<div style=\'font-weight:700;font-size:14px;color:#111827;\'>" + String(p.name).replace(/</g, "&lt;") + "</div>"
                    + "<div style=\'font-size:13px;font-weight:700;color:#008060;\'>" + (p.currency || "$") + Number(p.price || 0).toFixed(2) + "</div>"
                    + "</div>"
                    + "<a href=\'" + p.url + "\' style=\'background:#111827;color:#FFF;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;\'>Ver</a>"
                    + removeBtn
                    + "</div>";
            }).join("");
        }

        window.saveSharedItem = function(p) {
            var ids = getIds();
            var map = getItemsMap();
            if (ids.indexOf(Number(p.id)) === -1) ids.push(Number(p.id));
            map[p.id] = p;
            saveStore(ids, map);
            updateFab();
            var fb = document.getElementById("scms-wishlist-feedback");
            if (fb) {
                fb.textContent = "Producto guardado en tu propia lista de deseos.";
                fb.style.display = "block";
            }
        };

        window.removeWishlistItem = function(id) {
            window.toggleWishlist(id);
            window.openWishlistModal(false);
        };

        window.openWishlistModal = async function(sharedIds) {
            var modal = document.getElementById("scms-wishlist-modal");
            var title = document.getElementById("scms-wishlist-modal-title");
            var sub = document.getElementById("scms-wishlist-modal-sub");
            var fb = document.getElementById("scms-wishlist-feedback");
            if (fb) fb.style.display = "none";

            if (Array.isArray(sharedIds) && sharedIds.length) {
                if (title) title.textContent = "Lista de Deseos Compartida";
                if (sub) sub.textContent = "Alguien compartió estos productos favoritos contigo.";
                try {
                    var res = await fetch(BASE + "/plugin/wishlist/products?ids=" + sharedIds.join(","));
                    var data = await res.json();
                    renderModalList((data && data.products) ? data.products : [], true);
                } catch (e) {
                    renderModalList([], true);
                }
            } else {
                if (title) title.textContent = "Mi Lista de Deseos";
                if (sub) sub.textContent = "Guarda tus productos favoritos o comparte tu lista mediante un enlace.";
                var ids = getIds();
                var map = getItemsMap();
                var missing = ids.filter(function(id) { return !map[id]; });
                if (missing.length) {
                    try {
                        var res2 = await fetch(BASE + "/plugin/wishlist/products?ids=" + ids.join(","));
                        var data2 = await res2.json();
                        if (data2 && Array.isArray(data2.products)) {
                            data2.products.forEach(function(p) { map[p.id] = p; });
                            saveStore(ids, map);
                        }
                    } catch (e) {}
                }
                var list = ids.map(function(id) { return map[id]; }).filter(Boolean);
                renderModalList(list, false);
            }
            if (modal) modal.style.display = "flex";
        };

        window.closeWishlistModal = function() {
            var modal = document.getElementById("scms-wishlist-modal");
            if (modal) modal.style.display = "none";
        };

        window.copyWishlistShareLink = function() {
            var ids = getIds();
            if (!ids.length) return;
            var url = window.location.origin + BASE + "/catalog?wishlist=" + ids.join(",");
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url);
            }
            var fb = document.getElementById("scms-wishlist-feedback");
            if (fb) {
                fb.textContent = "Enlace copiado al portapapeles: " + url;
                fb.style.display = "block";
            }
        };

        window.clearWishlistAll = function() {
            saveStore([], {});
            updateFab();
            renderModalList([], false);
        };

        document.addEventListener("DOMContentLoaded", function() {
            updateFab();
            var params = new URLSearchParams(window.location.search);
            var shared = params.get("wishlist");
            if (shared) {
                var parsed = shared.split(",").map(Number).filter(Boolean);
                if (parsed.length) window.openWishlistModal(parsed);
            }
        });
    })();
    </script>';
});
