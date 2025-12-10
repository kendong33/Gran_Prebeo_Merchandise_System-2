<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gran Prebeo</title>
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
            box-shadow: 4px 0 20px rgba(139, 104, 68, 0.32);
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

        .summary-boxes .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 12px 20px rgba(139, 104, 68, 0.14);
            min-height: 100%;
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 700;
        }

        .card-panel {
            border: none;
            border-radius: 18px;
            box-shadow: 0 24px 45px rgba(139, 104, 68, 0.12);
        }

        .card-panel .card-header {
            border-bottom: none;
        }

        .quick-action {
            border-radius: 14px;
            padding: 1.25rem;
            background: rgba(219, 178, 122, 0.25);
            transition: transform 0.2s ease;
        }

        .quick-action:hover {
            transform: translateY(-3px);
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
            <div class="col-lg-2 px-0 sidebar">
                <div class="brand-header">
                    <img src="images/gran-prebeo-logo.png" alt="Gran Prebeo Logo" class="brand-logo">
                </div>
                <hr class="text-white-50">
                <ul class="nav flex-column">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link active"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a href="orders.php" class="nav-link"><i class="bi bi-cart"></i> Orders</a></li>
                    <li class="nav-item"><a href="customers.php" class="nav-link"><i class="bi bi-people"></i> Customers</a></li>
                    <li class="nav-item"><a href="invoices.php" class="nav-link"><i class="bi bi-receipt"></i> Invoices</a></li>
                    <li class="nav-item"><a href="delivery.php" class="nav-link"><i class="bi bi-truck"></i> Delivery</a></li>
                    <li class="nav-item"><a href="reports.php" class="nav-link"><i class="bi bi-graph-up"></i> Reports</a></li>
                    <li class="nav-item mt-4"><a href="?logout=1" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>

            <div class="col-lg-10 main-content">
                <div class="page-header">
                    <div>
                        <h2 class="mb-1">Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?>!</h2>
                        <p class="mb-0">Gran Prebeo Merchandise Management System</p>
                    </div>
                    <div class="text-end">
                        <div class="mb-1"><small class="brand-tagline">Gran Prebeo Merchandise System</small></div>
                    </div>
                </div>

                <div class="row g-3 summary-boxes mb-4">
                    <div class="col-md-3">
                        <div class="card text-white" style="background:linear-gradient(140deg,#6a11cb,#8e54e9)">
                            <div class="card-body">
                                <h6>Total Orders</h6>
                                <div class="summary-value" id="dashTotalOrders">0</div>
                                <small class="text-white-75">All statuses</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white" style="background:linear-gradient(140deg,#0ea5e9,#2563eb)">
                            <div class="card-body">
                                <h6>Customers</h6>
                                <div class="summary-value" id="dashCustomers">0</div>
                                <small class="text-white-75">Active profiles</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white" style="background:linear-gradient(140deg,#f59e0b,#ef4444)">
                            <div class="card-body">
                                <h6>Pending Orders</h6>
                                <div class="summary-value" id="dashPendingOrders">0</div>
                                <small class="text-white-75">Awaiting fulfillment</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white" style="background:linear-gradient(140deg,#22c55e,#16a34a)">
                            <div class="card-body">
                                <h6>Revenue</h6>
                                <div class="summary-value" id="dashRevenue">₱0</div>
                                <small class="text-white-75">Lifetime</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-xl-8">
                        <div class="card card-panel">
                            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Orders</h5>
                                <a href="orders.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-up-right"></i> View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Status</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recentOrdersBody">
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">Loading...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="card card-panel h-100">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-sm-12">
                                        <a href="orders.php" class="btn btn-outline-primary w-100 py-3"><i class="bi bi-plus-circle"></i> New Order</a>
                                    </div>
                                    <div class="col-sm-12">
                                        <a href="customers.php" class="btn btn-outline-success w-100 py-3"><i class="bi bi-person-plus"></i> Add Customer</a>
                                    </div>
                                    <div class="col-sm-12">
                                        <a href="delivery.php" class="btn btn-outline-info w-100 py-3"><i class="bi bi-truck"></i> Add Delivery</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-xl-12">
                        <div class="card card-panel h-100">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">Activity Overview</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-column gap-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <span class="fw-semibold">New customers</span>
                                            <p class="mb-0 text-muted">New this month</p>
                                        </div>
                                        <span class="badge bg-primary rounded-pill align-self-center" id="aoNewCustomers">0</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <span class="fw-semibold">Pending deliveries</span>
                                            <p class="mb-0 text-muted">Awaiting courier pickup</p>
                                        </div>
                                        <span class="badge bg-warning text-dark rounded-pill align-self-center" id="aoPendingDeliveries">0</span>
                                    </div>
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

        function formatCurrency(value) {
            const number = Number(value);
            if (Number.isNaN(number)) return '₱0.00';
            return `₱${number.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        function renderOrderStatusBadge(status) {
            const s = String(status || '').toLowerCase();
            const map = {
                pending: 'badge bg-warning text-dark',
                processing: 'badge bg-info',
                completed: 'badge bg-success',
                cancelled: 'badge bg-danger',
            };
            const cls = map[s] || 'badge bg-secondary';
            const label = s.charAt(0).toUpperCase() + s.slice(1);
            return `<span class="${cls}">${label || 'Unknown'}</span>`;
        }

        async function loadReportsSnapshot() {
            try {
                const endpoint = new URL('backend/reports.php', window.location.href);
                const res = await fetch(endpoint);
                const payload = await res.json();
                if (!res.ok || !payload.success) throw new Error(payload.message || 'Failed to load reports snapshot');

                const data = payload.data || {};
                const totals = data.totals || {};
                const orders = data.orders || {};
                const deliveries = data.deliveries || {};
                const customers = data.customers || {};

                const revenueEl = document.getElementById('dashRevenue');
                if (revenueEl) revenueEl.textContent = formatCurrency(totals.totalRevenue || 0);

                const pendingOrdersEl = document.getElementById('dashPendingOrders');
                if (pendingOrdersEl) pendingOrdersEl.textContent = Number(orders.pending || 0).toString();

                const aoNewCustomers = document.getElementById('aoNewCustomers');
                if (aoNewCustomers) aoNewCustomers.textContent = Number(totals.newCustomers || 0).toString();

                const aoPendingDeliveries = document.getElementById('aoPendingDeliveries');
                if (aoPendingDeliveries) aoPendingDeliveries.textContent = Number(deliveries.pending || 0).toString();
            } catch (e) {
                // silently ignore on dashboard
            }
        }

        async function loadCountsAndRecent() {
            try {
                // Orders: count and recent list
                const ordersUrl = new URL('backend/orders.php', window.location.href);
                const ordersRes = await fetch(ordersUrl);
                const ordersPayload = await ordersRes.json();
                if (ordersRes.ok && ordersPayload.success && Array.isArray(ordersPayload.data)) {
                    const list = ordersPayload.data;
                    const totalOrdersEl = document.getElementById('dashTotalOrders');
                    if (totalOrdersEl) totalOrdersEl.textContent = list.length.toString();

                    // Sort by order_date desc then id desc
                    list.sort((a, b) => {
                        const da = new Date(a.order_date || 0).getTime();
                        const db = new Date(b.order_date || 0).getTime();
                        if (db !== da) return db - da;
                        return (b.id || 0) - (a.id || 0);
                    });

                    const body = document.getElementById('recentOrdersBody');
                    if (body) {
                        body.innerHTML = '';
                        const top = list.slice(0, 5);
                        if (top.length === 0) {
                            body.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No orders recorded yet.</td></tr>';
                        } else {
                            top.forEach(o => {
                                const tr = document.createElement('tr');
                                const customer = `${(o.first_name || '').trim()} ${(o.last_name || '').trim()}`.trim() || '—';
                                tr.innerHTML = `
                                    <td>${o.id}</td>
                                    <td>${customer}</td>
                                    <td>${renderOrderStatusBadge(o.status)}</td>
                                    <td class="text-end">${formatCurrency(o.total_amount)}</td>
                                `;
                                body.appendChild(tr);
                            });
                        }
                    }
                }
            } catch (_) {}

            try {
                // Customers: count
                const customersUrl = new URL('backend/customers.php', window.location.href);
                const customersRes = await fetch(customersUrl);
                const customersPayload = await customersRes.json();
                if (customersRes.ok && customersPayload.success && Array.isArray(customersPayload.data)) {
                    const customersEl = document.getElementById('dashCustomers');
                    if (customersEl) customersEl.textContent = customersPayload.data.length.toString();
                }
            } catch (_) {}
        }

        (async () => {
            await loadReportsSnapshot();
            await loadCountsAndRecent();
        })();
    </script>
</body>
</html>
