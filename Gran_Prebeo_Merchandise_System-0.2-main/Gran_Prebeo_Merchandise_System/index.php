<?php
require_once __DIR__ . '/backend/session_bootstrap.php';
require_once __DIR__ . '/backend/db.php';

$showRegister = false;
$register_success = null;
$reg_errors = [];

if (isset($_POST['register'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($first_name === '') { $reg_errors['first_name'] = 'First name is required.'; }
    if ($last_name === '') { $reg_errors['last_name'] = 'Last name is required.'; }
    if ($username === '') { $reg_errors['username'] = 'Username is required.'; }
    if ($password === '') { $reg_errors['password'] = 'Password is required.'; }
    if (strlen($password) < 6) { $reg_errors['password'] = 'Password must be at least 6 characters.'; }
    if ($password !== $password_confirm) { $reg_errors['password_confirm'] = 'Passwords do not match.'; }

    if ($reg_errors === []) {
        try {
            $connection = get_db_connection();
            $check = $connection->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $check->bind_param('s', $username);
            $check->execute();
            $rs = $check->get_result();
            if ($rs->num_rows > 0) {
                $reg_errors['username'] = 'Username already taken.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
                $ins = $connection->prepare('INSERT INTO users (first_name, last_name, username, password_hash, created_at, updated_at) VALUES (?,?,?,?,?,?)');
                $ins->bind_param('ssssss', $first_name, $last_name, $username, $hash, $now, $now);
                $ins->execute();
                $register_success = 'Registration successful. You can now sign in.';
                $showRegister = false;
            }
        } catch (Throwable $e) {
            $reg_errors['general'] = 'Registration failed.';
        }
    }

    if ($reg_errors !== []) {
        $showRegister = true;
    }
}

if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $connection = get_db_connection();
        $stmt = $connection->prepare('SELECT id, password_hash FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if (password_verify($password, $row['password_hash'])) {
                $_SESSION['user'] = $username;
                header('Location: dashboard.php');
                exit();
            }
        }
    } catch (Throwable $e) {
    }

    if ($username === 'admin' && $password === 'password123') {
        $_SESSION['user'] = $username;
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gran Prebeo - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f7efe5;
            height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(140deg, #dbb27a 0%, #c08457 50%, #8d6a4f 100%);
        }
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .logo h2 {
            color: #333;
            font-weight: 600;
        }
        .form-control:focus {
            border-color: #c08457;
            box-shadow: 0 0 0 0.25rem rgba(192, 132, 87, 0.35);
        }
        .btn-login {
            background: linear-gradient(140deg, #dbb27a 0%, #c08457 50%, #8d6a4f 100%);
            border: none;
            padding: 10px 20px;
            font-weight: 500;
            width: 100%;
            margin-top: 10px;
        }
        .btn-login:hover {
            background: linear-gradient(140deg, #cfa061 0%, #b47342 50%, #7a543a 100%);
        }
        .text-link {
            color: #8d6a4f;
        }
        .text-link:hover {
            color: #5f4634;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <h2>Gran Prebeo</h2>
                <p class="text-muted">Merchandise Management System</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($register_success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($register_success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm" style="display: <?php echo $showRegister ? 'none' : 'block'; ?>;">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required 
                           placeholder="Enter your username">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required 
                           placeholder="••••••••">
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" name="login" class="btn btn-primary btn-login">
                        Sign In
                    </button>
                </div>
                <div class="text-center mt-3">
                    <a href="#" id="showRegisterLink" class="text-decoration-none text-link">Create an account</a>
                </div>
            </form>

            <form method="POST" action="" id="registerForm" style="display: <?php echo $showRegister ? 'block' : 'none'; ?>;">
                <?php if (!empty($reg_errors)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars(reset($reg_errors)); ?>
                    </div>
                <?php endif; ?>
                <div class="row g-2">
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="reg_username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="reg_username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="reg_password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="reg_password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="reg_password_confirm" class="form-label">Password Confirmation</label>
                    <input type="password" class="form-control" id="reg_password_confirm" name="password_confirm" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="button" id="backToLogin" class="btn btn-outline-secondary" style="border-color:#c08457;color:#8d6a4f;">Back to Login</button>
                    <button type="submit" name="register" class="btn btn-primary btn-login">Register</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const showRegisterLink = document.getElementById('showRegisterLink');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const backToLogin = document.getElementById('backToLogin');
        if (showRegisterLink) {
            showRegisterLink.addEventListener('click', (e) => {
                e.preventDefault();
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
            });
        }
        if (backToLogin) {
            backToLogin.addEventListener('click', () => {
                registerForm.style.display = 'none';
                loginForm.style.display = 'block';
            });
        }
    </script>
</body>
</html>
