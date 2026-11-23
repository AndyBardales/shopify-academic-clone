<?php \ShopCMS\Core\ThemeManager::renderHeader(get_defined_vars()); ?>

<main class="container" style="max-width: 480px; margin: 60px auto 100px;">
    <div style="background: var(--color-surface, #FFF); padding: 34px; border-radius: 12px; border: 1px solid var(--color-border); box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 48px; height: 48px; margin: 0 auto 14px; background: #F1F2F4; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--color-primary, #008060);">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h1 style="font-size: 22px; font-weight: 700; color: var(--color-text); margin: 0 0 8px;">Recuperar Contraseña</h1>
            <p style="font-size: 13px; color: var(--color-text-light); margin: 0;">Ingresa tu correo registrado y generaremos un enlace seguro válido por 30 minutos.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 18px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($notice)): ?>
            <div style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 12px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; line-height: 1.5;">
                <?= htmlspecialchars($notice) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($devResetLink)): ?>
            <div style="background: #EFF6FF; border: 1px solid #BFDBFE; color: #1E3A8A; padding: 12px 14px; border-radius: 8px; font-size: 12.5px; margin-bottom: 18px; line-height: 1.5;">
                <strong style="display: block; margin-bottom: 6px;">Enlace directo de prueba (entorno local):</strong>
                <a href="<?= htmlspecialchars($devResetLink) ?>" style="color: #1D4ED8; font-weight: 700; word-break: break-all;">
                    <?= htmlspecialchars($devResetLink) ?>
                </a>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $baseUrl ?>/forgot-password">
            <?= \ShopCMS\Core\Security::csrfField() ?>
            <div style="margin-bottom: 20px;">
                <label for="forgot-email" style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text); margin-bottom: 6px;">Correo Electrónico *</label>
                <input type="email" id="forgot-email" name="email" required placeholder="tu-correo@ejemplo.com" style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
            </div>
            <button type="submit" class="btn-store btn-store-primary" style="width: 100%; padding: 12px; font-size: 14px; font-weight: 600;">
                Enviar enlace de recuperación
            </button>
        </form>

        <div style="margin-top: 22px; padding-top: 16px; border-top: 1px solid var(--color-border); text-align: center;">
            <a href="<?= $baseUrl ?>/login" style="color: var(--color-primary, #008060); font-size: 13.5px; font-weight: 600; text-decoration: none;">
                &larr; Volver a Iniciar Sesión
            </a>
        </div>
    </div>
</main>

<?php \ShopCMS\Core\ThemeManager::renderFooter(get_defined_vars()); ?>
