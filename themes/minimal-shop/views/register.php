<?php \ShopCMS\Core\ThemeManager::renderHeader(get_defined_vars()); ?>

<main class="container" style="max-width: 520px; margin: 50px auto 90px;">
    <div style="background: #FFF; padding: 36px; border-radius: 12px; border: 1px solid var(--color-border); box-shadow: 0 4px 16px rgba(0,0,0,0.04);">

        <div style="text-align: center; margin-bottom: 26px;">
            <div style="width: 48px; height: 48px; margin: 0 auto 14px; background: #E3F1DF; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #008060;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <h1 style="font-size: 24px; font-weight: 700; color: var(--color-text); margin: 0 0 8px;">Crear Cuenta de Cliente</h1>
            <p style="font-size: 13px; color: var(--color-text-light); margin: 0;">Regístrate para agilizar tus compras y realizar seguimiento a tus pedidos.</p>
        </div>

        <?php if (!empty($notice)): ?>
            <div style="background: #EFF6FF; border: 1px solid #BFDBFE; color: #1E40AF; padding: 12px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 8px; line-height: 1.4;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 1px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <span><?= htmlspecialchars($notice) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 8px; line-height: 1.4;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 1px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form id="register-form" method="POST" action="<?= $baseUrl ?>/register" onsubmit="return validateRegisterPassword();">
            <?= \ShopCMS\Core\Security::csrfField() ?>
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect ?? '') ?>">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text); margin-bottom: 6px;">Nombre Completo *</label>
                <input type="text" name="name" required placeholder="Carlos Mendoza" style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text); margin-bottom: 6px;">Correo Electrónico *</label>
                <input type="email" name="email" required placeholder="carlos@ejemplo.com" style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 14px;">
                <label for="reg-password" style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text); margin-bottom: 6px;">Contraseña *</label>
                <div style="position: relative;">
                    <input type="password" id="reg-password" name="password" required minlength="8" placeholder="Mínimo 8 caracteres seguros" oninput="evaluatePasswordRules()" style="width: 100%; padding: 10px 42px 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
                    <button type="button" onclick="togglePassVisibility('reg-password', this)" aria-label="Mostrar u ocultar contraseña" title="Mostrar / Ocultar contraseña" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6B7280; cursor: pointer; display: inline-flex; padding: 4px;">
                        <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>

                <!-- BARRA DE FORTALEZA Y REGLAS EN VIVO -->
                <div style="margin-top: 8px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; margin-bottom: 6px;">
                        <span style="color: #4B5563; font-weight: 600;">Seguridad de contraseña:</span>
                        <strong id="pass-strength-label" style="color: #6B7280;">Sin evaluar</strong>
                    </div>
                    <div style="height: 5px; background: #E5E7EB; border-radius: 999px; overflow: hidden; margin-bottom: 8px;">
                        <div id="pass-strength-bar" style="height: 100%; width: 0%; background: #DC2626; transition: width 0.2s, background 0.2s;"></div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px 10px; font-size: 11.5px; color: #6B7280;">
                        <div id="rule-len">Mínimo 8 caracteres</div>
                        <div id="rule-upper">Una mayúscula (A-Z)</div>
                        <div id="rule-lower">Una minúscula (a-z)</div>
                        <div id="rule-num">Un número (0-9)</div>
                        <div id="rule-sym" style="grid-column: 1 / -1;">Un símbolo especial (ej. ! @ # $ % . _ -)</div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label for="reg-password-confirm" style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text); margin-bottom: 6px;">Confirmar Contraseña *</label>
                <div style="position: relative;">
                    <input type="password" id="reg-password-confirm" name="password_confirm" required placeholder="Repite tu contraseña" oninput="evaluatePasswordRules()" style="width: 100%; padding: 10px 42px 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
                    <button type="button" onclick="togglePassVisibility('reg-password-confirm', this)" aria-label="Mostrar u ocultar confirmación" title="Mostrar / Ocultar contraseña" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6B7280; cursor: pointer; display: inline-flex; padding: 4px;">
                        <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
                <div id="pass-match-msg" style="font-size: 11.5px; margin-top: 5px; display: none;"></div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text); margin-bottom: 6px;">Teléfono / WhatsApp</label>
                <input type="tel" name="phone" placeholder="+51 987 654 321" style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 22px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text); margin-bottom: 6px;">Dirección de Entrega</label>
                <textarea name="address" rows="2" placeholder="Av. Los Pinos 450, Miraflores, Lima" style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px; font-family: inherit;"></textarea>
            </div>

            <button type="submit" class="btn-store btn-store-primary" style="width: 100%; padding: 12px; font-size: 14px; font-weight: 600;">
                Registrarme y Continuar
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--color-border); text-align: center;">
            <p style="font-size: 13px; color: var(--color-text-light); margin: 0 0 8px;">¿Ya tienes una cuenta creada?</p>
            <a href="<?= $baseUrl ?>/login<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>" style="color: #008060; font-size: 14px; font-weight: 600; text-decoration: none;">
                Iniciar Sesión
            </a>
        </div>
    </div>
</main>

<script>
function togglePassVisibility(inputId, btn) {
    var inp = document.getElementById(inputId);
    if (!inp) return;
    var show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    var openIcon = btn.querySelector('.eye-open');
    var closedIcon = btn.querySelector('.eye-closed');
    if (openIcon) openIcon.style.display = show ? 'none' : '';
    if (closedIcon) closedIcon.style.display = show ? '' : 'none';
}

function setRuleState(elId, ok) {
    var el = document.getElementById(elId);
    if (!el) return;
    el.style.color = ok ? '#008060' : '#6B7280';
    el.style.fontWeight = ok ? '700' : '400';
}

function evaluatePasswordRules() {
    var p = document.getElementById('reg-password').value || '';
    var c = document.getElementById('reg-password-confirm').value || '';

    var rules = {
        len: p.length >= 8,
        upper: /[A-Z]/.test(p),
        lower: /[a-z]/.test(p),
        num: /[0-9]/.test(p),
        sym: /[^A-Za-z0-9]/.test(p)
    };

    setRuleState('rule-len', rules.len);
    setRuleState('rule-upper', rules.upper);
    setRuleState('rule-lower', rules.lower);
    setRuleState('rule-num', rules.num);
    setRuleState('rule-sym', rules.sym);

    var score = Object.values(rules).filter(Boolean).length;
    var bar = document.getElementById('pass-strength-bar');
    var lbl = document.getElementById('pass-strength-label');
    bar.style.width = (score * 20) + '%';

    if (p.length === 0) {
        lbl.textContent = 'Sin evaluar';
        lbl.style.color = '#6B7280';
    } else if (score <= 2) {
        bar.style.background = '#DC2626';
        lbl.textContent = 'Débil';
        lbl.style.color = '#DC2626';
    } else if (score <= 4) {
        bar.style.background = '#D97706';
        lbl.textContent = 'Media (completa los requisitos)';
        lbl.style.color = '#D97706';
    } else {
        bar.style.background = '#008060';
        lbl.textContent = 'Segura';
        lbl.style.color = '#008060';
    }

    var matchMsg = document.getElementById('pass-match-msg');
    if (c.length > 0) {
        matchMsg.style.display = 'block';
        if (p === c) {
            matchMsg.style.color = '#008060';
            matchMsg.textContent = 'Las contraseñas coinciden.';
        } else {
            matchMsg.style.color = '#DC2626';
            matchMsg.textContent = 'Las contraseñas aún no coinciden.';
        }
    } else {
        matchMsg.style.display = 'none';
    }

    return score === 5 && p === c;
}

function validateRegisterPassword() {
    var p = document.getElementById('reg-password').value || '';
    var c = document.getElementById('reg-password-confirm').value || '';
    if (!(p.length >= 8 && /[A-Z]/.test(p) && /[a-z]/.test(p) && /[0-9]/.test(p) && /[^A-Za-z0-9]/.test(p))) {
        evaluatePasswordRules();
        document.getElementById('reg-password').focus();
        return false;
    }
    if (p !== c) {
        evaluatePasswordRules();
        document.getElementById('reg-password-confirm').focus();
        return false;
    }
    return true;
}
</script>

<?php \ShopCMS\Core\ThemeManager::renderFooter(get_defined_vars()); ?>
