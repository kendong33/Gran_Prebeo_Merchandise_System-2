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
    <title>Invoices - Gran Prebeo</title>
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

        .container-fluid > .row {
            min-height: 100vh;
        }

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

        .sidebar .brand-header {
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
            box-shadow: 0 20px 38px rgba(139, 104, 68, 0.25);
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
            box-shadow: 0 12px 22px rgba(139, 104, 68, 0.14);
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

        .card-invoices {
            border: none;
            border-radius: 18px;
            box-shadow: 0 24px 45px rgba(139, 104, 68, 0.12);
        }

        .table thead th {
            border-bottom: none;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.75rem;
            cursor: pointer;
        }

        .table tbody tr {
            transition: transform 0.15s ease, background 0.2s ease;
        }

        .table tbody tr:hover {
            background: rgba(219, 178, 122, 0.18);
            transform: translateY(-2px);
        }

        body.dark-mode .table tbody tr:hover {
            background: rgba(219, 178, 122, 0.33);
        }

        .status-badge {
            display: inline-block;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .status-paid { background: rgba(164, 210, 150, 0.35); color: #3c6b3a; }
        .status-pending { background: rgba(251, 207, 146, 0.45); color: #8a5a2f; }
        .status-overdue { background: rgba(248, 160, 160, 0.35); color: #a12f2f; }
        .status-draft { background: rgba(148, 163, 184, 0.2); color: #475569; }
        .status-sent { background: rgba(59, 130, 246, 0.18); color: #1d4ed8; }
        .status-cancelled { background: rgba(107, 114, 128, 0.22); color: #374151; }

        body.dark-mode .status-paid { background: rgba(34, 197, 94, 0.28); color: #4ade80; }
        body.dark-mode .status-pending { background: rgba(251, 191, 36, 0.32); color: #fbbf24; }
        body.dark-mode .status-overdue { background: rgba(248, 113, 113, 0.35); color: #f87171; }
        body.dark-mode .status-draft { background: rgba(148, 163, 184, 0.26); color: #e2e8f0; }
        body.dark-mode .status-sent { background: rgba(59, 130, 246, 0.28); color: #93c5fd; }
        body.dark-mode .status-cancelled { background: rgba(107, 114, 128, 0.3); color: #cbd5f5; }

        .actions-column {
            display: flex;
            justify-content: flex-end;
            gap: 0.45rem;
        }

        .actions-column .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .skeleton-row {
            position: relative;
            overflow: hidden;
        }

        .skeleton-cell {
            height: 16px;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(226, 232, 240, 0.45) 25%, rgba(148, 163, 184, 0.45) 50%, rgba(226, 232, 240, 0.45) 75%);
            background-size: 400% 100%;
            animation: shimmer 1.2s ease-in-out infinite;
        }

        @keyframes shimmer {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }

        .table-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(2px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        body.dark-mode .table-overlay {
            background: rgba(17, 24, 39, 0.6);
        }

        .table-overlay.show {
            display: flex;
        }

        .pagination-controls {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }

        .btn-draft {
            background: rgba(148, 163, 184, 0.25);
            color: #475569;
        }

        body.dark-mode .btn-draft {
            background: rgba(148, 163, 184, 0.35);
            color: #e2e8f0;
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
                        <a href="invoices.php" class="nav-link active">
                            <i class="bi bi-receipt"></i> Invoices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="delivery.php" class="nav-link">
                            <i class="bi bi-truck"></i> Delivery
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reports.php" class="nav-link">
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

            <div class="col-lg-10 main-content">
                <div class="page-header">
                    <div>
                        <h2 class="mb-1">Invoice Center</h2>
                        <p class="mb-0">Monitor billing and payment status</p>
                    </div>
                    <div class="text-end">
                        <div class="mb-1"><small class="brand-tagline">Gran Prebeo Merchandise System</small></div>
                    </div>
                </div>

                <div class="row g-3 summary-boxes mb-4">
                    <div class="col-md-3">
                        <div class="card text-white" style="background:linear-gradient(140deg,#6a11cb,#8e54e9)">
                            <div class="card-body">
                                <h6 class="text-white-75">Total Invoices</h6>
                                <div class="summary-value" id="summaryTotal">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white" style="background:linear-gradient(140deg,#0ea5e9,#2563eb)">
                            <div class="card-body">
                                <h6 class="text-white-75">Paid</h6>
                                <div class="summary-value" id="summaryPaid">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white" style="background:linear-gradient(140deg,#f59e0b,#ef4444)">
                            <div class="card-body">
                                <h6 class="text-white-75">Pending</h6>
                                <div class="summary-value" id="summaryPending">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white" style="background:linear-gradient(140deg,#ef4444,#b91c1c)">
                            <div class="card-body">
                                <h6 class="text-white-75">Overdue</h6>
                                <div class="summary-value" id="summaryOverdue">0</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3 filters">
                            <div class="col-lg-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" id="searchInput" placeholder="Invoice # or customer">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="">All statuses</option>
                                    <option value="paid">Paid</option>
                                    <option value="pending">Pending</option>
                                    <option value="overdue">Overdue</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label">Issue Date (from)</label>
                                <input type="date" class="form-control" id="startDateFilter">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label">Issue Date (to)</label>
                                <input type="date" class="form-control" id="endDateFilter">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label">Amount Range</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="minAmountFilter" min="0" step="0.01" placeholder="Min">
                                    <input type="number" class="form-control" id="maxAmountFilter" min="0" step="0.01" placeholder="Max">
                                </div>
                            </div>
                            <div class="col-lg-3 d-flex align-items-end">
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-outline-success btn-sm" id="exportExcelBtn"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-invoices">
                    <div class="card-header bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <h5 class="mb-0">Invoices List</h5>
                        <div class="d-flex gap-2 align-items-center">
                            <select class="form-select form-select-sm" id="perPageSelect" style="width:auto">
                                <option value="10">10 rows</option>
                                <option value="25">25 rows</option>
                                <option value="50">50 rows</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive position-relative">
                            <div class="table-overlay" id="tableOverlay">
                                <div class="spinner-border text-primary" role="status"></div>
                            </div>
                            <table class="table align-middle mb-0" id="invoiceTable">
                                <thead>
                                    <tr>
                                        <th data-sort="invoice_number">Invoice # <i class="bi bi-arrow-down-up"></i></th>
                                        <th data-sort="customer">Customer <i class="bi bi-arrow-down-up"></i></th>
                                        <th data-sort="issue_date">Date Issued <i class="bi bi-arrow-down-up"></i></th>
                                        <th data-sort="status">Status <i class="bi bi-arrow-down-up"></i></th>
                                        <th data-sort="total_amount" class="text-end">Total <i class="bi bi-arrow-down-up"></i></th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="invoiceTableBody"></tbody>
                            </table>
                            <table class="table mb-0"><tbody id="skeletonBody" class="d-none"></tbody></table>
                        </div>
                        <div class="pagination-controls mt-4">
                            <div class="text-muted" id="paginationSummary">Showing 0 invoices</div>
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-secondary btn-sm" id="prevPageBtn"><i class="bi bi-chevron-left"></i> Prev</button>
                                <button class="btn btn-outline-secondary btn-sm" id="nextPageBtn">Next <i class="bi bi-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="invoicePreviewModal" tabindex="-1" aria-labelledby="invoicePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="invoicePreviewModalLabel">Invoice Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Invoice Info</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Invoice #</dt>
                                <dd class="col-sm-8" id="previewInvoiceNumber">—</dd>
                                <dt class="col-sm-4">Status</dt>
                                <dd class="col-sm-8" id="previewStatus">—</dd>
                                <dt class="col-sm-4">Type</dt>
                                <dd class="col-sm-8" id="previewType">—</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Customer</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Name</dt>
                                <dd class="col-sm-8" id="previewCustomer">—</dd>
                                <dt class="col-sm-4">Email</dt>
                                <dd class="col-sm-8" id="previewEmail">—</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Dates</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Issued</dt>
                                <dd class="col-sm-8" id="previewIssueDate">—</dd>
                                <dt class="col-sm-4">Due</dt>
                                <dd class="col-sm-8" id="previewDueDate">—</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Total</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Amount</dt>
                                <dd class="col-sm-8" id="previewTotal">—</dd>
                            </dl>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted">Notes</h6>
                            <p class="mb-0" id="previewNotes">No notes provided.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-primary" id="previewDownloadBtn"><i class="bi bi-printer"></i> Print Invoice</button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const state = {
            page: 1,
            perPage: 10,
            sortBy: 'issue_date',
            sortDir: 'desc',
        };

        const summaryTotal = document.getElementById('summaryTotal');
        const summaryPaid = document.getElementById('summaryPaid');
        const summaryPending = document.getElementById('summaryPending');
        const summaryOverdue = document.getElementById('summaryOverdue');

        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const startDateFilter = document.getElementById('startDateFilter');
        const endDateFilter = document.getElementById('endDateFilter');
        const minAmountFilter = document.getElementById('minAmountFilter');
        const maxAmountFilter = document.getElementById('maxAmountFilter');
        const perPageSelect = document.getElementById('perPageSelect');

        const invoiceTableBody = document.getElementById('invoiceTableBody');
        const skeletonBody = document.getElementById('skeletonBody');
        const tableOverlay = document.getElementById('tableOverlay');
        const paginationSummary = document.getElementById('paginationSummary');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');

        const invoicePreviewModal = new bootstrap.Modal(document.getElementById('invoicePreviewModal'));

        const previewInvoiceNumber = document.getElementById('previewInvoiceNumber');
        const previewStatus = document.getElementById('previewStatus');
        const previewType = document.getElementById('previewType');
        const previewCustomer = document.getElementById('previewCustomer');
        const previewEmail = document.getElementById('previewEmail');
        const previewIssueDate = document.getElementById('previewIssueDate');
        const previewDueDate = document.getElementById('previewDueDate');
        const previewTotal = document.getElementById('previewTotal');
        const previewNotes = document.getElementById('previewNotes');
        const previewDownloadBtn = document.getElementById('previewDownloadBtn');

        const exportExcelBtn = document.getElementById('exportExcelBtn');
        

        let customersCache = [];
        let lastInvoices = [];

        function formatCurrency(value) {
            const number = Number(value);

            if (Number.isNaN(number)) {
                return '₱0.00';
            }

            return `₱${number.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })}`;
        }

        function formatDate(value) {
            if (!value) {
                return '—';
            }

            const date = new Date(value);

            if (Number.isNaN(date.getTime())) {
                return value;
            }

            return date.toLocaleDateString();
        }

        function renderStatusBadge(status) {
            const normalized = (status || '').toLowerCase();
            const classes = {
                paid: 'status-badge status-paid',
                pending: 'status-badge status-pending',
                overdue: 'status-badge status-overdue',
                draft: 'status-badge status-draft',
                sent: 'status-badge status-sent',
                cancelled: 'status-badge status-cancelled',
            };
            const label = normalized.charAt(0).toUpperCase() + normalized.slice(1);
            return `<span class="${classes[normalized] || classes.draft}">${label || 'Draft'}</span>`;
        }

        function formatStatusLabel(status) {
            if (!status) {
                return 'None';
            }

            return status
                .split('_')
                .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
                .join(' ');
        }

        function getCustomerName(invoice) {
            if (invoice.customer_name && invoice.customer_name.trim() !== '') {
                return invoice.customer_name.trim();
            }

            const first = (invoice.first_name || '').trim();
            const last = (invoice.last_name || '').trim();
            const combined = `${first} ${last}`.trim();

            return combined !== '' ? combined : '—';
        }

        function getCustomerEmail(invoice) {
            if (invoice.customer_email && invoice.customer_email.trim() !== '') {
                return invoice.customer_email.trim();
            }

            if (invoice.email && invoice.email.trim() !== '') {
                return invoice.email.trim();
            }

            return '—';
        }

        function getGrandTotal(invoice) {
            const candidates = [invoice.grand_total, invoice.total_amount, invoice.subtotal];

            for (const value of candidates) {
                const number = Number(value);

                if (!Number.isNaN(number) && number !== null && number !== undefined) {
                    return number;
                }
            }

            return 0;
        }

        function showSkeleton(count = 6) {
            skeletonBody.classList.remove('d-none');
            skeletonBody.innerHTML = '';

            for (let index = 0; index < count; index += 1) {
                const row = document.createElement('tr');
                row.className = 'skeleton-row';

                for (let cellIndex = 0; cellIndex < 6; cellIndex += 1) {
                    const cell = document.createElement('td');
                    const placeholder = document.createElement('div');
                    placeholder.className = 'skeleton-cell';
                    placeholder.style.width = `${Math.random() * 40 + 40}%`;
                    cell.appendChild(placeholder);
                    row.appendChild(cell);
                }

                skeletonBody.appendChild(row);
            }
        }

        function hideSkeleton() {
            skeletonBody.classList.add('d-none');
            skeletonBody.innerHTML = '';
        }

        function updateSummary(summary = {}) {
            summaryTotal.textContent = summary.total ?? 0;
            summaryPaid.textContent = summary.paid ?? 0;
            summaryPending.textContent = summary.pending ?? 0;
            summaryOverdue.textContent = summary.overdue ?? 0;
        }

        function buildInvoiceRow(invoice) {
            const row = document.createElement('tr');
            row.className = 'invoice-row';
            row.dataset.invoiceId = invoice.id;

            const customerName = getCustomerName(invoice);
            const totalAmount = getGrandTotal(invoice);

            const cells = [
                invoice.invoice_number,
                customerName,
                formatDate(invoice.issue_date),
                renderStatusBadge(invoice.status),
                formatCurrency(totalAmount),
            ];

            cells.forEach((value, index) => {
                const cell = document.createElement('td');

                if (index === 3) {
                    cell.innerHTML = value;
                } else if (index === 4) {
                    cell.className = 'text-end';
                    cell.textContent = value;
                } else {
                    cell.textContent = value;
                }

                row.appendChild(cell);
            });

            const actionsCell = document.createElement('td');
            actionsCell.className = 'actions-column';

            const sendButton = document.createElement('button');
            sendButton.type = 'button';
            sendButton.className = 'btn btn-outline-primary btn-sm';
            sendButton.innerHTML = '<i class="bi bi-send"></i> Send';
            sendButton.addEventListener('click', (event) => {
                event.stopPropagation();
                alert(`Send invoice ${invoice.invoice_number} to ${getCustomerEmail(invoice)}`);
            });

            const viewButton = document.createElement('button');
            viewButton.type = 'button';
            viewButton.className = 'btn btn-outline-secondary btn-sm';
            viewButton.innerHTML = '<i class="bi bi-eye"></i> View / Print';
            viewButton.addEventListener('click', (event) => {
                event.stopPropagation();
                openPreview(invoice);
            });

            actionsCell.append(sendButton, viewButton);
            row.appendChild(actionsCell);

            row.addEventListener('click', () => openPreview(invoice));

            return row;
        }

        function renderInvoices(invoices) {
            const activeInvoices = Array.isArray(invoices)
                ? invoices.filter((invoice) => (invoice.status || '').toLowerCase() !== 'draft')
                : [];

            lastInvoices = activeInvoices;
            invoiceTableBody.innerHTML = '';
            if (activeInvoices.length === 0) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 6;
                cell.className = 'text-center text-muted py-4';
                cell.textContent = 'No active invoices found for the selected filters.';
                row.appendChild(cell);
                invoiceTableBody.appendChild(row);
            } else {
                activeInvoices.forEach((invoice) => {
                    invoiceTableBody.appendChild(buildInvoiceRow(invoice));
                });
            }

            return activeInvoices.length;
        }

        async function fetchInvoiceDetail(id) {
            const endpoint = new URL('backend/invoices.php', window.location.href);
            endpoint.searchParams.set('_', Date.now());
            endpoint.searchParams.set('id', id);

            const response = await fetch(endpoint);

            if (!response.ok) {
                throw new Error('Unable to load invoice details.');
            }

            const payload = await response.json();

            if (!payload.success || !payload.data) {
                throw new Error(payload.message || 'Unable to load invoice details.');
            }

            return payload.data;
        }

        function sanitizeString(value, fallback = '') {
            if (typeof value === 'string' && value.trim() !== '') {
                return value.trim();
            }

            return fallback;
        }

        function sanitizeNumber(value, fallback = 0) {
            const number = Number(value);

            if (Number.isFinite(number)) {
                return number;
            }

            return fallback;
        }

        function notify(message, description = '') {
            if (typeof window.showToast === 'function') {
                window.showToast(message, description);
                return;
            }

            const details = description ? `\n${description}` : '';
            window.alert(`${message}${details}`);
        }

        function applyPaginationUI(pagination, currentCount, totalActive = pagination.total) {
            const first = currentCount > 0 ? ((pagination.page - 1) * pagination.per_page) + 1 : 0;
            const last = currentCount > 0 ? ((pagination.page - 1) * pagination.per_page) + currentCount : 0;
            const rangeText = currentCount > 0 ? `${first}-${last}` : '0';
            const safeTotal = Number.isFinite(totalActive) && totalActive >= 0 ? totalActive : 0;
            const maxPages = safeTotal > 0 ? Math.ceil(safeTotal / Math.max(1, pagination.per_page)) : 1;
            paginationSummary.textContent = `Showing ${rangeText} of ${safeTotal} invoices`;
            prevPageBtn.disabled = pagination.page <= 1;
            nextPageBtn.disabled = pagination.page >= maxPages;
        }

        async function loadInvoices() {
            showSkeleton();
            tableOverlay.classList.add('show');

            const endpoint = new URL('backend/invoices.php', window.location.href);
            const params = {
                search: searchInput.value.trim(),
                status: statusFilter.value,
                start_date: startDateFilter.value,
                end_date: endDateFilter.value,
                min_amount: minAmountFilter.value,
                max_amount: maxAmountFilter.value,
                sort_by: state.sortBy,
                sort_dir: state.sortDir,
                page: state.page,
                per_page: state.perPage,
            };

            Object.entries(params).forEach(([key, value]) => {
                if (value !== '' && value !== null) {
                    endpoint.searchParams.set(key, value);
                }
            });

            endpoint.searchParams.set('_', Date.now());

            try {
                const response = await fetch(endpoint);

                if (!response.ok) {
                    throw new Error('Failed to load invoices.');
                }

                const payload = await response.json();

                if (!payload.success) {
                    throw new Error(payload.message || 'Failed to load invoices.');
                }

                const invoices = Array.isArray(payload.data) ? payload.data : [];
                const summary = payload.summary || {};
                const displayedCount = renderInvoices(invoices);
                updateSummary(summary);

                const draftCount = summary.draft ?? 0;
                const activeTotal = Math.max(0, (summary.total ?? invoices.length) - draftCount);

                const pagination = payload.pagination || { page: state.page, per_page: state.perPage, total: invoices.length, total_pages: 1 };
                state.page = pagination.page;
                state.perPage = pagination.per_page;
                perPageSelect.value = String(state.perPage);
                applyPaginationUI(pagination, displayedCount, activeTotal);
            } catch (error) {
                invoiceTableBody.innerHTML = '';
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 7;
                cell.className = 'text-center text-danger py-4';
                cell.textContent = error.message;
                row.appendChild(cell);
                invoiceTableBody.appendChild(row);
                paginationSummary.textContent = 'Showing 0 of 0 invoices';
            } finally {
                hideSkeleton();
                tableOverlay.classList.remove('show');
            }
        }

        async function loadCustomers() {
            if (customersCache.length) {
                populateCustomerSelect(customerFilter, customersCache, 'All customers');
                return;
            }

            try {
                const response = await fetch(new URL('backend/customers.php', window.location.href));

                if (!response.ok) {
                    throw new Error('Unable to load customers.');
                }

                const payload = await response.json();

                if (!payload.success || !Array.isArray(payload.data)) {
                    throw new Error(payload.message || 'Unable to load customers.');
                }

                customersCache = payload.data;
                populateCustomerSelect(customerFilter, customersCache, 'All customers');
            } catch (error) {
                console.error(error);
            }
        }

        function populateCustomerSelect(select, customers, placeholder) {
            select.innerHTML = '';
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = placeholder;
            select.appendChild(defaultOption);

            customers.forEach((customer) => {
                const option = document.createElement('option');
                option.value = customer.id;
                option.textContent = `${customer.first_name} ${customer.last_name}`.trim();
                select.appendChild(option);
            });
        }

        function openPreview(invoice) {
            previewInvoiceNumber.textContent = invoice.invoice_number;
            previewStatus.innerHTML = renderStatusBadge(invoice.status);
            previewType.textContent = invoice.invoice_type || 'product';
            previewCustomer.textContent = getCustomerName(invoice);
            previewEmail.textContent = getCustomerEmail(invoice);
            previewIssueDate.textContent = formatDate(invoice.issue_date);
            previewDueDate.textContent = formatDate(invoice.due_date);
            previewTotal.textContent = formatCurrency(getGrandTotal(invoice));
            previewNotes.textContent = invoice.notes && invoice.notes.trim() !== '' ? invoice.notes : 'No notes provided.';
            previewDownloadBtn.onclick = () => downloadInvoicePdf(invoice);

            invoicePreviewModal.show();
        }

        function downloadInvoicePdf(invoice) {
            const win = window.open('', '_blank');

            if (!win) {
                alert('Please allow popups to download PDF.');
                return;
            }

            win.document.write('<html><head><title>Invoice PDF</title></head><body>');
            win.document.write(`<h1>${invoice.invoice_number}</h1>`);
            win.document.write(`<p>Status: ${invoice.status}</p>`);
            win.document.write(`<p>Customer: ${getCustomerName(invoice)}</p>`);
            win.document.write(`<p>Total: ${formatCurrency(getGrandTotal(invoice))}</p>`);
            win.document.write(`<p>Issued: ${formatDate(invoice.issue_date)}</p>`);
            win.document.write(`<p>Due: ${formatDate(invoice.due_date)}</p>`);
            win.document.write(`<p>Notes: ${invoice.notes || '—'}</p>`);
            win.document.write('</body></html>');
            win.document.close();
            win.print();
        }

        function exportToExcel() {
            if (!lastInvoices.length) {
                alert('No invoices to export.');
                return;
            }

            const headers = ['Invoice #', 'Customer', 'Issue Date', 'Due Date', 'Status', 'Total'];
            const rows = lastInvoices.map((invoice) => [
                invoice.invoice_number,
                getCustomerName(invoice),
                formatDate(invoice.issue_date),
                formatDate(invoice.due_date),
                invoice.status,
                getGrandTotal(invoice),
            ]);

            const csv = [headers.join(','), ...rows.map((row) => row.map((value) => `"${String(value).replace(/"/g, '""')}"`).join(','))].join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'invoices.csv';
            link.click();
            URL.revokeObjectURL(url);
        }

        function exportToPdf() {
            if (!lastInvoices.length) {
                alert('No invoices to export.');
                return;
            }

            const win = window.open('', '_blank');

            if (!win) {
                alert('Please allow popups to export PDF.');
                return;
            }

            win.document.write('<html><head><title>Invoices</title></head><body>');
            win.document.write('<h1>Invoice Summary</h1>');
            win.document.write('<table border="1" cellspacing="0" cellpadding="6"><thead><tr>');
            win.document.write('<th>Invoice #</th><th>Customer</th><th>Issue Date</th><th>Due Date</th><th>Status</th><th>Total</th>');
            win.document.write('</tr></thead><tbody>');

            lastInvoices.forEach((invoice) => {
                win.document.write(`<tr><td>${invoice.invoice_number}</td><td>${getCustomerName(invoice)}</td><td>${formatDate(invoice.issue_date)}</td><td>${formatDate(invoice.due_date)}</td><td>${invoice.status}</td><td>${formatCurrency(getGrandTotal(invoice))}</td></tr>`);
            });

            win.document.write('</tbody></table>');
            win.document.write('</body></html>');
            win.document.close();
            win.print();
        }

        function handlePrint() {
            window.print();
        }

        function attachEvents() {
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    state.page = 1;
                    loadInvoices();
                }, 250);
            });

            [statusFilter, startDateFilter, endDateFilter, minAmountFilter, maxAmountFilter].forEach((element) => {
                element.addEventListener('change', () => {
                    state.page = 1;
                    loadInvoices();
                });
            });

            perPageSelect.addEventListener('change', () => {
                state.perPage = parseInt(perPageSelect.value, 10);
                state.page = 1;
                loadInvoices();
            });

            prevPageBtn.addEventListener('click', () => {
                if (state.page > 1) {
                    state.page -= 1;
                    loadInvoices();
                }
            });

            nextPageBtn.addEventListener('click', () => {
                state.page += 1;
                loadInvoices();
            });

            document.querySelectorAll('#invoiceTable thead th[data-sort]').forEach((header) => {
                header.addEventListener('click', () => {
                    const sortKey = header.getAttribute('data-sort');

                    if (state.sortBy === sortKey) {
                        state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        state.sortBy = sortKey;
                        state.sortDir = 'asc';
                    }

                    loadInvoices();
                });
            });

            exportExcelBtn.addEventListener('click', exportToExcel);
        }

        async function initialize() {
            attachEvents();
            loadInvoices();
        }

        initialize();
    </script>
</body>
</html>
