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
    <title>Customers - Gran Prebeo</title>
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

        .sidebar {
            min-height: 100vh;
            background: var(--sidebar-bg);
            color: #fff8f0;
            padding: 1.5rem 0;
            box-shadow: 4px 0 18px rgba(139, 104, 68, 0.32);
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

        .notes-box {
            border-radius: 16px;
            background-color: var(--surface);
            box-shadow: 0 18px 30px rgba(139, 104, 68, 0.12);
            padding: 1.5rem;
        }

        .add-customer-modal .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 35px rgba(139, 104, 68, 0.2);
        }

        .add-customer-modal .form-control,
        .add-customer-modal .form-select {
            border-radius: 15px;
            border: 1px solid #e0e0e0;
            padding: 0.75rem 1rem;
        }

        .add-customer-modal .form-check-input:checked {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }

        .add-customer-modal .btn-cancel {
            border-radius: 25px;
            border: 1px solid #ced4da;
            color: #6c757d;
            padding: 0.6rem 2.5rem;
            background: #fff;
        }

        .add-customer-modal .btn-submit {
            border-radius: 25px;
            padding: 0.6rem 2.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
        }

        .status-badge {
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: capitalize;
        }

        .status-new { background: rgba(59, 130, 246, 0.18); color: #1d4ed8; }
        .status-active { background: rgba(34, 197, 94, 0.22); color: #15803d; }
        .status-inactive { background: rgba(148, 163, 184, 0.25); color: #475569; }

        body.dark-mode .status-new { background: rgba(59, 130, 246, 0.32); color: #93c5fd; }
        body.dark-mode .status-active { background: rgba(34, 197, 94, 0.3); color: #4ade80; }
        body.dark-mode .status-inactive { background: rgba(148, 163, 184, 0.35); color: #e2e8f0; }
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
                    <li class="nav-item"><a href="customers.php" class="nav-link active"><i class="bi bi-people"></i> Customers</a></li>
                    <li class="nav-item"><a href="invoices.php" class="nav-link"><i class="bi bi-receipt"></i> Invoices</a></li>
                    <li class="nav-item"><a href="delivery.php" class="nav-link"><i class="bi bi-truck"></i> Delivery</a></li>
                    <li class="nav-item"><a href="reports.php" class="nav-link"><i class="bi bi-graph-up"></i> Reports</a></li>
                    <li class="nav-item mt-4"><a href="dashboard.php?logout=1" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>

            <div class="col-lg-10 main-content">
                <div class="page-header">
                    <div>
                        <h2 class="mb-1">Customer Directory</h2>
                        <p class="mb-0">Manage customer profiles and contact info</p>
                    </div>
                    <div class="text-end">
                        <div class="mb-1"><small class="brand-tagline">Gran Prebeo Merchandise System</small></div>
                    </div>
                </div>

                <div class="card card-surface mb-4">
                    <div class="card-body">
                        <div class="row g-3 filters">
                            <div class="col-md-4">
                                <label class="form-label">Search Customers</label>
                                <input type="text" class="form-control" placeholder="Search by name or email" id="searchCustomers">
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="statusFilter">
                                    <option value="" selected>All statuses</option>
                                    <option value="new">New</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-surface">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Customers List</h5>
                        <div class="d-flex align-items-center">
                            <a href="customers.php" id="backToCustomersBtn" class="btn btn-outline-secondary btn-sm me-2 d-none">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                            <a href="customers.php?deleted=1" id="viewDeletedBtn" class="btn btn-outline-secondary btn-sm me-2">
                                <i class="bi bi-archive"></i> Deleted Customers
                            </a>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                                <i class="bi bi-person-plus"></i> Add Customer
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Registered</th>
                                        <th>Status</th>
                                        <th>Address</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="customersTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade add-customer-modal" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerModalLabel">Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="addCustomerError"></div>
                    <form id="addCustomerForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="First name" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Last name" name="last_name" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="genderMale" value="male" required>
                                        <label class="form-check-label" for="genderMale">Male</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="female" required>
                                        <label class="form-check-label" for="genderFemale">Female</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Birth Date</label>
                                <input type="date" class="form-control" name="birth_date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" placeholder="ex: +1 (968) 283 8821" name="phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" placeholder="ex: name@gmail.com" name="email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" placeholder="ex: Matina, Davao City" name="address">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-submit" form="addCustomerForm">Add</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade add-customer-modal" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="editCustomerError"></div>
                    <form id="editCustomerForm">
                        <input type="hidden" name="id" id="editCustomerId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" id="editFirstName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" id="editLastName" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="editGenderMale" value="male" required>
                                        <label class="form-check-label" for="editGenderMale">Male</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="editGenderFemale" value="female" required>
                                        <label class="form-check-label" for="editGenderFemale">Female</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Birth Date</label>
                                <input type="date" class="form-control" name="birth_date" id="editBirthDate">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" id="editPhone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="editEmail">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="address" id="editAddress">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" name="status" id="editStatus" required>
                                    <option value="new">New</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-submit" form="editCustomerForm">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewCustomerModal" tabindex="-1" aria-labelledby="viewCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewCustomerModalLabel">Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Name</dt><dd class="col-sm-8" id="viewFullName">—</dd>
                        <dt class="col-sm-4">Gender</dt><dd class="col-sm-8" id="viewGender">—</dd>
                        <dt class="col-sm-4">Birth Date</dt><dd class="col-sm-8" id="viewBirthDate">—</dd>
                        <dt class="col-sm-4">Phone</dt><dd class="col-sm-8" id="viewPhone">—</dd>
                        <dt class="col-sm-4">Email</dt><dd class="col-sm-8" id="viewEmail">—</dd>
                        <dt class="col-sm-4">Address</dt><dd class="col-sm-8" id="viewAddress">—</dd>
                        <dt class="col-sm-4">Status</dt><dd class="col-sm-8" id="viewStatus">—</dd>
                        <dt class="col-sm-4">Registered</dt><dd class="col-sm-8" id="viewCreatedAt">—</dd>
                        <dt class="col-sm-4">Deleted At</dt><dd class="col-sm-8" id="viewDeletedAt">—</dd>
                        <dt class="col-sm-4">Delete Reason</dt><dd class="col-sm-8" id="viewDeleteReason">—</dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCustomerModalLabel">Delete Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="deleteCustomerError"></div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <select class="form-select" id="deleteReasonSelect" required>
                            <option value="">Select a reason</option>
                            <option value="Duplicate Record">Duplicate Record</option>
                            <option value="Requested by Customer">Requested by Customer</option>
                            <option value="Fraudulent/Invalid Account">Fraudulent/Invalid Account</option>
                            <option value="Business No Longer Exists">Business No Longer Exists</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="deleteReasonOtherGroup">
                        <label class="form-label">Custom Reason</label>
                        <input type="text" class="form-control" id="deleteReasonOtherInput" placeholder="Enter reason">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteCustomerBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        const customersTableBody = document.getElementById('customersTableBody');
        const searchCustomersInput = document.getElementById('searchCustomers');
        const statusFilterSelect = document.getElementById('statusFilter');
        const addCustomerForm = document.getElementById('addCustomerForm');
        const addCustomerModalElement = document.getElementById('addCustomerModal');
        const addCustomerError = document.getElementById('addCustomerError');
        const addCustomerModal = new bootstrap.Modal(addCustomerModalElement);
        const editCustomerModalElement = document.getElementById('editCustomerModal');
        const editCustomerModal = new bootstrap.Modal(editCustomerModalElement);
        const viewCustomerModalElement = document.getElementById('viewCustomerModal');
        const viewCustomerModal = new bootstrap.Modal(viewCustomerModalElement);
        const editCustomerForm = document.getElementById('editCustomerForm');
        const editCustomerError = document.getElementById('editCustomerError');
        const editCustomerIdInput = document.getElementById('editCustomerId');
        const editFirstNameInput = document.getElementById('editFirstName');
        const editLastNameInput = document.getElementById('editLastName');
        const editGenderMaleInput = document.getElementById('editGenderMale');
        const editGenderFemaleInput = document.getElementById('editGenderFemale');
        const editBirthDateInput = document.getElementById('editBirthDate');
        const editPhoneInput = document.getElementById('editPhone');
        const editEmailInput = document.getElementById('editEmail');
        const editAddressInput = document.getElementById('editAddress');
        const editStatusSelect = document.getElementById('editStatus');

        let searchTimeout;
        let currentStatusFilter = '';
        const backToCustomersBtn = document.getElementById('backToCustomersBtn');
        const viewDeletedBtn = document.getElementById('viewDeletedBtn');
        const urlParams = new URLSearchParams(window.location.search);
        const showDeleted = urlParams.get('deleted') === '1';

        if (showDeleted) {
            backToCustomersBtn?.classList.remove('d-none');
            viewDeletedBtn?.classList.add('d-none');
        }
        let pendingDeleteCustomerId = null;
        const viewFullName = document.getElementById('viewFullName');
        const viewGender = document.getElementById('viewGender');
        const viewBirthDate = document.getElementById('viewBirthDate');
        const viewPhone = document.getElementById('viewPhone');
        const viewEmail = document.getElementById('viewEmail');
        const viewAddress = document.getElementById('viewAddress');
        const viewStatus = document.getElementById('viewStatus');
        const viewCreatedAt = document.getElementById('viewCreatedAt');
        const viewDeletedAt = document.getElementById('viewDeletedAt');
        const viewDeleteReason = document.getElementById('viewDeleteReason');

        function renderCustomers(customers) {
            customersTableBody.innerHTML = '';

            if (!customers.length) {
                const emptyRow = document.createElement('tr');
                const emptyCell = document.createElement('td');
                emptyCell.colSpan = 7;
                emptyCell.className = 'text-center text-muted';
                emptyCell.textContent = 'No customers found.';
                emptyRow.appendChild(emptyCell);
                customersTableBody.appendChild(emptyRow);
                return;
            }

            customers.forEach(customer => {
                const row = document.createElement('tr');

                const nameCell = document.createElement('td');
                nameCell.textContent = `${customer.first_name} ${customer.last_name}`.trim();
                row.appendChild(nameCell);

                const emailCell = document.createElement('td');
                emailCell.textContent = customer.email || '—';
                row.appendChild(emailCell);

                const phoneCell = document.createElement('td');
                phoneCell.textContent = customer.phone || '—';
                row.appendChild(phoneCell);

                const registeredCell = document.createElement('td');
                registeredCell.textContent = formatDateTime(customer.created_at);
                row.appendChild(registeredCell);

                const statusCell = document.createElement('td');
                statusCell.innerHTML = renderStatusBadge(customer.status);
                row.appendChild(statusCell);

                const addressCell = document.createElement('td');
                addressCell.textContent = customer.address || '—';
                row.appendChild(addressCell);

                const actionsCell = document.createElement('td');
                actionsCell.className = 'text-end';

                if (showDeleted) {
                    const viewButton = document.createElement('button');
                    viewButton.type = 'button';
                    viewButton.className = 'btn btn-outline-secondary btn-sm';
                    viewButton.innerHTML = '<i class="bi bi-eye"></i> View';
                    viewButton.addEventListener('click', () => openViewModal(customer));
                    actionsCell.appendChild(viewButton);
                } else {
                    const editButton = document.createElement('button');
                    editButton.type = 'button';
                    editButton.className = 'btn btn-outline-primary btn-sm me-2';
                    editButton.innerHTML = '<i class="bi bi-pencil"></i> Edit';
                    editButton.addEventListener('click', () => openEditModal(customer));
                    actionsCell.appendChild(editButton);

                    const deleteButton = document.createElement('button');
                    deleteButton.type = 'button';
                    deleteButton.className = 'btn btn-outline-danger btn-sm';
                    deleteButton.innerHTML = '<i class="bi bi-trash"></i> Delete';
                    deleteButton.addEventListener('click', () => openDeleteModal(customer.id));
                    actionsCell.appendChild(deleteButton);
                }

                row.appendChild(actionsCell);

                customersTableBody.appendChild(row);
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

        function renderStatusBadge(status) {
            const normalized = (status || '').toLowerCase();
            const classes = {
                new: 'status-badge status-new',
                active: 'status-badge status-active',
                inactive: 'status-badge status-inactive'
            };

            const label = normalized.charAt(0).toUpperCase() + normalized.slice(1);
            return `<span class="${classes[normalized] || 'status-badge status-inactive'}">${label || 'Unknown'}</span>`;
        }

        async function loadCustomers(search = '', status = '') {
            try {
                const endpoint = new URL('backend/customers.php', window.location.href);

                if (search) {
                    endpoint.searchParams.set('search', search);
                }

                if (status) {
                    endpoint.searchParams.set('status', status);
                }

                if (showDeleted) {
                    endpoint.searchParams.set('deleted', '1');
                }

                const response = await fetch(endpoint);

                if (!response.ok) {
                    throw new Error('Failed to load customers.');
                }

                const payload = await response.json();

                if (!payload.success) {
                    throw new Error(payload.message || 'Failed to load customers.');
                }

                renderCustomers(Array.isArray(payload.data) ? payload.data : []);
            } catch (error) {
                customersTableBody.innerHTML = '';
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 7;
                cell.className = 'text-center text-danger';
                cell.textContent = error.message;
                row.appendChild(cell);
                customersTableBody.appendChild(row);
            }
        }

        addCustomerForm.addEventListener('submit', async event => {
            event.preventDefault();

            const formData = new FormData(addCustomerForm);
            const payload = Object.fromEntries(formData.entries());

            addCustomerError.classList.add('d-none');
            addCustomerError.textContent = '';

            try {
                const endpoint = new URL('backend/customers.php', window.location.href);

                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(payload)
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    const message = data.errors ? Object.values(data.errors).join(' ') : (data.message || 'Failed to add customer.');
                    throw new Error(message);
                }

                addCustomerForm.reset();
                addCustomerModal.hide();
                loadCustomers(searchCustomersInput.value.trim(), currentStatusFilter);

                const successAlert = document.createElement('div');
                successAlert.className = 'alert alert-success mt-3';
                successAlert.textContent = 'Customer added successfully!';
                addCustomerForm.prepend(successAlert);
                setTimeout(() => successAlert.remove(), 3000);

            } catch (error) {
                addCustomerError.textContent = error.message;
                addCustomerError.classList.remove('d-none');
            }
        });

        editCustomerForm.addEventListener('submit', async event => {
            event.preventDefault();

            if (!editCustomerIdInput.value) {
                return;
            }

            const formData = new FormData(editCustomerForm);
            const payload = Object.fromEntries(formData.entries());
            payload.id = editCustomerIdInput.value;

            editCustomerError.classList.add('d-none');
            editCustomerError.textContent = '';

            try {
                const endpoint = new URL('backend/customers.php', window.location.href);

                const response = await fetch(endpoint, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    const message = data.errors ? Object.values(data.errors).join(' ') : (data.message || 'Failed to update customer.');
                    throw new Error(message);
                }

                editCustomerModal.hide();
                loadCustomers(searchCustomersInput.value.trim(), currentStatusFilter);

            } catch (error) {
                editCustomerError.textContent = error.message;
                editCustomerError.classList.remove('d-none');
            }
        });

        searchCustomersInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadCustomers(searchCustomersInput.value.trim(), currentStatusFilter);
            }, 300);
        });

        statusFilterSelect.addEventListener('change', () => {
            currentStatusFilter = statusFilterSelect.value;
            loadCustomers(searchCustomersInput.value.trim(), currentStatusFilter);
        });

        addCustomerModalElement.addEventListener('hidden.bs.modal', () => {
            addCustomerForm.reset();
            addCustomerError.classList.add('d-none');
            addCustomerError.textContent = '';
        });

        editCustomerModalElement.addEventListener('hidden.bs.modal', () => {
            editCustomerForm.reset();
            editCustomerError.classList.add('d-none');
            editCustomerError.textContent = '';
        });

        function openEditModal(customer) {
            editCustomerIdInput.value = customer.id;
            editFirstNameInput.value = customer.first_name || '';
            editLastNameInput.value = customer.last_name || '';
            editBirthDateInput.value = customer.birth_date || '';
            editPhoneInput.value = customer.phone || '';
            editEmailInput.value = customer.email || '';
            editAddressInput.value = customer.address || '';
            editStatusSelect.value = (customer.status || 'new').toLowerCase();

            if (customer.gender === 'female') {
                editGenderFemaleInput.checked = true;
            } else {
                editGenderMaleInput.checked = true;
            }

            editCustomerModal.show();
        }

        function openViewModal(customer) {
            const fullName = `${customer.first_name || ''} ${customer.last_name || ''}`.trim() || '—';
            viewFullName.textContent = fullName;
            viewGender.textContent = (customer.gender || '—').toString();
            viewBirthDate.textContent = customer.birth_date ? new Date(customer.birth_date).toLocaleDateString() : '—';
            viewPhone.textContent = customer.phone || '—';
            viewEmail.textContent = customer.email || '—';
            viewAddress.textContent = customer.address || '—';
            viewStatus.textContent = (customer.status || '—');
            viewCreatedAt.textContent = formatDateTime(customer.created_at);
            viewDeletedAt.textContent = customer.deleted_at ? formatDateTime(customer.deleted_at) : '—';
            viewDeleteReason.textContent = customer.delete_reason || '—';
            viewCustomerModal.show();
        }

        function openDeleteModal(id) {
            pendingDeleteCustomerId = id;
            const select = document.getElementById('deleteReasonSelect');
            const otherGroup = document.getElementById('deleteReasonOtherGroup');
            const otherInput = document.getElementById('deleteReasonOtherInput');
            const err = document.getElementById('deleteCustomerError');
            select.value = '';
            otherInput.value = '';
            otherGroup.classList.add('d-none');
            err.classList.add('d-none');
            err.textContent = '';
            deleteCustomerModal.show();
        }

        async function handleDeleteCustomer(id, reason) {
            try {
                const endpoint = new URL('backend/customers.php', window.location.href);
                endpoint.searchParams.set('id', id);

                const response = await fetch(endpoint, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ delete_reason: reason })
                });

                if (response.status === 204) {
                    loadCustomers(searchCustomersInput.value.trim(), currentStatusFilter);
                    deleteCustomerModal.hide();
                    return;
                }

                const data = await response.json();
                const message = data && !data.success ? (data.message || 'Failed to delete customer.') : 'Failed to delete customer.';
                const err = document.getElementById('deleteCustomerError');
                err.textContent = message;
                err.classList.remove('d-none');
            } catch (error) {
                const err = document.getElementById('deleteCustomerError');
                err.textContent = error.message;
                err.classList.remove('d-none');
            }
        }

        const deleteCustomerModalElement = document.getElementById('deleteCustomerModal');
        const deleteCustomerModal = new bootstrap.Modal(deleteCustomerModalElement);
        const deleteReasonSelect = document.getElementById('deleteReasonSelect');
        const deleteReasonOtherGroup = document.getElementById('deleteReasonOtherGroup');
        const deleteReasonOtherInput = document.getElementById('deleteReasonOtherInput');
        const confirmDeleteCustomerBtn = document.getElementById('confirmDeleteCustomerBtn');

        deleteReasonSelect.addEventListener('change', () => {
            const val = deleteReasonSelect.value;
            if (val === 'Other') {
                deleteReasonOtherGroup.classList.remove('d-none');
                deleteReasonOtherInput.required = true;
                deleteReasonOtherInput.focus();
            } else {
                deleteReasonOtherGroup.classList.add('d-none');
                deleteReasonOtherInput.required = false;
                deleteReasonOtherInput.value = '';
            }
        });

        confirmDeleteCustomerBtn.addEventListener('click', () => {
            const selectVal = deleteReasonSelect.value;
            const otherVal = deleteReasonOtherInput.value.trim();
            const err = document.getElementById('deleteCustomerError');
            err.classList.add('d-none');
            err.textContent = '';

            if (selectVal === '') {
                err.textContent = 'Please select a reason.';
                err.classList.remove('d-none');
                return;
            }

            const reason = selectVal === 'Other' ? otherVal : selectVal;
            if (reason === '') {
                err.textContent = 'Please provide a reason.';
                err.classList.remove('d-none');
                return;
            }

            if (pendingDeleteCustomerId) {
                handleDeleteCustomer(pendingDeleteCustomerId, reason);
            }
        });

        loadCustomers('', currentStatusFilter);
    </script>
</body>
</html>
