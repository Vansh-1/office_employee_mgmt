<?php
session_start();

// If already logged in, redirect to correct dashboard
if (isset($_SESSION['admin'])) {
    header("Location: admin_dashboard.php");
    exit;
} elseif (isset($_SESSION['employee'])) {
    header("Location: employee_dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <title>Office Employee Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border: none;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            background: #fff;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            width: 420px;
        }
        .card h1 {
            font-weight: 700;
            margin-bottom: 15px;
            color: #4e73df;
        }
        .card p {
            font-size: 1.1rem;
            margin-bottom: 25px;
            color: #555;
        }
        .btn-custom {
            border-radius: 12px;
            padding: 12px 25px;
            font-weight: 600;
            border: none;
            width: 100%;
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            color: #fff;
            transition: 0.3s ease;
        }
        .btn-custom:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        footer {
            position: absolute;
            bottom: 15px;
            font-size: 0.9rem;
            color: #fff;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Welcome!</h1>
        <p>Office Employee Management System</p>
        <a href="login.php" class="btn btn-custom">Login to Continue</a>
    </div>

    <footer>
        &copy; <?php echo date("Y"); ?> Office Employee Management. All Rights Reserved.
    </footer>
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
