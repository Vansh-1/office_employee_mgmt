<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            $_SESSION['admin'] = $user['id'];   // ✅ add admin session
            header("Location: admin_dashboard.php");
        } else {
            $_SESSION['employee'] = $user['id']; // ✅ add employee session
            header("Location: employee_dashboard.php");
        }
        exit();
    } else {
        $error = "❌ Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Office Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-wrapper {
            width: 100%;
            max-width: 420px;
        }
        .card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            padding: 30px 20px;
            text-align: center;
        }
        .card-header i {
            font-size: 4rem;
            color: #fff;
        }
        .card-header h3 {
            margin-top: 15px;
            font-weight: 700;
            color: #fff;
        }
        .card-body {
            padding: 30px 25px;
            background: #fff;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.25);
            border-color: #4e73df;
        }
        .btn-login {
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            border: none;
            color: #fff;
            transition: 0.3s ease;
        }
        .btn-login:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .alert {
            border-radius: 12px;
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="card">
        <div class="card-header">
            <i class="bi bi-building-lock"></i>
            <h3>Office Management</h3>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">👤 Username</label>
                    <input type="text" name="username" id="username" class="form-control" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">🔒 Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-login w-100">Login</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
