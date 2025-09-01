<?php
session_start();
if (!isset($_SESSION['employee'])) {
    header("Location: login.php");
    exit;
}
require 'config.php';

$empId = $_SESSION['employee'];

// Fetch employee leaves
$stmt = $conn->prepare("SELECT * FROM leave_applications WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->execute([$empId]);
$leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Leaves</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow p-4">
        <h3>My Leave Applications</h3>
        <table class="table table-bordered mt-3">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Leave Type</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Applied On</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($leaves): ?>
                    <?php foreach ($leaves as $leave): ?>
                        <tr>
                            <td><?php echo $leave['id']; ?></td>
                            <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                            <td><?php echo htmlspecialchars($leave['start_date']); ?></td>
                            <td><?php echo htmlspecialchars($leave['end_date']); ?></td>
                            <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                            <td>
                                <?php 
                                    $status = strtolower($leave['status']); 
                                    if ($status == 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($status == 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php elseif ($status == 'rejected'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($leave['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No leave applications found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="employee_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
