<?php
session_start();
require 'config.php';

// Role-based security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit;
}

$empId = $_SESSION['user_id']; // use the generic session user_id

// Get employee details
$stmt = $conn->prepare("SELECT name, department FROM employees WHERE id = ?");
$stmt->execute([$empId]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Employee Dashboard - Employee Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f4f6f9; }

    /* Sidebar */
    .sidebar {
      height: 100vh;
      background: #0d6efd;
      color: #fff;
      padding-top: 20px;
      width: 240px;
      position: fixed;
      left: 0;
      top: 0;
    }
    .sidebar h4 {
      font-weight: bold;
      margin-bottom: 20px;
      padding-left: 15px;
    }
    .sidebar a {
      color: #dee2e6;
      display: block;
      padding: 12px 18px;
      border-radius: 6px;
      text-decoration: none;
      margin-bottom: 6px;
      transition: 0.3s;
    }
    .sidebar a:hover {
      background: rgba(255,255,255,0.2);
      color: #fff;
    }

    /* Content */
    .content {
      margin-left: 240px;
      padding: 30px;
    }

    /* Card */
    .card-custom {
      border: none;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      transition: transform 0.2s ease;
    }
    .card-custom:hover {
      transform: translateY(-5px);
    }

    /* Navbar */
    .topbar {
      background: #fff;
      padding: 10px 20px;
      border-bottom: 1px solid #ddd;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 1000;
    }
  </style>
</head>
<body>
<div class="sidebar">
  <h4>👨‍💻 Employee Panel</h4>
  <a href="employee_dashboard.php"><i class="bi bi-house-fill"></i> Dashboard</a>
  <a href="attendance.php"><i class="bi bi-clipboard-check-fill"></i> My Attendance</a>
  <a href="payslips.php"><i class="bi bi-cash-coin"></i> My Payslips</a>
  <a href="announcements.php"><i class="bi bi-megaphone-fill"></i> Announcements</a>
  <a href="logout.php" class="text-warning"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="content">
  <!-- Topbar -->
  <div class="topbar">
    <a href="index.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    <div>
      <span class="me-3"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($emp['name']); ?></span>
      <span class="text-muted">📅 <?= date("l, d F Y") ?></span>
    </div>
  </div>

  <!-- Dashboard Content -->
  <div class="mt-4">
    <h2 class="mb-3">Welcome, <?= htmlspecialchars($emp['name']); ?> 👋</h2>
    <p class="text-muted"><strong>Department:</strong> <?= htmlspecialchars($emp['department']); ?></p>

    <div class="row g-4 mt-4">
      <div class="col-md-4">
        <div class="card card-custom text-center p-4">
          <div class="card-body">
            <i class="bi bi-clipboard-check-fill fs-1 text-primary"></i>
            <h5 class="mt-3">Attendance</h5>
            <a href="attendance.php" class="btn btn-primary mt-2">View Attendance</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card card-custom text-center p-4">
          <div class="card-body">
            <i class="bi bi-cash-coin fs-1 text-success"></i>
            <h5 class="mt-3">Payslips</h5>
            <a href="payslips.php" class="btn btn-success mt-2">Download Payslips</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card card-custom text-center p-4">
          <div class="card-body">
            <i class="bi bi-megaphone-fill fs-1 text-warning"></i>
            <h5 class="mt-3">Announcements</h5>
            <a href="announcements.php" class="btn btn-warning mt-2">View Updates</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
