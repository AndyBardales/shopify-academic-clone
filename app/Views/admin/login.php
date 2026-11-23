<?php
$baseUrl = defined('APP_URL') ? APP_URL : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= \ShopCMS\Core\Security::csrfMetaTag() ?>
    <title>Iniciar Sesión — ShopCMS Polaris</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/polaris.css">
    <style>
        body {
            align-items: center;
            justify-content: center;
            background: #F6F6F7;
            min-height: 100vh;
            display: flex;
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: #FFF;
            border-radius: 12px;
            border: 1px solid var(--color-border);
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            padding: 32px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 24px;
        }
        .login-logo svg {
            width: 44px;
            height: 44px;
            fill: var(--color-shopify-green);
        }
        .login-logo h1 {
            font-size: 20px;
            font-weight: 700;
            color: var(--color-cta-dark);
            margin-top: 8px;
        }
        .btn-loading {
            opacity: 0.85;
            cursor: wait;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="login-page">

<div class="login-card">
    <div class="login-logo">
        <svg viewBox="0 0 24 24">
            <path d="M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm7 17H5V8h14v12z"/>
        </svg>
        <h1>ShopCMS Admin</h1>
        <p style="font-size: 13px; color: var(--color-text-secondary); margin-top: 4px;">Inicia sesión con tu cuenta de administrador</p>
    </div>

    <?php if (!empty($error)): ?>
        <div id="admin-login-alert" class="alert-message error" style="margin-bottom: 16px; background: #FEF2F2; color: #991B1B; border: 1px solid #F87171; padding: 10px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span><?= htmlspecialchars($error) ?><?php if (!empty($lockoutSeconds) && $lockoutSeconds > 0): ?> (<strong id="lockout-timer"><?= \ShopCMS\Core\Security::formatWaitTime((int)$lockoutSeconds) ?></strong>)<?php endif; ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $baseUrl ?>/admin/login" id="admin-login-form">
        <?= \ShopCMS\Core\Security::csrfField() ?>
        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="admin@ejemplo.com" required autofocus autocomplete="username" <?= !empty($lockoutSeconds) && $lockoutSeconds > 0 ? 'disabled' : '' ?>>
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password" <?= !empty($lockoutSeconds) && $lockoutSeconds > 0 ? 'disabled' : '' ?>>
        </div>

        <button type="submit" id="login-submit-btn" class="btn btn-primary" style="width: 100%; margin-top: 14px; padding: 10px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 6px;" <?= !empty($lockoutSeconds) && $lockoutSeconds > 0 ? 'disabled' : '' ?>>
            <span><?= !empty($lockoutSeconds) && $lockoutSeconds > 0 ? 'Acceso Bloqueado Temporalmente' : 'Iniciar Sesión' ?></span>
        </button>
    </form>
</div>

<script>
(function() {
    var rem = <?= (int)($lockoutSeconds ?? 0) ?>;
    if (rem > 0) {
        var timerEl = document.getElementById('lockout-timer');
        var btn = document.getElementById('login-submit-btn');
        var iv = setInterval(function() {
            rem = Math.max(0, rem - 1);
            var m = String(Math.floor(rem / 60)).padStart(2, '0');
            var s = String(rem % 60).padStart(2, '0');
            if (timerEl) timerEl.textContent = m + ':' + s;
            if (rem <= 0) {
                clearInterval(iv);
                document.querySelectorAll('#admin-login-form input, #admin-login-form button').forEach(function(el) { el.disabled = false; });
                if (btn) btn.innerHTML = '<span>Iniciar Sesión</span>';
            }
        }, 1000);
    }
})();
document.getElementById('admin-login-form').addEventListener('submit', function(e) {
    const btn = document.getElementById('login-submit-btn');
    if (btn) {
        btn.classList.add('btn-loading');
        btn.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 0.8s linear infinite;">
                <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                <path d="M12 2a10 10 0 0 1 10 10"></path>
            </svg>
            <span>Verificando...</span>
        `;
    }
});
</script>
</body>
</html>

