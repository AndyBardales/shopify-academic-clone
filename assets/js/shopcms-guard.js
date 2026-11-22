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

        const action = (form.action || '').toLowerCase();
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
    });
})();
