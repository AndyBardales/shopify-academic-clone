<?php
$defaultOpt1Preview = htmlspecialchars($product['option1_name'] ?? ($defaultOpt1 ?? 'Color'));
$defaultOpt2Preview = htmlspecialchars($product['option2_name'] ?? ($defaultOpt2 ?? 'Talla'));
?>
<!-- HERRAMIENTAS DE VARIANTES: GENERADOR DE MATRIZ Y ASIGNACION MASIVA POR ATRIBUTO -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; margin-bottom: 16px;">
    <!-- 1. Generador de Matriz -->
    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 12px 14px;">
        <div style="font-size: 12.5px; font-weight: 700; color: #0F172A; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
            <span>Generador rápido de matriz</span>
            <span style="font-size: 11px; font-weight: 500; color: #64748B;">Separa valores con coma</span>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
            <input type="text" id="scms_mx_opt1" class="form-control" placeholder="<?= $defaultOpt1Preview ?> (ej: Negro, Blanco)" style="font-size: 12px; padding: 7px 9px;">
            <input type="text" id="scms_mx_opt2" class="form-control" placeholder="<?= $defaultOpt2Preview ?> (ej: S, M, L, XL)" style="font-size: 12px; padding: 7px 9px;">
        </div>
        <div style="display: flex; gap: 8px;">
            <input type="text" id="scms_mx_opt3" class="form-control" placeholder="Atributo 3 opcional (ej: Algodón)" style="font-size: 12px; padding: 7px 9px; flex: 1;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="scmsGenerateVariantMatrix()" style="white-space: nowrap; font-size: 12px; font-weight: 600;">Generar filas</button>
        </div>
    </div>

    <!-- 2. Asignador masivo por atributo -->
    <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 10px; padding: 12px 14px;">
        <div style="font-size: 12.5px; font-weight: 700; color: #166534; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
            <span>Asignar foto / precio / ficha por atributo</span>
            <span id="scms_bulk_feedback" style="font-size: 11px; font-weight: 600; color: #15803D;"></span>
        </div>
        <div style="display: grid; grid-template-columns: 1.2fr 0.9fr 0.9fr; gap: 6px; margin-bottom: 8px;">
            <input type="text" id="scms_bulk_match" list="scms_bulk_attr_list" onfocus="scmsRefreshAttrDatalist()" class="form-control" placeholder="Valor (ej: Blanco o M)" style="font-size: 12px; padding: 7px 9px; background: #FFF;">
            <datalist id="scms_bulk_attr_list"></datalist>
            <input type="number" step="0.01" min="0" id="scms_bulk_price" class="form-control" placeholder="Precio" style="font-size: 12px; padding: 7px 9px; background: #FFF;">
            <input type="number" step="0.01" min="0" id="scms_bulk_compare" class="form-control" placeholder="P. Tachado" style="font-size: 12px; padding: 7px 9px; background: #FFF;">
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
            <label class="btn btn-secondary btn-sm" style="padding: 6px 9px; font-size: 11.5px; cursor: pointer; margin: 0; background: #FFF;">
                <span id="scms_bulk_file_label">Foto compartida</span>
                <input type="file" id="scms_bulk_file_input" accept=".webp,.jpg,.jpeg,.png,image/webp,image/jpeg,image/png" style="display: none;" onchange="scmsOnBulkFilePicked(this)">
            </label>
            <input type="text" id="scms_bulk_specs" class="form-control" placeholder="Ficha ej: Material: Lino (opcional)" style="font-size: 12px; padding: 6px 9px; flex: 1; min-width: 140px; background: #FFF;">
            <button type="button" class="btn btn-primary btn-sm" onclick="scmsApplyBulkVariantAttr()" style="white-space: nowrap; font-size: 12px;">Aplicar a coincidentes</button>
        </div>
    </div>
</div>

<div id="scms_bulk_files_bucket" style="display: none;"></div>

<!-- MODAL DE DETALLES Y FICHA TÉCNICA POR VARIANTE -->
<div id="scms_var_details_modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 10050; align-items: center; justify-content: center; padding: 16px;">
    <div style="background: #FFFFFF; border-radius: 12px; width: 100%; max-width: 520px; padding: 20px; box-shadow: 0 20px 44px rgba(0, 0, 0, 0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #E5E7EB; padding-bottom: 10px;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #111827;">Detalles propios de variante</h3>
                <div id="scms_vdm_title" style="font-size: 12px; color: #6B7280; margin-top: 2px;"></div>
            </div>
            <button type="button" onclick="scmsCloseVarDetailsModal()" style="border: none; background: transparent; font-size: 22px; cursor: pointer; color: #6B7280;">&times;</button>
        </div>

        <p style="font-size: 12px; color: #4B5563; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 6px; padding: 8px 10px; margin: 0 0 12px;">
            Si dejas un campo vacío, esta variante heredará automáticamente el valor del producto padre.
        </p>

        <div class="form-group">
            <label style="font-size: 12.5px; font-weight: 600;">Descripción corta (opcional)</label>
            <input type="text" id="scms_vdm_short" class="form-control" placeholder="Ej: Edición en fibra de carbono / Bluetooth 5.3">
        </div>
        <div class="form-group">
            <label style="font-size: 12.5px; font-weight: 600;">Descripción detallada / cuidados (opcional)</label>
            <textarea id="scms_vdm_desc" class="form-control" rows="3" placeholder="Texto alternativo solo para esta variante..."></textarea>
        </div>
        <div class="form-group" style="margin-bottom: 14px;">
            <label style="font-size: 12.5px; font-weight: 600;">Características técnicas (una por línea "Clave: Valor")</label>
            <textarea id="scms_vdm_specs" class="form-control" rows="4" placeholder="Material: Algodón orgánico&#10;Gramaje: 240 gsm&#10;Acabado: Mate"></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="scmsCloseVarDetailsModal()">Cancelar</button>
            <button type="button" class="btn btn-primary btn-sm" onclick="scmsSaveVarDetailsModal()">Guardar detalles</button>
        </div>
    </div>
</div>

<script>
(function() {
    var activeVarRow = null;
    var pendingBulkFile = null;
    var bulkCounter = 0;

    function createVariantThumbPlaceholder() {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
    }

    function updateRowDetailDot(row) {
        if (!row) return;
        var s = (row.querySelector('input[name="variant_short_description[]"]') || {}).value || '';
        var d = (row.querySelector('input[name="variant_description[]"]') || {}).value || '';
        var p = (row.querySelector('input[name="variant_specs[]"]') || {}).value || '';
        var hasCustom = (s.trim() !== '' || d.trim() !== '' || p.trim() !== '');
        var btn = row.querySelector('.scms-var-details-btn');
        if (btn) {
            btn.style.borderColor = hasCustom ? '#16A34A' : '';
            btn.style.color = hasCustom ? '#15803D' : '';
            btn.style.background = hasCustom ? '#F0FDF4' : '';
            btn.textContent = hasCustom ? 'Ficha *' : 'Ficha';
        }
    }

    window.scmsOpenVarDetailsModal = function(btn) {
        activeVarRow = btn.closest('tr');
        if (!activeVarRow) return;
        var titleInp = activeVarRow.querySelector('input[name="variant_title[]"]');
        document.getElementById('scms_vdm_title').textContent = (titleInp && titleInp.value) ? titleInp.value : 'Nueva variante';
        document.getElementById('scms_vdm_short').value = (activeVarRow.querySelector('input[name="variant_short_description[]"]') || {}).value || '';
        document.getElementById('scms_vdm_desc').value = (activeVarRow.querySelector('input[name="variant_description[]"]') || {}).value || '';
        document.getElementById('scms_vdm_specs').value = (activeVarRow.querySelector('input[name="variant_specs[]"]') || {}).value || '';
        document.getElementById('scms_var_details_modal').style.display = 'flex';
    };

    window.scmsCloseVarDetailsModal = function() {
        document.getElementById('scms_var_details_modal').style.display = 'none';
        activeVarRow = null;
    };

    window.scmsSaveVarDetailsModal = function() {
        if (!activeVarRow) return;
        var sEl = activeVarRow.querySelector('input[name="variant_short_description[]"]');
        var dEl = activeVarRow.querySelector('input[name="variant_description[]"]');
        var pEl = activeVarRow.querySelector('input[name="variant_specs[]"]');
        if (sEl) sEl.value = document.getElementById('scms_vdm_short').value;
        if (dEl) dEl.value = document.getElementById('scms_vdm_desc').value;
        if (pEl) pEl.value = document.getElementById('scms_vdm_specs').value;
        updateRowDetailDot(activeVarRow);
        window.scmsCloseVarDetailsModal();
    };

    window.scmsRefreshAttrDatalist = function() {
        var dl = document.getElementById('scms_bulk_attr_list');
        if (!dl) return;
        var seen = {};
        var html = '';
        document.querySelectorAll('input[name="variant_title[]"]').forEach(function(inp) {
            String(inp.value || '').split('/').forEach(function(part) {
                var clean = part.trim();
                if (clean && !seen[clean.toLowerCase()]) {
                    seen[clean.toLowerCase()] = true;
                    html += '<option value="' + clean.replace(/"/g, '&quot;') + '">';
                }
            });
        });
        dl.innerHTML = html;
    };

    window.scmsOnBulkFilePicked = function(input) {
        var lbl = document.getElementById('scms_bulk_file_label');
        if (!input || !input.files || !input.files[0]) {
            pendingBulkFile = null;
            if (lbl) lbl.textContent = 'Foto compartida';
            return;
        }
        var f = input.files[0];
        if (f.size > 2 * 1024 * 1024) {
            alert('La imagen excede 2 MB.');
            input.value = '';
            pendingBulkFile = null;
            if (lbl) lbl.textContent = 'Foto compartida';
            return;
        }
        pendingBulkFile = f;
        if (lbl) lbl.textContent = f.name.length > 18 ? (f.name.slice(0, 15) + '...') : f.name;
    };

    window.scmsApplyBulkVariantAttr = function() {
        var targetAttr = (document.getElementById('scms_bulk_match').value || '').trim().toLowerCase();
        var feedback = document.getElementById('scms_bulk_feedback');
        if (!targetAttr) {
            if (feedback) feedback.textContent = 'Escribe un valor de atributo primero.';
            return;
        }
        var newPrice = (document.getElementById('scms_bulk_price').value || '').trim();
        var newCompare = (document.getElementById('scms_bulk_compare').value || '').trim();
        var newSpecs = (document.getElementById('scms_bulk_specs').value || '').trim();

        var bulkRef = '';
        var previewDataUrl = '';
        if (pendingBulkFile) {
            bulkCounter++;
            bulkRef = 'bulk_' + bulkCounter + '_' + Date.now();
            var bucket = document.getElementById('scms_bulk_files_bucket');
            if (bucket && typeof DataTransfer === 'function') {
                var keyInp = document.createElement('input');
                keyInp.type = 'hidden';
                keyInp.name = 'bulk_variant_keys[]';
                keyInp.value = bulkRef;
                var fileInp = document.createElement('input');
                fileInp.type = 'file';
                fileInp.name = 'bulk_variant_files[]';
                var dt = new DataTransfer();
                dt.items.add(pendingBulkFile);
                fileInp.files = dt.files;
                bucket.appendChild(keyInp);
                bucket.appendChild(fileInp);
            }
            previewDataUrl = URL.createObjectURL(pendingBulkFile);
        }

        var matchedCount = 0;
        document.querySelectorAll('#variants_table_body tr.variant-row').forEach(function(row) {
            var titleInp = row.querySelector('input[name="variant_title[]"]');
            var parts = String(titleInp ? titleInp.value : '').split('/').map(function(p) { return p.trim().toLowerCase(); });
            if (parts.indexOf(targetAttr) === -1) return;
            matchedCount++;

            if (newPrice !== '') {
                var pInp = row.querySelector('input[name="variant_price[]"]');
                if (pInp) pInp.value = newPrice;
            }
            if (newCompare !== '') {
                var cInp = row.querySelector('input[name="variant_compare_price[]"]');
                if (cInp) cInp.value = newCompare;
            }
            if (newSpecs !== '') {
                var sInp = row.querySelector('input[name="variant_specs[]"]');
                if (sInp) {
                    sInp.value = newSpecs.replace(/\\n/g, '\n');
                    updateRowDetailDot(row);
                }
            }
            if (bulkRef !== '') {
                var refInp = row.querySelector('input[name="variant_bulk_ref[]"]');
                var clrInp = row.querySelector('input[name="variant_clear_image[]"]');
                var thumb = row.querySelector('.scms-var-thumb');
                var clrBtn = row.querySelector('.scms-var-clear');
                var pickLbl = row.querySelector('.scms-var-pick-label');
                if (refInp) refInp.value = bulkRef;
                if (clrInp) clrInp.value = '0';
                if (thumb && previewDataUrl) thumb.innerHTML = '<img src="' + previewDataUrl + '" alt="Variante" style="width:100%;height:100%;object-fit:cover;">';
                if (clrBtn) clrBtn.style.display = 'inline-flex';
                if (pickLbl) pickLbl.textContent = 'Cambiar';
            }
        });

        if (feedback) {
            feedback.textContent = matchedCount > 0
                ? ('Aplicado a ' + matchedCount + ' fila(s).')
                : 'Sin coincidencias para "' + targetAttr + '".';
        }
        if (matchedCount > 0 && pendingBulkFile) {
            pendingBulkFile = null;
            var fi = document.getElementById('scms_bulk_file_input');
            var fl = document.getElementById('scms_bulk_file_label');
            if (fi) fi.value = '';
            if (fl) fl.textContent = 'Foto compartida';
        }
    };

    window.scmsGenerateVariantMatrix = function() {
        var list1 = (document.getElementById('scms_mx_opt1').value || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean);
        var list2 = (document.getElementById('scms_mx_opt2').value || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean);
        var list3 = (document.getElementById('scms_mx_opt3').value || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean);
        if (!list1.length) return;

        var combos = [];
        list1.forEach(function(a) {
            if (!list2.length) {
                combos.push(a);
            } else {
                list2.forEach(function(b) {
                    if (!list3.length) {
                        combos.push(a + ' / ' + b);
                    } else {
                        list3.forEach(function(c) {
                            combos.push(a + ' / ' + b + ' / ' + c);
                        });
                    }
                });
            }
        });

        var tbody = document.getElementById('variants_table_body');
        if (!tbody) return;
        var existingTitles = {};
        tbody.querySelectorAll('input[name="variant_title[]"]').forEach(function(inp) {
            var v = (inp.value || '').trim().toLowerCase();
            if (v) existingTitles[v] = true;
        });

        var baseSku = (document.getElementById('sku') ? document.getElementById('sku').value : 'SKU').trim() || 'SKU';
        var firstEmptyRow = null;
        var rows = tbody.querySelectorAll('tr.variant-row');
        if (rows.length === 1) {
            var t0 = rows[0].querySelector('input[name="variant_title[]"]');
            if (t0 && (t0.value || '').trim() === '') firstEmptyRow = rows[0];
        }

        combos.forEach(function(title, idx) {
            if (existingTitles[title.toLowerCase()]) return;
            var targetRow = null;
            if (firstEmptyRow) {
                targetRow = firstEmptyRow;
                firstEmptyRow = null;
            } else {
                if (typeof window.addVariantRow === 'function') {
                    window.addVariantRow();
                }
                var allRows = tbody.querySelectorAll('tr.variant-row');
                targetRow = allRows[allRows.length - 1];
            }
            if (targetRow) {
                var ti = targetRow.querySelector('input[name="variant_title[]"]');
                var si = targetRow.querySelector('input[name="variant_sku[]"]');
                if (ti) ti.value = title;
                if (si && !si.value) {
                    si.value = baseSku + '-' + title.toUpperCase().replace(/[^A-Z0-9]+/g, '-').replace(/^-|-$/g, '');
                }
                existingTitles[title.toLowerCase()] = true;
            }
        });
        window.scmsRefreshAttrDatalist();
    };

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('#variants_table_body tr.variant-row').forEach(updateRowDetailDot);
    });
})();
</script>
