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

// Fetch latest 3 payslips for this employee
$payslipStmt = $conn->prepare("SELECT month, year, salary, file_path 
                               FROM payslips 
                               WHERE employee_id = ? 
                               ORDER BY year DESC, month DESC 
                               LIMIT 3");
$payslipStmt->execute([$empId]);
$payslips = $payslipStmt->fetchAll(PDO::FETCH_ASSOC);
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
    .sidebar {
      height: 100vh; background: #0d6efd; color: #fff; padding-top: 20px;
      width: 240px; position: fixed; left: 0; top: 0;
    }
    .sidebar h4 { font-weight: bold; margin-bottom: 20px; padding-left: 15px; }
    .sidebar a {
      color: #dee2e6; display: block; padding: 12px 18px; border-radius: 6px;
      text-decoration: none; margin-bottom: 6px; transition: 0.3s;
    }
    .sidebar a:hover { background: rgba(255,255,255,0.2); color: #fff; }
    .content { margin-left: 240px; padding: 30px; }
    .card-custom {
      border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      transition: transform 0.2s ease;
    }
    .card-custom:hover { transform: translateY(-5px); }
    .topbar {
      background: #fff; padding: 10px 20px; border-bottom: 1px solid #ddd;
      display: flex; justify-content: space-between; align-items: center;
      position: sticky; top: 0; z-index: 1000;
    }
    .table th { background-color: #343a40; color: white; }
    .download-btn {
      background: #0d6efd; color: white; border-radius: 8px; padding: 4px 10px;
      text-decoration: none; transition: 0.3s; font-size: 14px;
    }
    .download-btn:hover { background: #084298; color: white; }
  </style>
</head>
<body>
<div class="sidebar">
  <h4>👨‍💻 Employee Panel</h4>
  <a href="employee_dashboard.php"><i class="bi bi-house-fill"></i> Dashboard</a>
  <a href="attendance.php"><i class="bi bi-clipboard-check-fill"></i> My Attendance</a>
  <a href="payslips.php"><i class="bi bi-cash-coin"></i> My Payslips</a>
  <a href="my_leaves.php"><i class="bi bi-calendar-check-fill"></i> My Leaves</a>
  <a href="apply_leave.php"><i class="bi bi-pencil-square"></i> Apply for Leave</a>
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
      <!-- Attendance -->
      <div class="col-md-4">
        <div class="card card-custom text-center p-4">
          <div class="card-body">
            <i class="bi bi-clipboard-check-fill fs-1 text-primary"></i>
            <h5 class="mt-3">Attendance</h5>
            <a href="attendance.php" class="btn btn-primary mt-2">View Attendance</a>
          </div>
        </div>
      </div>

      <!-- Payslips -->
      <div class="col-md-8">
        <div class="card card-custom p-4">
          <div class="card-body">
            <i class="bi bi-cash-coin fs-1 text-success"></i>
            <h5 class="mt-3">My Latest Payslips</h5>
            
            <?php if($payslips): ?>
              <table class="table table-sm table-hover mt-3 text-center">
                <thead>
                  <tr>
                    <th>Month</th>
                    <th>Year</th>
                    <th>Amount</th>
                    <th>Download</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($payslips as $p): ?>
                  <tr>
                    <td><?= htmlspecialchars($p['month']); ?></td>
                    <td><?= htmlspecialchars($p['year']); ?></td>
                    <td>₹ <?= number_format($p['salary'],2); ?></td>
                    <td>
                      <?php if(!empty($p['file_path']) && file_exists($p['file_path'])): ?>
                        <a href="<?= htmlspecialchars($p['file_path']); ?>" class="download-btn" download>⬇ Download</a>
                      <?php else: ?>
                        <span class="text-muted">No file</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <a href="payslips.php" class="btn btn-outline-success btn-sm mt-2">View All Payslips</a>
            <?php else: ?>
              <p class="text-muted mt-3">No payslips available yet.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Leaves -->
      <div class="col-md-6">
        <div class="card card-custom text-center p-4">
          <div class="card-body">
            <i class="bi bi-calendar-check-fill fs-1 text-info"></i>
            <h5 class="mt-3">Leaves</h5>
            <div class="d-grid gap-2">
              <a href="my_leaves.php" class="btn btn-info">My Leaves</a>
              <a href="apply_leave.php" class="btn btn-warning">Apply Leave</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Announcements -->
      <div class="col-md-6">
        <div class="card card-custom text-center p-4">
          <div class="card-body">
            <i class="bi bi-megaphone-fill fs-1 text-danger"></i>
            <h5 class="mt-3">Announcements</h5>
            <a href="announcements.php" class="btn btn-danger mt-2">View Updates</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
