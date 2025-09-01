<?php
session_start();
require 'config.php';

// Only admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$adminName = $_SESSION['username'] ?? 'Admin';

// Fetch leaves from leave_applications table
try {
    $stmt = $conn->query("
        SELECT la.id, e.name AS employee_name, la.leave_type, la.start_date, la.end_date, la.reason, la.status, la.created_at
        FROM leave_applications la
        JOIN employees e ON la.employee_id = e.id
        ORDER BY la.created_at DESC
    ");
    $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching leaves: " . $e->getMessage());
}

// Handle approve/reject actions
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'] === 'approve' ? 'approved' : ($_GET['action'] === 'reject' ? 'rejected' : null);

    if ($action) {
        $update = $conn->prepare("UPDATE leave_applications SET status = ? WHERE id = ?");
        $update->execute([$action, $id]);
        header("Location: manage_leaves.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Leaves - Admin Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background:#f4f6f9; font-family: system-ui, sans-serif; }
.sidebar{ position:fixed; width:260px; height:100vh; background:linear-gradient(180deg,#111827,#1f2937); color:#fff; padding-top:20px; }
.sidebar a{ color:#cbd5e1; display:block; padding:10px 20px; text-decoration:none; border-radius:8px; margin:5px 10px;}
.sidebar a:hover, .sidebar a.active{ background:#0ea5e9; color:#fff; }
.content{ margin-left:260px; padding:20px; }
.table th, .table td{ vertical-align:middle; }
.status-pending{ background:#fff3cd; color:#664d03; padding:4px 10px; border-radius:5px; font-weight:600; }
.status-approved{ background:#d1e7dd; color:#0f5132; padding:4px 10px; border-radius:5px; font-weight:600; }
.status-rejected{ background:#f8d7da; color:#842029; padding:4px 10px; border-radius:5px; font-weight:600; }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h4 class="text-center mb-4">⚙️ Admin Panel</h4>
    <a href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="manage_employees.php"><i class="bi bi-people-fill"></i> Manage Employees</a>
    <a class="active" href="manage_leaves.php"><i class="bi bi-calendar-check-fill"></i> Manage Leaves</a>
    <a href="attendance.php"><i class="bi bi-clipboard-check-fill"></i> Attendance</a>
    <a href="payslips.php"><i class="bi bi-cash-coin"></i> Payslips</a>
    <a href="announcements.php"><i class="bi bi-megaphone-fill"></i> Announcements</a>
    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="content">
    <h2>Manage Leaves</h2>
    <div class="table-responsive mt-4">
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if(!empty($leaves)): ?>
                <?php foreach($leaves as $leave): ?>
                <tr>
                    <td><?= $leave['id'] ?></td>
                    <td><?= htmlspecialchars($leave['employee_name']) ?></td>
                    <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                    <td><?= $leave['start_date'] ?></td>
                    <td><?= $leave['end_date'] ?></td>
                    <td><?= htmlspecialchars($leave['reason']) ?></td>
                    <td><span class="status-<?= $leave['status'] ?>"><?= ucfirst($leave['status']) ?></span></td>
                    <td>
                        <?php if($leave['status']=='pending'): ?>
                            <a href="?action=approve&id=<?= $leave['id'] ?>" class="btn btn-success btn-sm">Approve</a>
                            <a href="?action=reject&id=<?= $leave['id'] ?>" class="btn btn-danger btn-sm">Reject</a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center text-muted">No leave requests found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
