<?php
namespace ShopCMS\Core;

class DashboardAnalytics {
    public static function periods(): array {
        return [
            '7d'  => ['label' => 'Últimos 7 días',    'days' => 7],
            '30d' => ['label' => 'Últimos 30 días',   'days' => 30],
            '90d' => ['label' => 'Últimos 90 días',   'days' => 90],
            'all' => ['label' => 'Todo el historial', 'days' => null],
        ];
    }

    public static function normalizePeriod(?string $period): string {
        $key = strtolower(trim((string)$period));
        $all = self::periods();
        return isset($all[$key]) ? $key : '30d';
    }

    public static function sinceTimestamp(string $period): ?string {
        $periods = self::periods();
        $days = $periods[$period]['days'] ?? 30;
        if ($days === null) {
            return null;
        }
        return date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
    }

    public static function getSummaryAndCharts(string $period): array {
        $period = self::normalizePeriod($period);
        $periods = self::periods();
        $since = self::sinceTimestamp($period);

        $oTable = Database::table('orders');
        $oiTable = Database::table('order_items');

        $whereClause = '';
        $params = [];
        if ($since !== null) {
            $whereClause = ' WHERE created_at >= ?';
            $params[] = $since;
        }

        $agg = Database::fetch("
            SELECT
                COUNT(*) AS total_orders,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled', 'returned') THEN 1 ELSE 0 END), 0) AS active_orders,
                COALESCE(SUM(CASE WHEN status IN ('cancelled', 'returned') THEN 1 ELSE 0 END), 0) AS cancelled_orders,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled', 'returned') THEN total ELSE 0 END), 0) AS gross_sales,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled', 'returned') AND payment_status = 'paid' THEN total ELSE 0 END), 0) AS paid_sales,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled', 'returned') AND payment_status = 'unpaid' THEN total ELSE 0 END), 0) AS pending_sales
            FROM {$oTable}
            {$whereClause}
        ", $params);

        $activeOrders = (int)($agg['active_orders'] ?? 0);
        $grossSales = (float)($agg['gross_sales'] ?? 0);
        $summary = [
            'total_orders'     => (int)($agg['total_orders'] ?? 0),
            'active_orders'    => $activeOrders,
            'cancelled_orders' => (int)($agg['cancelled_orders'] ?? 0),
            'gross_sales'      => $grossSales,
            'paid_sales'       => (float)($agg['paid_sales'] ?? 0),
            'pending_sales'    => (float)($agg['pending_sales'] ?? 0),
            'avg_ticket'       => $activeOrders > 0 ? ($grossSales / $activeOrders) : 0.0,
        ];

        $series = self::buildTimeSeries($period, $since, $oTable);

        $topWhere = $since !== null ? ' AND o.created_at >= ?' : '';
        $topParams = $since !== null ? [$since] : [];
        $topProducts = Database::fetchAll("
            SELECT
                oi.product_id,
                oi.product_name,
                COALESCE(SUM(oi.quantity), 0) AS units_sold,
                COALESCE(SUM(oi.total), 0) AS revenue
            FROM {$oiTable} oi
            INNER JOIN {$oTable} o ON oi.order_id = o.id
            WHERE o.status NOT IN ('cancelled', 'returned')
            {$topWhere}
            GROUP BY oi.product_id, oi.product_name
            ORDER BY units_sold DESC, revenue DESC
            LIMIT 5
        ", $topParams);

        $statusRows = Database::fetchAll("
            SELECT status, COUNT(*) AS cnt
            FROM {$oTable}
            {$whereClause}
            GROUP BY status
            ORDER BY cnt DESC
        ", $params);

        return [
            'period'          => $period,
            'periodLabel'     => $periods[$period]['label'],
            'since'           => $since,
            'summary'         => $summary,
            'series'          => $series,
            'topProducts'     => $topProducts,
            'statusBreakdown' => $statusRows,
        ];
    }

    private static function buildTimeSeries(string $period, ?string $since, string $oTable): array {
        $series = [];

        if ($period === 'all') {
            $rows = Database::fetchAll("
                SELECT
                    DATE_FORMAT(created_at, '%Y-%m') AS bucket,
                    COUNT(*) AS orders_count,
                    COALESCE(SUM(total), 0) AS revenue
                FROM {$oTable}
                WHERE status NOT IN ('cancelled', 'returned')
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY bucket ASC
                LIMIT 12
            ");
            foreach ($rows as $r) {
                $ym = (string)$r['bucket'];
                $series[] = [
                    'key'     => $ym,
                    'label'   => date('m/Y', strtotime($ym . '-01')),
                    'orders'  => (int)$r['orders_count'],
                    'revenue' => (float)$r['revenue'],
                ];
            }
            if (empty($series)) {
                $series[] = [
                    'key'     => date('Y-m'),
                    'label'   => date('m/Y'),
                    'orders'  => 0,
                    'revenue' => 0.0,
                ];
            }
            return $series;
        }

        $days = $period === '7d' ? 7 : ($period === '90d' ? 90 : 30);
        $raw = Database::fetchAll("
            SELECT
                DATE(created_at) AS bucket_date,
                COUNT(*) AS orders_count,
                COALESCE(SUM(total), 0) AS revenue
            FROM {$oTable}
            WHERE status NOT IN ('cancelled', 'returned')
              AND created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY bucket_date ASC
        ", [$since]);

        $byDate = [];
        foreach ($raw as $r) {
            $byDate[(string)$r['bucket_date']] = [
                'orders'  => (int)$r['orders_count'],
                'revenue' => (float)$r['revenue'],
            ];
        }

        if ($period === '90d') {
            // Agrupar en bloques de 7 días (~13 barras limpias)
            $startTs = strtotime(date('Y-m-d', strtotime('-89 days')));
            for ($offset = 0; $offset < 90; $offset += 7) {
                $sumRev = 0.0;
                $sumOrd = 0;
                $binStart = date('Y-m-d', strtotime("+{$offset} days", $startTs));
                for ($d = 0; $d < 7 && ($offset + $d) < 90; $d++) {
                    $dt = date('Y-m-d', strtotime('+' . ($offset + $d) . ' days', $startTs));
                    if (isset($byDate[$dt])) {
                        $sumRev += $byDate[$dt]['revenue'];
                        $sumOrd += $byDate[$dt]['orders'];
                    }
                }
                $series[] = [
                    'key'     => $binStart,
                    'label'   => date('d/m', strtotime($binStart)),
                    'orders'  => $sumOrd,
                    'revenue' => $sumRev,
                ];
            }
            return $series;
        }

        for ($i = $days - 1; $i >= 0; $i--) {
            $dt = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = [
                'key'     => $dt,
                'label'   => date('d/m', strtotime($dt)),
                'orders'  => $byDate[$dt]['orders'] ?? 0,
                'revenue' => $byDate[$dt]['revenue'] ?? 0.0,
            ];
        }
        return $series;
    }

    public static function getExportOrders(string $period): array {
        $period = self::normalizePeriod($period);
        $since = self::sinceTimestamp($period);
        $oTable = Database::table('orders');

        if ($since !== null) {
            return Database::fetchAll("SELECT * FROM {$oTable} WHERE created_at >= ? ORDER BY id DESC", [$since]);
        }
        return Database::fetchAll("SELECT * FROM {$oTable} ORDER BY id DESC");
    }
}
