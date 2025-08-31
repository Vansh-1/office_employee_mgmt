<?php
session_start();
if (!isset($_SESSION['employee']) && !isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require 'config.php';

// Payslips array
$payslips = [];

if (isset($_SESSION['employee'])) {
    // Employee sees only their payslips
    $userId = $_SESSION['employee'];
    $stmt = $conn->prepare("SELECT id, month, year, salary AS amount, file_path 
                            FROM payslips 
                            WHERE employee_id = ? 
                            ORDER BY year DESC, month DESC");
    $stmt->execute([$userId]);
    $payslips = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif (isset($_SESSION['admin'])) {
    // Admin sees all payslips with optional filters
    $where = [];
    $params = [];

    if (isset($_GET['employee_id']) && $_GET['employee_id'] != '') {
        $where[] = "p.employee_id = ?";
        $params[] = $_GET['employee_id'];
    }

    if (isset($_GET['month_year']) && $_GET['month_year'] != '') {
        $dateParts = explode("-", $_GET['month_year']);
        $year = intval($dateParts[0]);
        $month = intval($dateParts[1]); // numeric month 1-12
        $where[] = "p.year = ? AND p.month = ?";
        $params[] = $year;
        $params[] = $month;
    }

    $sql = "SELECT p.id, p.month, p.year, p.salary AS amount, p.file_path, e.name
            FROM payslips p
            JOIN employees e ON p.employee_id = e.id";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY p.year DESC, p.month DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $payslips = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslips - Employee Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 40px 15px;
        }
        .card {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0px 10px 30px rgba(0,0,0,0.2);
        }
        .card-header {
            background: #0d6efd;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            padding: 20px;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #343a40;
            color: white;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(13,110,253,0.1);
            transition: 0.3s;
        }
        .download-btn {
            background: #0d6efd;
            color: white;
            border-radius: 8px;
            padding: 6px 12px;
            text-decoration: none;
            transition: 0.3s;
        }
        .download-btn:hover {
            background: #084298;
            text-decoration: none;
            color: white;
        }
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: #6c757d;
            color: white;
            padding: 8px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }
        .back-btn:hover {
            background: #5a6268;
            color: white;
        }
    </style>
</head>
<body>
<a href="<?= isset($_SESSION['admin']) ? 'admin_dashboard.php' : 'employee_dashboard.php' ?>" class="back-btn">⬅ Back</a>

<div class="container">
    <div class="card shadow-lg border-0">
        <div class="card-header">💰 Payslips</div>
        <div class="card-body p-4">

            <!-- Admin Filters -->
            <?php if (isset($_SESSION['admin'])): ?>
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <select name="employee_id" class="form-select">
                        <option value="">All Employees</option>
                        <?php
                        $empStmt = $conn->query("SELECT id, name FROM employees ORDER BY name");
                        $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach($employees as $emp){
                            $selected = (isset($_GET['employee_id']) && $_GET['employee_id'] == $emp['id']) ? 'selected' : '';
                            echo "<option value='{$emp['id']}' $selected>{$emp['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="month" name="month_year" class="form-control" value="<?= $_GET['month_year'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="payslips.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
            <?php endif; ?>

            <!-- Payslip Table -->
            <table class="table table-hover align-middle text-center">
                <thead>
                    <tr>
                        <?php if (isset($_SESSION['admin'])): ?>
                            <th>Employee</th>
                        <?php endif; ?>
                        <th>Month</th>
                        <th>Year</th>
                        <th>Amount</th>
                        <th>Download</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payslips)): ?>
                        <?php foreach ($payslips as $row): ?>
                            <tr>
                                <?php if (isset($_SESSION['admin'])): ?>
                                    <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($row['month']) ?></td>
                                <td><?= htmlspecialchars($row['year']) ?></td>
                                <td><strong>₹ <?= number_format($row['amount'], 2) ?></strong></td>
                                <td>
                                    <?php if (!empty($row['file_path']) && file_exists($row['file_path'])): ?>
                                        <a href="<?= htmlspecialchars($row['file_path']) ?>" class="download-btn" download>
                                            ⬇ Download
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No file</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= isset($_SESSION['admin']) ? 5 : 4 ?>" class="text-center text-muted">
                                No payslips available
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>
</body>
</html>
