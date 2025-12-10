<?php
require_once __DIR__ . '/db.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}
header('Content-Type: application/json');

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

    refresh_customer_statuses($connection);

    $search = trim((string)($_GET['search'] ?? ''));
    $status = strtolower(trim((string)($_GET['status'] ?? '')));

    if ($status !== '' && !in_array($status, ['new', 'active', 'inactive'], true)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid status filter.'
        ]);
        return;
    }

    $conditions = [];
    $parameters = [];
    $types = '';

    if ($search !== '') {
        $like = "%{$search}%";
        $conditions[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)';
        $parameters[] = $like;
        $parameters[] = $like;
        $parameters[] = $like;
        $types .= 'sss';
    }

    if ($status !== '') {
        $conditions[] = 'status = ?';
        $parameters[] = $status;
        $types .= 's';
    }

    $query = 'SELECT id, first_name, last_name, gender, birth_date, phone, email, address, status, created_at, updated_at, deleted, deleted_at, delete_reason FROM customers';

    // Switch between active vs deleted list based on query param
    $deletedFlagRaw = strtolower(trim((string)($_GET['deleted'] ?? '0')));
    $viewDeleted = in_array($deletedFlagRaw, ['1', 'true', 'yes'], true);
    $baseWhere = $viewDeleted ? 'deleted = 1' : 'deleted = 0';
    if ($conditions !== []) {
        $query .= ' WHERE ' . $baseWhere . ' AND ' . implode(' AND ', $conditions);
    } else {
        $query .= ' WHERE ' . $baseWhere;
    }

    $query .= ' ORDER BY created_at DESC';

    $statement = $connection->prepare($query);

    if ($parameters !== []) {
        $statement->bind_param($types, ...$parameters);
    }

    $statement->execute();
    $result = $statement->get_result();
    $customers = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $customers
    ]);
}

function handle_post(): void
{
    [$fields, $errors] = prepare_customer_fields(resolve_request_payload());

    if ($errors !== []) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => $errors,
            'message' => 'Validation failed.'
        ]);
        return;
    }

    try {
        $connection = get_db_connection();

        $statement = $connection->prepare(
            'INSERT INTO customers (first_name, last_name, gender, birth_date, phone, email, address, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $statement->bind_param(
            'ssssssss',
            $fields['first_name'],
            $fields['last_name'],
            $fields['gender'],
            $fields['birth_date'],
            $fields['phone'],
            $fields['email'],
            $fields['address'],
            $fields['status']
        );

        $statement->execute();

        $customer = fetch_customer_by_id($connection, (int)$connection->insert_id);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer created successfully.'
        ]);
    } catch (mysqli_sql_exception $exception) {
        if ((int)$exception->getCode() === 1062) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'A customer with this email already exists.'
            ]);
            return;
        }

        throw $exception;
    }
}

function handle_put(): void
{
    $id = resolve_customer_id();

    if ($id === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Customer id is required.'
        ]);
        return;
    }

    [$fields, $errors] = prepare_customer_fields(resolve_request_payload(), true);

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

    $existing = fetch_customer_by_id($connection, $id);

    if ($existing === null) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Customer not found.'
        ]);
        return;
    }

    try {
        $statement = $connection->prepare(
            'UPDATE customers
            SET first_name = ?, last_name = ?, gender = ?, birth_date = ?, phone = ?, email = ?, address = ?, status = ?
            WHERE id = ?'
        );

        $statement->bind_param(
            'ssssssssi',
            $fields['first_name'],
            $fields['last_name'],
            $fields['gender'],
            $fields['birth_date'],
            $fields['phone'],
            $fields['email'],
            $fields['address'],
            $fields['status'],
            $id
        );

        $statement->execute();

        $customer = fetch_customer_by_id($connection, $id);

        echo json_encode([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer updated successfully.'
        ]);
    } catch (mysqli_sql_exception $exception) {
        if ((int)$exception->getCode() === 1062) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'A customer with this email already exists.'
            ]);
            return;
        }

        throw $exception;
    }
}

function handle_delete(): void
{
    $id = resolve_customer_id();

    if ($id === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Customer id is required.'
        ]);
        return;
    }

    $payload = resolve_request_payload();
    $deleteReason = trim((string)($payload['delete_reason'] ?? ''));

    if ($deleteReason === '') {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Delete reason is required.'
        ]);
        return;
    }

    $connection = get_db_connection();

    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    $statement = $connection->prepare('UPDATE customers SET deleted = 1, deleted_at = ?, delete_reason = ? WHERE id = ?');
    $statement->bind_param('ssi', $now, $deleteReason, $id);
    $statement->execute();

    if ($statement->affected_rows === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Customer not found.'
        ]);
        return;
    }

    http_response_code(204);
}

function validate_date(string $date): bool
{
    $dateTime = DateTime::createFromFormat('Y-m-d', $date);

    return $dateTime !== false && $dateTime->format('Y-m-d') === $date;
}

function prepare_customer_fields(array $payload, bool $isUpdate = false): array
{
    $firstName = trim((string)($payload['first_name'] ?? ''));
    $lastName = trim((string)($payload['last_name'] ?? ''));
    $gender = strtolower((string)($payload['gender'] ?? ''));
    $birthDate = trim((string)($payload['birth_date'] ?? ''));
    $phone = trim((string)($payload['phone'] ?? ''));
    $email = trim((string)($payload['email'] ?? ''));
    $address = trim((string)($payload['address'] ?? ''));
    $status = strtolower(trim((string)($payload['status'] ?? 'new')));

    $errors = [];

    if ($firstName === '') {
        $errors['first_name'] = 'First name is required.';
    }

    if ($lastName === '') {
        $errors['last_name'] = 'Last name is required.';
    }

    if (!in_array($gender, ['male', 'female'], true)) {
        $errors['gender'] = 'Gender must be male or female.';
    }

    if ($birthDate !== '' && !validate_date($birthDate)) {
        $errors['birth_date'] = 'Birth date must be in YYYY-MM-DD format.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email is not valid.';
    }

    if (!in_array($status, ['new', 'active', 'inactive'], true)) {
        $errors['status'] = 'Status must be new, active, or inactive.';
    }

    $fields = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'gender' => $gender,
        'birth_date' => $birthDate !== '' ? $birthDate : null,
        'phone' => $phone !== '' ? $phone : null,
        'email' => $email !== '' ? $email : null,
        'address' => $address !== '' ? $address : null,
        'status' => $status,
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

function resolve_customer_id(): ?int
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

function fetch_customer_by_id(mysqli $connection, int $id): ?array
{
    $statement = $connection->prepare(
        'SELECT id, first_name, last_name, gender, birth_date, phone, email, address, status, created_at, updated_at
        FROM customers WHERE id = ?'
    );
    $statement->bind_param('i', $id);
    $statement->execute();
    $result = $statement->get_result();

    return $result->num_rows === 0 ? null : $result->fetch_assoc();
}

function refresh_customer_statuses(mysqli $connection): void
{
    $connection->query(
        "UPDATE customers SET status = 'active' WHERE deleted = 0 AND status = 'new' AND TIMESTAMPDIFF(DAY, created_at, NOW()) >= 7"
    );

    $connection->query(
        "UPDATE customers AS c
         SET status = 'inactive'
         WHERE c.deleted = 0
           AND c.status <> 'inactive'
           AND TIMESTAMPDIFF(DAY,
               COALESCE(
                   (SELECT MAX(o.order_date)
                    FROM orders AS o
                    WHERE o.customer_id = c.id),
                   c.created_at
               ),
               NOW()
           ) >= 30"
    );
}
