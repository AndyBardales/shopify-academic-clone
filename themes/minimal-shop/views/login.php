<?php \ShopCMS\Core\ThemeManager::renderHeader(get_defined_vars()); ?>

<main class="container" style="max-width: 480px; margin: 60px auto 100px;">
    <div style="background: #FFF; padding: 36px; border-radius: 12px; border: 1px solid var(--color-border); box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
        
        <div style="text-align: center; margin-bottom: 28px;">
            <div style="width: 48px; height: 48px; margin: 0 auto 14px; background: #F1F2F4; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #202223;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <h1 style="font-size: 24px; font-weight: 700; color: var(--color-text); margin: 0 0 8px;">Iniciar Sesión</h1>
            <p style="font-size: 13px; color: var(--color-text-light); margin: 0;">Accede a tu cuenta de cliente para gestionar pedidos y calificar compras.</p>
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

        <form method="POST" action="<?= $baseUrl ?>/login">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect ?? '') ?>">

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text); margin-bottom: 6px;">Correo Electrónico *</label>
                <input type="email" name="email" required placeholder="tu-correo@ejemplo.com" style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 22px;">
                <label for="login-password" style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text); margin-bottom: 6px;">Contraseña *</label>
                <div style="position: relative;">
                    <input type="password" id="login-password" name="password" required placeholder="••••••••" style="width: 100%; padding: 10px 42px 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
                    <button type="button" onclick="toggleLoginPass(this)" aria-label="Mostrar u ocultar contraseña" title="Mostrar / Ocultar contraseña" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6B7280; cursor: pointer; display: inline-flex; padding: 4px;">
                        <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-store btn-store-primary" style="width: 100%; padding: 12px; font-size: 14px; font-weight: 600;">
                Ingresar a Mi Cuenta
            </button>
        </form>

        <script>
        function toggleLoginPass(btn) {
            var inp = document.getElementById('login-password');
            if (!inp) return;
            var show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            var o = btn.querySelector('.eye-open');
            var c = btn.querySelector('.eye-closed');
            if (o) o.style.display = show ? 'none' : '';
            if (c) c.style.display = show ? '' : 'none';
        }
        </script>

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--color-border); text-align: center;">
            <p style="font-size: 13px; color: var(--color-text-light); margin: 0 0 8px;">¿Aún no tienes cuenta?</p>
            <a href="<?= $baseUrl ?>/register<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>" style="color: #008060; font-size: 14px; font-weight: 600; text-decoration: none;">
                Crear una cuenta nueva
            </a>
        </div>
    </div>
</main>

<?php \ShopCMS\Core\ThemeManager::renderFooter(get_defined_vars()); ?>
