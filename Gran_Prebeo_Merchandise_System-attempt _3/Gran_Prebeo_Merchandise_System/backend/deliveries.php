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

// Helpers for legacy schema compatibility
function deliveries_table_has_column(mysqli $connection, string $column): bool
{
    $columnEscaped = $connection->real_escape_string($column);
    $result = $connection->query("SHOW COLUMNS FROM deliveries LIKE '{$columnEscaped}'");
    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function deliveries_generate_uuid_v4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function handle_put(): void {
    $payload = resolve_request_payload();
    $id = filter_var($payload['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $status = normalize_status($payload['status'] ?? '');

    if ($id === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Delivery id is required.']);
        return;
    }

    if (!in_array($status, ['pending', 'shipped', 'completed'], true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
        return;
    }

    $connection = get_db_connection();

    $existing = fetch_delivery_by_id($connection, (int)$id);
    if ($existing === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Delivery not found.']);
        return;
    }

    $timestamps = build_timestamps_simple();
    $statement = $connection->prepare('UPDATE deliveries SET status = ?, updated_at = ? WHERE id = ?');
    $statement->bind_param('ssi', $status, $timestamps['updated_at'], $id);
    $statement->execute();

    // If marked delivered, also mark invoice paid and order completed
    if ($status === 'completed') {
        if (isset($existing['invoice_id'])) {
            mark_invoice_paid($connection, (int)$existing['invoice_id']);
        }
        $orderIdRaw = (string)($existing['order_id'] ?? '');
        if ($orderIdRaw !== '' && ctype_digit($orderIdRaw)) {
            mark_order_completed($connection, (int)$orderIdRaw);
        }
    }

    $updated = fetch_delivery_by_id($connection, (int)$id);

    echo json_encode([
        'success' => true,
        'data' => $updated,
        'message' => 'Delivery status updated.'
    ]);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handle_get();
            break;
        case 'POST':
            handle_post();
            break;
        case 'PUT':
            handle_put();
            break;
        default:
            http_response_code(405);
            echo json_encode([ 'success' => false, 'message' => 'Method not allowed.' ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unexpected server error.',
        'detail' => $e->getMessage(),
    ]);
}

function handle_get(): void {
    $connection = get_db_connection();

    if (isset($_GET['available_invoices'])) {
        $invoices = fetch_available_invoices($connection);
        echo json_encode(['success' => true, 'data' => $invoices]);
        return;
    }

    $rows = fetch_deliveries_with_filters($connection);
    echo json_encode(['success' => true, 'data' => $rows]);
}

function handle_post(): void {
    $payload = resolve_request_payload();

    $invoiceId = filter_var($payload['invoice_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $deliveryDate = normalize_date_only_value($payload['delivery_date'] ?? null);
    $status = normalize_status($payload['status'] ?? 'pending');
    $courier = normalize_string($payload['courier'] ?? null);
    $notes = normalize_string($payload['notes'] ?? null);

    $errors = [];

    if ($invoiceId === false) {
        $errors['invoice_id'] = 'Invoice is required.';
    }

    if ($deliveryDate === null) {
        $errors['delivery_date'] = 'Delivery date is required (YYYY-MM-DD).';
    }

    if (!in_array($status, ['pending', 'shipped', 'completed'], true)) {
        $errors['status'] = 'Status must be pending, shipped, or completed.';
    }

    if ($courier === null || $courier === '') {
        $errors['courier'] = 'Courier is required.';
    }

    if ($errors !== []) {
        http_response_code(422);
        echo json_encode(['success' => false, 'errors' => $errors, 'message' => 'Validation failed.']);
        return;
    }

    $connection = get_db_connection();

    // Ensure the invoice exists and is not already linked
    $invoice = fetch_invoice_brief($connection, (int)$invoiceId);

    if ($invoice === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Invoice not found.']);
        return;
    }

    if (delivery_exists_for_invoice($connection, (int)$invoiceId)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'A delivery already exists for this invoice.']);
        return;
    }

    $timestamps = build_timestamps_simple();
    $deliveryTrackingId = generate_delivery_tracking_id($connection);

    $statement = $connection->prepare(
        'INSERT INTO deliveries (
            invoice_id,
            order_id,
            invoice_number,
            tracking_number,
            delivery_tracking_id,
            customer_id,
            customer_name,
            address,
            courier,
            status,
            delivery_date,
            notes,
            created_at,
            updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )'
    );

    $address = trim(($invoice['billing_street'] ?? '') . ', ' . ($invoice['billing_city'] ?? '') . ', ' . ($invoice['billing_state'] ?? '') . ' ' . ($invoice['billing_postal_code'] ?? '') . ', ' . ($invoice['billing_country'] ?? ''));

    // Bind using variables (mysqli requires references)
    $p_invoice_id = (int)$invoice['id'];
    $p_order_id = (string)($invoice['order_id'] ?? '');
    $p_invoice_number = (string)($invoice['invoice_number'] ?? '');
    $p_tracking_number = (string)($invoice['tracking_number'] ?? '');
    $p_delivery_tracking_id = (string)$deliveryTrackingId;
    $p_customer_id = (int)$invoice['customer_id'];
    $p_customer_name = (string)($invoice['customer_name'] ?? '');
    $p_address = $address;
    $p_courier = (string)$courier;
    $p_status = (string)$status;
    $p_delivery_date = (string)$deliveryDate;
    $p_notes = $notes !== null ? (string)$notes : null;
    $p_created_at = $timestamps['created_at'];
    $p_updated_at = $timestamps['updated_at'];

    // If legacy schema has a NOT NULL 'uid' column, include it in the insert
    if (deliveries_table_has_column($connection, 'uid')) {
        $statement->close();
        $uid = deliveries_generate_uuid_v4();
        $statement = $connection->prepare(
            'INSERT INTO deliveries (
                uid,
                invoice_id,
                order_id,
                invoice_number,
                tracking_number,
                delivery_tracking_id,
                customer_id,
                customer_name,
                address,
                courier,
                status,
                delivery_date,
                notes,
                created_at,
                updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )'
        );
        $statement->bind_param(
            'sissssissssssss',
            $uid,
            $p_invoice_id,
            $p_order_id,
            $p_invoice_number,
            $p_tracking_number,
            $p_delivery_tracking_id,
            $p_customer_id,
            $p_customer_name,
            $p_address,
            $p_courier,
            $p_status,
            $p_delivery_date,
            $p_notes,
            $p_created_at,
            $p_updated_at
        );
    } else {
        $statement->bind_param(
            'issssissssssss',
            $p_invoice_id,
            $p_order_id,
            $p_invoice_number,
            $p_tracking_number,
            $p_delivery_tracking_id,
            $p_customer_id,
            $p_customer_name,
            $p_address,
            $p_courier,
            $p_status,
            $p_delivery_date,
            $p_notes,
            $p_created_at,
            $p_updated_at
        );
    }

    $statement->execute();

    if ($statement->error) {
        throw new RuntimeException('Failed to create delivery: ' . $statement->error);
    }

    // Apply status side-effects when initially created as completed
    $newId = (int)$connection->insert_id;
    if ($status === 'completed') {
        if (isset($invoice['id'])) {
            mark_invoice_paid($connection, (int)$invoice['id']);
        }
        $orderIdRaw = (string)($invoice['order_id'] ?? '');
        if ($orderIdRaw !== '' && ctype_digit($orderIdRaw)) {
            mark_order_completed($connection, (int)$orderIdRaw);
        }
    }

    $created = fetch_delivery_by_id($connection, $newId);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'data' => $created,
        'message' => 'Delivery created successfully.'
    ]);
}

function resolve_request_payload(): array {
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
    return is_array($data) ? $data : [];
}

function normalize_string($value, ?string $fallback = null): ?string {
    if ($value === null) return $fallback;
    $trimmed = trim((string)$value);
    return $trimmed !== '' ? $trimmed : $fallback;
}

function normalize_date_only_value($value): ?string {
    if ($value === null) return null;
    $t = strtotime((string)$value);
    if ($t === false) return null;
    return date('Y-m-d', $t);
}

function normalize_status($value): string {
    $s = strtolower(trim((string)$value));
    return in_array($s, ['pending', 'shipped', 'completed'], true) ? $s : 'pending';
}

function fetch_available_invoices(mysqli $connection): array {
    $query = 'SELECT i.id, i.invoice_number, i.order_id, i.customer_id, i.customer_name, i.billing_street, i.billing_city, i.billing_state, i.billing_postal_code, i.billing_country, i.tracking_number
        FROM invoices i
        LEFT JOIN deliveries d ON d.invoice_id = i.id
        WHERE (d.id IS NULL) AND i.deleted = 0
        ORDER BY i.created_at DESC
        LIMIT 200';

    $result = $connection->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_deliveries_with_filters(mysqli $connection): array {
    $query = 'SELECT d.*, i.invoice_number, i.order_id, i.customer_id AS invoice_customer_id
        FROM deliveries d
        LEFT JOIN invoices i ON i.id = d.invoice_id';

    $conditions = [];
    $parameters = [];
    $types = '';

    // Search across tracking IDs, invoice number, and customer name
    $search = trim((string)($_GET['search'] ?? ''));
    if ($search !== '') {
        $conditions[] = '(
            d.delivery_tracking_id LIKE ? OR
            d.tracking_number LIKE ? OR
            i.invoice_number LIKE ? OR
            d.customer_name LIKE ?
        )';
        $like = "%{$search}%";
        $parameters[] = $like;
        $parameters[] = $like;
        $parameters[] = $like;
        $parameters[] = $like;
        $types .= 'ssss';
    }

    // Status filter
    $status = strtolower(trim((string)($_GET['status'] ?? '')));
    if (in_array($status, ['pending', 'shipped', 'completed'], true)) {
        $conditions[] = 'd.status = ?';
        $parameters[] = $status;
        $types .= 's';
    }

    // Schedule date filter (delivery_date exact match)
    $schedule = normalize_date_only_value($_GET['schedule_date'] ?? null);
    if ($schedule !== null) {
        $conditions[] = 'd.delivery_date = ?';
        $parameters[] = $schedule;
        $types .= 's';
    }

    if ($conditions !== []) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $query .= ' ORDER BY d.created_at DESC';

    $statement = $connection->prepare($query);
    if ($types !== '') {
        $statement->bind_param($types, ...$parameters);
    }
    $statement->execute();
    $result = $statement->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetch_delivery_by_id(mysqli $connection, int $id): ?array {
    $statement = $connection->prepare(
        'SELECT d.*, i.invoice_number, i.order_id, i.customer_id AS invoice_customer_id
         FROM deliveries d
         LEFT JOIN invoices i ON i.id = d.invoice_id
         WHERE d.id = ? LIMIT 1'
    );
    $statement->bind_param('i', $id);
    $statement->execute();
    $result = $statement->get_result();
    return $result->num_rows === 0 ? null : $result->fetch_assoc();
}

function fetch_invoice_brief(mysqli $connection, int $id): ?array {
    $statement = $connection->prepare(
        'SELECT id, invoice_number, order_id, customer_id, customer_name, billing_street, billing_city, billing_state, billing_postal_code, billing_country, tracking_number, deleted
         FROM invoices WHERE id = ? LIMIT 1'
    );
    $statement->bind_param('i', $id);
    $statement->execute();
    $result = $statement->get_result();
    if ($result->num_rows === 0) {
        return null;
    }
    $row = $result->fetch_assoc();
    if ((int)($row['deleted'] ?? 0) === 1) {
        return null;
    }
    return $row;
}

function delivery_exists_for_invoice(mysqli $connection, int $invoiceId): bool {
    $statement = $connection->prepare('SELECT id FROM deliveries WHERE invoice_id = ? LIMIT 1');
    $statement->bind_param('i', $invoiceId);
    $statement->execute();
    $result = $statement->get_result();
    return $result->num_rows > 0;
}

function build_timestamps_simple(): array {
    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    return ['created_at' => $now, 'updated_at' => $now];
}

function generate_delivery_tracking_id(mysqli $connection): string {
    do {
        $candidate = 'DLV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    } while (delivery_tracking_id_exists($connection, $candidate));
    return $candidate;
}

function delivery_tracking_id_exists(mysqli $connection, string $trackingId): bool {
    $stmt = $connection->prepare('SELECT id FROM deliveries WHERE delivery_tracking_id = ? LIMIT 1');
    $stmt->bind_param('s', $trackingId);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->num_rows > 0;
}

function mark_invoice_paid(mysqli $connection, int $invoiceId): void {
    // Set status to paid and payment_date if not already paid
    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    $statement = $connection->prepare(
        "UPDATE invoices SET status='paid', payment_date = COALESCE(payment_date, ?), updated_at = ? WHERE id = ? AND deleted = 0"
    );
    $statement->bind_param('ssi', $now, $now, $invoiceId);
    $statement->execute();
}

function mark_order_completed(mysqli $connection, int $orderId): void {
    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    $statement = $connection->prepare(
        "UPDATE orders SET status = 'completed', updated_at = ? WHERE id = ?"
    );
    $statement->bind_param('si', $now, $orderId);
    $statement->execute();
}
