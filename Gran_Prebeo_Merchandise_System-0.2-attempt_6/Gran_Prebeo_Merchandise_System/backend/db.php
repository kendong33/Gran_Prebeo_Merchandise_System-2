<?php
function get_db_connection(): mysqli
{
    static $connection;

    if ($connection instanceof mysqli && $connection->ping()) {
        return $connection;
    }

function ensure_users_table_exists(mysqli $connection): void
{
    $connection->query(
        'CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            username VARCHAR(100) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uq_users_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function ensure_deliveries_schema_is_current(mysqli $connection): void
{
    $col1 = $connection->query("SHOW COLUMNS FROM deliveries LIKE 'delivery_tracking_id'");
    if ($col1 !== false && $col1->num_rows === 0) {
        $connection->query('ALTER TABLE deliveries ADD COLUMN delivery_tracking_id VARCHAR(50) NOT NULL AFTER tracking_number');
    }
    if ($col1 instanceof mysqli_result) { $col1->free(); }

    $col2 = $connection->query("SHOW COLUMNS FROM deliveries LIKE 'courier'");
    if ($col2 !== false && $col2->num_rows === 0) {
        $connection->query('ALTER TABLE deliveries ADD COLUMN courier VARCHAR(80) NOT NULL AFTER address');
    }
    if ($col2 instanceof mysqli_result) { $col2->free(); }
}

function ensure_orders_schema_is_current(mysqli $connection): void
{
    $col = $connection->query("SHOW COLUMNS FROM orders LIKE 'delivery_address'");
    if ($col !== false && $col->num_rows === 0) {
        $connection->query('ALTER TABLE orders ADD COLUMN delivery_address VARCHAR(255) NULL AFTER total_amount');
    }
    if ($col instanceof mysqli_result) {
        $col->free();
    }

    // Ensure 'notes' column exists for older schemas
    $colNotes = $connection->query("SHOW COLUMNS FROM orders LIKE 'notes'");
    if ($colNotes !== false && $colNotes->num_rows === 0) {
        $connection->query('ALTER TABLE orders ADD COLUMN notes TEXT NULL AFTER delivery_address');
    }
    if ($colNotes instanceof mysqli_result) {
        $colNotes->free();
    }
}

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Read connection from environment for cloud deployments (e.g., Vercel, Render)
    $host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost';
    $user = getenv('DB_USER') !== false ? getenv('DB_USER') : 'root';
    $password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
    $database = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'gran_prebeo';
    $port = getenv('DB_PORT') !== false && ctype_digit((string)getenv('DB_PORT')) ? (int)getenv('DB_PORT') : 3306;
    $socket = getenv('DB_SOCKET') !== false ? getenv('DB_SOCKET') : null;

    $connection = new mysqli($host, $user, $password, '', $port, $socket);

    if ($connection->connect_error) {
        throw new RuntimeException('Database connection failed: ' . $connection->connect_error);
    }

    $connection->set_charset('utf8mb4');

    // Only auto-create the database on local hosts unless explicitly disabled
    $autoCreate = getenv('DB_AUTO_CREATE');
    $shouldCreateDb = in_array($host, ['localhost', '127.0.0.1'], true) && ($autoCreate === false || $autoCreate === '1');

    if ($shouldCreateDb) {
        $connection->query("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    $connection->select_db($database);

    ensure_customers_table_exists($connection);
    ensure_customers_schema_is_current($connection);
    ensure_orders_table_exists($connection);
    ensure_orders_schema_is_current($connection);
    ensure_invoices_table_exists($connection);
    ensure_deliveries_table_exists($connection);
    ensure_deliveries_schema_is_current($connection);
    ensure_users_table_exists($connection);

    return $connection;
}

function ensure_customers_table_exists(mysqli $connection): void
{
    $connection->query(
        'CREATE TABLE IF NOT EXISTS customers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            gender ENUM("male", "female") NOT NULL,
            birth_date DATE NULL,
            phone VARCHAR(30) NULL,
            email VARCHAR(150) NULL,
            address VARCHAR(255) NULL,
            status ENUM("new", "active", "inactive") NOT NULL DEFAULT "new",
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_customers_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function ensure_invoices_table_exists(mysqli $connection): void
{
    // Create table first to avoid SHOW COLUMNS failing on non-existent table
    $connection->query(
        'CREATE TABLE IF NOT EXISTS invoices (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            uid CHAR(36) NOT NULL,
            invoice_number VARCHAR(50) NOT NULL,
            order_id VARCHAR(50) NULL,
            customer_id INT UNSIGNED NOT NULL,
            customer_name VARCHAR(200) NOT NULL,
            customer_email VARCHAR(200) NOT NULL,
            billing_street VARCHAR(255) NOT NULL,
            billing_city VARCHAR(120) NOT NULL,
            billing_state VARCHAR(120) NOT NULL,
            billing_postal_code VARCHAR(30) NOT NULL,
            billing_country VARCHAR(120) NOT NULL,
            items_json LONGTEXT NOT NULL,
            currency CHAR(3) NOT NULL DEFAULT "USD",
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
            tax_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            shipping_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            grand_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT "draft",
            next_status VARCHAR(20) NULL,
            payment_method VARCHAR(100) NULL,
            payment_date DATETIME NULL,
            issue_date DATE NOT NULL,
            due_date DATE NULL,
            notes TEXT NULL,
            terms TEXT NULL,
            tracking_number VARCHAR(40) NULL,
            deleted TINYINT(1) NOT NULL DEFAULT 0,
            deleted_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            CONSTRAINT fk_invoices_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
            UNIQUE KEY uq_invoice_uid (uid),
            UNIQUE KEY uq_invoice_number (invoice_number),
            KEY idx_invoice_customer (customer_id),
            KEY idx_invoice_order (order_id),
            KEY idx_invoice_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    // Add missing columns for legacy schemas
    $col = $connection->query("SHOW COLUMNS FROM invoices LIKE 'deleted'");
    if ($col !== false && $col->num_rows === 0) {
        $connection->query('ALTER TABLE invoices ADD COLUMN deleted TINYINT(1) NOT NULL DEFAULT 0 AFTER tracking_number');
    }
    if ($col instanceof mysqli_result) { $col->free(); }

    $col = $connection->query("SHOW COLUMNS FROM invoices LIKE 'deleted_at'");
    if ($col !== false && $col->num_rows === 0) {
        $connection->query('ALTER TABLE invoices ADD COLUMN deleted_at DATETIME NULL AFTER deleted');
    }
    if ($col instanceof mysqli_result) { $col->free(); }

    $col = $connection->query("SHOW COLUMNS FROM invoices LIKE 'items_json'");
    if ($col !== false && $col->num_rows === 0) {
        $connection->query('ALTER TABLE invoices ADD COLUMN items_json LONGTEXT NULL AFTER billing_country');
    }
    if ($col instanceof mysqli_result) { $col->free(); }

    $col = $connection->query("SHOW COLUMNS FROM invoices LIKE 'customer_name'");
    if ($col !== false && $col->num_rows === 0) {
        $connection->query('ALTER TABLE invoices ADD COLUMN customer_name VARCHAR(200) NOT NULL DEFAULT "" AFTER customer_id');
    }
    if ($col instanceof mysqli_result) { $col->free(); }

    $col = $connection->query("SHOW COLUMNS FROM invoices LIKE 'customer_email'");
    if ($col !== false && $col->num_rows === 0) {
        $connection->query('ALTER TABLE invoices ADD COLUMN customer_email VARCHAR(200) NOT NULL DEFAULT "" AFTER customer_name');
    }
    if ($col instanceof mysqli_result) { $col->free(); }

    $connection->query(
        'ALTER TABLE invoices MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT "draft"'
    );

    $nextStatusColumn = $connection->query("SHOW COLUMNS FROM invoices LIKE 'next_status'");

    if ($nextStatusColumn !== false && $nextStatusColumn->num_rows === 0) {
        $connection->query('ALTER TABLE invoices ADD COLUMN next_status VARCHAR(20) NULL AFTER status');
    }

    if ($nextStatusColumn instanceof mysqli_result) {
        $nextStatusColumn->free();
    }
}

function ensure_customers_schema_is_current(mysqli $connection): void
{
    $statusColumn = $connection->query("SHOW COLUMNS FROM customers LIKE 'status'");

    if ($statusColumn !== false && $statusColumn->num_rows === 0) {
        $connection->query(
            'ALTER TABLE customers ADD COLUMN status ENUM("new", "active", "inactive") NOT NULL DEFAULT "new" AFTER address'
        );
    }

    // Add soft delete columns if missing
    $deletedCol = $connection->query("SHOW COLUMNS FROM customers LIKE 'deleted'");
    if ($deletedCol !== false && $deletedCol->num_rows === 0) {
        $connection->query('ALTER TABLE customers ADD COLUMN deleted TINYINT(1) NOT NULL DEFAULT 0 AFTER status');
    }
    if ($deletedCol instanceof mysqli_result) { $deletedCol->free(); }

    $deletedAtCol = $connection->query("SHOW COLUMNS FROM customers LIKE 'deleted_at'");
    if ($deletedAtCol !== false && $deletedAtCol->num_rows === 0) {
        $connection->query('ALTER TABLE customers ADD COLUMN deleted_at DATETIME NULL AFTER deleted');
    }
    if ($deletedAtCol instanceof mysqli_result) { $deletedAtCol->free(); }

    $deleteReasonCol = $connection->query("SHOW COLUMNS FROM customers LIKE 'delete_reason'");
    if ($deleteReasonCol !== false && $deleteReasonCol->num_rows === 0) {
        $connection->query('ALTER TABLE customers ADD COLUMN delete_reason VARCHAR(255) NULL AFTER deleted_at');
    }
    if ($deleteReasonCol instanceof mysqli_result) { $deleteReasonCol->free(); }
}

function ensure_orders_table_exists(mysqli $connection): void
{
    $connection->query(
        'CREATE TABLE IF NOT EXISTS orders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id INT UNSIGNED NOT NULL,
            order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status ENUM("pending", "processing", "completed", "cancelled") NOT NULL DEFAULT "pending",
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            delivery_address VARCHAR(255) NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function ensure_deliveries_table_exists(mysqli $connection): void
{
    $connection->query(
        'CREATE TABLE IF NOT EXISTS deliveries (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT UNSIGNED NOT NULL,
            order_id VARCHAR(50) NULL,
            invoice_number VARCHAR(50) NOT NULL,
            tracking_number VARCHAR(40) NULL,
            delivery_tracking_id VARCHAR(50) NOT NULL,
            customer_id INT UNSIGNED NOT NULL,
            customer_name VARCHAR(200) NOT NULL,
            address VARCHAR(255) NOT NULL,
            courier VARCHAR(80) NOT NULL,
            status ENUM("pending","shipped","completed") NOT NULL DEFAULT "pending",
            delivery_date DATE NOT NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            CONSTRAINT fk_deliveries_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
            KEY idx_delivery_invoice (invoice_id),
            KEY idx_delivery_status (status),
            KEY idx_delivery_date (delivery_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}
