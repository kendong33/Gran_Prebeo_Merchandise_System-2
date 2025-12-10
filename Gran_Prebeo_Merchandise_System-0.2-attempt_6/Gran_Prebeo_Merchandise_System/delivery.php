<?php
session_start();

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
    <title>Delivery - Gran Prebeo</title>
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

        .status-badge {
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .status-out { background: rgba(217, 182, 123, 0.4); color: #8d6a4f; }
        .status-delivered { background: rgba(164, 210, 150, 0.35); color: #3c6b3a; }
        .status-pending { background: rgba(251, 207, 146, 0.45); color: #8a5a2f; }
        .status-delayed { background: rgba(248, 113, 113, 0.25); color: #b91c1c; }

        body.dark-mode .status-out { background: rgba(217, 182, 123, 0.35); color: #f5d8b0; }
        body.dark-mode .status-delivered { background: rgba(164, 210, 150, 0.35); color: #ade2a5; }
        body.dark-mode .status-pending { background: rgba(251, 207, 146, 0.35); color: #ffd6a4; }
        body-dark-mode .status-delayed { background: rgba(248, 113, 113, 0.35); color: #f6b2b2; }
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
                    <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a href="orders.php" class="nav-link"><i class="bi bi-cart"></i> Orders</a></li>
                    <li class="nav-item"><a href="customers.php" class="nav-link"><i class="bi bi-people"></i> Customers</a></li>
                    <li class="nav-item"><a href="invoices.php" class="nav-link"><i class="bi bi-receipt"></i> Invoices</a></li>
                    <li class="nav-item"><a href="delivery.php" class="nav-link active"><i class="bi bi-truck"></i> Delivery</a></li>
                    <li class="nav-item"><a href="reports.php" class="nav-link"><i class="bi bi-graph-up"></i> Reports</a></li>
                    <li class="nav-item mt-4"><a href="dashboard.php?logout=1" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>

            <div class="col-lg-10 main-content">
                <div class="page-header">
                    <div>
                        <h2 class="mb-1">Delivery Tracking</h2>
                        <p class="mb-0">Monitor shipment statuses and schedules</p>
                    </div>
                    <div class="text-end">
                        <div class="mb-1"><small class="brand-tagline">Gran Prebeo Merchandise System</small></div>
                    </div>
                </div>

                <div class="row g-3 summary-cards mb-4">
                    <div class="col-md-3">
                        <div class="card text-white" style="background:linear-gradient(140deg,#2563eb,#1d4ed8)">
                            <div class="card-body">
                                <h6>Out for Delivery</h6>
                                <div class="summary-value" id="outForDeliveryCount">0</div>
                                <small class="text-white-75">Packages on route</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white" style="background:linear-gradient(140deg,#22c55e,#15803d)">
                            <div class="card-body">
                                <h6>Delivered</h6>
                                <div class="summary-value" id="deliveredCount">0</div>
                                <small class="text-white-75">Completed today</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white" style="background:linear-gradient(140deg,#f59e0b,#d97706)">
                            <div class="card-body">
                                <h6>Pending Pickup</h6>
                                <div class="summary-value" id="pendingPickupCount">0</div>
                                <small class="text-white-75">Awaiting courier</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white" style="background:linear-gradient(140deg,#ef4444,#b91c1c)">
                            <div class="card-body">
                                <h6>Delayed</h6>
                                <div class="summary-value" id="delayedCount">0</div>
                                <small class="text-white-75">Needs attention</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-surface mb-4">
                    <div class="card-body">
                        <div class="row g-3 filters">
                            <div class="col-md-4">
                                <label class="form-label">Search Deliveries</label>
                                <input type="text" class="form-control" id="deliverySearch" placeholder="Search by tracking ID, invoice #, or customer">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Delivery Status</label>
                                <select class="form-select" id="deliveryStatusFilter">
                                    <option value="" selected>All statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Schedule Date</label>
                                <input type="date" class="form-control" id="deliveryScheduleDate">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-surface">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Delivery List</h5>
                        <button class="btn btn-warning btn-sm text-white" id="addDeliveryBtn">
                            <i class="bi bi-plus-circle"></i> Add Delivery
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Tracking ID</th>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Courier</th>
                                        <th>Status</th>
                                        <th>Expected Date</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="deliveriesTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Add Delivery Modal -->
                <div class="modal fade" id="deliveryModal" tabindex="-1" aria-labelledby="deliveryModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deliveryModalLabel">Add Delivery</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-danger d-none" id="deliveryFormError"></div>
                                <form id="deliveryForm">
                                    <div class="mb-3">
                                        <label class="form-label">Invoice</label>
                                        <select class="form-select" id="invoiceSelect" name="invoice_id" required></select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Customer</label>
                                        <input type="text" class="form-control" id="deliveryCustomer" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" id="deliveryAddress" rows="2" readonly></textarea>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Type</label>
                                            <input type="text" class="form-control" id="deliveryType" value="Invoice" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Order / Invoice #</label>
                                            <input type="text" class="form-control" id="deliveryRef" readonly>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Delivery Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="delivery_date" id="deliveryDate" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Courier <span class="text-danger">*</span></label>
                                            <select class="form-select" name="courier" id="deliveryCourier" required>
                                                <option value="" selected>Select courier</option>
                                                <option value="Car 1">Car 1</option>
                                                <option value="Car 2">Car 2</option>
                                                <option value="Car 3">Car 3</option>
                                                <option value="Car 4">Car 4</option>
                                                <option value="Car 5">Car 5</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Status <span class="text-danger">*</span></label>
                                            <select class="form-select" name="status" id="deliveryStatus" required>
                                                <option value="pending" selected>Pending</option>
                                                <option value="shipped">Shipped</option>
                                                <option value="completed">Completed</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Notes</label>
                                            <textarea class="form-control" name="notes" id="deliveryNotes" rows="3"></textarea>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" form="deliveryForm" id="saveDeliveryBtn">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        const deliveriesTableBody = document.getElementById('deliveriesTableBody');
        const deliverySearch = document.getElementById('deliverySearch');
        const deliveryStatusFilter = document.getElementById('deliveryStatusFilter');
        const deliveryScheduleDate = document.getElementById('deliveryScheduleDate');
        const outForDeliveryCount = document.getElementById('outForDeliveryCount');
        const deliveredCount = document.getElementById('deliveredCount');
        const pendingPickupCount = document.getElementById('pendingPickupCount');
        const delayedCount = document.getElementById('delayedCount');
        const addDeliveryBtn = document.getElementById('addDeliveryBtn');
        const deliveryModalElement = document.getElementById('deliveryModal');
        const deliveryModal = new bootstrap.Modal(deliveryModalElement);
        const deliveryForm = document.getElementById('deliveryForm');
        const deliveryFormError = document.getElementById('deliveryFormError');
        const invoiceSelect = document.getElementById('invoiceSelect');
        const deliveryCustomer = document.getElementById('deliveryCustomer');
        const deliveryAddress = document.getElementById('deliveryAddress');
        const deliveryType = document.getElementById('deliveryType');
        const deliveryRef = document.getElementById('deliveryRef');
        const deliveryDate = document.getElementById('deliveryDate');
        const deliveryStatus = document.getElementById('deliveryStatus');
        const deliveryNotes = document.getElementById('deliveryNotes');

        function formatDate(value) {
            if (!value) return '—';
            const d = new Date(value);
            return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString();
        }

        function renderDeliveries(rows) {
            deliveriesTableBody.innerHTML = '';
            if (!Array.isArray(rows) || rows.length === 0) {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = 7;
                td.className = 'text-center text-muted py-4';
                td.textContent = 'No deliveries scheduled yet.';
                tr.appendChild(td);
                deliveriesTableBody.appendChild(tr);
                updateSummaryCounters([]);
                return;
            }

            rows.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${row.delivery_tracking_id || row.tracking_number || '—'}</td>
                    <td>${row.order_id || '—'}</td>
                    <td>${row.customer_name || '—'}</td>
                    <td>${row.courier || '—'}</td>
                    <td>${(row.status || '').charAt(0).toUpperCase() + (row.status || '').slice(1)}</td>
                    <td>${formatDate(row.delivery_date)}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-outline-primary btn-sm me-2" ${row.status === 'shipped' ? 'disabled' : ''} data-action="deliver">Deliver</button>
                        <button type="button" class="btn btn-outline-success btn-sm" ${row.status === 'completed' ? 'disabled' : ''} data-action="delivered">Delivered</button>
                    </td>
                `;
                // Attach button handlers
                const [deliverBtn, deliveredBtn] = Array.from(tr.querySelectorAll('button'));
                if (deliverBtn) {
                    deliverBtn.addEventListener('click', async () => {
                        const ok = confirm('Mark this delivery as Out for Delivery?');
                        if (!ok) return;
                        await updateDeliveryStatus(row.id, 'shipped');
                    });
                }
                if (deliveredBtn) {
                    deliveredBtn.addEventListener('click', async () => {
                        const ok = confirm('Mark this delivery as Delivered?');
                        if (!ok) return;
                        await updateDeliveryStatus(row.id, 'completed');
                    });
                }
                deliveriesTableBody.appendChild(tr);
            });

            updateSummaryCounters(rows);
        }

        async function loadDeliveries() {
            try {
                const endpoint = new URL('backend/deliveries.php', window.location.href);
                const search = (deliverySearch?.value || '').trim();
                const status = (deliveryStatusFilter?.value || '').trim();
                const schedule = (deliveryScheduleDate?.value || '').trim();
                if (search !== '') endpoint.searchParams.set('search', search);
                if (status !== '') endpoint.searchParams.set('status', status);
                if (schedule !== '') endpoint.searchParams.set('schedule_date', schedule);
                const res = await fetch(endpoint);
                if (!res.ok) throw new Error('Failed to load deliveries');
                const payload = await res.json();
                const rows = Array.isArray(payload.data) ? payload.data : [];
                renderDeliveries(rows);
            } catch (err) {
                deliveriesTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${err.message}</td></tr>`;
                updateSummaryCounters([]);
            }
        }

        // Attach filter listeners
        (function attachDeliveryFilters(){
            if (deliverySearch) {
                let t; deliverySearch.addEventListener('input', () => {
                    clearTimeout(t);
                    t = setTimeout(() => loadDeliveries(), 250);
                });
            }
            if (deliveryStatusFilter) {
                deliveryStatusFilter.addEventListener('change', () => loadDeliveries());
            }
            if (deliveryScheduleDate) {
                deliveryScheduleDate.addEventListener('change', () => loadDeliveries());
            }
        })();

        async function updateDeliveryStatus(id, status) {
            try {
                const endpoint = new URL('backend/deliveries.php', window.location.href);
                const res = await fetch(endpoint, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, status })
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    const msg = data && data.message ? data.message : 'Failed to update delivery status.';
                    throw new Error(msg);
                }
                await loadDeliveries();
            } catch (err) {
                alert(err.message);
            }
        }

        function isPast(dateStr) {
            if (!dateStr) return false;
            const d = new Date(dateStr);
            const today = new Date();
            today.setHours(0,0,0,0);
            d.setHours(0,0,0,0);
            return d.getTime() < today.getTime();
        }

        function updateSummaryCounters(rows) {
            let outForDelivery = 0;
            let delivered = 0;
            let pending = 0;
            let delayed = 0;

            rows.forEach(r => {
                const status = (r.status || '').toLowerCase();
                if (status === 'shipped') outForDelivery += 1;
                if (status === 'completed') delivered += 1;
                if (status === 'pending') pending += 1;
                if (status !== 'completed' && isPast(r.delivery_date)) delayed += 1;
            });

            if (outForDeliveryCount) outForDeliveryCount.textContent = outForDelivery;
            if (deliveredCount) deliveredCount.textContent = delivered;
            if (pendingPickupCount) pendingPickupCount.textContent = pending;
            if (delayedCount) delayedCount.textContent = delayed;
        }

        function buildAddress(inv) {
            const parts = [inv.billing_street, inv.billing_city, inv.billing_state, inv.billing_postal_code, inv.billing_country];
            return parts
                .map((part) => (part || '').trim())
                .filter((part) => part !== '' && part.toLowerCase() !== 'not provided' && part !== '0000')
                .join(', ');
        }

        function fillInvoiceFields(inv) {
            const addr = buildAddress(inv);
            deliveryCustomer.value = inv.customer_name || '';
            deliveryAddress.value = addr;
            deliveryType.value = 'Invoice';
            deliveryRef.value = `${inv.order_id || ''} / ${inv.invoice_number || ''}`.trim();
        }

        async function loadAvailableInvoices() {
            invoiceSelect.innerHTML = '<option value="">Loading...</option>';
            try {
                const endpoint = new URL('backend/deliveries.php', window.location.href);
                endpoint.searchParams.set('available_invoices', '1');
                const res = await fetch(endpoint);
                if (!res.ok) throw new Error('Failed to load invoices');
                const data = await res.json();
                const invoices = Array.isArray(data.data) ? data.data : [];
                invoiceSelect.innerHTML = '<option value="">Select invoice</option>';
                invoices.forEach(inv => {
                    const opt = document.createElement('option');
                    opt.value = inv.id;
                    opt.textContent = `${inv.invoice_number} — ${inv.customer_name}`;
                    opt.dataset.payload = JSON.stringify(inv);
                    invoiceSelect.appendChild(opt);
                });
            } catch (e) {
                invoiceSelect.innerHTML = '<option value="">Failed to load invoices</option>';
            }
        }

        invoiceSelect?.addEventListener('change', () => {
            const selected = invoiceSelect.options[invoiceSelect.selectedIndex];
            const data = selected && selected.dataset.payload ? JSON.parse(selected.dataset.payload) : null;
            if (data) fillInvoiceFields(data);
        });

        addDeliveryBtn?.addEventListener('click', async () => {
            deliveryForm.reset();
            deliveryFormError.classList.add('d-none');
            deliveryFormError.textContent = '';
            await loadAvailableInvoices();
            const today = new Date();
            deliveryDate.value = today.toISOString().slice(0, 10);
            deliveryModal.show();
        });

        deliveryForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            deliveryFormError.classList.add('d-none');
            deliveryFormError.textContent = '';

            const formData = new FormData(deliveryForm);
            const payload = Object.fromEntries(formData.entries());

            try {
                const endpoint = new URL('backend/deliveries.php', window.location.href);
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    const msg = data.errors ? Object.values(data.errors).join(' ') : (data.message || 'Failed to create delivery.');
                    throw new Error(msg);
                }
                deliveryModal.hide();
                loadDeliveries();
                alert('Delivery created successfully.');
            } catch (error) {
                deliveryFormError.textContent = error.message;
                deliveryFormError.classList.remove('d-none');
            }
        });

        loadDeliveries();
    </script>
</body>
</html>
