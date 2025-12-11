<?php
require_once __DIR__ . '/backend/session_bootstrap.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Gran Prebeo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #dbb27a;
            --secondary: #8d6a4f;
            --sidebar-bg: #c3925b;
            --sidebar-hover: rgba(255, 255, 255, 0.18);
            --surface: #fff9f2;
            --background: #f7efe5;
            --text: #3c2a1e;
            --muted: #8a725f;
        }

        body {
            background: var(--background);
            color: var(--text);
            transition: background 0.3s ease, color 0.3s ease;
            min-height: 100vh;
            overflow: hidden;
        }

        body.dark-mode {
            --surface: #2c2621;
            --background: #1d1814;
            --text: #f8f3ec;
            --muted: #cbb8a6;
        }

        body.dark-mode .card,
        body.dark-mode .table,
        body.dark-mode .modal-content {
            background: var(--surface);
            color: var(--text);
        }

        .container-fluid > .row { min-height: 100vh; }

        .sidebar {
            min-height: 100vh;
            background: var(--sidebar-bg);
            color: #fff8f0;
            padding: 1.5rem 0;
            box-shadow: 4px 0 18px rgba(139, 104, 68, 0.32);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .brand-header {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 1.5rem 1.5rem;
        }

        .brand-logo {
            height: 96px;
            width: auto;
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0.35rem 1.25rem;
            padding: 0.8rem 1rem;
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.82);
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--sidebar-hover);
            color: #fff;
            transform: translateX(4px);
        }

        .main-content {
            padding: 2.25rem;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            background: var(--primary);
            color: #3c2a1e;
            padding: 1.8rem 2.2rem;
            margin: -2.25rem -2.25rem 2.5rem -2.25rem;
            border-radius: 0 0 18px 18px;
            box-shadow: 0 20px 36px rgba(139, 104, 68, 0.25);
        }

        .toggle-switch {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            cursor: pointer;
        }

        .toggle-switch input {
            display: none;
        }

        .toggle-track {
            width: 48px;
            height: 24px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.35);
            position: relative;
            transition: background 0.3s ease;
        }

        .toggle-thumb {
            position: absolute;
            top: 3px;
            left: 4px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #fff8f0;
            transition: transform 0.3s ease;
        }

        .toggle-switch input:checked + .toggle-track {
            background: rgba(255, 255, 255, 0.68);
        }

        .toggle-switch input:checked + .toggle-track .toggle-thumb {
            transform: translateX(22px);
        }

        .brand-tagline {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            letter-spacing: .02em;
        }

        .card-surface {
            border: none;
            border-radius: 18px;
            box-shadow: 0 24px 45px rgba(139, 104, 68, 0.12);
        }

        .card-surface .card-header {
            border-bottom: none;
        }

        .summary-cards .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 16px 28px rgba(139, 104, 68, 0.12);
            min-height: 100%;
            color: #fff;
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 700;
        }

        .filters .form-control,
        .filters .form-select {
            border-radius: 12px;
        }

        .filters .form-control:focus,
        .filters .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(219, 178, 122, 0.35);
        }

        .card-report {
            border: none;
            border-radius: 18px;
            box-shadow: 0 24px 45px rgba(139, 104, 68, 0.12);
        }

        .chart-placeholder {
            height: 280px;
            border-radius: 14px;
            background: repeating-linear-gradient(135deg, rgba(219, 178, 122, 0.25), rgba(219, 178, 122, 0.25) 10px, rgba(141, 106, 79, 0.18) 10px, rgba(141, 106, 79, 0.18) 20px);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(15,23,42,0.35);
            font-weight: 600;
            letter-spacing: 0.05rem;
        }

        .actions .btn {
            border-radius: 30px;
        }

        .table thead th {
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.75rem;
            border-bottom: none;
        }

        .table tbody tr {
            transition: background 0.2s ease;
        }

        .table tbody tr:hover {
            background: rgba(219, 178, 122, 0.18);
        }

        body.dark-mode .table tbody tr:hover {
            background: rgba(219, 178, 122, 0.33);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 px-0 sidebar">
                <div class="brand-header mb-4">
                    <img src="images/gran-prebeo-logo.png" alt="Gran Prebeo Logo" class="brand-logo">
                </div>
                <hr>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="orders.php" class="nav-link">
                            <i class="bi bi-cart"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="customers.php" class="nav-link">
                            <i class="bi bi-people"></i> Customers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="invoices.php" class="nav-link">
                            <i class="bi bi-receipt"></i> Invoices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="delivery.php" class="nav-link">
                            <i class="bi bi-truck"></i> Delivery
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reports.php" class="nav-link active">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a href="dashboard.php?logout=1" class="nav-link text-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-md-10 main-content">
                <div class="page-header">
                    <div>
                        <h2 class="mb-1">Reports & Analytics</h2>
                        <p class="mb-0">Visualize sales performance and customer trends</p>
                    </div>
                    <div class="text-end">
                        <div class="mb-1"><small class="brand-tagline">Gran Prebeo Merchandise System</small></div>
                    </div>
                </div>

                <div class="row g-3 summary-cards mb-4">
                    <div class="col-md-4">
                        <div class="card" style="background-color: #6a11cb;">
                            <div class="card-body">
                                <h6 class="card-title">Total Revenue</h6>
                                <h3 class="mb-0" id="metricRevenue">₱0</h3>
                                <small class="text-white-50">vs last period 0%</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card" style="background-color: #2575fc;">
                            <div class="card-body">
                                <h6 class="card-title">Orders</h6>
                                <h3 class="mb-0" id="metricOrders">0</h3>
                                <small class="text-white-50">Completed</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card" style="background-color: #f39c12;">
                            <div class="card-body">
                                <h6 class="card-title">New Customers</h6>
                                <h3 class="mb-0" id="metricNewCustomers">0</h3>
                                <small class="text-white-50">This month</small>
                            </div>
                        </div>
                    </div>
                </div>

                

                <div class="row mt-4 g-4">
                    <div class="col-lg-8">
                        <div class="card card-report">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Sales Reports</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <h6 class="mb-2">Daily Sales (7 days)</h6>
                                        <div class="table-responsive">
                                            <table class="table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th class="text-end">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="dailySalesBody">
                                                    <tr>
                                                        <td colspan="2" class="text-center text-muted">No data</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-2">Monthly Sales (This Year)</h6>
                                        <div class="table-responsive">
                                            <table class="table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Month</th>
                                                        <th class="text-end">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="monthlySalesBody">
                                                    <tr>
                                                        <td colspan="2" class="text-center text-muted">No data</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="d-flex flex-column gap-4 h-100">
                            <div class="card card-report flex-fill">
                                <div class="card-header bg-white"><h5 class="mb-0">Invoice Reports</h5></div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <span>Paid</span><strong id="invoicePaidCount">0</strong>
                                    </div>
                                    <div class="progress mb-3" style="height:10px">
                                        <div class="progress-bar bg-success" id="invoicePaidBar" style="width:0%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Unpaid</span><strong id="invoiceUnpaidCount">0</strong>
                                    </div>
                                    <div class="progress" style="height:10px">
                                        <div class="progress-bar bg-warning" id="invoiceUnpaidBar" style="width:0%"></div>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <span>Total Revenue</span><strong id="invoiceTotalRevenue">₱0</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="card card-report flex-fill">
                                <div class="card-header bg-white"><h5 class="mb-0">Order Reports</h5></div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <span>Pending</span><strong id="ordersPendingCount">0</strong>
                                    </div>
                                    <div class="progress mb-3" style="height:10px">
                                        <div class="progress-bar bg-secondary" id="ordersPendingBar" style="width:0%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Completed</span><strong id="ordersCompletedCount">0</strong>
                                    </div>
                                    <div class="progress" style="height:10px">
                                        <div class="progress-bar bg-success" id="ordersCompletedBar" style="width:0%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="card card-report flex-fill">
                                <div class="card-header bg-white"><h5 class="mb-0">Delivery Reports</h5></div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <span>Pending</span><strong id="deliveriesPendingCount">0</strong>
                                    </div>
                                    <div class="progress mb-3" style="height:10px">
                                        <div class="progress-bar bg-secondary" id="deliveriesPendingBar" style="width:0%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Delivered</span><strong id="deliveriesDeliveredCount">0</strong>
                                    </div>
                                    <div class="progress" style="height:10px">
                                        <div class="progress-bar bg-success" id="deliveriesDeliveredBar" style="width:0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customers -->
                <div class="row mt-4 g-4">
                    <div class="col-lg-6">
                        <div class="card card-report h-100">
                            <div class="card-header bg-white"><h5 class="mb-0">Customer Reports</h5></div>
                            <div class="card-body">
                                <h6 class="mb-2">New Customers per Month</h6>
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Month</th>
                                                <th class="text-end">New Customers</th>
                                            </tr>
                                        </thead>
                                        <tbody id="customersMonthlyBody">
                                            <tr>
                                                <td colspan="2" class="text-center text-muted">No data</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        // Reports metrics and datasets
        const metricRevenue = document.getElementById('metricRevenue');
        const metricOrders = document.getElementById('metricOrders');
        const metricNewCustomers = document.getElementById('metricNewCustomers');
        const dailySalesBody = document.getElementById('dailySalesBody');
        const monthlySalesBody = document.getElementById('monthlySalesBody');
        const invoicePaidCount = document.getElementById('invoicePaidCount');
        const invoiceUnpaidCount = document.getElementById('invoiceUnpaidCount');
        const invoicePaidBar = document.getElementById('invoicePaidBar');
        const invoiceUnpaidBar = document.getElementById('invoiceUnpaidBar');
        const invoiceTotalRevenue = document.getElementById('invoiceTotalRevenue');
        const ordersPendingCount = document.getElementById('ordersPendingCount');
        const ordersCompletedCount = document.getElementById('ordersCompletedCount');
        const ordersPendingBar = document.getElementById('ordersPendingBar');
        const ordersCompletedBar = document.getElementById('ordersCompletedBar');
        const deliveriesPendingCount = document.getElementById('deliveriesPendingCount');
        const deliveriesDeliveredCount = document.getElementById('deliveriesDeliveredCount');
        const deliveriesPendingBar = document.getElementById('deliveriesPendingBar');
        const deliveriesDeliveredBar = document.getElementById('deliveriesDeliveredBar');
        const customersMonthlyBody = document.getElementById('customersMonthlyBody');

        function formatPHP(amount) {
            try {
                return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 2 }).format(amount || 0);
            } catch (_) {
                const n = Number(amount || 0).toFixed(2);
                return '₱' + n.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
        }

        function percent(n, d) { return d > 0 ? Math.round((n / d) * 100) : 0; }

        function monthName(m) {
            const names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const idx = Math.max(1, Math.min(12, Number(m))) - 1; return names[idx];
        }

        function renderTableBody(bodyEl, rows, columns) {
            if (!bodyEl) return;
            if (!Array.isArray(rows) || rows.length === 0) {
                bodyEl.innerHTML = '<tr><td colspan="' + columns + '" class="text-center text-muted">No data</td></tr>';
                return;
            }
            const frag = document.createDocumentFragment();
            rows.forEach(r => frag.appendChild(r));
            bodyEl.innerHTML = '';
            bodyEl.appendChild(frag);
        }

        function makeRow(cells) {
            const tr = document.createElement('tr');
            tr.innerHTML = cells.join('');
            return tr;
        }

        async function loadReportData() {
            try {
                const endpoint = new URL('backend/reports.php', window.location.href);
                const res = await fetch(endpoint);
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Failed to load reports');

                const d = data.data || {};

                // Top metrics
                const t = d.totals || {};
                if (metricRevenue) metricRevenue.textContent = formatPHP(t.totalRevenue || 0);
                if (metricOrders) metricOrders.textContent = (t.completedOrders || 0).toLocaleString();
                if (metricNewCustomers) metricNewCustomers.textContent = (t.newCustomers || 0).toLocaleString();

                // Sales - daily
                const daily = Array.isArray(d.sales?.daily) ? d.sales.daily : [];
                const dailyRows = daily.map(item => makeRow([
                    `<td>${item.date}</td>`,
                    `<td class="text-end">${formatPHP(item.total)}</td>`
                ]));
                renderTableBody(dailySalesBody, dailyRows, 2);

                // Sales - monthly
                const monthly = Array.isArray(d.sales?.monthly) ? d.sales.monthly : [];
                const monthlyRows = monthly.map(item => {
                    let label = item.month;
                    // if month like YYYY-MM, show MMM YYYY
                    if (/^\d{4}-\d{2}$/.test(label)) {
                        const [yyyy, mm] = label.split('-');
                        label = `${monthName(mm)} ${yyyy}`;
                    }
                    return makeRow([
                        `<td>${label}</td>`,
                        `<td class="text-end">${formatPHP(item.total)}</td>`
                    ]);
                });
                renderTableBody(monthlySalesBody, monthlyRows, 2);

                

                // Invoices
                const inv = d.invoices || {};
                const invTotal = (inv.paid || 0) + (inv.unpaid || 0);
                if (invoicePaidCount) invoicePaidCount.textContent = (inv.paid || 0).toLocaleString();
                if (invoiceUnpaidCount) invoiceUnpaidCount.textContent = (inv.unpaid || 0).toLocaleString();
                if (invoicePaidBar) invoicePaidBar.style.width = percent(inv.paid || 0, invTotal) + '%';
                if (invoiceUnpaidBar) invoiceUnpaidBar.style.width = percent(inv.unpaid || 0, invTotal) + '%';
                if (invoiceTotalRevenue) invoiceTotalRevenue.textContent = formatPHP(inv.total_revenue || 0);

                // Orders
                const ord = d.orders || {};
                const ordTotal = (ord.pending || 0) + (ord.completed || 0);
                if (ordersPendingCount) ordersPendingCount.textContent = (ord.pending || 0).toLocaleString();
                if (ordersCompletedCount) ordersCompletedCount.textContent = (ord.completed || 0).toLocaleString();
                if (ordersPendingBar) ordersPendingBar.style.width = percent(ord.pending || 0, ordTotal) + '%';
                if (ordersCompletedBar) ordersCompletedBar.style.width = percent(ord.completed || 0, ordTotal) + '%';

                // Deliveries
                const del = d.deliveries || {};
                const delTotal = (del.pending || 0) + (del.delivered || 0);
                if (deliveriesPendingCount) deliveriesPendingCount.textContent = (del.pending || 0).toLocaleString();
                if (deliveriesDeliveredCount) deliveriesDeliveredCount.textContent = (del.delivered || 0).toLocaleString();
                if (deliveriesPendingBar) deliveriesPendingBar.style.width = percent(del.pending || 0, delTotal) + '%';
                if (deliveriesDeliveredBar) deliveriesDeliveredBar.style.width = percent(del.delivered || 0, delTotal) + '%';

                // Customers monthly
                const cm = Array.isArray(d.customers?.monthly_new) ? d.customers.monthly_new : [];
                const cmRows = cm.map(r => makeRow([
                    `<td>${monthName(r.month)}</td>`,
                    `<td class="text-end">${(r.count || 0).toLocaleString()}</td>`
                ]));
                renderTableBody(customersMonthlyBody, cmRows, 2);
            } catch (err) {
                console.error(err);
            }
        }

        loadReportData();
    </script>
</body>
</html>
