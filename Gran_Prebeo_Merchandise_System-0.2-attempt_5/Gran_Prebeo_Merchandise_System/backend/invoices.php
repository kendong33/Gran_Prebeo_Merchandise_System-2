<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handle_get();
            break;
        case 'POST':
            handle_post();
            break;
        case 'PUT':
            handle_put();
            break;
        case 'DELETE':
            handle_delete();
            break;
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed.',
            ]);
    }
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unexpected server error.',
        'detail' => $exception->getMessage(),
    ]);
}

function handle_get(): void
{
    $connection = get_db_connection();

    $lookup = resolve_invoice_lookup();

    if ($lookup !== null) {
        $invoice = fetch_invoice($connection, $lookup['field'], $lookup['value'], $lookup['include_deleted']);

        if ($invoice === null) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Invoice not found.',
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => $invoice,
        ]);
        return;
    }

    $filters = resolve_filters();
    $sort = resolve_sorting();
    $pagination = resolve_pagination();

    $result = fetch_invoice_collection($connection, $filters, $sort, $pagination);

    echo json_encode([
        'success' => true,
        'data' => $result['invoices'],
        'pagination' => $result['pagination'],
        'summary' => $result['summary'],
    ]);
}

function handle_post(): void
{
    $payload = resolve_request_payload();
    $connection = get_db_connection();

    [$fields, $errors] = prepare_invoice_payload($payload, false, null);

    if ($errors !== []) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => $errors,
            'message' => 'Validation failed.',
        ]);
        return;
    }

    $customer = fetch_customer_profile($connection, $fields['customer_id']);

    if ($customer === null) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => ['customer_id' => 'Selected customer does not exist.'],
            'message' => 'Validation failed.',
        ]);
        return;
    }

    if ($fields['invoice_number'] === '') {
        $fields['invoice_number'] = generate_invoice_number($connection);
    } elseif (invoice_number_exists($connection, $fields['invoice_number'])) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => ['invoice_number' => 'Invoice number already exists.'],
            'message' => 'Validation failed.',
        ]);
        return;
    }

    if ($fields['tracking_number'] === '') {
        $fields['tracking_number'] = generate_tracking_number($connection);
    } elseif (tracking_number_exists($connection, $fields['tracking_number'])) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => ['tracking_number' => 'Tracking number already exists.'],
            'message' => 'Validation failed.',
        ]);
        return;
    }

    $uid = generate_uuid_v4();

    while (invoice_uid_exists($connection, $uid)) {
        $uid = generate_uuid_v4();
    }

    $timestamps = build_timestamps();
    $itemsJson = json_encode($fields['items'], JSON_UNESCAPED_UNICODE);
    $customerName = trim($customer['first_name'] . ' ' . $customer['last_name']);

    $statement = $connection->prepare(
        'INSERT INTO invoices (
            uid,
            invoice_number,
            order_id,
            customer_id,
            customer_name,
            customer_email,
            billing_street,
            billing_city,
            billing_state,
            billing_postal_code,
            billing_country,
            items_json,
            currency,
            subtotal,
            tax_total,
            shipping_total,
            discount_total,
            grand_total,
            status,
            next_status,
            payment_method,
            payment_date,
            issue_date,
            due_date,
            notes,
            terms,
            tracking_number,
            created_at,
            updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )'
    );

    $statement->bind_param(
        'sssisssssssssdddddsssssssssss',
        $uid,
        $fields['invoice_number'],
        $fields['order_id'],
        $fields['customer_id'],
        $customerName,
        $customer['email'],
        $fields['billing_street'],
        $fields['billing_city'],
        $fields['billing_state'],
        $fields['billing_postal_code'],
        $fields['billing_country'],
        $itemsJson,
        $fields['currency'],
        $fields['subtotal'],
        $fields['tax_total'],
        $fields['shipping_total'],
        $fields['discount_total'],
        $fields['grand_total'],
        $fields['status'],
        $fields['next_status'],
        $fields['payment_method'],
        $fields['payment_date'],
        $fields['issue_date'],
        $fields['due_date'],
        $fields['notes'],
        $fields['terms'],
        $fields['tracking_number'],
        $timestamps['created_at'],
        $timestamps['updated_at']
    );

    $statement->execute();

    if ($statement->error) {
        throw new RuntimeException('Failed to create invoice: ' . $statement->error);
    }

    $invoice = fetch_invoice($connection, 'id', (int)$connection->insert_id, true);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'data' => $invoice,
        'message' => 'Invoice created successfully.',
    ]);
}

function handle_put(): void
{
    $payload = resolve_request_payload();
    $connection = get_db_connection();

    $lookup = resolve_mutation_lookup($payload);

    if ($lookup === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'An invoice identifier is required.',
        ]);
        return;
    }

    $existing = fetch_invoice($connection, $lookup['field'], $lookup['value'], true);

    if ($existing === null || $existing['deleted']) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Invoice not found.',
        ]);
        return;
    }

    [$fields, $errors] = prepare_invoice_payload($payload, true, $existing);

    if ($errors !== []) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => $errors,
            'message' => 'Validation failed.',
        ]);
        return;
    }

    $customer = fetch_customer_profile($connection, $fields['customer_id']);

    if ($customer === null) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => ['customer_id' => 'Selected customer does not exist.'],
            'message' => 'Validation failed.',
        ]);
        return;
    }

    if ($fields['invoice_number'] === '') {
        $fields['invoice_number'] = $existing['invoice_number'];
    } elseif (invoice_number_exists($connection, $fields['invoice_number'], (int)$existing['id'])) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => ['invoice_number' => 'Invoice number already exists.'],
            'message' => 'Validation failed.',
        ]);
        return;
    }

    if ($fields['tracking_number'] === '') {
        $fields['tracking_number'] = $existing['tracking_number'] !== ''
            ? $existing['tracking_number']
            : generate_tracking_number($connection, (int)$existing['id']);
    } elseif (tracking_number_exists($connection, $fields['tracking_number'], (int)$existing['id'])) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => ['tracking_number' => 'Tracking number already exists.'],
            'message' => 'Validation failed.',
        ]);
        return;
    }

    $itemsJson = json_encode($fields['items'], JSON_UNESCAPED_UNICODE);
    $timestamps = build_timestamps($existing['created_at']);
    $customerName = trim($customer['first_name'] . ' ' . $customer['last_name']);
    $deletedFlag = $fields['deleted'] ? 1 : 0;
    $deletedAt = $fields['deleted'] ? $timestamps['deleted_at'] : null;

    $statement = $connection->prepare(
        'UPDATE invoices SET
            invoice_number = ?,
            order_id = ?,
            customer_id = ?,
            customer_name = ?,
            customer_email = ?,
            billing_street = ?,
            billing_city = ?,
            billing_state = ?,
            billing_postal_code = ?,
            billing_country = ?,
            items_json = ?,
            currency = ?,
            subtotal = ?,
            tax_total = ?,
            shipping_total = ?,
            discount_total = ?,
            grand_total = ?,
            status = ?,
            next_status = ?,
            payment_method = ?,
            payment_date = ?,
            issue_date = ?,
            due_date = ?,
            notes = ?,
            terms = ?,
            tracking_number = ?,
            updated_at = ?,
            deleted = ?,
            deleted_at = ?
        WHERE id = ?'
    );

    $statement->bind_param(
        'ssisssssssssdddddssssssssssisi',
        $fields['invoice_number'],
        $fields['order_id'],
        $fields['customer_id'],
        $customerName,
        $customer['email'],
        $fields['billing_street'],
        $fields['billing_city'],
        $fields['billing_state'],
        $fields['billing_postal_code'],
        $fields['billing_country'],
        $itemsJson,
        $fields['currency'],
        $fields['subtotal'],
        $fields['tax_total'],
        $fields['shipping_total'],
        $fields['discount_total'],
        $fields['grand_total'],
        $fields['status'],
        $fields['next_status'],
        $fields['payment_method'],
        $fields['payment_date'],
        $fields['issue_date'],
        $fields['due_date'],
        $fields['notes'],
        $fields['terms'],
        $fields['tracking_number'],
        $timestamps['updated_at'],
        $deletedFlag,
        $deletedAt,
        $existing['id']
    );

    $statement->execute();

    if ($statement->error) {
        throw new RuntimeException('Failed to update invoice: ' . $statement->error);
    }

    $invoice = fetch_invoice($connection, 'id', (int)$existing['id'], true);

    echo json_encode([
        'success' => true,
        'data' => $invoice,
        'message' => 'Invoice updated successfully.',
    ]);
}

function handle_delete(): void
{
    $payload = resolve_request_payload();
    $connection = get_db_connection();

    $lookup = resolve_mutation_lookup($payload);

    if ($lookup === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'An invoice identifier is required.',
        ]);
        return;
    }

    $existing = fetch_invoice($connection, $lookup['field'], $lookup['value'], true);

    if ($existing === null || $existing['deleted']) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Invoice not found.',
        ]);
        return;
    }

    if ($existing['status'] !== 'draft') {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Only draft invoices can be deleted.',
        ]);
        return;
    }

    $timestamps = build_timestamps($existing['created_at']);

    $statement = $connection->prepare(
        'UPDATE invoices SET deleted = 1, deleted_at = ?, updated_at = ? WHERE id = ?'
    );

    $statement->bind_param('ssi', $timestamps['deleted_at'], $timestamps['updated_at'], $existing['id']);
    $statement->execute();

    if ($statement->error) {
        throw new RuntimeException('Failed to delete invoice: ' . $statement->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Invoice deleted successfully.',
    ]);
}

function resolve_request_payload(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $decoded = json_decode(file_get_contents('php://input'), true);
        return is_array($decoded) ? $decoded : [];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return $_GET;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $decoded = json_decode(file_get_contents('php://input'), true);
        return is_array($decoded) ? $decoded : $_POST;
    }

    parse_str(file_get_contents('php://input'), $data);
    return is_array($data) ? $data : [];
}

function resolve_invoice_lookup(): ?array
{
    $includeDeleted = resolve_boolean_flag($_GET['include_deleted'] ?? false);

    if (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
        return ['field' => 'id', 'value' => (int)$_GET['id'], 'include_deleted' => $includeDeleted];
    }

    $uid = trim((string)($_GET['uid'] ?? ''));

    if ($uid !== '') {
        return ['field' => 'uid', 'value' => $uid, 'include_deleted' => $includeDeleted];
    }

    $number = trim((string)($_GET['invoice_number'] ?? ($_GET['number'] ?? '')));

    if ($number !== '') {
        return ['field' => 'invoice_number', 'value' => $number, 'include_deleted' => $includeDeleted];
    }

    return null;
}

function resolve_mutation_lookup(array $payload): ?array
{
    if (isset($payload['id']) && ctype_digit((string)$payload['id'])) {
        return ['field' => 'id', 'value' => (int)$payload['id']];
    }

    if (isset($payload['uid']) && trim((string)$payload['uid']) !== '') {
        return ['field' => 'uid', 'value' => trim((string)$payload['uid'])];
    }

    if (isset($payload['invoice_number']) && trim((string)$payload['invoice_number']) !== '') {
        return ['field' => 'invoice_number', 'value' => trim((string)$payload['invoice_number'])];
    }

    return null;
}

function resolve_filters(): array
{
    $conditions = [];
    $parameters = [];
    $types = '';

    $includeDeleted = resolve_boolean_flag($_GET['include_deleted'] ?? false);

    if (!$includeDeleted) {
        $conditions[] = 'invoices.deleted = 0';
    }

    $status = strtolower(trim((string)($_GET['status'] ?? '')));
    $allowedStatuses = ['draft', 'sent', 'pending', 'paid', 'overdue', 'cancelled'];

    if ($status !== '' && in_array($status, $allowedStatuses, true)) {
        $conditions[] = 'invoices.status = ?';
        $parameters[] = $status;
        $types .= 's';
    }

    $customerId = $_GET['customer_id'] ?? null;

    if ($customerId !== null && ctype_digit((string)$customerId)) {
        $conditions[] = 'invoices.customer_id = ?';
        $parameters[] = (int)$customerId;
        $types .= 'i';
    }

    $orderId = trim((string)($_GET['order_id'] ?? ''));

    if ($orderId !== '') {
        $conditions[] = 'invoices.order_id = ?';
        $parameters[] = $orderId;
        $types .= 's';
    }

    $startDate = normalize_date_only_value($_GET['start_date'] ?? ($_GET['issue_date_from'] ?? null));

    if ($startDate !== null) {
        $conditions[] = 'invoices.issue_date >= ?';
        $parameters[] = $startDate;
        $types .= 's';
    }

    $endDate = normalize_date_only_value($_GET['end_date'] ?? ($_GET['issue_date_to'] ?? null));

    if ($endDate !== null) {
        $conditions[] = 'invoices.issue_date <= ?';
        $parameters[] = $endDate;
        $types .= 's';
    }

    // Amount range filters
    $minAmountInput = $_GET['min_amount'] ?? null;
    $maxAmountInput = $_GET['max_amount'] ?? null;

    if ($minAmountInput !== null && trim((string)$minAmountInput) !== '' && is_numeric($minAmountInput)) {
        $conditions[] = 'invoices.grand_total >= ?';
        $parameters[] = (float)$minAmountInput;
        $types .= 'd';
    }

    if ($maxAmountInput !== null && trim((string)$maxAmountInput) !== '' && is_numeric($maxAmountInput)) {
        $conditions[] = 'invoices.grand_total <= ?';
        $parameters[] = (float)$maxAmountInput;
        $types .= 'd';
    }

    $search = trim((string)($_GET['search'] ?? ''));

    if ($search !== '') {
        $conditions[] = '(
            invoices.invoice_number LIKE ? OR
            invoices.customer_name LIKE ? OR
            invoices.customer_email LIKE ? OR
            invoices.order_id LIKE ?
        )';
        $like = "%{$search}%";
        $parameters[] = $like;
        $parameters[] = $like;
        $parameters[] = $like;
        $parameters[] = $like;
        $types .= 'ssss';
    }

    return [
        'conditions' => $conditions,
        'parameters' => $parameters,
        'types' => $types,
    ];
}

function resolve_sorting(): array
{
    $sortBy = strtolower(trim((string)($_GET['sort_by'] ?? 'created_at')));
    $direction = strtolower(trim((string)($_GET['sort_dir'] ?? 'desc')));

    $columns = [
        'invoice_number' => 'invoices.invoice_number',
        'issue_date' => 'invoices.issue_date',
        'due_date' => 'invoices.due_date',
        'grand_total' => 'invoices.grand_total',
        'status' => 'invoices.status',
        'customer' => 'invoices.customer_name',
        'created_at' => 'invoices.created_at',
        'updated_at' => 'invoices.updated_at',
    ];

    $orderBy = $columns[$sortBy] ?? $columns['created_at'];
    $direction = $direction === 'asc' ? 'ASC' : 'DESC';

    return ['order_by' => $orderBy, 'direction' => $direction];
}

function resolve_pagination(): array
{
    $perPage = (int)($_GET['per_page'] ?? 50);
    $perPage = max(1, min(100, $perPage));

    $page = (int)($_GET['page'] ?? 1);
    $page = max(1, $page);

    return [
        'page' => $page,
        'per_page' => $perPage,
        'offset' => ($page - 1) * $perPage,
    ];
}

function fetch_invoice_collection(mysqli $connection, array $filters, array $sort, array $pagination): array
{
    $query = 'SELECT invoices.*, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name
        FROM invoices
        LEFT JOIN customers ON customers.id = invoices.customer_id';

    if ($filters['conditions'] !== []) {
        $query .= ' WHERE ' . implode(' AND ', $filters['conditions']);
    }

    $query .= ' ORDER BY ' . $sort['order_by'] . ' ' . $sort['direction'];
    // Inline LIMIT/OFFSET as integers for compatibility with some MySQL/MariaDB versions
    $limit = (int)$pagination['per_page'];
    $offset = (int)$pagination['offset'];
    $query .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;

    $parameters = $filters['parameters'];

    $statement = $connection->prepare($query);
    $types = $filters['types'];

    if ($types !== '') {
        $statement->bind_param($types, ...$parameters);
    }

    $statement->execute();
    $result = $statement->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    $invoices = array_map('map_invoice_row', $rows);

    $count = count_total_invoices($connection, $filters);
    $summary = fetch_invoice_summary($connection, $filters);

    return [
        'invoices' => $invoices,
        'pagination' => [
            'page' => $pagination['page'],
            'per_page' => $pagination['per_page'],
            'total' => $count,
            'total_pages' => (int)ceil($count / max(1, $pagination['per_page'])),
        ],
        'summary' => $summary,
    ];
}

function count_total_invoices(mysqli $connection, array $filters): int
{
    $query = 'SELECT COUNT(*) AS total FROM invoices';

    if ($filters['conditions'] !== []) {
        $query .= ' WHERE ' . implode(' AND ', $filters['conditions']);
    }

    $statement = $connection->prepare($query);

    if ($filters['parameters'] !== []) {
        $statement->bind_param($filters['types'], ...$filters['parameters']);
    }

    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();

    return (int)($row['total'] ?? 0);
}

function fetch_invoice_summary(mysqli $connection, array $filters): array
{
    $query = 'SELECT status, COUNT(*) AS total FROM invoices';

    if ($filters['conditions'] !== []) {
        $query .= ' WHERE ' . implode(' AND ', $filters['conditions']);
    }

    $query .= ' GROUP BY status';

    $statement = $connection->prepare($query);

    if ($filters['parameters'] !== []) {
        $statement->bind_param($filters['types'], ...$filters['parameters']);
    }

    $statement->execute();
    $result = $statement->get_result();

    $summary = [
        'total' => 0,
        'draft' => 0,
        'sent' => 0,
        'pending' => 0,
        'paid' => 0,
        'overdue' => 0,
        'cancelled' => 0,
    ];

    while ($row = $result->fetch_assoc()) {
        $status = strtolower($row['status']);
        $count = (int)$row['total'];
        $summary['total'] += $count;

        if (array_key_exists($status, $summary)) {
            $summary[$status] = $count;
        }
    }

    return $summary;
}

function fetch_invoice(mysqli $connection, string $field, $value, bool $includeDeleted): ?array
{
    // Use PHP 7-compatible logic instead of PHP 8 match
    $column = 'id';
    if ($field === 'uid') {
        $column = 'uid';
    } elseif ($field === 'invoice_number') {
        $column = 'invoice_number';
    } elseif ($field === 'id') {
        $column = 'id';
    }

    $query = 'SELECT invoices.*, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name
        FROM invoices
        LEFT JOIN customers ON customers.id = invoices.customer_id
        WHERE invoices.' . $column . ' = ?';

    if (!$includeDeleted) {
        $query .= ' AND invoices.deleted = 0';
    }

    $query .= ' LIMIT 1';

    $statement = $connection->prepare($query);

    if ($column === 'id') {
        $statement->bind_param('i', $value);
    } else {
        $statement->bind_param('s', $value);
    }

    $statement->execute();
    $result = $statement->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    return map_invoice_row($result->fetch_assoc());
}

function map_invoice_row(array $row): array
{
    $row['id'] = (int)$row['id'];
    $row['customer_id'] = (int)$row['customer_id'];
    $row['subtotal'] = (float)$row['subtotal'];
    $row['tax_total'] = (float)$row['tax_total'];
    $row['shipping_total'] = (float)$row['shipping_total'];
    $row['discount_total'] = (float)$row['discount_total'];
    $row['grand_total'] = (float)$row['grand_total'];
    $row['deleted'] = (int)$row['deleted'] === 1;

    $firstName = trim((string)($row['customer_first_name'] ?? ''));
    $lastName = trim((string)($row['customer_last_name'] ?? ''));
    unset($row['customer_first_name'], $row['customer_last_name']);

    $customerName = trim((string)($row['customer_name'] ?? ''));

    if ($customerName === '' || $customerName === '0') {
        $customerName = trim($firstName . ' ' . $lastName);
    }

    $row['customer_name'] = $customerName !== '' ? $customerName : null;

    $status = strtolower(trim((string)($row['status'] ?? '')));

    if ($status === '' || $status === '0') {
        $status = 'draft';
    }

    $row['status'] = $status;
    $row['items'] = json_decode($row['items_json'], true) ?? [];
    unset($row['items_json']);

    return $row;
}

function prepare_invoice_payload(array $payload, bool $isUpdate, ?array $existing): array
{
    $errors = [];

    $customerIdInput = $payload['customer_id'] ?? ($existing['customer_id'] ?? null);
    $customerId = filter_var($customerIdInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($customerId === false) {
        $errors['customer_id'] = 'Customer is required.';
    }

    $orderId = trim((string)($payload['order_id'] ?? ($existing['order_id'] ?? '')));
    $orderId = $orderId !== '' ? $orderId : null;

    $invoiceNumber = strtoupper(trim((string)($payload['invoice_number'] ?? ($existing['invoice_number'] ?? ''))));

    if ($invoiceNumber !== '' && !preg_match('/^[A-Z0-9\-]+$/', $invoiceNumber)) {
        $errors['invoice_number'] = 'Invoice number may only contain letters, numbers, and hyphens.';
    }

    $trackingNumber = strtoupper(trim((string)($payload['tracking_number'] ?? ($existing['tracking_number'] ?? ''))));

    if ($trackingNumber !== '' && !preg_match('/^[A-Z0-9\-]+$/', $trackingNumber)) {
        $errors['tracking_number'] = 'Tracking number may only contain letters, numbers, and hyphens.';
    }

    $currency = strtoupper(trim((string)($payload['currency'] ?? ($existing['currency'] ?? 'PHP'))));

    if ($currency === '' || !preg_match('/^[A-Z]{3}$/', $currency)) {
        $errors['currency'] = 'Currency must be a three-letter ISO code.';
    }

    $status = strtolower(trim((string)($payload['status'] ?? ($existing['status'] ?? 'draft'))));
    $allowedStatuses = ['draft', 'sent', 'pending', 'paid', 'overdue', 'cancelled'];

    if (!in_array($status, $allowedStatuses, true)) {
        $errors['status'] = 'Status must be draft, sent, pending, paid, overdue, or cancelled.';
    }

    $rawNextStatus = strtolower(trim((string)($payload['next_status'] ?? ($existing['next_status'] ?? ''))));
    $nextStatus = null;

    if ($status === 'draft') {
        if ($rawNextStatus === '' || $rawNextStatus === 'draft') {
            $rawNextStatus = strtolower(trim((string)($existing['next_status'] ?? 'pending')));
        }

        if ($rawNextStatus === '' || !in_array($rawNextStatus, $allowedStatuses, true) || $rawNextStatus === 'draft') {
            $errors['next_status'] = 'Draft invoices must specify the next status (sent, pending, paid, overdue, or cancelled).';
        } else {
            $nextStatus = $rawNextStatus;
        }
    } else {
        $nextStatus = $status;
    }

    $issueDateInput = $payload['issue_date'] ?? ($existing['issue_date'] ?? null);
    $issueDate = normalize_date_only_value($issueDateInput);

    if ($issueDate === null) {
        $errors['issue_date'] = 'Issue date is required and must be a valid date (YYYY-MM-DD).';
    }

    $dueDateInput = $payload['due_date'] ?? ($existing['due_date'] ?? null);
    $dueDate = normalize_date_only_value($dueDateInput);

    if ($dueDateInput !== null && trim((string)$dueDateInput) !== '' && $dueDate === null) {
        $errors['due_date'] = 'Due date must be a valid date (YYYY-MM-DD).';
    }

    if ($issueDate !== null && $dueDate !== null && strtotime($dueDate) < strtotime($issueDate)) {
        $errors['due_date'] = 'Due date cannot be earlier than issue date.';
    }

    $itemsInput = $payload['items'] ?? ($existing['items'] ?? []);
    [$items, $subtotal, $taxTotal, $discountTotal, $itemErrors] = normalize_invoice_items($itemsInput);

    if ($itemErrors !== []) {
        $errors['items'] = $itemErrors;
    }

    $shippingTotal = sanitize_float($payload['shipping_total'] ?? ($existing['shipping_total'] ?? 0), 0);
    $additionalDiscount = sanitize_float($payload['discount_total'] ?? ($existing['discount_total'] ?? 0), 0);

    $totals = calculate_invoice_totals($items, $shippingTotal, $additionalDiscount, $discountTotal);

    $paymentMethod = normalize_string($payload['payment_method'] ?? ($existing['payment_method'] ?? null));
    $paymentDate = normalize_datetime_value($payload['payment_date'] ?? ($existing['payment_date'] ?? null));

    if ($status === 'paid' && $paymentDate === null) {
        $paymentDate = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    $notes = normalize_string($payload['notes'] ?? ($existing['notes'] ?? null));
    $terms = normalize_string($payload['terms'] ?? ($existing['terms'] ?? null));

    $billing = normalize_billing($payload, $existing);

    $deleted = resolve_boolean_flag($payload['deleted'] ?? ($existing['deleted'] ?? false));

    $fields = [
        'customer_id' => $customerId !== false ? $customerId : null,
        'order_id' => $orderId,
        'invoice_number' => $invoiceNumber,
        'tracking_number' => $trackingNumber,
        'currency' => $currency,
        'status' => $status,
        'next_status' => $nextStatus,
        'issue_date' => $issueDate,
        'due_date' => $dueDate,
        'items' => $items,
        'subtotal' => $totals['subtotal'],
        'tax_total' => $totals['tax_total'],
        'shipping_total' => $totals['shipping_total'],
        'discount_total' => $totals['discount_total'],
        'grand_total' => $totals['grand_total'],
        'payment_method' => $paymentMethod,
        'payment_date' => $paymentDate,
        'notes' => $notes,
        'terms' => $terms,
        'billing_street' => $billing['street'],
        'billing_city' => $billing['city'],
        'billing_state' => $billing['state'],
        'billing_postal_code' => $billing['postal_code'],
        'billing_country' => $billing['country'],
        'deleted' => $deleted,
    ];

    return [$fields, $errors];
}

function normalize_billing(array $payload, ?array $existing): array
{
    $billing = $payload['billing'] ?? [];

    return [
        'street' => normalize_string($billing['street'] ?? ($existing['billing_street'] ?? 'Not provided'), 'Not provided'),
        'city' => normalize_string($billing['city'] ?? ($existing['billing_city'] ?? 'Not provided'), 'Not provided'),
        'state' => normalize_string($billing['state'] ?? ($existing['billing_state'] ?? 'Not provided'), 'Not provided'),
        'postal_code' => normalize_string($billing['postal_code'] ?? ($existing['billing_postal_code'] ?? '0000'), '0000'),
        'country' => normalize_string($billing['country'] ?? ($existing['billing_country'] ?? 'Philippines'), 'Philippines'),
    ];
}

function normalize_invoice_items($items): array
{
    $normalized = [];
    $subtotal = 0.0;
    $taxTotal = 0.0;
    $discountTotal = 0.0;
    $errors = [];

    if (!is_array($items) || $items === []) {
        return [[], 0.0, 0.0, 0.0, ['items' => 'At least one invoice item is required.']];
    }

    foreach ($items as $index => $item) {
        $productId = normalize_string($item['productId'] ?? ($item['product_id'] ?? null));
        $description = normalize_string($item['description'] ?? null, 'Item');
        $quantity = sanitize_float($item['quantity'] ?? 0, 0);
        $unitPrice = sanitize_float($item['unit_price'] ?? ($item['unitPrice'] ?? 0), 0);
        $lineTax = sanitize_float($item['tax'] ?? ($item['tax_rate'] ?? 0), 0);
        $lineDiscount = sanitize_float($item['discount'] ?? 0, 0);

        if ($quantity <= 0 || $unitPrice <= 0) {
            $errors[$index] = 'Item quantity and unit price must be greater than zero.';
            continue;
        }

        $lineSubtotal = round($quantity * $unitPrice, 2);
        $lineTotal = round($lineSubtotal + $lineTax - $lineDiscount, 2);

        $subtotal += $lineSubtotal;
        $taxTotal += $lineTax;
        $discountTotal += $lineDiscount;

        $normalized[] = [
            'product_id' => $productId,
            'description' => $description,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax' => $lineTax,
            'discount' => $lineDiscount,
            'line_subtotal' => $lineSubtotal,
            'line_total' => $lineTotal,
        ];
    }

    return [$normalized, round($subtotal, 2), round($taxTotal, 2), round($discountTotal, 2), $errors];
}

function sanitize_float($value, float $fallback): float
{
    $number = filter_var($value, FILTER_VALIDATE_FLOAT);
    return $number !== false ? round((float)$number, 2) : $fallback;
}

function normalize_string($value, ?string $fallback = null): ?string
{
    if ($value === null) {
        return $fallback;
    }

    $trimmed = trim((string)$value);
    return $trimmed !== '' ? $trimmed : $fallback;
}

function build_timestamps(?string $createdAt = null): array
{
    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

    return [
        'created_at' => $createdAt !== null ? $createdAt : $now,
        'updated_at' => $now,
        'deleted_at' => $now,
    ];
}

function calculate_invoice_totals(array $items, float $shippingTotal, float $additionalDiscount, float $itemDiscountTotal): array
{
    $subtotal = 0.0;
    $taxTotal = 0.0;
    $discountTotal = $itemDiscountTotal;

    foreach ($items as $item) {
        $subtotal += $item['line_subtotal'];
        $taxTotal += $item['tax'];
    }

    $subtotal = round($subtotal, 2);
    $taxTotal = round($taxTotal, 2);
    $shippingTotal = round($shippingTotal, 2);
    $additionalDiscount = round($additionalDiscount, 2);
    $discountTotal = round($discountTotal + $additionalDiscount, 2);

    $grandTotal = round($subtotal + $taxTotal + $shippingTotal - $discountTotal, 2);

    return [
        'subtotal' => $subtotal,
        'tax_total' => $taxTotal,
        'shipping_total' => $shippingTotal,
        'discount_total' => $discountTotal,
        'grand_total' => $grandTotal,
    ];
}

function fetch_customer_profile(mysqli $connection, int $customerId): ?array
{
    $statement = $connection->prepare(
        'SELECT id, first_name, last_name, email FROM customers WHERE id = ? LIMIT 1'
    );

    $statement->bind_param('i', $customerId);
    $statement->execute();
    $result = $statement->get_result();

    return $result->num_rows === 0 ? null : $result->fetch_assoc();
}

function invoice_number_exists(mysqli $connection, string $invoiceNumber, ?int $excludeId = null): bool
{
    $query = 'SELECT id FROM invoices WHERE invoice_number = ?';

    if ($excludeId !== null) {
        $query .= ' AND id <> ?';
    }

    $query .= ' LIMIT 1';

    $statement = $connection->prepare($query);

    if ($excludeId !== null) {
        $statement->bind_param('si', $invoiceNumber, $excludeId);
    } else {
        $statement->bind_param('s', $invoiceNumber);
    }

    $statement->execute();
    $result = $statement->get_result();

    return $result->num_rows > 0;
}

function tracking_number_exists(mysqli $connection, string $trackingNumber, ?int $excludeId = null): bool
{
    if ($trackingNumber === '') {
        return false;
    }

    $query = 'SELECT id FROM invoices WHERE tracking_number = ?';

    if ($excludeId !== null) {
        $query .= ' AND id <> ?';
    }

    $query .= ' LIMIT 1';

    $statement = $connection->prepare($query);

    if ($excludeId !== null) {
        $statement->bind_param('si', $trackingNumber, $excludeId);
    } else {
        $statement->bind_param('s', $trackingNumber);
    }

    $statement->execute();
    $result = $statement->get_result();

    return $result->num_rows > 0;
}

function invoice_uid_exists(mysqli $connection, string $uid): bool
{
    $statement = $connection->prepare('SELECT id FROM invoices WHERE uid = ? LIMIT 1');
    $statement->bind_param('s', $uid);
    $statement->execute();
    $result = $statement->get_result();

    return $result->num_rows > 0;
}

function generate_invoice_number(mysqli $connection): string
{
    $prefix = 'INV-' . date('Ym') . '-';
    $counter = 1;

    do {
        $candidate = $prefix . str_pad((string)$counter, 4, '0', STR_PAD_LEFT);
        $counter++;
    } while (invoice_number_exists($connection, $candidate));

    return $candidate;
}

function generate_tracking_number(mysqli $connection, ?int $excludeId = null): string
{
    do {
        $candidate = 'TRK-' . strtoupper(bin2hex(random_bytes(4)));
    } while (tracking_number_exists($connection, $candidate, $excludeId));

    return $candidate;
}

function generate_uuid_v4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function normalize_date_only_value($value): ?string
{
    if ($value === null) {
        return null;
    }

    $trimmed = trim((string)$value);

    if ($trimmed === '') {
        return null;
    }

    $formats = ['Y-m-d', 'Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'd/m/Y', 'm/d/Y'];

    foreach ($formats as $format) {
        $dateTime = DateTime::createFromFormat($format, $trimmed);

        if ($dateTime instanceof DateTime) {
            return $dateTime->format('Y-m-d');
        }
    }

    $timestamp = strtotime($trimmed);

    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }

    return null;
}

function normalize_datetime_value($value): ?string
{
    if ($value === null) {
        return null;
    }

    $trimmed = trim((string)$value);

    if ($trimmed === '') {
        return null;
    }

    $formats = [
        'Y-m-d H:i:s',
        'Y-m-d\TH:i:sP',
        DATE_ATOM,
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i',
    ];

    foreach ($formats as $format) {
        $dateTime = DateTime::createFromFormat($format, $trimmed);

        if ($dateTime instanceof DateTime) {
            return $dateTime->format('Y-m-d H:i:s');
        }
    }

    $timestamp = strtotime($trimmed);

    if ($timestamp !== false) {
        return date('Y-m-d H:i:s', $timestamp);
    }

    return null;
}

function resolve_boolean_flag($value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_numeric($value)) {
        return ((int)$value) === 1;
    }

    $value = strtolower(trim((string)$value));

    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function calculateSubtotal(array $items): float
{
    $subtotal = 0.0;

    foreach ($items as $item) {
        $subtotal += sanitize_float($item['quantity'] ?? 0, 0) * sanitize_float($item['unit_price'] ?? 0, 0);
    }

    return round($subtotal, 2);
}

function calculateTax(array $items): float
{
    $taxTotal = 0.0;

    foreach ($items as $item) {
        $taxTotal += sanitize_float($item['tax'] ?? ($item['tax_rate'] ?? 0), 0);
    }

    return round($taxTotal, 2);
}

function createInvoice(array $payload): array
{
    ob_start();
    handle_post();
    $output = ob_get_clean();

    return json_decode($output, true) ?? ['success' => false];
}

function updateInvoice(array $payload): array
{
    $_SERVER['REQUEST_METHOD'] = 'PUT';
    $stream = fopen('php://memory', 'rw');
    fwrite($stream, json_encode($payload));
    rewind($stream);
    $GLOBALS['__INVOICE_STREAM'] = $stream;

    ob_start();
    handle_put();
    $output = ob_get_clean();

    fclose($stream);
    unset($GLOBALS['__INVOICE_STREAM']);

    return json_decode($output, true) ?? ['success' => false];
}

function deleteInvoice(array $payload): array
{
    $_SERVER['REQUEST_METHOD'] = 'DELETE';
    $stream = fopen('php://memory', 'rw');
    fwrite($stream, json_encode($payload));
    rewind($stream);
    $GLOBALS['__INVOICE_STREAM'] = $stream;

    ob_start();
    handle_delete();
    $output = ob_get_clean();

    fclose($stream);
    unset($GLOBALS['__INVOICE_STREAM']);

    return json_decode($output, true) ?? ['success' => false];
}
