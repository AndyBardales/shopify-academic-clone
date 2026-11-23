<?php
$statusFilter = $statusFilter ?? '';
$ratingFilter = $ratingFilter ?? 0;
$search = $search ?? '';
$counts = $counts ?? ['all' => count($reviews), 'approved' => 0, 'pending' => 0, 'rejected' => 0, 'hidden' => 0];
$starBreakdown = $starBreakdown ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$activeRatingCount = $activeRatingCount ?? 0;
$avgRating = $avgRating ?? 0.0;
$satisfactionPct = $satisfactionPct ?? 0;
$repliedCount = $repliedCount ?? 0;

$buildReviewUrl = function(array $overrides = []) use ($baseUrl, $statusFilter, $ratingFilter, $search): string {
    $params = [
        'status' => array_key_exists('status', $overrides) ? $overrides['status'] : $statusFilter,
        'rating' => array_key_exists('rating', $overrides) ? $overrides['rating'] : ($ratingFilter ?: ''),
        'q'      => array_key_exists('q', $overrides) ? $overrides['q'] : $search,
    ];
    $filtered = [];
    foreach ($params as $k => $v) {
        if ($v !== '' && $v !== null && $v !== 0) {
            $filtered[$k] = $v;
        }
    }
    return $baseUrl . '/admin/reviews' . (!empty($filtered) ? '?' . http_build_query($filtered) : '');
};
?>

<style>
.reviews-layout-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 20px;
    align-items: start;
}
@media (max-width: 980px) {
    .reviews-layout-grid {
        grid-template-columns: 1fr;
    }
}
.review-item-card {
    border-bottom: 1px solid #F3F4F6;
    padding: 18px 20px;
    transition: background 0.15s ease;
}
.review-item-card:hover {
    background: #FCFCFD;
}
.review-item-card:last-child {
    border-bottom: none;
}
.reply-template-chip {
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 11.5px;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    text-align: left;
    width: 100%;
    transition: all 0.15s ease;
}
.reply-template-chip:hover {
    background: #ECFDF5;
    border-color: #059669;
    color: #065F46;
}
.star-bar-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 8px;
    border-radius: 6px;
    text-decoration: none;
    color: #374151;
    font-size: 12.5px;
    transition: background 0.15s ease;
}
.star-bar-row:hover,
.star-bar-row.active {
    background: #F3F4F6;
}
</style>

<!-- CABECERA -->
<div class="page-header" style="flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 class="page-title">Reseñas &amp; Calificaciones</h1>
        <p class="page-subtitle">Modera opiniones verificadas, responde como tienda oficial y destaca los mejores comentarios en tu catálogo.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button type="button" onclick="toggleReviewGuide()" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span>¿Cómo usar?</span>
        </button>
    </div>
</div>

<!-- GUÍA RÁPIDA EN 3 PASOS -->
<div id="review-quick-guide" class="polaris-card" style="background: #F9FAFB; border-color: #E5E7EB; margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                <strong style="font-size: 13px; color: #111827;">Guía rápida de moderación e interacción</strong>
            </div>
            <button type="button" onclick="toggleReviewGuide()" style="background: none; border: none; color: #6B7280; font-size: 12px; cursor: pointer; text-decoration: underline;">Ocultar</button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #E8F5E9; color: #008060; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">1</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Aprueba, rechaza u oculta</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Revisa si el autor tiene <strong>Compra verificada</strong> y aprueba o deshabilita (borrado lógico) en 1 clic.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #EBF5FA; color: #006FAD; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">2</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Responde como tienda oficial</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Pulsa <strong>Responder</strong> para escribir o usar una plantilla rápida; tu respuesta aparecerá en la tienda web.</div>
                </div>
            </div>
            <div style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; display: flex; gap: 10px; align-items: flex-start;">
                <span style="width: 22px; height: 22px; border-radius: 50%; background: #FEF3C7; color: #B45309; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">3</span>
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; color: #111827; margin-bottom: 2px;">Destaca las mejores reseñas</div>
                    <div style="font-size: 12px; color: #6B7280; line-height: 1.4;">Marca una opinión con <strong>Destacar</strong> para fijarla arriba en la ficha del producto y dar mayor confianza.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MÉTRICAS DE REPUTACIÓN -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-title" style="display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="#F59E0B" stroke="#F59E0B" stroke-width="1.5">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
            <span>Calificación Promedio</span>
        </div>
        <div class="metric-value"><?= number_format((float)$avgRating, 1) ?> <span style="font-size: 14px; font-weight: 500; color: var(--color-text-muted);">/ 5.0</span></div>
        <div class="metric-sub">Calculada sobre <?= (int)$activeRatingCount ?> <?= (int)$activeRatingCount === 1 ? 'reseña activa' : 'reseñas activas' ?></div>
    </div>

    <div class="metric-card">
        <div class="metric-title" style="display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#B95000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <span>Pendientes por Moderar</span>
        </div>
        <div class="metric-value" style="color: <?= ($counts['pending'] ?? 0) > 0 ? '#B95000' : '#111827' ?>;"><?= (int)($counts['pending'] ?? 0) ?></div>
        <div class="metric-sub">
            <?php if (($counts['pending'] ?? 0) > 0): ?>
                <a href="<?= $buildReviewUrl(['status' => 'pending']) ?>" style="color: #B95000; text-decoration: underline; font-weight: 600;">Revisar pendientes ahora</a>
            <?php else: ?>
                Todo al día sin pendientes
            <?php endif; ?>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-title" style="display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>
            </svg>
            <span>Índice de Satisfacción</span>
        </div>
        <div class="metric-value" style="color: #008060;"><?= (int)$satisfactionPct ?>%</div>
        <div class="metric-sub">Opiniones de 4-5 estrellas · <?= (int)$repliedCount ?> respondidas</div>
    </div>
</div>

<!-- LAYOUT DE MODERACIÓN + TERMÓMETRO / RESPUESTA -->
<div class="reviews-layout-grid">
    <!-- COLUMNA IZQUIERDA: BANDEJA DE RESEÑAS -->
    <div class="polaris-card">
        <div class="card-header" style="flex-direction: column; align-items: stretch; gap: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <a href="<?= $buildReviewUrl(['status' => '']) ?>" class="btn <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                        Todas (<?= (int)($counts['all'] ?? 0) ?>)
                    </a>
                    <a href="<?= $buildReviewUrl(['status' => 'pending']) ?>" class="btn <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                        Pendientes (<?= (int)($counts['pending'] ?? 0) ?>)
                    </a>
                    <a href="<?= $buildReviewUrl(['status' => 'approved']) ?>" class="btn <?= $statusFilter === 'approved' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                        Aprobadas (<?= (int)($counts['approved'] ?? 0) ?>)
                    </a>
                    <a href="<?= $buildReviewUrl(['status' => 'rejected']) ?>" class="btn <?= $statusFilter === 'rejected' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                        Rechazadas (<?= (int)($counts['rejected'] ?? 0) ?>)
                    </a>
                    <a href="<?= $buildReviewUrl(['status' => 'hidden']) ?>" class="btn <?= $statusFilter === 'hidden' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                        Deshabilitadas (<?= (int)($counts['hidden'] ?? 0) ?>)
                    </a>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding-top: 8px; border-top: 1px solid #F3F4F6;">
                <form method="GET" action="<?= $baseUrl ?>/admin/reviews" style="display: flex; gap: 8px; flex: 1; min-width: 240px; max-width: 440px;">
                    <?php if (!empty($statusFilter)): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                    <?php endif; ?>
                    <?php if (!empty($ratingFilter)): ?>
                        <input type="hidden" name="rating" value="<?= (int)$ratingFilter ?>">
                    <?php endif; ?>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por producto, cliente o comentario..." class="form-control" style="padding: 6px 10px; font-size: 13px;">
                    <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
                    <?php if (!empty($search) || !empty($ratingFilter)): ?>
                        <a href="<?= $buildReviewUrl(['q' => '', 'rating' => '']) ?>" class="btn btn-secondary btn-sm">Limpiar</a>
                    <?php endif; ?>
                </form>

                <?php if (!empty($ratingFilter)): ?>
                    <span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px;">
                        <span>Filtro: <?= (int)$ratingFilter ?> estrella<?= $ratingFilter > 1 ? 's' : '' ?></span>
                        <a href="<?= $buildReviewUrl(['rating' => '']) ?>" style="color: inherit; font-weight: 700; text-decoration: none;">×</a>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <?php if (empty($reviews)): ?>
                <div style="padding: 52px 24px; text-align: center; color: var(--color-text-muted);">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 12px; display: block;">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    <p style="font-size: 14.5px; font-weight: 600; color: #374151; margin: 0 0 4px;">No hay reseñas con los filtros actuales</p>
                    <p style="font-size: 12.5px; color: #6B7280; margin: 0;">Cuando tus clientes publiquen opiniones verificadas sobre sus compras aparecerán en esta bandeja.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $r): ?>
                    <?php
                    $isHidden = ($r['status'] === 'hidden');
                    $isFeatured = !empty($r['is_featured']);
                    $isVerified = !empty($r['is_verified_buyer']);
                    $hasReply = !empty(trim((string)($r['admin_reply'] ?? '')));
                    ?>
                    <div class="review-item-card" style="<?= $isHidden ? 'background: #FAFBFB; opacity: 0.85;' : ($isFeatured ? 'background: #F9FEFC;' : '') ?>">
                        <!-- CABECERA DE RESEÑA -->
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 10px;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                                    <div style="display: inline-flex; gap: 2px;" title="<?= (int)$r['rating'] ?> de 5 estrellas">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="<?= $s <= $r['rating'] ? '#F59E0B' : '#E5E7EB' ?>" stroke="<?= $s <= $r['rating'] ? '#F59E0B' : '#D1D5DB' ?>" stroke-width="1.5">
                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($r['product_slug']) ?>" target="_blank" style="font-weight: 700; font-size: 13.5px; color: #111827; text-decoration: none;" title="Ver producto en tienda">
                                        <?= htmlspecialchars($r['product_name']) ?>
                                    </a>
                                    <?php if ($isVerified): ?>
                                        <span class="badge badge-success" style="font-size: 10.5px; padding: 2px 7px; display: inline-flex; align-items: center; gap: 4px;">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                            Compra verificada
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($isFeatured): ?>
                                        <span class="badge badge-warning" style="font-size: 10.5px; padding: 2px 7px;">Destacada</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 12px; color: var(--color-text-secondary);">
                                    Por <strong><?= htmlspecialchars($r['customer_name']) ?></strong> (<?= htmlspecialchars($r['customer_email']) ?>) · <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                                </div>
                            </div>

                            <!-- BADGE DE ESTADO -->
                            <div>
                                <?php if ($r['status'] === 'approved'): ?>
                                    <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 5px;">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                                        Aprobada
                                    </span>
                                <?php elseif ($r['status'] === 'rejected'): ?>
                                    <span class="badge badge-danger" style="display: inline-flex; align-items: center; gap: 5px;">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                                        Rechazada
                                    </span>
                                <?php elseif ($r['status'] === 'hidden'): ?>
                                    <span class="badge" style="background: #E5E7EB; color: #4B5563; display: inline-flex; align-items: center; gap: 5px;">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                                        Deshabilitada
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 5px;">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                                        Pendiente
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- COMENTARIO -->
                        <?php if (!empty($r['title'])): ?>
                            <div style="font-weight: 600; font-size: 13.5px; color: #111827; margin-bottom: 4px;"><?= htmlspecialchars($r['title']) ?></div>
                        <?php endif; ?>
                        <p style="font-size: 13px; color: #374151; line-height: 1.55; margin: 0 0 12px;">
                            <?= nl2br(htmlspecialchars($r['comment'])) ?>
                        </p>

                        <!-- RESPUESTA OFICIAL EXISTENTE -->
                        <?php if ($hasReply): ?>
                            <div style="background: #F3F4F6; border-left: 3px solid #008060; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <span style="font-size: 11.5px; font-weight: 700; color: #008060;">Respuesta oficial de la tienda</span>
                                    <?php if (!empty($r['admin_replied_at'])): ?>
                                        <span style="font-size: 11px; color: #6B7280;"><?= date('d/m/Y H:i', strtotime($r['admin_replied_at'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 12.5px; color: #374151; line-height: 1.5;">
                                    <?= nl2br(htmlspecialchars($r['admin_reply'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- BARRA DE ACCIONES -->
                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <?php if (!$isHidden): ?>
                                <?php if ($r['status'] !== 'approved'): ?>
                                    <form method="POST" action="<?= $baseUrl ?>/admin/reviews/update-status/<?= $r['id'] ?>" style="display:inline;">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color: #008060; font-weight: 600;">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                            <span>Aprobar</span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($r['status'] !== 'rejected'): ?>
                                    <form method="POST" action="<?= $baseUrl ?>/admin/reviews/update-status/<?= $r['id'] ?>" style="display:inline;">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--color-error);">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                            </svg>
                                            <span>Rechazar</span>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <button type="button"
                                    onclick='openReplyPanel(<?= json_encode($r) ?>)'
                                    class="btn btn-secondary btn-sm"
                                    style="display: inline-flex; align-items: center; gap: 5px; color: #006FAD; font-weight: 600;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                    </svg>
                                    <span><?= $hasReply ? 'Editar respuesta' : 'Responder' ?></span>
                                </button>

                                <form method="POST" action="<?= $baseUrl ?>/admin/reviews/toggle-featured/<?= $r['id'] ?>" style="display: inline;">
                                    <?= \ShopCMS\Core\Security::csrfField() ?>
                                    <button type="submit" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 5px; <?= $isFeatured ? 'background: #FEF3C7; color: #92400E; border-color: #FDE68A;' : 'color: #6B7280;' ?>" title="<?= $isFeatured ? 'Quitar de reseñas destacadas' : 'Fijar arriba en la página del producto' ?>">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="<?= $isFeatured ? '#F59E0B' : 'none' ?>" stroke="currentColor" stroke-width="2">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                        </svg>
                                        <span><?= $isFeatured ? 'Destacada' : 'Destacar' ?></span>
                                    </button>
                                </form>

                                <form method="POST" action="<?= $baseUrl ?>/admin/reviews/update-status/<?= $r['id'] ?>" style="display:inline;">
                                    <input type="hidden" name="status" value="hidden">
                                    <button type="submit" onclick="return confirm('¿Deshabilitar esta reseña? No se mostrará en la tienda, pero se conservará el registro (borrado lógico).');" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 5px; color: #B95000;" title="Deshabilitar reseña (borrado lógico)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                            <line x1="1" y1="1" x2="23" y2="23"></line>
                                        </svg>
                                        <span>Deshabilitar</span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="<?= $baseUrl ?>/admin/reviews/update-status/<?= $r['id'] ?>" style="display:inline;">
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 5px; color: #008060; border-color: #008060;" title="Reactivar y publicar reseña">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        <span>Activar</span>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- COLUMNA DERECHA: PANEL INTELIGENTE (TERMÓMETRO DE CALIDAD / RESPONDER RESEÑA) -->
    <div style="position: sticky; top: 20px;">
        <!-- PANEL A: DESGLOSE POR ESTRELLAS (POR DEFECTO) -->
        <div class="polaris-card" id="reviews-insights-card">
            <div class="card-header">
                <h2 class="card-title">Distribución de Estrellas</h2>
            </div>
            <div class="card-body">
                <div style="display: flex; align-items: center; gap: 14px; padding-bottom: 14px; margin-bottom: 14px; border-bottom: 1px solid #F3F4F6;">
                    <div style="font-size: 32px; font-weight: 800; color: #111827; line-height: 1;"><?= number_format((float)$avgRating, 1) ?></div>
                    <div>
                        <div style="display: flex; gap: 2px; margin-bottom: 4px;">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="<?= $s <= round($avgRating) ? '#F59E0B' : '#E5E7EB' ?>" stroke="<?= $s <= round($avgRating) ? '#F59E0B' : '#D1D5DB' ?>" stroke-width="1.5">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                            <?php endfor; ?>
                        </div>
                        <div style="font-size: 12px; color: var(--color-text-secondary);">Haz clic en una fila para filtrar</div>
                    </div>
                </div>

                <?php for ($star = 5; $star >= 1; $star--): ?>
                    <?php
                    $cnt = (int)($starBreakdown[$star] ?? 0);
                    $pct = $activeRatingCount > 0 ? round(($cnt / $activeRatingCount) * 100) : 0;
                    $isCurrentStar = ($ratingFilter === $star);
                    ?>
                    <a href="<?= $buildReviewUrl(['rating' => $isCurrentStar ? '' : $star]) ?>" class="star-bar-row <?= $isCurrentStar ? 'active' : '' ?>">
                        <span style="width: 44px; font-weight: 600; flex-shrink: 0; display: inline-flex; align-items: center; gap: 4px;">
                            <span><?= $star ?></span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="#F59E0B" stroke="#F59E0B" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </span>
                        <div style="flex: 1; height: 8px; background: #E5E7EB; border-radius: 6px; overflow: hidden;">
                            <div style="width: <?= $pct ?>%; height: 100%; background: <?= $star >= 4 ? '#008060' : ($star === 3 ? '#F59E0B' : '#EF4444') ?>;"></div>
                        </div>
                        <span style="width: 34px; text-align: right; color: #6B7280; font-weight: 600;"><?= $cnt ?></span>
                    </a>
                <?php endfor; ?>

                <div style="margin-top: 16px; padding: 12px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 12px; color: #4B5563; line-height: 1.5;">
                    <strong>Tip de conversión:</strong> responder a las reseñas de clientes y destacar al menos 1 opinión con <em>Compra verificada</em> por producto aumenta la confianza en la página de compra.
                </div>
            </div>
        </div>

        <!-- PANEL B: RESPONDER COMO TIENDA OFICIAL (SE ACTIVA AL PULSAR RESPONDER) -->
        <div class="polaris-card" id="review-reply-card" style="display: none; border-color: #006FAD;">
            <div class="card-header" style="background: #F4F9FD;">
                <div>
                    <h2 class="card-title" style="margin: 0;">Responder Reseña</h2>
                    <p id="reply-target-subtitle" style="font-size: 12px; color: var(--color-text-secondary); margin: 2px 0 0;">Respuesta pública en tienda</p>
                </div>
                <button type="button" onclick="closeReplyPanel()" class="btn btn-secondary btn-sm" style="padding: 4px 8px;" title="Volver a distribución">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="card-body">
                <!-- CITA DEL CLIENTE -->
                <div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px 12px; margin-bottom: 14px;">
                    <div id="reply-quote-author" style="font-size: 12px; font-weight: 700; color: #111827; margin-bottom: 4px;"></div>
                    <div id="reply-quote-text" style="font-size: 12px; color: #4B5563; line-height: 1.45;"></div>
                </div>

                <!-- PLANTILLAS RÁPIDAS DE RESPUESTA -->
                <div style="margin-bottom: 14px;">
                    <div style="font-size: 11.5px; font-weight: 600; color: var(--color-text-secondary); margin-bottom: 6px;">Respuestas rápidas (1 clic):</div>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <button type="button" class="reply-template-chip" onclick="useReplyTemplate('¡Muchas gracias por tu compra y por compartir tu opinión! Nos alegra saber que el producto cumplió tus expectativas.')">
                            Agradecimiento cordial (5 estrellas)
                        </button>
                        <button type="button" class="reply-template-chip" onclick="useReplyTemplate('Agradecemos tu comentario. Nuestro equipo ya tomó nota de tus observaciones para seguir mejorando tu experiencia.')">
                            Agradecimiento y mejora (3-4 estrellas)
                        </button>
                        <button type="button" class="reply-template-chip" onclick="useReplyTemplate('Lamentamos cualquier inconveniente con tu pedido. Por favor contáctanos respondiendo a tu correo de compra para brindarte solución inmediata.')">
                            Soporte y atención prioritaria
                        </button>
                    </div>
                </div>

                <form id="review-reply-form" method="POST" action="">
                    <div class="form-group">
                        <label for="admin_reply_input">Tu respuesta oficial</label>
                        <textarea id="admin_reply_input" name="admin_reply" rows="4" class="form-control" placeholder="Escribe una respuesta amable que verán todos los visitantes..."></textarea>
                        <small style="font-size: 11px; color: var(--color-text-secondary);">Deja vacío si deseas borrar una respuesta previa. Al escribir aparecerá arriba la barra Guardar / Descartar.</small>
                    </div>

                    <div id="reply-approve-wrap" class="form-group" style="display: none; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 6px; padding: 8px 10px;">
                        <label style="display: flex; align-items: center; gap: 8px; margin: 0; font-size: 12px; color: #065F46; cursor: pointer;">
                            <input type="checkbox" id="approve_on_reply" name="approve_on_reply" value="1" checked style="accent-color: #008060;">
                            <span>Aprobar y publicar esta reseña al guardar</span>
                        </label>
                    </div>

                    <button type="submit" style="display:none;" aria-hidden="true"></button>
                </form>

                <div style="margin-top: 10px;">
                    <button type="button" onclick="closeReplyPanel()" class="btn btn-secondary btn-sm" style="width: 100%;">
                        Volver a distribución de estrellas
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const reviewBaseUrl = '<?= $baseUrl ?>';

function toggleReviewGuide() {
    const guide = document.getElementById('review-quick-guide');
    if (!guide) return;
    const isHidden = guide.style.display === 'none';
    guide.style.display = isHidden ? 'block' : 'none';
    try {
        localStorage.setItem('shopcms_hide_review_guide', isHidden ? '0' : '1');
    } catch (e) {}
}

function openReplyPanel(review) {
    const insightsCard = document.getElementById('reviews-insights-card');
    const replyCard = document.getElementById('review-reply-card');
    const form = document.getElementById('review-reply-form');
    const input = document.getElementById('admin_reply_input');
    const approveWrap = document.getElementById('reply-approve-wrap');

    insightsCard.style.display = 'none';
    replyCard.style.display = 'block';

    form.action = reviewBaseUrl + '/admin/reviews/reply/' + review.id;
    document.getElementById('reply-target-subtitle').textContent = 'Producto: ' + (review.product_name || '');
    document.getElementById('reply-quote-author').textContent = (review.customer_name || 'Cliente') + ' (' + (review.rating || 5) + '/5)';
    document.getElementById('reply-quote-text').textContent = review.comment || '';
    input.value = review.admin_reply || '';

    approveWrap.style.display = (review.status === 'pending') ? 'block' : 'none';
    document.getElementById('approve_on_reply').checked = (review.status === 'pending');

    if (window.ShopCMS && window.ShopCMS.syncFormInitialState) {
        window.ShopCMS.syncFormInitialState(form);
    }

    input.focus();
    replyCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function closeReplyPanel() {
    const form = document.getElementById('review-reply-form');
    if (window.ShopCMS && window.ShopCMS.markClean) {
        window.ShopCMS.markClean();
    }
    if (form) form.reset();
    document.getElementById('review-reply-card').style.display = 'none';
    document.getElementById('reviews-insights-card').style.display = 'block';
}

function useReplyTemplate(text) {
    const form = document.getElementById('review-reply-form');
    const input = document.getElementById('admin_reply_input');
    input.value = text;
    input.focus();
    if (window.ShopCMS && window.ShopCMS.markDirty) {
        window.ShopCMS.markDirty(form);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        if (localStorage.getItem('shopcms_hide_review_guide') === '1') {
            const guide = document.getElementById('review-quick-guide');
            if (guide) guide.style.display = 'none';
        }
    } catch (e) {}
});
</script>
