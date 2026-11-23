/**
 * ShopCMS UI Guard & Numeric Sanitizer
 * 1. Control estricto de inputs numéricos (precios, stock, cantidades):
 *    - Bloquea caracteres inválidos y exponentes.
 *    - Al salir del campo (blur/change), valores negativos (< 0), letras o inválidos (como '10e')
 *      se cambian automáticamente por defecto a 0 (o 0.00).
 * 2. Bloqueo de UI y menús durante transacciones:
 *    - Al enviar cualquier formulario o procesar una transacción, bloquea el menú lateral,
 *      topbar y todo clic, mostrando una ventana modal de procesamiento seguro.
 */
(function() {
    'use strict';

    // =========================================================================
    // 1. SANITIZACIÓN AUTOMÁTICA DE CAMPOS NUMÉRICOS
    // =========================================================================
    function isNumericInput(input) {
        if (!input || input.nodeType !== 1) return false;
        if (input.type === 'number') return true;
        if (input.classList && input.classList.contains('numeric-input')) return true;

        const name = (input.name || '').toLowerCase();
        const id = (input.id || '').toLowerCase();
        const numericTokens = ['price', 'cost', 'qty', 'quantity', 'stock', 'threshold', 'compare_price', 'amount', 'discount', 'min_spend', 'usage_limit'];
        return numericTokens.some(tok => name.includes(tok) || id.includes(tok));
    }

    function isDecimalInput(input) {
        if (!input) return false;
        const step = input.getAttribute('step');
        if (step === '0.01' || step === 'any') return true;
        const name = (input.name || '').toLowerCase();
        const id = (input.id || '').toLowerCase();
        return name.includes('price') || id.includes('price') || name.includes('cost') || id.includes('cost') || name.includes('discount') || id.includes('discount') || name.includes('amount') || id.includes('amount');
    }

    function sanitizeInput(input) {
        if (!isNumericInput(input)) return;

        const isDecimal = isDecimalInput(input);
        const defaultValue = isDecimal ? '0.00' : '0';
        let val = input.value;

        // 1. Detección de badInput (cuando el usuario escribe letras o formato exponencial como 10e)
        if (input.validity && input.validity.badInput) {
            input.value = defaultValue;
            triggerEvents(input);
            return;
        }

        // 2. Si el valor está vacío: si es requerido se asigna valor por defecto, si es opcional se permite vacío
        if (val === null || val === undefined || String(val).trim() === '') {
            if (input.required) {
                input.value = defaultValue;
                triggerEvents(input);
            }
            return;
        }

        // 3. Limpiar cualquier caracter extraño si viniera como texto
        let cleanVal = String(val).trim();
        // Si contiene letras o exponentes 'e' / 'E'
        if (/[a-zA-Z]/.test(cleanVal)) {
            input.value = defaultValue;
            triggerEvents(input);
            return;
        }

        let num = parseFloat(cleanVal);

        // 4. Si es NaN o es negativo (< 0), cambiar por defecto a 0
        if (isNaN(num) || num < 0) {
            input.value = defaultValue;
            triggerEvents(input);
            return;
        }

        // 5. Formato uniforme
        if (isDecimal) {
            input.value = num.toFixed(2);
            triggerEvents(input);
        } else {
            input.value = Math.floor(num).toString();
            triggerEvents(input);
        }
    }

    function triggerEvents(input) {
        try {
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (e) {}
    }

    // Prevenir teclas negativas y exponenciales durante la escritura
    document.addEventListener('keydown', function(e) {
        const input = e.target;
        if (!isNumericInput(input)) return;

        // Bloquear tecla '-' (negativos) y 'e', 'E', '+' (notación científica)
        if (e.key === '-' || e.key === 'e' || e.key === 'E' || e.key === '+') {
            e.preventDefault();
        }
    }, true);

    // Al perder el foco (blur) o cambiar (change)
    document.addEventListener('blur', function(e) {
        if (isNumericInput(e.target)) {
            sanitizeInput(e.target);
        }
    }, true);

    document.addEventListener('change', function(e) {
        if (isNumericInput(e.target)) {
            sanitizeInput(e.target);
        }
    }, true);


    // =========================================================================
    // 2. BLOQUEO DE NAVEGACIÓN Y MENÚS DURANTE OPERACIONES / TRANSACCIONES
    // =========================================================================
    let overlayElem = null;

    function isExcludedForm(form) {
        if (!form || form.nodeType !== 1) return true;
        if (form.method && form.method.toLowerCase() === 'get' && !form.dataset.lock) return true;
        if (form.dataset.noGuard === 'true' || form.dataset.authForm === 'true') return true;

        const action = (form.action || '').toLowerCase();
        if (action.includes('login') || action.includes('logout') || action.includes('register') || action.includes('auth')) {
            return true;
        }

        if (form.id === 'admin-login-form' || form.classList.contains('login-form') || form.closest('.login-card') || form.closest('.auth-card')) {
            return true;
        }

        const path = (window.location.pathname || '').toLowerCase();
        if (path.includes('/login') || path.includes('/register')) {
            return true;
        }

        return false;
    }

    function getOverlay() {
        if (!overlayElem) {
            overlayElem = document.getElementById('shopcms-transaction-overlay');
            if (!overlayElem) {
                overlayElem = document.createElement('div');
                overlayElem.id = 'shopcms-transaction-overlay';
                overlayElem.innerHTML = `
                    <div class="shopcms-modal-box">
                        <div class="shopcms-spinner-wrap">
                            <svg class="shopcms-spin-svg" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" stroke-opacity="0.2"></circle>
                                <path d="M12 2a10 10 0 0 1 10 10"></path>
                            </svg>
                        </div>
                        <h3 class="shopcms-modal-title" id="shopcms-modal-title">Procesando Operación...</h3>
                        <p class="shopcms-modal-text" id="shopcms-modal-desc">
                            Guardando información y asegurando los datos. Las opciones de navegación y botones han sido bloqueados temporalmente para evitar inconsistencias o duplicidad de datos.
                        </p>
                        <div class="shopcms-modal-badge">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <span>Transacción segura en progreso</span>
                        </div>
                    </div>
                `;
                document.body.appendChild(overlayElem);
            }
        }
        return overlayElem;
    }

    function lockUI(title, desc) {
        const overlay = getOverlay();
        if (title) {
            const titleEl = document.getElementById('shopcms-modal-title');
            if (titleEl) titleEl.innerText = title;
        }
        if (desc) {
            const descEl = document.getElementById('shopcms-modal-desc');
            if (descEl) descEl.innerText = desc;
        }

        document.body.classList.add('shopcms-locked');
        overlay.classList.add('active');

        // Salvaguarda: si por algún problema de red extrema tardara más de 20s, desbloquear
        setTimeout(function() {
            if (document.body.classList.contains('shopcms-locked')) {
                unlockUI();
            }
        }, 20000);
    }

    function unlockUI() {
        if (overlayElem) {
            overlayElem.classList.remove('active');
        }
        document.body.classList.remove('shopcms-locked');
    }

    // Interceptar cualquier clic en links o menús cuando la UI está bloqueada
    window.addEventListener('click', function(e) {
        if (document.body.classList.contains('shopcms-locked')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
    }, true);

    // Escuchar el envío de formularios de datos (excluyendo logins y búsquedas)
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || form.nodeType !== 1) return;

        // Si es formulario de login, autenticación o búsqueda GET, no activar overlay
        if (isExcludedForm(form)) {
            return;
        }
        if (form.target && form.target === '_blank') {
            return;
        }

        // Sanitizar todos los campos numéricos del formulario antes de enviar
        form.querySelectorAll('input').forEach(inp => {
            if (isNumericInput(inp)) {
                sanitizeInput(inp);
            }
        });

        // Validar validez HTML5
        if (form.checkValidity && !form.checkValidity()) {
            return; // Permite mostrar tooltips nativos del navegador sin bloquear la pantalla
        }

        // Si es formulario de tienda pública salvo checkout final, no bloquear UI
        const action = (form.action || '').toLowerCase();
        if (!isAdminContext && !action.includes('checkout/process')) {
            return;
        }

        let customTitle = 'Guardando Registro...';
        let customDesc = 'Procesando los datos enviados. Por favor espera, las opciones de navegación se encuentran deshabilitadas.';

        if (action.includes('checkout') || action.includes('order')) {
            customTitle = 'Procesando Pedido y Pago...';
            customDesc = 'Generando orden de compra y procesando transacción. Por favor, no cierres ni recargues la página.';
        } else if (action.includes('product')) {
            customTitle = 'Guardando Producto y Variantes...';
            customDesc = 'Actualizando catálogo e inventario. Los menús de navegación están bloqueados temporalmente.';
        } else if (action.includes('categories')) {
            customTitle = 'Actualizando Categorías...';
            customDesc = 'Sincronizando estructura de categorías y taxonomía.';
        } else if (action.includes('settings')) {
            customTitle = 'Guardando Configuración...';
            customDesc = 'Aplicando ajustes del sistema de la tienda.';
        } else if (action.includes('plugin') || action.includes('theme')) {
            customTitle = 'Procesando Archivo ZIP...';
            customDesc = 'Subiendo y extrayendo paquete en el servidor. Por favor espere.';
        }

        lockUI(customTitle, customDesc);
    }, true);

    // =========================================================================
    // 3. BARRA DE GUARDADO CONTEXTUAL (Solo en Panel Administrativo /admin)
    // =========================================================================
    const isAdminContext = !!document.querySelector('.polaris-sidebar');
    let saveBarElem = null;
    let activeDirtyForm = null;
    let isFormDirty = false;
    let isSubmitting = false;
    let shakeTimeout = null;
    let textRevertTimeout = null;

    function getSaveBar() {
        if (!isAdminContext) return null;
        if (!saveBarElem) {
            saveBarElem = document.getElementById('shopcms-contextual-savebar');
            if (!saveBarElem) {
                saveBarElem = document.createElement('div');
                saveBarElem.id = 'shopcms-contextual-savebar';
                saveBarElem.className = 'shopcms-savebar';
                saveBarElem.setAttribute('role', 'region');
                saveBarElem.setAttribute('aria-label', 'Barra de guardado contextual');
                saveBarElem.innerHTML = `
                    <div class="shopcms-savebar-content">
                        <div class="shopcms-savebar-indicator">
                            <span class="shopcms-savebar-dot"></span>
                            <span class="shopcms-savebar-text" id="shopcms-savebar-text">Cambios no guardados</span>
                        </div>
                        <div class="shopcms-savebar-actions">
                            <button type="button" class="shopcms-btn-discard" id="shopcms-savebar-discard">
                                Descartar
                            </button>
                            <button type="button" class="shopcms-btn-save" id="shopcms-savebar-save">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>Guardar</span>
                            </button>
                        </div>
                    </div>
                `;
                document.body.appendChild(saveBarElem);
            }

            // Vincular eventos de botones de la barra
            const btnDiscard = document.getElementById('shopcms-savebar-discard');
            if (btnDiscard) {
                btnDiscard.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    discardActiveFormChanges();
                });
            }

            const btnSave = document.getElementById('shopcms-savebar-save');
            if (btnSave) {
                btnSave.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    saveActiveFormChanges();
                });
            }
        }
        return saveBarElem;
    }

    function showSaveBar() {
        const bar = getSaveBar();
        bar.classList.add('active');
        document.body.classList.add('shopcms-has-unsaved-changes');
        const textEl = document.getElementById('shopcms-savebar-text');
        if (textEl && !bar.classList.contains('shake')) {
            textEl.innerText = 'Cambios no guardados';
        }
    }

    function hideSaveBar() {
        if (saveBarElem) {
            saveBarElem.classList.remove('active');
            saveBarElem.classList.remove('shake');
        }
        document.body.classList.remove('shopcms-has-unsaved-changes');
        if (shakeTimeout) clearTimeout(shakeTimeout);
        if (textRevertTimeout) clearTimeout(textRevertTimeout);
    }

    function shakeSaveBar(customMsg) {
        const bar = getSaveBar();
        if (!bar) return;

        showSaveBar();
        bar.classList.remove('shake');
        void bar.offsetWidth; // Forzar reflow para reiniciar animación
        bar.classList.add('shake');

        const textEl = document.getElementById('shopcms-savebar-text');
        if (textEl) {
            textEl.innerText = customMsg || 'Guarda o descarta los cambios antes de salir';
        }

        if (shakeTimeout) clearTimeout(shakeTimeout);
        shakeTimeout = setTimeout(function() {
            bar.classList.remove('shake');
        }, 600);

        if (textRevertTimeout) clearTimeout(textRevertTimeout);
        textRevertTimeout = setTimeout(function() {
            if (isFormDirty && textEl) {
                textEl.innerText = 'Cambios no guardados';
            }
        }, 2200);
    }

    // Inicializar estado base de los campos de un formulario
    function captureFormInitialState(form) {
        if (!form || isExcludedForm(form) || form.dataset.savebarInitialized) return;
        form.dataset.savebarInitialized = 'true';

        // Contar filas dinámicas iniciales (ej. variantes)
        const initialVariantRows = form.querySelectorAll('.variant-row').length;
        form.dataset.initialVariantRows = initialVariantRows.toString();

        const fields = form.querySelectorAll('input, textarea, select');
        fields.forEach(field => {
            if (field.type === 'submit' || field.type === 'button' || field.type === 'reset' || field.type === 'hidden') {
                return;
            }
            if (field.type === 'checkbox' || field.type === 'radio') {
                field.dataset.initialChecked = field.checked ? 'true' : 'false';
            } else {
                field.dataset.initialVal = field.value !== undefined ? field.value : '';
            }
        });
    }

    // Evaluar si el formulario tiene cambios respecto a su estado inicial
    function evaluateFormDirty(form) {
        if (!form || isExcludedForm(form)) return false;

        // Comprobar filas dinámicas añadidas o eliminadas
        const initialVariantRows = parseInt(form.dataset.initialVariantRows || '0', 10);
        const currentVariantRows = form.querySelectorAll('.variant-row').length;
        if (initialVariantRows !== currentVariantRows) {
            return true;
        }

        const fields = form.querySelectorAll('input, textarea, select');
        for (let i = 0; i < fields.length; i++) {
            const field = fields[i];
            if (field.type === 'submit' || field.type === 'button' || field.type === 'reset' || field.type === 'hidden') {
                continue;
            }

            if (field.type === 'file') {
                if (field.files && field.files.length > 0) return true;
            } else if (field.type === 'checkbox' || field.type === 'radio') {
                const initChecked = field.dataset.initialChecked === 'true';
                if (field.checked !== initChecked) return true;
            } else {
                const initVal = field.dataset.initialVal !== undefined ? field.dataset.initialVal : '';
                if (field.value !== initVal) {
                    return true;
                }
            }
        }
        return false;
    }

    function checkActiveFormDirty(form) {
        if (!isAdminContext || !form || isExcludedForm(form)) {
            return;
        }

        captureFormInitialState(form);
        const dirty = evaluateFormDirty(form);

        if (dirty) {
            isFormDirty = true;
            activeDirtyForm = form;
            showSaveBar();
        } else {
            // Si volvió al estado original
            if (activeDirtyForm === form) {
                isFormDirty = false;
                activeDirtyForm = null;
                hideSaveBar();
            }
        }
    }

    function discardActiveFormChanges() {
        if (!activeDirtyForm) {
            hideSaveBar();
            isFormDirty = false;
            return;
        }

        const form = activeDirtyForm;

        // 1. Restaurar campos a sus valores iniciales
        const fields = form.querySelectorAll('input, textarea, select');
        fields.forEach(field => {
            if (field.type === 'file') {
                field.value = '';
            } else if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = field.dataset.initialChecked === 'true';
            } else if (field.dataset.initialVal !== undefined) {
                field.value = field.dataset.initialVal;
            }
        });

        // 2. Eliminar variantes dinámicas añadidas posteriormente
        const initialVariantRows = parseInt(form.dataset.initialVariantRows || '0', 10);
        const currentVariantRows = form.querySelectorAll('.variant-row');
        if (currentVariantRows.length > initialVariantRows) {
            for (let i = currentVariantRows.length - 1; i >= initialVariantRows; i--) {
                currentVariantRows[i].remove();
            }
        }

        // 3. Si el formulario tiene función de reset propia (como categorías)
        if (typeof window.resetForm === 'function' && form.id === 'category-form') {
            window.resetForm();
        }

        // Disparar eventos para recalcular si aplica
        fields.forEach(f => {
            try {
                f.dispatchEvent(new Event('input', { bubbles: true }));
                f.dispatchEvent(new Event('change', { bubbles: true }));
            } catch (e) {}
        });

        if (typeof window.onShopCMSDiscard === 'function') {
            try { window.onShopCMSDiscard(form); } catch (e) {}
        }

        isFormDirty = false;
        activeDirtyForm = null;
        hideSaveBar();
    }

    function saveActiveFormChanges() {
        if (!activeDirtyForm) return;

        // 1. Sanitizar campos numéricos antes de guardar
        activeDirtyForm.querySelectorAll('input').forEach(inp => {
            if (isNumericInput(inp)) {
                sanitizeInput(inp);
            }
        });

        // 2. Validar con HTML5
        if (activeDirtyForm.reportValidity && !activeDirtyForm.reportValidity()) {
            shakeSaveBar('Completa los campos obligatorios antes de guardar');
            return;
        }

        // 3. Marcar como enviando para no bloquear la salida
        isSubmitting = true;

        // 4. Enviar formulario
        if (typeof activeDirtyForm.requestSubmit === 'function') {
            activeDirtyForm.requestSubmit();
        } else {
            activeDirtyForm.submit();
        }
    }

    // Escuchar cambios y bloqueos de navegación SOLO dentro del Panel Admin
    if (isAdminContext) {
        document.addEventListener('input', function(e) {
            const target = e.target;
            if (!target || !target.form || isExcludedForm(target.form)) return;
            if (target.closest('#shopcms-contextual-savebar')) return;

            checkActiveFormDirty(target.form);
        }, true);

        document.addEventListener('change', function(e) {
            const target = e.target;
            if (!target || !target.form || isExcludedForm(target.form)) return;
            if (target.closest('#shopcms-contextual-savebar')) return;

            checkActiveFormDirty(target.form);
        }, true);

        document.addEventListener('keyup', function(e) {
            const target = e.target;
            if (!target || !target.form || isExcludedForm(target.form)) return;
            if (target.closest('#shopcms-contextual-savebar')) return;

            checkActiveFormDirty(target.form);
        }, true);

        window.addEventListener('click', function(e) {
            if (document.body.classList.contains('shopcms-locked')) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }

            if (isFormDirty && !isSubmitting) {
                if (e.target.closest('#shopcms-contextual-savebar')) {
                    return;
                }
                if (activeDirtyForm && (e.target.closest('form') === activeDirtyForm || activeDirtyForm.contains(e.target))) {
                    return;
                }
                const clickedLink = e.target.closest('a') || e.target.closest('button') || e.target.closest('.nav-item') || e.target.closest('.polaris-sidebar');
                if (clickedLink) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    shakeSaveBar('Guarda o descarta los cambios antes de salir');
                    return false;
                }
            }
        }, true);

        window.addEventListener('beforeunload', function(e) {
            if (isFormDirty && !isSubmitting) {
                e.preventDefault();
                e.returnValue = 'Tienes cambios sin guardar en el formulario.';
                return e.returnValue;
            }
        });
    }

    // Exponer API global ShopCMS
    window.ShopCMS = window.ShopCMS || {};
    window.ShopCMS.lockUI = lockUI;
    window.ShopCMS.unlockUI = unlockUI;
    window.ShopCMS.sanitizeInput = sanitizeInput;
    window.ShopCMS.shakeSaveBar = shakeSaveBar;
    window.ShopCMS.showSaveBar = showSaveBar;
    window.ShopCMS.hideSaveBar = hideSaveBar;
    window.ShopCMS.markDirty = function(form) {
        if (form) {
            isFormDirty = true;
            activeDirtyForm = form;
            showSaveBar();
        }
    };
    window.ShopCMS.markClean = function() {
        isFormDirty = false;
        activeDirtyForm = null;
        hideSaveBar();
    };
    window.ShopCMS.syncFormInitialState = function(form) {
        if (!form) return;
        delete form.dataset.savebarInitialized;
        captureFormInitialState(form);
        isFormDirty = false;
        activeDirtyForm = null;
        hideSaveBar();
    };

    // Inicializar al cargar el DOM
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Sanitizar inputs numéricos existentes
        document.querySelectorAll('input').forEach(inp => {
            if (isNumericInput(inp)) {
                if (inp.value && (parseFloat(inp.value) < 0 || isNaN(parseFloat(inp.value)))) {
                    sanitizeInput(inp);
                }
            }
        });

        // 2. Inicializar estado base de formularios de contenido solo en Admin
        if (isAdminContext) {
            document.querySelectorAll('form').forEach(form => {
                if (!isExcludedForm(form)) {
                    captureFormInitialState(form);
                }
            });

            // 3. Crear contenedor de la barra de guardado contextual solo en paneles de administración
            const path = (window.location.pathname || '').toLowerCase();
            if (!path.includes('/login') && !path.includes('/register') && !document.querySelector('.login-card')) {
                getSaveBar();
            }
        }

        // 4. Protección CSRF automática en formularios POST y peticiones fetch()
        injectCsrfIntoAllForms();

        // 5. Buscador en vivo global en cabecera de tienda (Storefront)
        if (!isAdminContext) {
            initStorefrontLiveSearch();
            initProductLinksNewTab();
            initProductVariantsAndGallerySync();
            initProductSideBasket();
        }
    });

    function initStorefrontLiveSearch() {
        const header = document.querySelector('header.site-header');
        if (!header || document.getElementById('scms-header-search-wrap')) return;
        const actions = header.querySelector('.header-actions') || header.querySelector('.header-inner');
        if (!actions) return;

        const metaBase = document.querySelector('meta[name="scms-base-url"]');
        const baseUrl = metaBase ? (metaBase.getAttribute('content') || '').replace(/\/$/, '') : '';

        const wrap = document.createElement('div');
        wrap.id = 'scms-header-search-wrap';
        wrap.className = 'scms-header-search-wrap';
        wrap.innerHTML = `
            <button type="button" class="scms-header-search-toggle" aria-label="Abrir buscador" title="Buscar productos o SKU">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </button>
            <form method="GET" action="${baseUrl}/catalog" class="scms-header-search-form" role="search">
                <input type="search" name="q" class="scms-header-search-input" placeholder="Buscar producto o SKU..." autocomplete="off" aria-label="Buscar producto o SKU">
                <button type="submit" class="scms-header-search-submit" aria-label="Buscar">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
            </form>
            <div class="scms-search-dropdown" aria-live="polite"></div>
        `;

        actions.insertBefore(wrap, actions.firstChild);

        const toggleBtn = wrap.querySelector('.scms-header-search-toggle');
        const input = wrap.querySelector('.scms-header-search-input');
        const dropdown = wrap.querySelector('.scms-search-dropdown');
        let timer = null;
        let controller = null;

        function esc(str) {
            return String(str || '').replace(/[&<>"']/g, function(ch) {
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[ch];
            });
        }

        function closeDropdown() {
            dropdown.classList.remove('open');
        }

        toggleBtn.addEventListener('click', function() {
            wrap.classList.toggle('is-mobile-open');
            if (wrap.classList.contains('is-mobile-open')) {
                setTimeout(function() { input.focus(); }, 40);
            } else {
                closeDropdown();
            }
        });

        input.addEventListener('input', function() {
            const q = input.value.trim();
            clearTimeout(timer);
            if (q.length < 2) {
                closeDropdown();
                return;
            }
            timer = setTimeout(async function() {
                if (controller) controller.abort();
                controller = new AbortController();
                try {
                    const res = await fetch(baseUrl + '/api/search?q=' + encodeURIComponent(q), {
                        signal: controller.signal
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (!data || !data.ok) return;
                    const sym = (data.currency && data.currency.symbol) ? data.currency.symbol : '$';
                    if (!data.items || !data.items.length) {
                        dropdown.innerHTML = '<div class="scms-search-empty">Sin coincidencias para "' + esc(q) + '".</div>';
                    } else {
                        const rowsHtml = data.items.map(function(it) {
                            const imgHtml = it.image_url
                                ? '<img src="' + esc(it.image_url) + '" alt="' + esc(it.name) + '">'
                                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>';
                            const skuTxt = it.sku ? (' · SKU: ' + it.sku) : '';
                            return '<a class="scms-search-item" href="' + esc(it.url) + '">'
                                + '<div class="scms-search-thumb">' + imgHtml + '</div>'
                                + '<div class="scms-search-meta">'
                                + '<div class="scms-search-title">' + esc(it.name) + '</div>'
                                + '<div class="scms-search-sub">' + esc(it.category_name + skuTxt) + '</div>'
                                + '</div>'
                                + '<div class="scms-search-price">' + esc(sym) + Number(it.price || 0).toFixed(2) + '</div>'
                                + '</a>';
                        }).join('');
                        dropdown.innerHTML = rowsHtml
                            + '<a class="scms-search-footer" href="' + esc(baseUrl) + '/catalog?q=' + encodeURIComponent(q) + '">Ver los ' + Number(data.total || data.items.length) + ' resultados en el catálogo</a>';
                    }
                    dropdown.classList.add('open');
                } catch (err) {
                    if (err && err.name !== 'AbortError') closeDropdown();
                }
            }, 220);
        });

        input.addEventListener('focus', function() {
            if (input.value.trim().length >= 2 && dropdown.innerHTML !== '') {
                dropdown.classList.add('open');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDropdown();
                wrap.classList.remove('is-mobile-open');
            }
        });

        document.addEventListener('click', function(e) {
            if (!wrap.contains(e.target)) {
                closeDropdown();
                wrap.classList.remove('is-mobile-open');
            }
        });
    }

    function initProductLinksNewTab() {
        if (window.self !== window.top) return;

        function markLinks(root) {
            const scope = root || document;
            const selector = '.products-grid a[href*="/product/"], .fashion-grid a[href*="/product/"], .tech-grid a[href*="/product/"], .product-card a[href*="/product/"], a.fashion-card[href*="/product/"], #scms-live-search-dropdown a[href*="/product/"]';
            scope.querySelectorAll(selector).forEach(function(link) {
                if (!link.closest('.scms-side-basket') && !link.closest('.breadcrumb')) {
                    link.setAttribute('target', '_blank');
                    link.setAttribute('rel', 'noopener');
                }
            });
        }

        markLinks(document);

        if (typeof MutationObserver === 'function') {
            const obs = new MutationObserver(function(mutations) {
                for (let i = 0; i < mutations.length; i++) {
                    const added = mutations[i].addedNodes;
                    for (let j = 0; j < added.length; j++) {
                        if (added[j] && added[j].nodeType === 1) {
                            markLinks(added[j]);
                        }
                    }
                }
            });
            obs.observe(document.body, { childList: true, subtree: true });
        }
    }

    function initProductSideBasket() {
        const aside = document.getElementById('scms-side-basket');
        if (!aside) return;

        const fab = document.getElementById('scms-side-basket-fab');
        const fabQty = document.getElementById('scms-sb-fab-qty');
        const subtotalEl = document.getElementById('scms-sb-subtotal');
        const savingsEl = document.getElementById('scms-sb-savings');
        const countEl = document.getElementById('scms-sb-count');
        const itemsEl = document.getElementById('scms-sb-items');
        const toastEl = document.getElementById('scms-sb-toast');

        const metaBase = document.querySelector('meta[name="scms-base-url"]');
        const baseUrl = metaBase ? (metaBase.getAttribute('content') || '').replace(/\/$/, '') : '';
        let toastTimer = null;

        function escHtml(str) {
            return String(str || '').replace(/[&<>"']/g, function(ch) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch] || ch;
            });
        }

        function showToast(msg) {
            if (!toastEl || !msg) return;
            toastEl.textContent = msg;
            toastEl.style.display = 'block';
            clearTimeout(toastTimer);
            toastTimer = setTimeout(function() {
                toastEl.style.display = 'none';
            }, 2600);
        }

        function syncHeaderCartBadges(totalQty) {
            document.querySelectorAll('.cart-badge').forEach(function(badge) {
                badge.textContent = String(totalQty);
            });
        }

        function broadcastBasketSync() {
            try {
                localStorage.setItem('scms_basket_sync', String(Date.now()));
            } catch (e) {}
        }

        window.scmsToggleMobileSideBasket = function(forceState) {
            if (typeof forceState === 'boolean') {
                aside.classList.toggle('is-mobile-open', forceState);
            } else {
                aside.classList.toggle('is-mobile-open');
            }
        };

        function renderBasket(basket) {
            const data = basket || { has_items: false, total_qty: 0, subtotal: 0, savings: 0, currency: { symbol: '$' }, items: [] };
            const hasItems = !!data.has_items && Array.isArray(data.items) && data.items.length > 0;
            const totalQty = parseInt(data.total_qty || 0, 10) || 0;
            const sym = (data.currency && data.currency.symbol) ? data.currency.symbol : '$';

            syncHeaderCartBadges(totalQty);
            if (fabQty) fabQty.textContent = String(totalQty);

            if (!hasItems) {
                aside.classList.remove('is-visible', 'is-mobile-open');
                document.body.classList.remove('scms-has-side-basket');
                if (fab) fab.style.display = 'none';
                if (itemsEl) itemsEl.innerHTML = '';
                if (subtotalEl) subtotalEl.textContent = sym + ' 0.00';
                if (savingsEl) savingsEl.style.display = 'none';
                if (countEl) countEl.textContent = '0';
                return;
            }

            aside.classList.add('is-visible');
            document.body.classList.add('scms-has-side-basket');
            if (subtotalEl) subtotalEl.textContent = sym + ' ' + Number(data.subtotal || 0).toFixed(2);
            if (countEl) countEl.textContent = String(totalQty);
            if (savingsEl) {
                const sav = Number(data.savings || 0);
                if (sav > 0.009) {
                    savingsEl.textContent = 'Ahorrado: ' + sym + ' ' + sav.toFixed(2);
                    savingsEl.style.display = 'block';
                } else {
                    savingsEl.style.display = 'none';
                }
            }

            if (itemsEl) {
                itemsEl.innerHTML = data.items.map(function(item) {
                    const lowStockHtml = (item.track_stock && item.available_stock > 0 && item.available_stock <= 5)
                        ? '<span class="scms-sb-low-badge">Solo quedan ' + Number(item.available_stock) + '</span>'
                        : '';
                    const thumbHtml = item.image_url
                        ? '<img src="' + escHtml(item.image_url) + '" alt="' + escHtml(item.name) + '" loading="lazy">'
                        : '<span style="font-size:11px; color:#9CA3AF; font-weight:600;">Sin img</span>';
                    const priceText = sym + ' ' + Number(item.price || 0).toFixed(2);
                    const qtyVal = parseInt(item.quantity || 1, 10) || 1;
                    const maxReached = item.track_stock && item.available_stock > 0 && qtyVal >= item.available_stock;

                    return (
                        '<div class="scms-sb-item" data-cart-key="' + escHtml(item.key) + '">' +
                            '<a href="' + escHtml(item.product_url) + '" target="_blank" rel="noopener" class="scms-sb-thumb-wrap">' +
                                thumbHtml +
                                lowStockHtml +
                            '</a>' +
                            '<a href="' + escHtml(item.product_url) + '" target="_blank" rel="noopener" class="scms-sb-item-name" title="' + escHtml(item.name) + '">' +
                                escHtml(item.name) +
                            '</a>' +
                            '<div class="scms-sb-item-price">' + escHtml(priceText) + '</div>' +
                            '<div class="scms-sb-stepper">' +
                                '<button type="button" class="scms-sb-step-btn" data-step="-1" data-key="' + escHtml(item.key) + '" data-qty="' + qtyVal + '" data-name="' + escHtml(item.name) + '" aria-label="Disminuir cantidad">-</button>' +
                                '<span class="scms-sb-qty">' + qtyVal + '</span>' +
                                '<button type="button" class="scms-sb-step-btn" data-step="1" data-key="' + escHtml(item.key) + '" data-qty="' + qtyVal + '" data-name="' + escHtml(item.name) + '"' + (maxReached ? ' disabled style="opacity:0.45;cursor:not-allowed;"' : '') + ' aria-label="Aumentar cantidad">+</button>' +
                            '</div>' +
                        '</div>'
                    );
                }).join('');
            }
        }

        async function updateSideItem(key, nextQty) {
            try {
                const res = await fetch(baseUrl + '/cart/side-update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ key: key, quantity: nextQty }),
                });
                const json = await res.json();
                if (res.status === 401 && json && json.login_url) {
                    window.location.href = json.login_url;
                    return;
                }
                if (json && json.ok && json.basket) {
                    renderBasket(json.basket);
                    broadcastBasketSync();
                    if (key && json.notice) showToast(json.notice);
                }
            } catch (err) {
                console.error('Error updating side basket', err);
            }
        }

        // Hydrate initial state from server-rendered JSON attribute
        try {
            const initialRaw = aside.getAttribute('data-initial-basket') || '{}';
            renderBasket(JSON.parse(initialRaw));
        } catch (err) {
            renderBasket({ has_items: false, items: [] });
        }

        // Confirmation modal helpers for removing last unit (qty === 1 -> 0)
        const confirmModal = document.getElementById('scms-sb-confirm-modal');
        const confirmMsgEl = document.getElementById('scms-sb-confirm-msg');
        const confirmOkBtn = document.getElementById('scms-sb-confirm-ok');
        const confirmCancelBtn = document.getElementById('scms-sb-confirm-cancel');
        const confirmCloseBtn = document.getElementById('scms-sb-confirm-close');
        let pendingRemoveKey = '';

        function openRemoveConfirm(key, productName) {
            if (!confirmModal) {
                updateSideItem(key, 0);
                return;
            }
            pendingRemoveKey = key;
            if (confirmMsgEl) {
                confirmMsgEl.textContent = productName
                    ? ('¿Deseas quitar "' + productName + '" de tu cesta?')
                    : '¿Deseas quitar este producto de tu cesta?';
            }
            confirmModal.style.display = 'flex';
            if (confirmCancelBtn) confirmCancelBtn.focus();
        }

        function closeRemoveConfirm() {
            pendingRemoveKey = '';
            if (confirmModal) confirmModal.style.display = 'none';
        }

        if (confirmCancelBtn) confirmCancelBtn.addEventListener('click', closeRemoveConfirm);
        if (confirmCloseBtn) confirmCloseBtn.addEventListener('click', closeRemoveConfirm);
        if (confirmModal) {
            confirmModal.addEventListener('click', function(e) {
                if (e.target === confirmModal) closeRemoveConfirm();
            });
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && confirmModal && confirmModal.style.display === 'flex') {
                closeRemoveConfirm();
            }
        });
        if (confirmOkBtn) {
            confirmOkBtn.addEventListener('click', function() {
                const key = pendingRemoveKey;
                closeRemoveConfirm();
                if (key) updateSideItem(key, 0);
            });
        }

        // Handle +/- stepper buttons inside side basket
        aside.addEventListener('click', function(e) {
            const btn = e.target.closest('.scms-sb-step-btn');
            if (!btn || btn.disabled) return;
            const key = btn.getAttribute('data-key') || '';
            const curQty = parseInt(btn.getAttribute('data-qty') || '1', 10) || 1;
            const step = parseInt(btn.getAttribute('data-step') || '0', 10) || 0;
            const prodName = btn.getAttribute('data-name') || '';
            if (step < 0 && curQty <= 1) {
                openRemoveConfirm(key, prodName);
                return;
            }
            const nextQty = Math.max(0, curQty + step);
            updateSideItem(key, nextQty);
        });

        // Multi-tab real-time basket sync across open product tabs
        window.addEventListener('storage', function(e) {
            if (e.key === 'scms_basket_sync') {
                updateSideItem('', 0);
            }
        });

        // Keep "Comprar ahora" disabled state synchronized with variant stock updates
        const submitBtn = document.getElementById('submit_cart_btn');
        const buyNowBtn = document.getElementById('buy_now_btn');
        if (submitBtn && buyNowBtn && typeof MutationObserver === 'function') {
            const syncBuyNow = function() {
                buyNowBtn.disabled = !!submitBtn.disabled;
            };
            syncBuyNow();
            new MutationObserver(syncBuyNow).observe(submitBtn, { attributes: true, attributeFilter: ['disabled'] });
        }

        // Intercept "Añadir al Carrito" on product pages for live right-side basket
        const addForm = document.querySelector('form[action$="/cart/add"]');
        if (addForm) {
            let lastSubmitter = null;
            addForm.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    lastSubmitter = btn;
                });
            });

            addForm.addEventListener('submit', async function(e) {
                const submitter = e.submitter || lastSubmitter || document.activeElement;
                if (submitter && submitter.getAttribute && submitter.getAttribute('data-buy-now') === '1') {
                    return; // "Comprar ahora" proceeds directly to checkout via normal form POST
                }

                e.preventDefault();
                if (submitBtn && submitBtn.disabled) return;

                const formData = new FormData(addForm);
                formData.set('ajax', '1');
                const origOpacity = submitBtn ? submitBtn.style.opacity : '';
                if (submitBtn) submitBtn.style.opacity = '0.7';

                try {
                    const res = await fetch(addForm.getAttribute('action') || (baseUrl + '/cart/add'), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });
                    const json = await res.json();
                    if (res.status === 401 && json && json.login_url) {
                        window.location.href = json.login_url;
                        return;
                    }
                    if (!res.ok || !json || !json.ok) {
                        showToast((json && json.error) ? json.error : 'No se pudo agregar a la cesta.');
                        return;
                    }
                    if (json.basket) {
                        renderBasket(json.basket);
                        broadcastBasketSync();
                    }
                    showToast(json.notice || 'Agregado a tu cesta.');
                } catch (err) {
                    addForm.submit();
                } finally {
                    if (submitBtn) submitBtn.style.opacity = origOpacity;
                }
            });
        }
    }

    function initProductVariantsAndGallerySync() {
        const payloadEl = document.getElementById('scms-variant-payload');
        const wrap = document.getElementById('scms-variant-selectors');
        if (!payloadEl || !wrap) return;

        let payload = null;
        try {
            payload = JSON.parse(payloadEl.textContent || '{}');
        } catch (e) {
            return;
        }
        const variants = Array.isArray(payload.variants) ? payload.variants : [];
        if (!variants.length) return;

        const maxParts = Math.max(1, parseInt(payload.max_parts || 1, 10) || 1);
        const sym = payload.currency_symbol || '$';
        const threshold = Math.max(1, parseInt(payload.low_stock_threshold || 5, 10) || 5);
        let selectedParts = Array.isArray(payload.initial_variant && payload.initial_variant.parts)
            ? payload.initial_variant.parts.slice(0, maxParts)
            : (variants[0].parts || []).slice(0, maxParts);

        function esc(s) {
            return String(s || '').replace(/[&<>"']/g, function(ch) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch] || ch;
            });
        }

        function normalizeUrlPath(url) {
            if (!url) return '';
            try {
                return new URL(url, window.location.origin).pathname;
            } catch (e) {
                return String(url);
            }
        }

        function resolveBestVariantForAxis(changedAxis, preferStock) {
            const p0 = selectedParts[0] || '';
            const p1 = selectedParts[1] || '';
            const p2 = selectedParts[2] || '';

            const exact = variants.find(function(v) {
                const vp = v.parts || [];
                return vp[0] === p0 && (maxParts < 2 || vp[1] === p1) && (maxParts < 3 || vp[2] === p2);
            });
            if (exact && (!preferStock || parseInt(exact.quantity || 0, 10) > 0)) {
                return exact;
            }

            if (changedAxis === 0 && maxParts >= 2) {
                if (exact && !variants.some(function(v) { return (v.parts || [])[0] === p0 && parseInt(v.quantity || 0, 10) > 0; })) {
                    return exact;
                }
                const byOpt0InStock = variants.find(function(v) {
                    return (v.parts || [])[0] === p0 && parseInt(v.quantity || 0, 10) > 0;
                });
                if (byOpt0InStock) return byOpt0InStock;
                const byOpt0 = variants.find(function(v) { return (v.parts || [])[0] === p0; });
                if (byOpt0) return byOpt0;
            }

            if (changedAxis === 1 && maxParts >= 3) {
                const by01InStock = variants.find(function(v) {
                    const vp = v.parts || [];
                    return vp[0] === p0 && vp[1] === p1 && parseInt(v.quantity || 0, 10) > 0;
                });
                if (by01InStock) return by01InStock;
                const by01 = variants.find(function(v) {
                    const vp = v.parts || [];
                    return vp[0] === p0 && vp[1] === p1;
                });
                if (by01) return by01;
            }

            return exact || variants[0];
        }

        function refreshSelectorStates() {
            for (let axis = 0; axis < maxParts; axis++) {
                const lblEl = document.getElementById('scms_axis_label_' + axis);
                if (lblEl) lblEl.textContent = selectedParts[axis] || '';
            }
            wrap.querySelectorAll('.scms-var-btn').forEach(function(btn) {
                const ax = parseInt(btn.getAttribute('data-axis') || '0', 10);
                const val = btn.getAttribute('data-value') || '';
                const isSelected = (selectedParts[ax] === val);
                btn.classList.toggle('is-active', isSelected);

                let hasAvailable = false;
                if (ax === 0) {
                    hasAvailable = variants.some(function(v) {
                        return (v.parts || [])[0] === val && parseInt(v.quantity || 0, 10) > 0;
                    });
                } else if (ax === 1) {
                    hasAvailable = variants.some(function(v) {
                        const vp = v.parts || [];
                        return vp[0] === selectedParts[0] && vp[1] === val && parseInt(v.quantity || 0, 10) > 0;
                    });
                } else {
                    hasAvailable = variants.some(function(v) {
                        const vp = v.parts || [];
                        return vp[0] === selectedParts[0] && vp[1] === selectedParts[1] && vp[2] === val && parseInt(v.quantity || 0, 10) > 0;
                    });
                }
                btn.classList.toggle('is-oos', !hasAvailable);
            });
        }

        function applyVariantRich(v, opts) {
            if (!v) return;
            opts = opts || {};

            if (Array.isArray(v.parts)) {
                selectedParts = v.parts.slice(0, maxParts);
            }
            refreshSelectorStates();

            const vidInput = document.getElementById('variant_id_input');
            if (vidInput) vidInput.value = v.id;

            const priceEl = document.getElementById('display_price');
            if (priceEl) {
                priceEl.textContent = sym + Number(v.price || 0).toFixed(2);
            }

            const cmpEl = document.getElementById('display_compare_price');
            const discEl = document.getElementById('display_discount_badge');
            const cmpPrice = v.compare_price !== null && v.compare_price !== undefined ? Number(v.compare_price) : 0;
            const curPrice = Number(v.price || 0);
            if (cmpEl) {
                if (cmpPrice > curPrice && cmpPrice > 0) {
                    const prefix = cmpEl.getAttribute('data-prefix') || '';
                    cmpEl.textContent = prefix + sym + cmpPrice.toFixed(2);
                    cmpEl.style.display = 'inline';
                } else {
                    cmpEl.style.display = 'none';
                }
            }
            if (discEl) {
                const pct = parseInt(v.discount_pct || 0, 10);
                if (pct > 0) {
                    discEl.textContent = '-' + pct + '% dto.';
                    discEl.style.display = 'inline-flex';
                } else {
                    discEl.style.display = 'none';
                }
            }

            const qty = parseInt(v.quantity || 0, 10) || 0;
            const stockBadge = document.getElementById('stock_badge');
            const submitBtn = document.getElementById('submit_cart_btn');
            const qtyInput = document.getElementById('quantity_input');

            if (qtyInput) {
                qtyInput.max = Math.max(1, qty);
                if (parseInt(qtyInput.value || '1', 10) > qty && qty > 0) {
                    qtyInput.value = String(qty);
                }
            }

            if (submitBtn) {
                submitBtn.disabled = qty <= 0;
            }

            if (stockBadge) {
                const mode = stockBadge.getAttribute('data-stock-mode') || 'text';
                if (qty <= 0) {
                    stockBadge.textContent = mode === 'pill' ? 'AGOTADO' : 'Agotado';
                    if (mode === 'pill') {
                        stockBadge.style.background = '#FEE2E2';
                        stockBadge.style.color = '#991B1B';
                    } else {
                        stockBadge.style.color = '#DC2626';
                    }
                } else if (qty <= threshold) {
                    stockBadge.textContent = (mode === 'pill' ? 'ÚLTIMAS ' : 'Últimas ') + qty + (mode === 'pill' ? ' UNIDADES EN STOCK' : ' unidades en stock');
                    if (mode === 'pill') {
                        stockBadge.style.background = '#FEF3C7';
                        stockBadge.style.color = '#92400E';
                    } else {
                        stockBadge.style.color = '#B95000';
                    }
                } else {
                    stockBadge.textContent = (mode === 'pill' ? 'EN STOCK (' : 'En stock (') + qty + (mode === 'pill' ? ' DISPONIBLES)' : ' disponibles)');
                    if (mode === 'pill') {
                        stockBadge.style.background = '#DCFCE7';
                        stockBadge.style.color = '#166534';
                    } else {
                        stockBadge.style.color = 'var(--color-primary, #008060)';
                    }
                }
            }

            const shortEl = document.getElementById('product_short_description');
            if (shortEl) {
                const sTxt = (v.short_description || '').trim();
                shortEl.textContent = sTxt;
                shortEl.style.display = sTxt ? 'block' : 'none';
            }

            const descWrap = document.getElementById('product_description_wrap');
            const descBody = document.getElementById('product_description_body');
            if (descWrap && descBody) {
                const dTxt = (v.description || '').trim();
                if (dTxt) {
                    descBody.innerHTML = esc(dTxt).replace(/\r\n|\r|\n/g, '<br>');
                    descWrap.style.display = 'block';
                } else {
                    descBody.innerHTML = '';
                    descWrap.style.display = 'none';
                }
            }

            const specsWrap = document.getElementById('scms_product_specs_wrap');
            const specsList = document.getElementById('scms_product_specs_list');
            if (specsWrap && specsList) {
                const sp = Array.isArray(v.specs) ? v.specs : [];
                if (sp.length) {
                    specsList.innerHTML = sp.map(function(row) {
                        return '<div class="scms-spec-row"><dt>' + esc(row.label) + '</dt><dd>' + esc(row.value) + '</dd></div>';
                    }).join('');
                    specsWrap.style.display = 'block';
                } else {
                    specsList.innerHTML = '';
                    specsWrap.style.display = 'none';
                }
            }

            if (!opts.skipGallerySync && v.image_url && typeof window.syncProductGalleryActive === 'function') {
                window.syncProductGalleryActive(v.image_url);
            }
        }

        wrap.addEventListener('click', function(e) {
            const btn = e.target.closest('.scms-var-btn');
            if (!btn) return;
            const axis = parseInt(btn.getAttribute('data-axis') || '0', 10);
            const val = btn.getAttribute('data-value') || '';
            selectedParts[axis] = val;
            const matched = resolveBestVariantForAxis(axis, axis < maxParts - 1);
            applyVariantRich(matched, { skipGallerySync: false });
        });

        window.addEventListener('scms:gallery-image-changed', function(e) {
            const detail = e && e.detail ? e.detail : {};
            const targetPath = normalizeUrlPath(detail.src || '');
            if (!targetPath) return;

            const matchingVars = variants.filter(function(v) {
                return v.own_image_url && normalizeUrlPath(v.own_image_url) === targetPath;
            });
            if (!matchingVars.length) return;

            if (maxParts === 1) {
                applyVariantRich(matchingVars[0], { skipGallerySync: true });
                return;
            }

            const ownersOpt0 = [];
            matchingVars.forEach(function(v) {
                const p0 = (v.parts || [])[0] || '';
                if (p0 && ownersOpt0.indexOf(p0) === -1) ownersOpt0.push(p0);
            });
            if (ownersOpt0.length === 1 && ownersOpt0[0] !== selectedParts[0]) {
                selectedParts[0] = ownersOpt0[0];
                const matched = resolveBestVariantForAxis(0, true);
                applyVariantRich(matched, { skipGallerySync: true });
            }
        });

        if (payload.initial_variant) {
            applyVariantRich(payload.initial_variant, { skipGallerySync: false });
        }
    }

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? (meta.getAttribute('content') || '') : '';
    }

    function injectCsrfIntoForm(form) {
        if (!form || !form.tagName || form.tagName.toUpperCase() !== 'FORM') return;
        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (method !== 'POST') return;
        const token = getCsrfToken();
        if (!token) return;
        let inp = form.querySelector('input[name="_csrf_token"]');
        if (!inp) {
            inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = '_csrf_token';
            form.appendChild(inp);
        }
        inp.value = token;
    }

    function injectCsrfIntoAllForms() {
        document.querySelectorAll('form').forEach(injectCsrfIntoForm);
    }

    document.addEventListener('submit', function(e) {
        if (e.target && e.target.tagName === 'FORM') {
            injectCsrfIntoForm(e.target);
        }
    }, true);

    if (typeof window.fetch === 'function') {
        const origFetch = window.fetch.bind(window);
        window.fetch = function(input, init) {
            const opts = Object.assign({}, init || {});
            const method = (opts.method || (input && input.method) || 'GET').toUpperCase();
            const token = getCsrfToken();
            if (method === 'POST' && token) {
                const urlStr = typeof input === 'string' ? input : (input && input.url ? input.url : '');
                const sameOrigin = !urlStr || urlStr.startsWith('/') || urlStr.startsWith(window.location.origin);
                if (sameOrigin) {
                    const headers = new Headers(opts.headers || (input && input.headers) || {});
                    if (!headers.has('X-CSRF-Token')) {
                        headers.set('X-CSRF-Token', token);
                    }
                    opts.headers = headers;
                }
            }
            return origFetch(input, opts);
        };
    }
})();
