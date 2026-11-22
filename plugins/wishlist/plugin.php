<?php
/**
 * Plugin Name: Wishlist - Lista de Deseos
 * Description: Lista de favoritos aislada por cuenta de cliente con guardado en base de datos
 */

use ShopCMS\Core\Auth;
use ShopCMS\Core\Database;
use ShopCMS\Core\PluginManager;
use ShopCMS\Core\Settings;
use ShopCMS\Core\ThemeCustomizer;

if (!function_exists('scms_wishlist_customer_data')) {
    function scms_wishlist_customer_ids(int $customerId): array {
        if ($customerId <= 0) return [];
        try {
            $raw = Settings::getRaw('customer_wishlist_' . $customerId, '[]');
            $decoded = json_decode($raw, true);
            return is_array($decoded)
                ? array_values(array_unique(array_filter(array_map('intval', $decoded), fn($n) => $n > 0)))
                : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    function scms_wishlist_customer_products(int $customerId): array {
        $ids = scms_wishlist_customer_ids($customerId);
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::fetchAll(
            "SELECT p.id, p.name, p.slug, p.price, p.featured_image, p.quantity
             FROM " . Database::table('products') . " p
             WHERE p.is_active = 1 AND p.id IN ({$placeholders})",
            $ids
        );
        $byId = [];
        $baseUrl = defined('APP_URL') ? APP_URL : '';
        $sym = Settings::get('store_currency_symbol', '$');
        foreach ($rows as $r) {
            $byId[(int)$r['id']] = [
                'id' => (int)$r['id'],
                'name' => $r['name'],
                'slug' => $r['slug'],
                'price' => (float)$r['price'],
                'currency' => $sym,
                'image' => !empty($r['featured_image']) ? ($baseUrl . '/' . ltrim($r['featured_image'], '/')) : '',
                'url' => $baseUrl . '/product/' . $r['slug'],
            ];
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) $ordered[] = $byId[$id];
        }
        return $ordered;
    }
}

PluginManager::addAction('product_page_buttons', function ($product) {
    $prodId = (int)$product['id'];
    $customerId = Auth::customerId();
    $savedIds = $customerId > 0 ? scms_wishlist_customer_ids($customerId) : [];
    $isSaved = in_array($prodId, $savedIds, true);

    echo '
    <div style="margin-top: 12px; margin-bottom: 10px;">
        <button type="button" onclick="toggleWishlist(' . $prodId . ')" id="wishlist_btn_' . $prodId . '" class="btn-store btn-store-secondary" style="width: 100%; padding: 10px; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <svg id="wishlist_icon_' . $prodId . '" width="16" height="16" viewBox="0 0 24 24" fill="' . ($isSaved ? '#D72C0D' : 'none') . '" stroke="' . ($isSaved ? '#D72C0D' : 'currentColor') . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            <span id="wishlist_text_' . $prodId . '">' . ($isSaved ? 'Guardado en tus Favoritos' : 'Guardar en Lista de Deseos') . '</span>
        </button>
    </div>';
});

PluginManager::addAction('theme_footer', function () {
    if (ThemeCustomizer::isEditorPreview()) {
        return;
    }
    $baseUrl = defined('APP_URL') ? APP_URL : '';
    $customerId = Auth::customerId();
    $initialItems = $customerId > 0 ? scms_wishlist_customer_products($customerId) : [];
    $waBottom = (Settings::getBool('whatsapp_widget_enabled', false) && trim(Settings::get('store_phone', '')) !== '') ? '72px' : '20px';

    if ($customerId <= 0) {
        // Usuario no autenticado: limpiar posibles restos heredados y exigir inicio de sesión al interactuar
        echo '
        <script>
        (function() {
            try {
                localStorage.removeItem("shopcms_wishlist");
                localStorage.removeItem("shopcms_wishlist_items");
            } catch (e) {}
            var BASE = ' . json_encode($baseUrl) . ';
            window.toggleWishlist = function() {
                var target = window.location.pathname;
                if (BASE && target.indexOf(BASE) === 0) target = target.slice(BASE.length) || "/";
                window.location.href = BASE + "/login?auth_msg=wishlist&redirect=" + encodeURIComponent(target);
            };
        })();
        </script>';
        return;
    }

    echo '
    <!-- BOTÓN FLOTANTE DE WISHLIST (SOLO USUARIO AUTENTICADO) -->
    <button type="button" id="scms-wishlist-fab" onclick="openWishlistModal(false)" style="display: ' . (count($initialItems) > 0 ? 'inline-flex' : 'none') . '; position: fixed; bottom: ' . $waBottom . '; right: 20px; z-index: 985; background: #FFFFFF; color: #111827; border: 1px solid #E5E7EB; border-radius: 999px; padding: 10px 15px; align-items: center; gap: 7px; font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 6px 18px rgba(0,0,0,0.12);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="#DC2626" stroke="#DC2626" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        <span>Favoritos (<span id="scms-wishlist-count">' . count($initialItems) . '</span>)</span>
    </button>

    <!-- MODAL DE LISTA DE DESEOS -->
    <div id="scms-wishlist-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 10020; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #FFFFFF; max-width: 620px; width: 100%; border-radius: 14px; padding: 24px; box-shadow: 0 20px 44px rgba(0,0,0,0.25); max-height: 88vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #E5E7EB; padding-bottom: 12px;">
                <div>
                    <h3 id="scms-wishlist-modal-title" style="font-size: 18px; font-weight: 700; margin: 0; color: #111827;">Mi Lista de Deseos</h3>
                    <div id="scms-wishlist-modal-sub" style="font-size: 12px; color: #6B7280; margin-top: 2px;">Tus productos favoritos guardados de forma privada en tu cuenta.</div>
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
        try {
            localStorage.removeItem("shopcms_wishlist");
            localStorage.removeItem("shopcms_wishlist_items");
        } catch (e) {}

        var BASE = ' . json_encode($baseUrl) . ';
        var wishlistProducts = ' . json_encode($initialItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';

        function esc(s) {
            return String(s || "").replace(/[&<>"\x27]/g, function(m) {
                return {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","\x27":"&#39;"}[m];
            });
        }

        function syncButtons() {
            var ids = wishlistProducts.map(function(p) { return Number(p.id); });
            document.querySelectorAll("[id^=\'wishlist_text_\']").forEach(function(span) {
                var pid = Number(span.id.replace("wishlist_text_", ""));
                var icon = document.getElementById("wishlist_icon_" + pid);
                var saved = ids.indexOf(pid) !== -1;
                span.innerText = saved ? "Guardado en tus Favoritos" : "Guardar en Lista de Deseos";
                if (icon) {
                    icon.setAttribute("fill", saved ? "#D72C0D" : "none");
                    icon.setAttribute("stroke", saved ? "#D72C0D" : "currentColor");
                }
            });
        }

        function updateFab() {
            var fab = document.getElementById("scms-wishlist-fab");
            var countEl = document.getElementById("scms-wishlist-count");
            if (countEl) countEl.textContent = wishlistProducts.length;
            if (fab) fab.style.display = wishlistProducts.length > 0 ? "inline-flex" : "none";
            syncButtons();
        }

        async function syncServer(action, productId) {
            var res = await fetch(BASE + "/plugin/wishlist/sync", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: action, product_id: Number(productId || 0) })
            });
            var data = await res.json();
            if (res.status === 401 || (data && data.require_login)) {
                window.location.href = BASE + "/login?auth_msg=wishlist";
                return null;
            }
            if (data && data.ok) {
                wishlistProducts = Array.isArray(data.products) ? data.products : [];
                updateFab();
            }
            return data;
        }

        window.toggleWishlist = async function(prodId) {
            await syncServer("toggle", prodId);
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
                    ? "<img src=\'" + esc(p.image) + "\' style=\'width:54px;height:54px;object-fit:cover;border-radius:8px;border:1px solid #E5E7EB;\'>"
                    : "<div style=\'width:54px;height:54px;border-radius:8px;background:#F3F4F6;\'></div>";
                var removeBtn = isSharedMode
                    ? "<button type=\'button\' onclick=\'saveSharedItem(" + Number(p.id) + ")\' style=\'background:#F3F4F6;border:1px solid #D1D5DB;border-radius:6px;padding:6px 10px;font-size:12px;font-weight:600;cursor:pointer;\'>Guardar</button>"
                    : "<button type=\'button\' onclick=\'removeWishlistItem(" + Number(p.id) + ")\' style=\'background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;border-radius:6px;padding:6px 10px;font-size:12px;font-weight:600;cursor:pointer;\'>Quitar</button>";

                return "<div style=\'display:flex;align-items:center;gap:12px;padding:10px;border:1px solid #E5E7EB;border-radius:10px;margin-bottom:10px;\'>"
                    + imgHtml
                    + "<div style=\'flex:1;\'>"
                    + "<div style=\'font-weight:700;font-size:14px;color:#111827;\'>" + esc(p.name) + "</div>"
                    + "<div style=\'font-size:13px;font-weight:700;color:#008060;\'>" + esc(p.currency || "$") + Number(p.price || 0).toFixed(2) + "</div>"
                    + "</div>"
                    + "<a href=\'" + esc(p.url) + "\' style=\'background:#111827;color:#FFF;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;\'>Ver</a>"
                    + removeBtn
                    + "</div>";
            }).join("");
        }

        window.saveSharedItem = async function(id) {
            await syncServer("add", id);
            var fb = document.getElementById("scms-wishlist-feedback");
            if (fb) {
                fb.textContent = "Producto guardado en tu propia lista de deseos.";
                fb.style.display = "block";
            }
        };

        window.removeWishlistItem = async function(id) {
            await syncServer("toggle", id);
            renderModalList(wishlistProducts, false);
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
                if (sub) sub.textContent = "Tus productos favoritos guardados de forma privada en tu cuenta.";
                renderModalList(wishlistProducts, false);
            }
            if (modal) modal.style.display = "flex";
        };

        window.closeWishlistModal = function() {
            var modal = document.getElementById("scms-wishlist-modal");
            if (modal) modal.style.display = "none";
        };

        window.copyWishlistShareLink = function() {
            var ids = wishlistProducts.map(function(p) { return p.id; });
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

        window.clearWishlistAll = async function() {
            await syncServer("clear", 0);
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
