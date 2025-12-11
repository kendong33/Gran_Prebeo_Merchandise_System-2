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
    <title>Orders - Gran Prebeo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
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
            box-shadow: 4px 0 18px rgba(122, 92, 61, 0.35);
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

        .filters .form-control,
        .filters .form-select {
            border-radius: 12px;
        }

        .filters .form-control:focus,
        .filters .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(37, 117, 252, 0.18);
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
            background: rgba(37, 117, 252, 0.08);
        }

        body.dark-mode .table tbody tr:hover {
            background: rgba(59, 130, 246, 0.2);
        }

        .status-badge {
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: capitalize;
        }

        .status-pending { background: rgba(251, 191, 36, 0.25); color: #92400e; }
        .status-processing { background: rgba(59, 130, 246, 0.22); color: #1d4ed8; }
        .status-completed { background: rgba(34, 197, 94, 0.22); color: #15803d; }
        .status-cancelled { background: rgba(248, 113, 113, 0.25); color: #b91c1c; }

        body.dark-mode .status-pending { background: rgba(251, 191, 36, 0.32); color: #facc15; }
        body.dark-mode .status-processing { background: rgba(59, 130, 246, 0.32); color: #93c5fd; }
        body.dark-mode .status-completed { background: rgba(34, 197, 94, 0.3); color: #4ade80; }
        body.dark-mode .status-cancelled { background: rgba(248, 113, 113, 0.35); color: #f87171; }

        .btn-purple { color: #fff; background-color: #6f42c1; border-color: #6f42c1; }
        .btn-purple:hover, .btn-purple:focus { color: #fff; background-color: #5a36a0; border-color: #5a36a0; }
        .btn-outline-purple { color: #6f42c1; border-color: #6f42c1; }
        .btn-outline-purple:hover, .btn-outline-purple:focus { color: #fff; background-color: #6f42c1; border-color: #6f42c1; }
        .select2-container { width: 100% !important; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { display: none; }
        
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
                    <li class="nav-item"><a href="orders.php" class="nav-link active"><i class="bi bi-cart"></i> Orders</a></li>
                    <li class="nav-item"><a href="customers.php" class="nav-link"><i class="bi bi-people"></i> Customers</a></li>
                    <li class="nav-item"><a href="invoices.php" class="nav-link"><i class="bi bi-receipt"></i> Invoices</a></li>
                    <li class="nav-item"><a href="delivery.php" class="nav-link"><i class="bi bi-truck"></i> Delivery</a></li>
                    <li class="nav-item"><a href="reports.php" class="nav-link"><i class="bi bi-graph-up"></i> Reports</a></li>
                    <li class="nav-item mt-4"><a href="dashboard.php?logout=1" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>

            <div class="col-lg-10 main-content">
                <div class="page-header">
                    <div>
                        <h2 class="mb-1">Orders Overview</h2>
                        <p class="mb-0">Track and manage customer orders</p>
                    </div>
                    <div class="text-end">
                        <div class="mb-1"><small class="brand-tagline">Gran Prebeo Merchandise System</small></div>
                    </div>
                </div>

                <div class="card card-surface mb-4">
                    <div class="card-body">
                        <div class="row g-3 filters">
                            <div class="col-lg-4">
                                <label class="form-label">Search Orders</label>
                                <input type="text" class="form-control" placeholder="Search by order ID or customer" id="searchOrders">
                            </div>
                            <div class="col-lg-4">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="" selected>All statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <label class="form-label">Date Range</label>
                                <div class="d-flex gap-2">
                                    <input type="date" class="form-control" id="startDate">
                                    <input type="date" class="form-control" id="endDate">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-surface">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Orders List</h5>
                        <div class="d-flex align-items-center">
                            <a href="orders.php" id="backToOrdersBtn" class="btn btn-outline-secondary btn-sm me-2 d-none">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                            <a href="?archived=1" class="btn btn-purple btn-sm me-2">
                                <i class="bi bi-archive"></i> Archive
                            </a>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#orderModal">
                                <i class="bi bi-plus-circle"></i> New Order
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th>Address</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="ordersTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalLabel">New Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="orderFormError"></div>
                    <form id="orderForm">
                        <input type="hidden" name="id" id="orderId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select" name="customer_id" id="orderCustomerSelect" required></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Order Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" name="order_date" id="orderDate" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" name="status" id="orderStatus" required>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" name="total_amount" id="orderTotal" min="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Delivery Address</label>
                                <textarea class="form-control" name="delivery_address" id="orderAddress" rows="2" placeholder="House/Street, Barangay, City/Province, Postal Code"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" id="orderNotes" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" form="orderForm">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0" id="orderDetailsList">
                        <dt class="col-sm-4">Order ID</dt>
                        <dd class="col-sm-8" id="detailOrderId">—</dd>
                        <dt class="col-sm-4">Customer</dt>
                        <dd class="col-sm-8" id="detailCustomer">—</dd>
                        <dt class="col-sm-4">Order Date</dt>
                        <dd class="col-sm-8" id="detailOrderDate">—</dd>
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8" id="detailStatus">—</dd>
                        <dt class="col-sm-4">Total</dt>
                        <dd class="col-sm-8" id="detailTotal">—</dd>
                        <dt class="col-sm-4">Notes</dt>
                        <dd class="col-sm-8" id="detailNotes" class="text-muted">No notes provided.</dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        
        async function handleMakeInvoice(order, buttonEl) {
            try {
                if (buttonEl) {
                    buttonEl.disabled = true;
                    const original = buttonEl.innerHTML;
                    if (!buttonEl.dataset.original) {
                        buttonEl.dataset.original = original;
                    }
                    buttonEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Making...';
                }

                const endpoint = new URL('backend/orders.php', window.location.href);
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'make_invoice', id: order.id })
                });

                const data = await response.json();

                if (response.status === 409) {
                    const invNum = data && data.invoice && data.invoice.invoice_number ? data.invoice.invoice_number : '';
                    alert(`${data.message || 'An invoice already exists for this order.'}${invNum ? `\nInvoice #: ${invNum}` : ''}`);
                    if (buttonEl) {
                        buttonEl.dataset.keepDisabled = '1';
                        buttonEl.disabled = true;
                        buttonEl.innerHTML = '<i class="bi bi-receipt"></i> Invoice Exists';
                    }
                    return;
                }

                if (!response.ok || !data.success) {
                    const msg = (data && (data.detail || data.message)) ? (data.detail || data.message) : 'Failed to create invoice from order.';
                    throw new Error(msg);
                }

                const invoice = data.invoice;
                alert(`Invoice created successfully: ${invoice.invoice_number}`);
                if (buttonEl) {
                    buttonEl.dataset.keepDisabled = '1';
                    buttonEl.disabled = true;
                    buttonEl.innerHTML = '<i class="bi bi-check2-circle"></i> Invoice Created';
                }
            } catch (error) {
                alert(error.message);
            } finally {
                if (buttonEl && buttonEl.dataset.original) {
                    if (buttonEl.dataset.keepDisabled === '1') {
                        // Keep button disabled and with updated label
                        delete buttonEl.dataset.original;
                    } else {
                        buttonEl.disabled = false;
                        buttonEl.innerHTML = buttonEl.dataset.original;
                    }
                }
            }
        }

        const ordersTableBody = document.getElementById('ordersTableBody');
        const searchOrdersInput = document.getElementById('searchOrders');
        const statusFilterSelect = document.getElementById('statusFilter');
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        const orderForm = document.getElementById('orderForm');
        const orderFormError = document.getElementById('orderFormError');
        const orderModalElement = document.getElementById('orderModal');
        const orderModal = new bootstrap.Modal(orderModalElement);
        const orderIdInput = document.getElementById('orderId');
        const orderModalLabel = document.getElementById('orderModalLabel');
        const orderCustomerSelect = document.getElementById('orderCustomerSelect');
        const orderDateInput = document.getElementById('orderDate');
        const orderStatusSelect = document.getElementById('orderStatus');
        const orderTotalInput = document.getElementById('orderTotal');
        const orderNotesInput = document.getElementById('orderNotes');
        const orderAddressInput = document.getElementById('orderAddress');
        const orderSubmitButton = document.querySelector('button[type="submit"][form="orderForm"]');
        const orderDetailsModalElement = document.getElementById('orderDetailsModal');
        const orderDetailsModal = new bootstrap.Modal(orderDetailsModalElement);
        const detailOrderId = document.getElementById('detailOrderId');
        const detailCustomer = document.getElementById('detailCustomer');
        const detailOrderDate = document.getElementById('detailOrderDate');
        const detailStatus = document.getElementById('detailStatus');
        const detailTotal = document.getElementById('detailTotal');
        const detailNotes = document.getElementById('detailNotes');
        const urlParams = new URLSearchParams(window.location.search);
        const isArchivedView = urlParams.get('archived') === '1';
        const headerTitle = document.querySelector('.card-header h5');
        if (headerTitle) { headerTitle.textContent = isArchivedView ? 'Archived Orders' : 'Orders List'; }
        const backToOrdersBtn = document.getElementById('backToOrdersBtn');
        if (isArchivedView && backToOrdersBtn) { backToOrdersBtn.classList.remove('d-none'); }
        const actionsHeader = document.querySelector('table thead th.text-end');

        let searchTimeout;
        let customersCache = [];
        let isOrderSubmitting = false;

        async function loadCustomersOptions() {
            if (customersCache.length) {
                populateCustomerOptions(customersCache);
                return;
            }

            try {
                const endpoint = new URL('backend/customers.php', window.location.href);
                const response = await fetch(endpoint);

                if (!response.ok) {
                    throw new Error('Failed to load customers.');
                }

                const payload = await response.json();

                if (!payload.success || !Array.isArray(payload.data)) {
                    throw new Error(payload.message || 'Failed to load customers.');
                }

                customersCache = payload.data;
                populateCustomerOptions(customersCache);
            } catch (error) {
                orderFormError.textContent = error.message;
                orderFormError.classList.remove('d-none');
            }
        }

        function buildCustomerLabel(customer) {
            const name = `${customer.first_name} ${customer.last_name}`.trim();
            return name !== '' ? name : `Customer #${customer.id}`;
        }

        function populateCustomerOptions(customers) {
            orderCustomerSelect.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Search customer';
            orderCustomerSelect.appendChild(placeholder);

            customers.forEach(customer => {
                const option = document.createElement('option');
                option.value = customer.id;
                option.textContent = buildCustomerLabel(customer);
                orderCustomerSelect.appendChild(option);
            });

            if (window.jQuery && jQuery.fn.select2) {
                const $sel = jQuery('#orderCustomerSelect');
                if (!$sel.hasClass('select2-hidden-accessible')) {
                    $sel.select2({ dropdownParent: jQuery('#orderModal'), width: '100%', placeholder: 'Search customer', allowClear: true });
                } else {
                    $sel.trigger('change.select2');
                }
            }
        }

        function updateSelectedCustomerFromInput() { }

        function renderOrders(orders) {
            ordersTableBody.innerHTML = '';

            if (!orders.length) {
                const emptyRow = document.createElement('tr');
                const emptyCell = document.createElement('td');
                emptyCell.colSpan = 7;
                emptyCell.className = 'text-center text-muted';
                emptyCell.textContent = 'No orders found.';
                emptyRow.appendChild(emptyCell);
                ordersTableBody.appendChild(emptyRow);
                return;
            }

            orders.forEach(order => {
                const row = document.createElement('tr');

                const idCell = document.createElement('td');
                idCell.textContent = order.id;
                row.appendChild(idCell);

                const customerCell = document.createElement('td');
                customerCell.textContent = `${order.first_name} ${order.last_name}`.trim();
                row.appendChild(customerCell);

                const dateCell = document.createElement('td');
                dateCell.textContent = formatDateTime(order.order_date);
                row.appendChild(dateCell);

                const statusCell = document.createElement('td');
                statusCell.innerHTML = renderStatusBadge(order.status);
                row.appendChild(statusCell);

                const totalCell = document.createElement('td');
                totalCell.textContent = formatCurrency(order.total_amount);
                row.appendChild(totalCell);

                const addressCell = document.createElement('td');
                addressCell.textContent = (order.delivery_address || '').trim() !== '' ? order.delivery_address : '—';
                row.appendChild(addressCell);

                if (!isArchivedView) {
                    const actionsCell = document.createElement('td');
                    actionsCell.className = 'text-end';

                    const viewButton = document.createElement('button');
                    viewButton.type = 'button';
                    viewButton.className = 'btn btn-outline-secondary btn-sm me-2';
                    viewButton.innerHTML = '<i class="bi bi-eye"></i> View';
                    viewButton.addEventListener('click', () => openOrderDetailsModal(order));
                    actionsCell.appendChild(viewButton);

                    const makeInvoiceButton = document.createElement('button');
                    makeInvoiceButton.type = 'button';
                    makeInvoiceButton.className = 'btn btn-outline-success btn-sm me-2';
                    makeInvoiceButton.innerHTML = '<i class="bi bi-receipt"></i> Make Invoice';
                    makeInvoiceButton.addEventListener('click', () => handleMakeInvoice(order, makeInvoiceButton));
                    actionsCell.appendChild(makeInvoiceButton);

                    const editButton = document.createElement('button');
                    editButton.type = 'button';
                    editButton.className = 'btn btn-outline-primary btn-sm me-2';
                    editButton.innerHTML = '<i class="bi bi-pencil"></i> Edit';
                    editButton.addEventListener('click', () => openOrderModal(order));
                    actionsCell.appendChild(editButton);

                    const archiveButton = document.createElement('button');
                    archiveButton.type = 'button';
                    archiveButton.className = 'btn btn-outline-purple btn-sm';
                    archiveButton.innerHTML = '<i class="bi bi-archive"></i> Archive';
                    archiveButton.addEventListener('click', () => handleArchiveOrder(order.id));
                    actionsCell.appendChild(archiveButton);

                    row.appendChild(actionsCell);
                } else {
                    const actionsCell = document.createElement('td');
                    actionsCell.className = 'text-end';
                    const unarchiveButton = document.createElement('button');
                    unarchiveButton.type = 'button';
                    unarchiveButton.className = 'btn btn-outline-purple btn-sm';
                    unarchiveButton.innerHTML = '<i class="bi bi-archive"></i> Unarchive';
                    unarchiveButton.addEventListener('click', () => handleUnarchiveOrder(order.id));
                    actionsCell.appendChild(unarchiveButton);
                    row.appendChild(actionsCell);
                }

                ordersTableBody.appendChild(row);
            });
        }

        function formatDateTime(value) {
            if (!value) {
                return '—';
            }

            const date = new Date(value);

            if (Number.isNaN(date.getTime())) {
                return value;
            }

            return date.toLocaleString();
        }

        function renderStatusBadge(orderStatus) {
            const normalized = (orderStatus || '').toLowerCase();
            const statusClasses = {
                pending: 'status-badge status-pending',
                processing: 'status-badge status-processing',
                completed: 'status-badge status-completed',
                cancelled: 'status-badge status-cancelled'
            };

            const label = normalized.charAt(0).toUpperCase() + normalized.slice(1);
            return `<span class="${statusClasses[normalized] || 'status-badge status-processing'}">${label || 'Unknown'}</span>`;
        }

        function formatCurrency(value) {
            const number = Number(value);

            if (Number.isNaN(number)) {
                return '₱0.00';
            }

            return `₱${number.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        async function loadOrders() {
            try {
                const endpoint = new URL('backend/orders.php', window.location.href);
                const search = searchOrdersInput.value.trim();
                const status = statusFilterSelect.value;
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;
                endpoint.searchParams.set('archived', isArchivedView ? '1' : '0');

                if (search) {
                    endpoint.searchParams.set('search', search);
                }

                if (status) {
                    endpoint.searchParams.set('status', status);
                }

                if (startDate) {
                    endpoint.searchParams.set('start_date', startDate);
                }

                if (endDate) {
                    endpoint.searchParams.set('end_date', endDate);
                }

                const response = await fetch(endpoint);

                if (!response.ok) {
                    throw new Error('Failed to load orders.');
                }

                const payload = await response.json();

                if (!payload.success) {
                    throw new Error(payload.message || 'Failed to load orders.');
                }

                renderOrders(Array.isArray(payload.data) ? payload.data : []);
            } catch (error) {
                ordersTableBody.innerHTML = '';
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 7;
                cell.className = 'text-center text-danger';
                cell.textContent = error.message;
                row.appendChild(cell);
                ordersTableBody.appendChild(row);
            }
        }

        orderForm.addEventListener('submit', async event => {
            event.preventDefault();

            if (isOrderSubmitting) {
                return;
            }

            if (!orderCustomerSelect.value) {
                orderFormError.textContent = 'Please select a valid customer.';
                orderFormError.classList.remove('d-none');
                return;
            }

            const formData = new FormData(orderForm);
            const payload = Object.fromEntries(formData.entries());
            // Normalize order_date to ISO-like format; backend further normalizes
            if (payload.order_date && typeof payload.order_date === 'string') {
                payload.order_date = payload.order_date.replace(' ', 'T').slice(0, 16);
            }

            orderFormError.classList.add('d-none');
            orderFormError.textContent = '';

            const method = orderIdInput.value ? 'PUT' : 'POST';

            if (method === 'PUT') {
                payload.id = orderIdInput.value;
            }

            try {
                isOrderSubmitting = true;

                if (orderSubmitButton) {
                    if (!orderSubmitButton.dataset.originalContent) {
                        orderSubmitButton.dataset.originalContent = orderSubmitButton.innerHTML;
                    }

                    orderSubmitButton.disabled = true;
                    orderSubmitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';
                }

                const endpoint = new URL('backend/orders.php', window.location.href);

                const response = await fetch(endpoint, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    const message = data.errors ? Object.values(data.errors).join(' ') : (data.detail || data.message || 'Failed to save order.');
                    throw new Error(message);
                }

                orderModal.hide();
                loadOrders();
            } catch (error) {
                orderFormError.textContent = error.message;
                orderFormError.classList.remove('d-none');
            } finally {
                isOrderSubmitting = false;

                if (orderSubmitButton && orderSubmitButton.dataset.originalContent) {
                    orderSubmitButton.disabled = false;
                    orderSubmitButton.innerHTML = orderSubmitButton.dataset.originalContent;
                }
            }
        });

        function openOrderDetailsModal(order) {
            detailOrderId.textContent = order.id;
            detailCustomer.textContent = `${order.first_name} ${order.last_name}`.trim();
            detailOrderDate.textContent = formatDateTime(order.order_date);
            detailStatus.innerHTML = renderStatusBadge(order.status);
            detailTotal.textContent = formatCurrency(order.total_amount);
            detailNotes.textContent = order.notes && order.notes.trim() !== '' ? order.notes : 'No notes provided.';

            orderDetailsModal.show();
        }

        function openOrderModal(order = null) {
            loadCustomersOptions();
            orderFormError.classList.add('d-none');
            orderFormError.textContent = '';

            if (order) {
                orderIdInput.value = order.id;
                orderModalLabel.textContent = `Edit Order #${order.id}`;
                if (orderCustomerSelect) {
                    orderCustomerSelect.value = String(order.customer_id || '');
                    if (window.jQuery && jQuery.fn.select2) {
                        jQuery('#orderCustomerSelect').val(String(order.customer_id || '')).trigger('change');
                    }
                }
                orderDateInput.value = order.order_date ? order.order_date.replace(' ', 'T').slice(0, 16) : '';
                orderStatusSelect.value = (order.status || 'pending').toLowerCase();
                orderTotalInput.value = order.total_amount;
                orderNotesInput.value = order.notes || '';
                orderAddressInput.value = order.delivery_address || '';
            } else {
                orderIdInput.value = '';
                orderModalLabel.textContent = 'New Order';
                orderForm.reset();
                const now = new Date();
                orderDateInput.value = `${now.toISOString().slice(0, 16)}`;
                if (orderCustomerSelect) {
                    orderCustomerSelect.value = '';
                    if (window.jQuery && jQuery.fn.select2) {
                        jQuery('#orderCustomerSelect').val(null).trigger('change');
                    }
                }
            }

            orderModal.show();
        }

        async function handleArchiveOrder(id) {
            if (!confirm('Are you sure you want to archive this order?')) {
                return;
            }

            try {
                const endpoint = new URL('backend/orders.php', window.location.href);
                endpoint.searchParams.set('id', id);

                const response = await fetch(endpoint, {
                    method: 'DELETE'
                });

                if (response.status === 204) {
                    loadOrders();
                    return;
                }

                const data = await response.json();
                const message = data && !data.success ? (data.message || 'Failed to archive order.') : 'Failed to archive order.';
                alert(message);
            } catch (error) {
                alert(error.message);
            }
        }

        function handleUnarchiveOrder(id) {
            fetch('backend/orders.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'unarchive', id })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Failed to unarchive order.');
                loadOrders();
            })
            .catch(err => alert(err.message));
        }

        searchOrdersInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(loadOrders, 300);
        });

        if (orderCustomerSelect) {
            orderCustomerSelect.addEventListener('change', () => {
                if (orderCustomerSelect.value) {
                    orderFormError.classList.add('d-none');
                    orderFormError.textContent = '';
                }
            });
        }

        statusFilterSelect.addEventListener('change', loadOrders);
        startDateInput.addEventListener('change', loadOrders);
        endDateInput.addEventListener('change', loadOrders);

        orderModalElement.addEventListener('hidden.bs.modal', () => {
            orderForm.reset();
            orderFormError.classList.add('d-none');
            orderFormError.textContent = '';

            if (orderSubmitButton && orderSubmitButton.dataset.originalContent) {
                orderSubmitButton.disabled = false;
                orderSubmitButton.innerHTML = orderSubmitButton.dataset.originalContent;
            }

            isOrderSubmitting = false;
        });

        loadCustomersOptions();
        loadOrders();
    </script>
</body>
</html>