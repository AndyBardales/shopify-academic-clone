<?php \ShopCMS\Core\ThemeManager::renderHeader(get_defined_vars()); ?>

<main class="container" style="padding-top: 44px; padding-bottom: 84px; max-width: 880px;">
    <?php if (empty($page)): ?>
        <div style="background: var(--color-surface, #FFFFFF); border: 1px solid var(--color-border, #E5E7EB); border-radius: 12px; padding: 48px 28px; text-align: center;">
            <h1 style="font-size: 26px; font-weight: 700; margin-bottom: 10px;">Página no encontrada</h1>
            <p style="color: var(--color-text-light, #6B7280); font-size: 14px; margin-bottom: 22px;">El enlace que intentaste visitar no existe o aún no ha sido publicado.</p>
            <a href="<?= $baseUrl ?>/" class="btn-store btn-store-primary">Volver a la Tienda</a>
        </div>
    <?php else: ?>
        <nav style="font-size: 12.5px; color: var(--color-text-light, #6B7280); margin-bottom: 16px; display: flex; gap: 8px; align-items: center;">
            <a href="<?= $baseUrl ?>/" style="color: var(--color-primary, #008060); text-decoration: none; font-weight: 600;">Inicio</a>
            <span>/</span>
            <span><?= htmlspecialchars($page['title']) ?></span>
        </nav>

        <article style="background: var(--color-surface, #FFFFFF); border: 1px solid var(--color-border, #E5E7EB); border-radius: 14px; padding: 36px 32px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
            <header style="border-bottom: 1px solid var(--color-border, #E5E7EB); padding-bottom: 18px; margin-bottom: 24px;">
                <h1 style="font-size: 30px; font-weight: 800; line-height: 1.25; margin: 0 0 8px; color: var(--color-text, #111827);">
                    <?= htmlspecialchars($page['title']) ?>
                </h1>
                <?php if (!empty($page['updated_at'])): ?>
                    <div style="font-size: 12px; color: var(--color-text-light, #6B7280);">
                        Última actualización: <?= date('d/m/Y', strtotime((string)$page['updated_at'])) ?>
                    </div>
                <?php endif; ?>
            </header>

            <div style="font-size: 15px; line-height: 1.8; color: var(--color-text, #1F2937);">
                <?= nl2br(htmlspecialchars((string)$page['content'])) ?>
            </div>
        </article>

        <div style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <a href="<?= $baseUrl ?>/catalog" class="btn-store btn-store-secondary" style="font-size: 13px; padding: 9px 18px;">Explorar Catálogo</a>
            <a href="<?= $baseUrl ?>/" style="color: var(--color-text-light, #6B7280); text-decoration: none; font-size: 13px; font-weight: 600;">Volver al inicio</a>
        </div>
    <?php endif; ?>
</main>

<?php \ShopCMS\Core\ThemeManager::renderFooter(get_defined_vars()); ?>
