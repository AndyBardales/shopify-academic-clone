<div class="page-header">
    <div>
        <h1 class="page-title">Páginas Legales CMS &amp; Boletín (Newsletter)</h1>
        <p class="page-subtitle">Crea páginas informativas (/page/slug) visibles en el footer y administra tus suscriptores guardados en base de datos.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?= $baseUrl ?>/admin/pages/seed-legal" class="btn btn-secondary">
            + Generar Plantillas Legales Recomendadas
        </a>
        <a href="<?= $baseUrl ?>/admin/newsletter/export" class="btn btn-primary">
            Exportar Suscriptores (CSV)
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.35fr 1fr; gap: 20px; align-items: start;">
    <!-- COLUMNA IZQUIERDA: GESTOR DE PÁGINAS -->
    <div>
        <div class="polaris-card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2 class="card-title"><?= !empty($editingPage) ? 'Editar Página: ' . htmlspecialchars($editingPage['title']) : 'Nueva Página Informativa / Legal' ?></h2>
                <?php if (!empty($editingPage)): ?>
                    <a href="<?= $baseUrl ?>/admin/pages" class="btn btn-secondary btn-sm">Cancelar edición</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= !empty($editingPage) ? ($baseUrl . '/admin/pages/update/' . (int)$editingPage['id']) : ($baseUrl . '/admin/pages/store') ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="page_title">Título de la Página *</label>
                            <input type="text" id="page_title" name="title" class="form-control" required placeholder="Ej: Términos y Condiciones" value="<?= htmlspecialchars($editingPage['title'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="page_slug">URL Slug (opcional)</label>
                            <input type="text" id="page_slug" name="slug" class="form-control" placeholder="terminos-y-condiciones" value="<?= htmlspecialchars($editingPage['slug'] ?? '') ?>">
                            <small style="color: var(--color-text-secondary); font-size: 11px;">Ruta pública: <code>/page/{slug}</code></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="page_content">Contenido *</label>
                        <textarea id="page_content" name="content" class="form-control" rows="7" required placeholder="Escribe aquí las políticas, términos o información institucional de tu tienda..."><?= htmlspecialchars($editingPage['content'] ?? '') ?></textarea>
                    </div>

                    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 16px;">
                        <label style="display: inline-flex; align-items: center; gap: 7px; font-size: 13px; cursor: pointer;">
                            <input type="checkbox" name="is_published" value="1" <?= empty($editingPage) || !empty($editingPage['is_published']) ? 'checked' : '' ?> style="accent-color: #008060;">
                            <span>Publicada (visible al público)</span>
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 7px; font-size: 13px; cursor: pointer;">
                            <input type="checkbox" name="show_in_footer" value="1" <?= empty($editingPage) || !empty($editingPage['show_in_footer']) ? 'checked' : '' ?> style="accent-color: #008060;">
                            <span>Mostrar enlace automático en el Footer</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary"><?= !empty($editingPage) ? 'Actualizar Página' : 'Guardar Página' ?></button>
                </form>
            </div>
        </div>

        <div class="polaris-card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2 class="card-title">Páginas Creadas</h2>
                <span class="badge badge-info"><?= count($pages ?? []) ?> página(s)</span>
            </div>
            <div style="overflow-x: auto;">
                <table class="polaris-table">
                    <thead>
                        <tr>
                            <th>Título / Ruta</th>
                            <th>Estado</th>
                            <th>Footer</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 24px; color: var(--color-text-secondary); font-size: 13px;">
                                    Aún no hay páginas creadas. Puedes crear una arriba o usar <strong>"+ Generar Plantillas Legales Recomendadas"</strong>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pages as $pg): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($pg['title']) ?></strong>
                                        <div style="font-size: 11.5px; color: var(--color-text-secondary);">
                                            <a href="<?= $baseUrl ?>/page/<?= htmlspecialchars($pg['slug']) ?>" target="_blank" style="color: #008060; text-decoration: none;">
                                                /page/<?= htmlspecialchars($pg['slug']) ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($pg['is_published'])): ?>
                                            <span class="badge badge-success">Publicada</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #E5E7EB; color: #374151;">Borrador</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 12px;">
                                        <?= !empty($pg['show_in_footer']) ? 'Sí' : 'Oculta' ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="<?= $baseUrl ?>/admin/pages?edit=<?= (int)$pg['id'] ?>" class="btn btn-secondary btn-sm">Editar</a>
                                        <a href="<?= $baseUrl ?>/admin/pages/delete/<?= (int)$pg['id'] ?>" class="btn btn-secondary btn-sm" style="color: #DC2626;" onclick="return confirm('¿Eliminar esta página?');">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- COLUMNA DERECHA: SUSCRIPTORES DEL NEWSLETTER -->
    <div>
        <div class="polaris-card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 class="card-title">Suscriptores del Boletín</h2>
                    <div style="font-size: 12px; color: var(--color-text-secondary);">Correos capturados desde el footer de la tienda.</div>
                </div>
                <span class="badge badge-success"><?= count($subscribers ?? []) ?></span>
            </div>
            <div style="overflow-x: auto;">
                <table class="polaris-table">
                    <thead>
                        <tr>
                            <th>Correo</th>
                            <th>Fecha</th>
                            <th style="text-align: right;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subscribers)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 24px; color: var(--color-text-secondary); font-size: 13px;">
                                    Aún no hay correos suscritos desde el footer.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subscribers as $sub): ?>
                                <tr>
                                    <td>
                                        <strong style="font-size: 13px;"><?= htmlspecialchars($sub['email']) ?></strong>
                                        <div style="font-size: 11px; color: var(--color-text-secondary);">Origen: <?= htmlspecialchars($sub['source'] ?? 'footer') ?></div>
                                    </td>
                                    <td style="font-size: 12px; color: var(--color-text-secondary);">
                                        <?= date('d/m/Y H:i', strtotime((string)$sub['created_at'])) ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="<?= $baseUrl ?>/admin/newsletter/delete/<?= (int)$sub['id'] ?>" class="btn btn-secondary btn-sm" style="color: #DC2626;" onclick="return confirm('¿Quitar este suscriptor?');">Quitar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
