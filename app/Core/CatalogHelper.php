<?php
namespace ShopCMS\Core;

class CatalogHelper {
    public static function sortOptions(): array {
        return [
            'newest'       => 'Más recientes',
            'best_selling' => 'Más vendidos',
            'price_asc'    => 'Precio: menor a mayor',
            'price_desc'   => 'Precio: mayor a menor',
            'name_asc'     => 'Nombre: A - Z',
        ];
    }

    public static function sanitizeFilters(array $input, int $defaultPerPage = 12): array {
        $search = trim((string)($input['q'] ?? ''));
        $category = trim((string)($input['category'] ?? ''));
        $sort = trim((string)($input['sort'] ?? 'newest'));
        if (!array_key_exists($sort, self::sortOptions())) {
            $sort = 'newest';
        }

        $minRaw = trim((string)($input['min_price'] ?? ''));
        $maxRaw = trim((string)($input['max_price'] ?? ''));
        $minPrice = ($minRaw !== '' && is_numeric($minRaw)) ? max(0.0, round((float)$minRaw, 2)) : null;
        $maxPrice = ($maxRaw !== '' && is_numeric($maxRaw)) ? max(0.0, round((float)$maxRaw, 2)) : null;

        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        $inStock = ((string)($input['in_stock'] ?? '') === '1');
        $perPageRaw = (int)($input['per_page'] ?? $defaultPerPage);
        $perPage = max(4, min(48, $perPageRaw > 0 ? $perPageRaw : $defaultPerPage));
        $page = max(1, (int)($input['page'] ?? 1));

        return [
            'q'         => $search,
            'category'  => $category,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'sort'      => $sort,
            'in_stock'  => $inStock,
            'per_page'  => $perPage,
            'page'      => $page,
        ];
    }

    public static function hasActiveFilters(array $filters): bool {
        return ($filters['q'] ?? '') !== ''
            || ($filters['category'] ?? '') !== ''
            || ($filters['min_price'] ?? null) !== null
            || ($filters['max_price'] ?? null) !== null
            || !empty($filters['in_stock'])
            || (($filters['sort'] ?? 'newest') !== 'newest');
    }

    public static function queryCatalog(array $input, int $defaultPerPage = 12): array {
        $filters = self::sanitizeFilters($input, $defaultPerPage);

        $pTable = Database::table('products');
        $cTable = Database::table('categories');
        $pvTable = Database::table('product_variants');
        $oiTable = Database::table('order_items');
        $oTable = Database::table('orders');

        $fromWhere = " FROM {$pTable} p
            LEFT JOIN {$cTable} c ON p.category_id = c.id AND c.is_active = 1
            LEFT JOIN {$cTable} pc ON c.parent_id = pc.id AND pc.is_active = 1
            WHERE p.is_active = 1";
        $params = [];

        if ($filters['category'] !== '') {
            $fromWhere .= " AND (c.slug = ? OR pc.slug = ?)";
            $params[] = $filters['category'];
            $params[] = $filters['category'];
        }

        if ($filters['q'] !== '') {
            $like = '%' . $filters['q'] . '%';
            $fromWhere .= " AND (
                p.name LIKE ?
                OR p.description LIKE ?
                OR p.short_description LIKE ?
                OR p.sku LIKE ?
                OR p.barcode LIKE ?
                OR c.name LIKE ?
                OR EXISTS (
                    SELECT 1 FROM {$pvTable} pv
                    WHERE pv.product_id = p.id
                      AND (pv.sku LIKE ? OR pv.title LIKE ?)
                )
            )";
            array_push($params, $like, $like, $like, $like, $like, $like, $like, $like);
        }

        if ($filters['min_price'] !== null) {
            $fromWhere .= " AND p.price >= ?";
            $params[] = $filters['min_price'];
        }

        if ($filters['max_price'] !== null) {
            $fromWhere .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
        }

        if ($filters['in_stock']) {
            $fromWhere .= " AND (
                p.track_quantity = 0
                OR p.quantity > 0
                OR (p.has_variants = 1 AND EXISTS (
                    SELECT 1 FROM {$pvTable} pv_s WHERE pv_s.product_id = p.id AND pv_s.quantity > 0
                ))
            )";
        }

        $countRow = Database::fetch("SELECT COUNT(DISTINCT p.id) AS total" . $fromWhere, $params);
        $totalProducts = (int)($countRow['total'] ?? 0);
        $perPage = $filters['per_page'];
        $totalPages = max(1, (int)ceil($totalProducts / $perPage));
        $page = min($filters['page'], $totalPages);
        $filters['page'] = $page;
        $offset = ($page - 1) * $perPage;

        $orderSelect = '';
        $orderParams = [];
        if ($filters['q'] !== '') {
            $orderSelect = ", CASE
                WHEN UPPER(COALESCE(p.sku, '')) = UPPER(?) THEN 3
                WHEN EXISTS (SELECT 1 FROM {$pvTable} pv_e WHERE pv_e.product_id = p.id AND UPPER(COALESCE(pv_e.sku, '')) = UPPER(?)) THEN 2
                WHEN p.name LIKE ? THEN 1
                ELSE 0
            END AS search_relevance";
            $orderParams = [$filters['q'], $filters['q'], $filters['q'] . '%'];
        }

        $salesExpr = "COALESCE((
            SELECT SUM(oi.quantity)
            FROM {$oiTable} oi
            INNER JOIN {$oTable} o ON oi.order_id = o.id
            WHERE oi.product_id = p.id AND o.status NOT IN ('cancelled', 'returned')
        ), 0)";

        $orderMap = [
            'newest'       => 'p.id DESC',
            'price_asc'    => 'p.price ASC, p.id DESC',
            'price_desc'   => 'p.price DESC, p.id DESC',
            'best_selling' => "{$salesExpr} DESC, p.id DESC",
            'name_asc'     => 'p.name ASC, p.id DESC',
        ];
        $orderBy = $orderMap[$filters['sort']] ?? 'p.id DESC';
        if ($filters['q'] !== '' && $filters['sort'] === 'newest') {
            $orderBy = 'search_relevance DESC, p.id DESC';
        }

        $sql = "SELECT p.*, c.name AS category_name,
                    (SELECT MIN(pv_m.sku) FROM {$pvTable} pv_m WHERE pv_m.product_id = p.id AND pv_m.sku IS NOT NULL AND pv_m.sku <> '') AS variant_sku_sample
                    {$orderSelect}"
            . $fromWhere
            . " ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";

        $queryParams = array_merge($orderParams, $params);
        $products = Database::fetchAll($sql, $queryParams);

        $bounds = Database::fetch("SELECT COALESCE(MIN(price), 0) AS min_p, COALESCE(MAX(price), 0) AS max_p FROM {$pTable} WHERE is_active = 1");

        return [
            'products'      => $products,
            'filters'       => $filters,
            'totalProducts' => $totalProducts,
            'totalPages'    => $totalPages,
            'currentPage'   => $page,
            'perPage'       => $perPage,
            'showingFrom'   => $totalProducts > 0 ? ($offset + 1) : 0,
            'showingTo'     => min($totalProducts, $offset + count($products)),
            'priceBounds'   => [
                'min' => (float)($bounds['min_p'] ?? 0),
                'max' => (float)($bounds['max_p'] ?? 0),
            ],
        ];
    }

    public static function buildUrl(string $baseUrl, array $filters, array $overrides = []): string {
        $merged = array_merge($filters, $overrides);
        $query = [];
        if (($merged['q'] ?? '') !== '') $query['q'] = $merged['q'];
        if (($merged['category'] ?? '') !== '') $query['category'] = $merged['category'];
        if (($merged['min_price'] ?? null) !== null && $merged['min_price'] !== '') $query['min_price'] = $merged['min_price'];
        if (($merged['max_price'] ?? null) !== null && $merged['max_price'] !== '') $query['max_price'] = $merged['max_price'];
        if (($merged['sort'] ?? 'newest') !== 'newest') $query['sort'] = $merged['sort'];
        if (!empty($merged['in_stock'])) $query['in_stock'] = '1';
        $page = (int)($merged['page'] ?? 1);
        if ($page > 1) $query['page'] = $page;

        $qs = http_build_query($query);
        return $baseUrl . '/catalog' . ($qs !== '' ? '?' . $qs : '');
    }

    public static function renderFilterFields(array $filters, array $categories, array $fltSec, array $currency, array $options = []): string {
        $showSearch = ($fltSec['show_search'] ?? true) !== false;
        $showCategories = ($fltSec['show_categories'] ?? true) !== false;
        $showPrice = ($fltSec['show_price_filter'] ?? true) !== false;
        $showSort = ($fltSec['show_sort'] ?? true) !== false;
        $showStock = ($fltSec['show_stock_filter'] ?? true) !== false;

        $baseUrl = (string)($options['base_url'] ?? '');
        $btnClass = (string)($options['btn_class'] ?? 'btn-store btn-store-primary');
        $btnStyle = (string)($options['btn_style'] ?? 'padding: 10px 20px;');
        $radius = (string)($options['radius'] ?? '8px');
        $border = (string)($options['border'] ?? '1px solid var(--color-border)');
        $searchPlaceholder = (string)($options['search_placeholder'] ?? 'Buscar por producto, SKU o palabra clave...');
        $catPlaceholder = (string)($options['category_placeholder'] ?? 'Todas las categorías');
        $btnLabel = (string)($options['btn_label'] ?? 'Aplicar');
        $totalProducts = (int)($options['total_products'] ?? 0);
        $showingFrom = (int)($options['showing_from'] ?? 0);
        $showingTo = (int)($options['showing_to'] ?? 0);
        $sym = htmlspecialchars((string)($currency['symbol'] ?? '$'));

        $inpStyle = "padding: 9px 12px; border: {$border}; border-radius: {$radius}; font-size: 13.5px; background: #FFFFFF; color: var(--color-text);";

        ob_start();
        ?>
        <form method="GET" action="<?= htmlspecialchars($baseUrl) ?>/catalog" class="scms-catalog-filter-form" style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <?php if ($showSearch): ?>
                    <input type="text" name="q" placeholder="<?= htmlspecialchars($searchPlaceholder) ?>" value="<?= htmlspecialchars((string)($filters['q'] ?? '')) ?>" style="<?= $inpStyle ?> flex: 2 1 210px; min-width: 190px;">
                <?php endif; ?>

                <?php if ($showCategories): ?>
                    <select name="category" style="<?= $inpStyle ?> flex: 1 1 165px; min-width: 155px;">
                        <option value=""><?= htmlspecialchars($catPlaceholder) ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= ($filters['category'] ?? '') === $cat['slug'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <?php if ($showSort): ?>
                    <select name="sort" aria-label="Ordenar productos" onchange="this.form.submit()" style="<?= $inpStyle ?> flex: 1 1 175px; min-width: 165px;">
                        <?php foreach (self::sortOptions() as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= ($filters['sort'] ?? 'newest') === $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <?php if ($showPrice || $showStock || true): ?>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                        <?php if ($showPrice): ?>
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                <span style="font-size: 12.5px; color: var(--color-text-light); font-weight: 600;">Precio (<?= $sym ?>):</span>
                                <input type="number" step="0.01" min="0" name="min_price" placeholder="Mín" value="<?= ($filters['min_price'] ?? null) !== null ? htmlspecialchars((string)$filters['min_price']) : '' ?>" style="<?= $inpStyle ?> width: 92px; padding: 7px 10px; font-size: 13px;">
                                <span style="color: var(--color-text-light); font-size: 12px;">-</span>
                                <input type="number" step="0.01" min="0" name="max_price" placeholder="Máx" value="<?= ($filters['max_price'] ?? null) !== null ? htmlspecialchars((string)$filters['max_price']) : '' ?>" style="<?= $inpStyle ?> width: 92px; padding: 7px 10px; font-size: 13px;">
                            </div>
                        <?php endif; ?>

                        <?php if ($showStock): ?>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: var(--color-text); cursor: pointer; user-select: none; padding: 4px 2px;">
                                <input type="checkbox" name="in_stock" value="1" <?= !empty($filters['in_stock']) ? 'checked' : '' ?> style="width: 15px; height: 15px; accent-color: var(--color-primary, #008060);">
                                <span>Solo disponibles en stock</span>
                            </label>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-left: auto;">
                        <?php if ($totalProducts > 0): ?>
                            <span style="font-size: 12.5px; color: var(--color-text-light); margin-right: 4px;">
                                Mostrando <strong><?= $showingFrom ?>-<?= $showingTo ?></strong> de <strong><?= $totalProducts ?></strong>
                            </span>
                        <?php endif; ?>
                        <button type="submit" class="<?= htmlspecialchars($btnClass) ?>" style="<?= htmlspecialchars($btnStyle) ?>"><?= htmlspecialchars($btnLabel) ?></button>
                        <?php if (self::hasActiveFilters($filters)): ?>
                            <a href="<?= htmlspecialchars($baseUrl) ?>/catalog" class="btn-store btn-store-secondary" style="padding: 8px 14px; font-size: 13px; border-radius: <?= htmlspecialchars($radius) ?>;">Limpiar</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </form>
        <?php
        return ob_get_clean();
    }

    public static function renderPagination(string $baseUrl, array $filters, int $currentPage, int $totalPages, array $styleOpts = []): string {
        if ($totalPages <= 1) {
            return '';
        }
        $radius = (string)($styleOpts['radius'] ?? '8px');
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);

        ob_start();
        ?>
        <nav class="scms-pagination" aria-label="Paginación del catálogo" style="display: flex; justify-content: center; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 32px;">
            <?php if ($currentPage > 1): ?>
                <a href="<?= htmlspecialchars(self::buildUrl($baseUrl, $filters, ['page' => $currentPage - 1])) ?>" class="scms-page-link" style="padding: 8px 13px; border: 1px solid var(--color-border); border-radius: <?= htmlspecialchars($radius) ?>; background: #FFF; color: var(--color-text); text-decoration: none; font-size: 13px; font-weight: 600;">Anterior</a>
            <?php endif; ?>

            <?php if ($start > 1): ?>
                <a href="<?= htmlspecialchars(self::buildUrl($baseUrl, $filters, ['page' => 1])) ?>" class="scms-page-link" style="padding: 8px 12px; border: 1px solid var(--color-border); border-radius: <?= htmlspecialchars($radius) ?>; background: #FFF; color: var(--color-text); text-decoration: none; font-size: 13px;">1</a>
                <?php if ($start > 2): ?><span style="padding: 0 4px; color: var(--color-text-light);">...</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i === $currentPage): ?>
                    <span aria-current="page" style="padding: 8px 13px; border: 1px solid var(--color-primary, #008060); border-radius: <?= htmlspecialchars($radius) ?>; background: var(--color-primary, #008060); color: #FFF; font-size: 13px; font-weight: 700;"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= htmlspecialchars(self::buildUrl($baseUrl, $filters, ['page' => $i])) ?>" class="scms-page-link" style="padding: 8px 12px; border: 1px solid var(--color-border); border-radius: <?= htmlspecialchars($radius) ?>; background: #FFF; color: var(--color-text); text-decoration: none; font-size: 13px; font-weight: 500;"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><span style="padding: 0 4px; color: var(--color-text-light);">...</span><?php endif; ?>
                <a href="<?= htmlspecialchars(self::buildUrl($baseUrl, $filters, ['page' => $totalPages])) ?>" class="scms-page-link" style="padding: 8px 12px; border: 1px solid var(--color-border); border-radius: <?= htmlspecialchars($radius) ?>; background: #FFF; color: var(--color-text); text-decoration: none; font-size: 13px;"><?= $totalPages ?></a>
            <?php endif; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="<?= htmlspecialchars(self::buildUrl($baseUrl, $filters, ['page' => $currentPage + 1])) ?>" class="scms-page-link" style="padding: 8px 13px; border: 1px solid var(--color-border); border-radius: <?= htmlspecialchars($radius) ?>; background: #FFF; color: var(--color-text); text-decoration: none; font-size: 13px; font-weight: 600;">Siguiente</a>
            <?php endif; ?>
        </nav>
        <?php
        return ob_get_clean();
    }
}
