<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

try {
    $connection = get_db_connection();

    $res1 = $connection->query("SELECT COALESCE(SUM(i.grand_total),0) AS total
        FROM invoices i
        WHERE i.deleted = 0
          AND EXISTS (SELECT 1 FROM deliveries d WHERE d.invoice_id = i.id AND d.status = 'completed')");
    $row1 = $res1 ? $res1->fetch_assoc() : ['total' => 0];
    $totalRevenue = (float)($row1['total'] ?? 0);

    $res2 = $connection->query("SELECT COUNT(*) AS cnt FROM orders WHERE status = 'completed'");
    $row2 = $res2 ? $res2->fetch_assoc() : ['cnt' => 0];
    $completedOrders = (int)($row2['cnt'] ?? 0);

    $res3 = $connection->query('SELECT COUNT(*) AS cnt FROM customers WHERE YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())');
    $row3 = $res3 ? $res3->fetch_assoc() : ['cnt' => 0];
    $newCustomers = (int)($row3['cnt'] ?? 0);

    $start7 = (new DateTimeImmutable('today'))->modify('-6 days')->format('Y-m-d');
    $dailyMap = [];
    $resDaily = $connection->query("SELECT issue_date AS d, SUM(grand_total) AS total
        FROM invoices
        WHERE deleted=0
          AND issue_date >= '{$start7}'
          AND EXISTS (SELECT 1 FROM deliveries d WHERE d.invoice_id = invoices.id AND d.status='completed')
        GROUP BY issue_date
        ORDER BY issue_date ASC");
    while ($row = $resDaily->fetch_assoc()) {
        $dailyMap[$row['d']] = (float)$row['total'];
    }
    $dailySales = [];
    for ($i=6; $i>=0; $i--) {
        $d = (new DateTimeImmutable('today'))->modify("-{$i} days")->format('Y-m-d');
        $dailySales[] = ['date' => $d, 'total' => (float)($dailyMap[$d] ?? 0)];
    }

    $year = (new DateTimeImmutable('today'))->format('Y');
    $monthlyMap = array_fill(1, 12, 0.0);
    $resMonthly = $connection->query("SELECT MONTH(issue_date) AS m, SUM(grand_total) AS total
        FROM invoices
        WHERE deleted=0
          AND YEAR(issue_date) = {$year}
          AND EXISTS (SELECT 1 FROM deliveries d WHERE d.invoice_id = invoices.id AND d.status='completed')
        GROUP BY MONTH(issue_date)");
    while ($row = $resMonthly->fetch_assoc()) {
        $monthlyMap[(int)$row['m']] = (float)$row['total'];
    }
    $monthlySales = [];
    for ($m=1; $m<=12; $m++) {
        $monthlySales[] = ['month' => sprintf('%s-%02d', $year, $m), 'total' => $monthlyMap[$m]];
    }

    $resInv = $connection->query("SELECT 
        SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) AS paid,
        SUM(CASE WHEN status IN ('draft','sent','pending','overdue') THEN 1 ELSE 0 END) AS unpaid
        FROM invoices WHERE deleted=0");
    $rowInv = $resInv ? $resInv->fetch_assoc() : ['paid' => 0, 'unpaid' => 0];
    $invoicePaid = (int)($rowInv['paid'] ?? 0);
    $invoiceUnpaid = (int)($rowInv['unpaid'] ?? 0);

    $resOrd = $connection->query("SELECT 
        SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed
        FROM orders");
    $rowOrd = $resOrd ? $resOrd->fetch_assoc() : ['pending' => 0, 'completed' => 0];
    $ordersPending = (int)($rowOrd['pending'] ?? 0);
    $ordersCompleted = (int)($rowOrd['completed'] ?? 0);

    $resDel = $connection->query("SELECT 
        SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS delivered
        FROM deliveries");
    $rowDel = $resDel ? $resDel->fetch_assoc() : ['pending' => 0, 'delivered' => 0];
    $deliveriesPending = (int)($rowDel['pending'] ?? 0);
    $deliveriesDelivered = (int)($rowDel['delivered'] ?? 0);

    $resCust = $connection->query("SELECT MONTH(created_at) AS m, COUNT(*) AS cnt FROM customers WHERE YEAR(created_at)={$year} GROUP BY MONTH(created_at)");
    $custMonthlyMap = array_fill(1, 12, 0);
    while ($row = $resCust->fetch_assoc()) {
        $custMonthlyMap[(int)$row['m']] = (int)$row['cnt'];
    }
    $customersMonthly = [];
    for ($m=1; $m<=12; $m++) {
        $customersMonthly[] = ['month' => $m, 'count' => $custMonthlyMap[$m]];
    }

    $topMap = [];
    $resItems = $connection->query("SELECT i.items_json
        FROM invoices i
        WHERE i.deleted=0
          AND EXISTS (SELECT 1 FROM deliveries d WHERE d.invoice_id = i.id AND d.status='completed')");
    while ($row = $resItems->fetch_assoc()) {
        $items = json_decode($row['items_json'] ?? '[]', true);
        if (!is_array($items)) continue;
        foreach ($items as $it) {
            $name = trim((string)($it['description'] ?? ($it['product_id'] ?? 'Item')));
            $qty = (float)($it['quantity'] ?? 0);
            $unit = (float)($it['unit_price'] ?? 0);
            $tax = (float)($it['tax'] ?? 0);
            $disc = (float)($it['discount'] ?? 0);
            $rev = max(0.0, round($qty * $unit + $tax - $disc, 2));
            if (!isset($topMap[$name])) $topMap[$name] = ['name' => $name, 'revenue' => 0.0, 'quantity' => 0.0];
            $topMap[$name]['revenue'] += $rev;
            $topMap[$name]['quantity'] += $qty;
        }
    }
    usort($topMap, function($a, $b) { return $b['revenue'] <=> $a['revenue']; });
    $topProducts = array_slice(array_values($topMap), 0, 5);

    echo json_encode([
        'success' => true,
        'data' => [
            'totals' => [
                'totalRevenue' => $totalRevenue,
                'completedOrders' => $completedOrders,
                'newCustomers' => $newCustomers,
            ],
            'sales' => [
                'daily' => $dailySales,
                'monthly' => $monthlySales,
                'top_products' => $topProducts,
            ],
            'invoices' => [
                'paid' => $invoicePaid,
                'unpaid' => $invoiceUnpaid,
                'total_revenue' => $totalRevenue,
            ],
            'orders' => [
                'pending' => $ordersPending,
                'completed' => $ordersCompleted,
            ],
            'deliveries' => [
                'pending' => $deliveriesPending,
                'delivered' => $deliveriesDelivered,
            ],
            'customers' => [
                'monthly_new' => $customersMonthly,
            ],
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to compute reports.',
        'detail' => $e->getMessage(),
    ]);
}
