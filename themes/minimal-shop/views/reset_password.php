<?php \ShopCMS\Core\ThemeManager::renderHeader(get_defined_vars()); ?>

<main class="container" style="max-width: 480px; margin: 60px auto 100px;">
    <div style="background: var(--color-surface, #FFF); padding: 34px; border-radius: 12px; border: 1px solid var(--color-border); box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
        <div style="text-align: center; margin-bottom: 24px;">
            <h1 style="font-size: 22px; font-weight: 700; color: var(--color-text); margin: 0 0 8px;">Restablecer Contraseña</h1>
            <p style="font-size: 13px; color: var(--color-text-light); margin: 0;">Define una nueva contraseña segura para tu cuenta.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 18px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($tokenValid)): ?>
            <form method="POST" action="<?= $baseUrl ?>/reset-password">
                <?= \ShopCMS\Core\Security::csrfField() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

                <div style="margin-bottom: 16px;">
                    <label for="reset-pass" style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text); margin-bottom: 6px;">Nueva Contraseña *</label>
                    <input type="password" id="reset-pass" name="password" required minlength="8" placeholder="Mín. 8 caracteres, mayús., minús., número y símbolo" style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="reset-pass-confirm" style="display: block; font-size: 13px; font-weight: 600; color: var(--color-text); margin-bottom: 6px;">Confirmar Contraseña *</label>
                    <input type="password" id="reset-pass-confirm" name="password_confirm" required minlength="8" placeholder="Repite la nueva contraseña" style="width: 100%; padding: 10px 14px; border: 1px solid var(--color-border); border-radius: 8px; font-size: 14px;">
                </div>

                <button type="submit" class="btn-store btn-store-primary" style="width: 100%; padding: 12px; font-size: 14px; font-weight: 600;">
                    Guardar Nueva Contraseña
                </button>
            </form>
        <?php else: ?>
            <div style="text-align: center; padding: 10px 0;">
                <a href="<?= $baseUrl ?>/forgot-password" class="btn-store btn-store-primary" style="display: inline-block; padding: 10px 18px; text-decoration: none;">
                    Solicitar nuevo enlace
                </a>
            </div>
        <?php endif; ?>

        <div style="margin-top: 22px; padding-top: 16px; border-top: 1px solid var(--color-border); text-align: center;">
            <a href="<?= $baseUrl ?>/login" style="color: var(--color-primary, #008060); font-size: 13.5px; font-weight: 600; text-decoration: none;">
                &larr; Volver a Iniciar Sesión
            </a>
        </div>
    </div>
</main>

<?php \ShopCMS\Core\ThemeManager::renderFooter(get_defined_vars()); ?>
