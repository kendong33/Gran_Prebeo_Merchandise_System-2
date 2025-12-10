<?php
require_once __DIR__ . '/db.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
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
                'message' => 'Method not allowed.'
            ]);
    }
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unexpected server error.',
        'detail' => $exception->getMessage()
    ]);
}

function handle_get(): void
{
    $connection = get_db_connection();
    ensure_orders_archive_columns($connection);

    $search = trim((string)($_GET['search'] ?? ''));
    $status = strtolower(trim((string)($_GET['status'] ?? '')));
    $startDate = trim((string)($_GET['start_date'] ?? ''));
    $endDate = trim((string)($_GET['end_date'] ?? ''));
    $archived = (int)($_GET['archived'] ?? 0);
    $archived = $archived === 1 ? 1 : 0;

    if ($status !== '' && !in_array($status, ['pending', 'processing', 'completed', 'cancelled'], true)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid status filter.'
        ]);
        return;
    }

    $conditions = [];
    $types = '';
    $parameters = [];

    if ($search !== '') {
        if (ctype_digit($search)) {
            $conditions[] = 'orders.id = ?';
            $parameters[] = (int)$search;
            $types .= 'i';
        } else {
            $like = "%{$search}%";
            $conditions[] = '(
                customers.first_name LIKE ? OR
                customers.last_name LIKE ? OR
                CONCAT(customers.first_name, " ", customers.last_name) LIKE ? OR
                customers.email LIKE ?
            )';
            $parameters[] = $like;
            $parameters[] = $like;
            $parameters[] = $like;
            $parameters[] = $like;
            $types .= 'ssss';
        }
    }

    if ($status !== '') {
        $conditions[] = 'orders.status = ?';
        $parameters[] = $status;
        $types .= 's';
    }

    if ($startDate !== '') {
        $normalizedStart = normalize_date_filter($startDate, 'start');

        if ($normalizedStart === null) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid start date.'
            ]);
            return;
        }

        $conditions[] = 'orders.order_date >= ?';
        $parameters[] = $normalizedStart;
        $types .= 's';
    }

    if ($endDate !== '') {
        $normalizedEnd = normalize_date_filter($endDate, 'end');

        if ($normalizedEnd === null) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid end date.'
            ]);
            return;
        }

        $conditions[] = 'orders.order_date <= ?';
        $parameters[] = $normalizedEnd;
        $types .= 's';
    }

    // Archived filter (default to active orders only)
    $conditions[] = 'orders.archived = ?';
    $parameters[] = $archived;
    $types .= 'i';

    $query = 'SELECT
            orders.id,
            orders.customer_id,
            orders.order_date,
            orders.status,
            orders.total_amount,
            orders.delivery_address,
            orders.notes,
            orders.created_at,
            orders.updated_at,
            customers.first_name,
            customers.last_name,
            customers.email
        FROM orders
        INNER JOIN customers ON customers.id = orders.customer_id';

    if ($conditions !== []) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $query .= ' ORDER BY orders.order_date DESC, orders.id DESC';

    $statement = $connection->prepare($query);

    if ($parameters !== []) {
        $statement->bind_param($types, ...$parameters);
    }

    $statement->execute();
    $result = $statement->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $orders
    ]);
}

function handle_post(): void
{
    $incoming = resolve_request_payload();

    $action = strtolower(trim((string)($incoming['action'] ?? ($_GET['action'] ?? ''))));

    if ($action === 'unarchive') {
        $connection = get_db_connection();
        ensure_orders_archive_columns($connection);

        $rawId = $incoming['id'] ?? ($_GET['id'] ?? null);
        $orderId = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($orderId === false) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Order id is required to unarchive.'
            ]);
            return;
        }

        $statement = $connection->prepare('UPDATE orders SET archived = 0, archived_at = NULL WHERE id = ?');
        $statement->bind_param('i', $orderId);
        $statement->execute();

        if ($statement->affected_rows === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Order not found.'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Order unarchived.'
        ]);
        return;
    }

    if ($action === 'make_invoice') {
        $connection = get_db_connection();

        $rawId = $incoming['id'] ?? ($_GET['id'] ?? null);
        $orderId = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($orderId === false) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Order id is required to create an invoice.'
            ]);
            return;
        }

        $order = fetch_order_by_id($connection, (int)$orderId);

        if ($order === null) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Order not found.'
            ]);
            return;
        }

        // Guard: do not create another invoice if one already exists for this order
        $existingInvoiceId = orders_find_invoice_id_by_order($connection, (int)$orderId);
        if ($existingInvoiceId !== null) {
            $existingInvoice = orders_fetch_invoice_by_id($connection, $existingInvoiceId);
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'An invoice already exists for this order.',
                'invoice' => $existingInvoice,
            ]);
            return;
        }

        try {
            $invoice = create_invoice_for_order($connection, $order);

            echo json_encode([
                'success' => true,
                'invoice' => $invoice,
                'message' => 'Invoice created from order.'
            ]);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create invoice.',
                'detail' => $exception->getMessage(),
            ]);
        }

        return;
    }

    [$fields, $errors] = prepare_order_fields($incoming);

    if ($errors !== []) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => $errors,
            'message' => 'Validation failed.'
        ]);
        return;
    }

    $connection = get_db_connection();

    if (!customer_exists($connection, $fields['customer_id'])) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => ['customer_id' => 'Selected customer does not exist.'],
            'message' => 'Validation failed.'
        ]);
        return;
    }

    $statement = $connection->prepare(
        // Statement will be replaced below if a legacy 'uid' column is present
        'INSERT INTO orders (customer_id, order_date, status, total_amount, delivery_address, notes)
        VALUES (?, ?, ?, ?, ?, ?)'
    );

    // Some legacy databases include a NOT NULL 'uid' column without default in the orders table.
    // If present, include it in the INSERT with a generated UUID to prevent SQL errors.
    if (orders_table_has_column($connection, 'uid')) {
        $statement->close();
        $uid = orders_generate_uuid_v4();
        $statement = $connection->prepare(
            'INSERT INTO orders (uid, customer_id, order_date, status, total_amount, delivery_address, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param(
            'sissdss',
            $uid,
            $fields['customer_id'],
            $fields['order_date'],
            $fields['status'],
            $fields['total_amount'],
            $fields['delivery_address'],
            $fields['notes']
        );
    } else {
        $statement->bind_param(
            'issdss',
            $fields['customer_id'],
            $fields['order_date'],
            $fields['status'],
            $fields['total_amount'],
            $fields['delivery_address'],
            $fields['notes']
        );
    }

    $statement->execute();

    $order = fetch_order_by_id($connection, (int)$connection->insert_id);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'data' => $order,
        'message' => 'Order created successfully.'
    ]);
}

function handle_put(): void
{
    $id = resolve_order_id();

    if ($id === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Order id is required.'
        ]);
        return;
    }

    [$fields, $errors] = prepare_order_fields(resolve_request_payload(), true);

    if ($errors !== []) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => $errors,
            'message' => 'Validation failed.'
        ]);
        return;
    }

    $connection = get_db_connection();

    if (!customer_exists($connection, $fields['customer_id'])) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => ['customer_id' => 'Selected customer does not exist.'],
            'message' => 'Validation failed.'
        ]);
        return;
    }

    $existing = fetch_order_by_id($connection, $id);

    if ($existing === null) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Order not found.'
        ]);
        return;
    }

    $statement = $connection->prepare(
        'UPDATE orders
        SET customer_id = ?, order_date = ?, status = ?, total_amount = ?, delivery_address = ?, notes = ?
        WHERE id = ?'
    );

    $statement->bind_param(
        'issdssi',
        $fields['customer_id'],
        $fields['order_date'],
        $fields['status'],
        $fields['total_amount'],
        $fields['delivery_address'],
        $fields['notes'],
        $id
    );

    $statement->execute();

    $order = fetch_order_by_id($connection, $id);

    echo json_encode([
        'success' => true,
        'data' => $order,
        'message' => 'Order updated successfully.'
    ]);
}

function handle_delete(): void
{
    $id = resolve_order_id();

    if ($id === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Order id is required.'
        ]);
        return;
    }

    $connection = get_db_connection();
    ensure_orders_archive_columns($connection);

    $statement = $connection->prepare('UPDATE orders SET archived = 1, archived_at = NOW() WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();

    if ($statement->affected_rows === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Order not found.'
        ]);
        return;
    }

    http_response_code(204);
}

function prepare_order_fields(array $payload, bool $isUpdate = false): array
{
    $customerId = $payload['customer_id'] ?? null;
    $orderDate = $payload['order_date'] ?? '';
    $status = strtolower(trim((string)($payload['status'] ?? 'pending')));
    $totalAmount = $payload['total_amount'] ?? 0;
    $notes = trim((string)($payload['notes'] ?? ''));
    $deliveryAddress = trim((string)($payload['delivery_address'] ?? ''));

    $errors = [];

    $customerIdValue = filter_var($customerId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($customerIdValue === false) {
        $errors['customer_id'] = 'Customer is required.';
    }

    $normalizedOrderDate = normalize_order_date($orderDate);

    if ($normalizedOrderDate === null) {
        $errors['order_date'] = 'Order date is invalid.';
    }

    if (!in_array($status, ['pending', 'processing', 'completed', 'cancelled'], true)) {
        $errors['status'] = 'Status must be pending, processing, completed, or cancelled.';
    }

    if (!is_numeric($totalAmount)) {
        $errors['total_amount'] = 'Total amount must be numeric.';
    }

    $totalAmountValue = (float)$totalAmount;

    if ($totalAmountValue < 0) {
        $errors['total_amount'] = 'Total amount cannot be negative.';
    }

    $fields = [
        'customer_id' => $customerIdValue === false ? 0 : $customerIdValue,
        'order_date' => $normalizedOrderDate ?? date('Y-m-d H:i:s'),
        'status' => $status,
        'total_amount' => round($totalAmountValue, 2),
        'delivery_address' => $deliveryAddress !== '' ? $deliveryAddress : null,
        'notes' => $notes !== '' ? $notes : null,
    ];

    return [$fields, $errors];
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
        $payload = json_decode(file_get_contents('php://input'), true);
        if (is_array($payload)) {
            return $payload;
        }

        return $_POST;
    }

    parse_str(file_get_contents('php://input'), $data);

    if (!is_array($data)) {
        return [];
    }

    return $data;
}

function resolve_order_id(): ?int
{
    $id = $_GET['id'] ?? null;

    if ($id === null) {
        $payload = resolve_request_payload();
        $id = $payload['id'] ?? null;
    }

    if ($id === null) {
        return null;
    }

    $intId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $intId === false ? null : $intId;
}

function fetch_order_by_id(mysqli $connection, int $id): ?array
{
    $statement = $connection->prepare(
        'SELECT
            orders.id,
            orders.customer_id,
            orders.order_date,
            orders.status,
            orders.total_amount,
            orders.delivery_address,
            orders.notes,
            orders.created_at,
            orders.updated_at,
            customers.first_name,
            customers.last_name,
            customers.email
        FROM orders
        INNER JOIN customers ON customers.id = orders.customer_id
        WHERE orders.id = ?'
    );
    $statement->bind_param('i', $id);
    $statement->execute();
    $result = $statement->get_result();

    return $result->num_rows === 0 ? null : $result->fetch_assoc();
}

function customer_exists(mysqli $connection, int $customerId): bool
{
    $statement = $connection->prepare('SELECT id FROM customers WHERE id = ?');
    $statement->bind_param('i', $customerId);
    $statement->execute();
    $result = $statement->get_result();

    return $result->num_rows > 0;
}

function create_invoice_for_order(mysqli $connection, array $order): array
{
    $customer = fetch_customer_profile_for_invoice($connection, (int)$order['customer_id']);

    if ($customer === null) {
        throw new RuntimeException('Unable to locate customer for invoice generation.');
    }

    $invoiceNumber = orders_generate_invoice_number($connection);
    $trackingNumber = orders_generate_tracking_number($connection);
    $uid = orders_generate_uuid_v4();

    while (orders_invoice_uid_exists($connection, $uid)) {
        $uid = orders_generate_uuid_v4();
    }

    $issueDate = !empty($order['order_date'])
        ? (new DateTimeImmutable($order['order_date']))->format('Y-m-d')
        : (new DateTimeImmutable())->format('Y-m-d');

    $dueDate = (new DateTimeImmutable($issueDate))->modify('+7 days')->format('Y-m-d');

    $statusMap = [
        'pending' => 'pending',
        'processing' => 'sent',
        'completed' => 'paid',
        'cancelled' => 'cancelled',
    ];

    $status = $statusMap[strtolower((string)($order['status'] ?? 'pending'))] ?? 'pending';
    $nextStatus = $status;

    $items = [
        [
            'product_id' => null,
            'description' => 'Order #' . $order['id'],
            'quantity' => 1,
            'unit_price' => (float)$order['total_amount'],
            'tax' => 0.0,
            'discount' => 0.0,
            'line_subtotal' => (float)$order['total_amount'],
            'line_total' => (float)$order['total_amount'],
        ],
    ];

    $itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE);

    if ($itemsJson === false) {
        throw new RuntimeException('Failed to encode invoice items.');
    }

    $grandTotal = round((float)$order['total_amount'], 2);
    $timestamps = orders_build_timestamps();

    $customerId = (int)$customer['id'];
    $orderIdString = (string)$order['id'];
    $customerName = trim((string)$customer['name']);

    if ($customerName === '' && isset($order['first_name'], $order['last_name'])) {
        $customerName = trim((string)$order['first_name'] . ' ' . (string)$order['last_name']);
    }

    if ($customerName === '') {
        $customerName = 'Customer #' . $customerId;
    }

    $customerEmail = (string)($customer['email'] ?? '');
    $billingStreet = trim((string)($order['delivery_address'] ?? '')) !== ''
        ? trim((string)$order['delivery_address'])
        : (string)($customer['street'] ?? 'Not provided');
    $billingCity = (string)($customer['city'] ?? 'Not provided');
    $billingState = (string)($customer['state'] ?? 'Not provided');
    $billingPostal = (string)($customer['postal_code'] ?? '0000');
    $billingCountry = (string)($customer['country'] ?? 'Philippines');

    $currency = 'PHP';
    $subtotal = $grandTotal;
    $taxTotal = 0.0;
    $shippingTotal = 0.0;
    $discountTotal = 0.0;

    $paymentMethod = null;
    $paymentDate = $status === 'paid'
        ? (new DateTimeImmutable($order['order_date'] ?? 'now'))->format('Y-m-d H:i:s')
        : null;

    $issueDateString = $issueDate;
    $dueDateString = $dueDate;

    $notes = isset($order['notes']) && $order['notes'] !== null ? trim((string)$order['notes']) : null;
    $terms = null;
    $createdAt = $timestamps['created_at'];
    $updatedAt = $timestamps['updated_at'];

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
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )'
    );

    $types = 'sss' . 'i' . str_repeat('s', 9) . str_repeat('d', 5) . str_repeat('s', 11);
    $statement->bind_param(
        $types,
        $uid,
        $invoiceNumber,
        $orderIdString,
        $customerId,
        $customerName,
        $customerEmail,
        $billingStreet,
        $billingCity,
        $billingState,
        $billingPostal,
        $billingCountry,
        $itemsJson,
        $currency,
        $subtotal,
        $taxTotal,
        $shippingTotal,
        $discountTotal,
        $grandTotal,
        $status,
        $nextStatus,
        $paymentMethod,
        $paymentDate,
        $issueDateString,
        $dueDateString,
        $notes,
        $terms,
        $trackingNumber,
        $createdAt,
        $updatedAt
    );

    $statement->execute();

    if ($statement->error) {
        throw new RuntimeException('Failed to create invoice: ' . $statement->error);
    }

    $invoice = orders_fetch_invoice_by_id($connection, (int)$connection->insert_id);

    if ($invoice === null) {
        throw new RuntimeException('Failed to load generated invoice.');
    }

    $invoice['items'] = $items;

    return $invoice;
}

function fetch_customer_profile_for_invoice(mysqli $connection, int $customerId): ?array
{
    $statement = $connection->prepare(
        'SELECT id, first_name, last_name, email, address FROM customers WHERE id = ? LIMIT 1'
    );

    $statement->bind_param('i', $customerId);
    $statement->execute();
    $result = $statement->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    $row = $result->fetch_assoc();

    return [
        'id' => (int)$row['id'],
        'name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
        'email' => (string)($row['email'] ?? ''),
        'street' => trim((string)($row['address'] ?? '')),
        'city' => '',
        'state' => '',
        'postal_code' => '',
        'country' => 'Philippines',
    ];
}

function orders_generate_invoice_number(mysqli $connection): string
{
    $prefix = 'INV-' . date('Ym') . '-';
    $counter = 1;

    do {
        $candidate = $prefix . str_pad((string)$counter, 4, '0', STR_PAD_LEFT);
        $counter++;
    } while (orders_invoice_number_exists($connection, $candidate));

    return $candidate;
}

function orders_invoice_number_exists(mysqli $connection, string $invoiceNumber): bool
{
    $statement = $connection->prepare('SELECT id FROM invoices WHERE invoice_number = ? LIMIT 1');
    $statement->bind_param('s', $invoiceNumber);
    $statement->execute();
    $result = $statement->get_result();

    return $result->num_rows > 0;
}

function orders_generate_tracking_number(mysqli $connection): string
{
    do {
        $candidate = 'TRK-' . strtoupper(bin2hex(random_bytes(4)));
    } while (orders_tracking_number_exists($connection, $candidate));

    return $candidate;
}

function orders_tracking_number_exists(mysqli $connection, string $trackingNumber): bool
{
    $statement = $connection->prepare('SELECT id FROM invoices WHERE tracking_number = ? LIMIT 1');
    $statement->bind_param('s', $trackingNumber);
    $statement->execute();
    $result = $statement->get_result();

    return $result->num_rows > 0;
}

function orders_find_invoice_id_by_order(mysqli $connection, int $orderId): ?int
{
    $orderIdString = (string)$orderId;
    $statement = $connection->prepare('SELECT id FROM invoices WHERE order_id = ? AND deleted = 0 LIMIT 1');
    $statement->bind_param('s', $orderIdString);
    $statement->execute();
    $result = $statement->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    $row = $result->fetch_assoc();
    return (int)$row['id'];
}

function orders_invoice_uid_exists(mysqli $connection, string $uid): bool
{
    $statement = $connection->prepare('SELECT id FROM invoices WHERE uid = ? LIMIT 1');
    $statement->bind_param('s', $uid);
    $statement->execute();
    $result = $statement->get_result();

    return $result->num_rows > 0;
}

function orders_generate_uuid_v4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function orders_build_timestamps(): array
{
    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

    return [
        'created_at' => $now,
        'updated_at' => $now,
    ];
}

function orders_fetch_invoice_by_id(mysqli $connection, int $id): ?array
{
    $statement = $connection->prepare(
        'SELECT invoices.*, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name
        FROM invoices
        LEFT JOIN customers ON customers.id = invoices.customer_id
        WHERE invoices.id = ? LIMIT 1'
    );

    $statement->bind_param('i', $id);
    $statement->execute();
    $result = $statement->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    return orders_map_invoice_row($result->fetch_assoc());
}

function orders_map_invoice_row(array $row): array
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

function normalize_order_date(string $value): ?string
{
    $trimmed = trim($value);

    if ($trimmed === '') {
        return date('Y-m-d H:i:s');
    }

    // Accept common browser/localized formats
    $formats = [
        'Y-m-d\TH:i',       // datetime-local
        'Y-m-d H:i:s',      // explicit timestamp
        'Y-m-d',            // date only
        'm/d/Y h:i A',      // 12/10/2025 03:33 AM
        'm/d/Y h:i a',      // 12/10/2025 03:33 am
        'd/m/Y h:i A',      // 10/12/2025 03:33 AM
        'd/m/Y h:i a',      // 10/12/2025 03:33 am
    ];

    foreach ($formats as $format) {
        $dateTime = DateTime::createFromFormat($format, $trimmed);

        if ($dateTime instanceof DateTime) {
            return $dateTime->format('Y-m-d H:i:s');
        }
    }

    // Final fallback: strtotime on free-form strings
    $ts = strtotime($trimmed);
    if ($ts !== false) {
        return date('Y-m-d H:i:s', $ts);
    }

    return null;
}

function normalize_date_filter(string $value, string $bound): ?string
{
    $dateTime = DateTime::createFromFormat('Y-m-d', $value);

    if ($dateTime === false) {
        return null;
    }

    if ($bound === 'start') {
        $dateTime->setTime(0, 0, 0);
    } else {
        $dateTime->setTime(23, 59, 59);
    }

    return $dateTime->format('Y-m-d H:i:s');
}

function orders_table_has_column(mysqli $connection, string $column): bool
{
    $columnEscaped = $connection->real_escape_string($column);
    $result = $connection->query("SHOW COLUMNS FROM orders LIKE '{$columnEscaped}'");
    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function ensure_orders_archive_columns(mysqli $connection): void
{
    if (!orders_table_has_column($connection, 'archived')) {
        $connection->query('ALTER TABLE orders ADD COLUMN archived TINYINT(1) NOT NULL DEFAULT 0');
    }

    if (!orders_table_has_column($connection, 'archived_at')) {
        $connection->query('ALTER TABLE orders ADD COLUMN archived_at DATETIME NULL');
    }
}
