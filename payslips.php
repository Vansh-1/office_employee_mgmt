<?php
session_start();
if (!isset($_SESSION['employee']) && !isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require 'config.php';

// ----------------------
// Fetch payslips
// ----------------------
$payslips = [];
$params = [];
$where = "";

if (isset($_SESSION['employee'])) {
    $where = "WHERE employee_id = ?";
    $params[] = $_SESSION['employee'];
}

$sql = "SELECT p.*, e.name 
        FROM payslips p 
        JOIN employees e ON p.employee_id = e.id
        $where
        ORDER BY year DESC, month DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$payslips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payslips</title>
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px 0; }
.card { border-radius: 20px; box-shadow: 0px 10px 30px rgba(0,0,0,0.2); }
.card-header { background: #0d6efd; color: #fff; text-align: center; font-size: 1.5rem; font-weight: bold; }
.table th { background-color: #343a40; color: white; }
.table-hover tbody tr:hover { background-color: rgba(13,110,253,0.1); transition: 0.3s; }
.download-btn { background: #0d6efd; color: white; border-radius: 8px; padding: 6px 12px; text-decoration: none; transition: 0.3s; }
.download-btn:hover { background: #084298; text-decoration: none; color: white; }
.back-btn { position: absolute; top: 20px; left: 20px; background: #6c757d; color: white; padding: 8px 14px; border-radius: 10px; text-decoration: none; font-weight: 500; transition: 0.3s; }
.back-btn:hover { background: #5a6268; color: white; }
</style>
</head>
<body>

<a href="<?= isset($_SESSION['admin']) ? 'admin_dashboard.php' : 'employee_dashboard.php' ?>" class="back-btn">⬅ Back</a>

<div class="container">
    <div class="card shadow-lg border-0">
        <div class="card-header">💰 Payslips</div>
        <div class="card-body p-4">

            <table class="table table-hover text-center align-middle">
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
                        <?php foreach ($payslips as $p): ?>
                            <tr>
                                <?php if (isset($_SESSION['admin'])): ?>
                                    <td><?= htmlspecialchars($p['name']) ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($p['month']) ?></td>
                                <td><?= htmlspecialchars($p['year']) ?></td>
                                <td><strong>₹ <?= number_format($p['salary'],2) ?></strong></td>
                                <td>
                                    <?php if (!empty($p['file_path']) && file_exists($p['file_path'])): ?>
                                        <a href="<?= htmlspecialchars($p['file_path']) ?>" class="download-btn" download>⬇ Download</a>
                                    <?php else: ?>
                                        <span class="text-muted">No file</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= isset($_SESSION['admin']) ? 5 : 4 ?>" class="text-center text-muted">No payslips available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

</body>
</html>
