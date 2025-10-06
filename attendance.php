<?php
session_start();
require 'config.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'] ?? null;

// Filters: default to current month
$yearFilter = $_GET['year'] ?? date('Y');
$monthFilter = $_GET['month'] ?? date('m');
$empFilter = $_GET['employee'] ?? '';

// Admin: fetch all employees for filter dropdown
$employees = [];
if ($role === 'admin') {
    $empStmt = $conn->query("SELECT id, name FROM employees ORDER BY name ASC");
    $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Build query (date range to leverage indexes)
$params = [];
// Compute start/end of selected month
$yearNum = (int)$yearFilter;
$monthNum = (int)$monthFilter;
$startDate = sprintf('%04d-%02d-01', $yearNum, $monthNum);
$endDate = (new DateTimeImmutable($startDate))->modify('+1 month')->format('Y-m-d');

$query = "SELECT e.name, 
                 SUM(CASE WHEN a.status='Present' THEN 1 ELSE 0 END) AS present_days,
                 SUM(CASE WHEN a.status='Absent' THEN 1 ELSE 0 END) AS absent_days,
                 SUM(CASE WHEN a.status='Leave' THEN 1 ELSE 0 END) AS leave_days
          FROM attendance a
          JOIN employees e ON a.emp_id = e.id
          WHERE a.date >= ? AND a.date < ?";
$params[] = $startDate;
$params[] = $endDate;

// Apply employee filter (admin only)
if ($role==='admin' && !empty($empFilter)) {
    $query .= " AND a.emp_id=?";
    $params[] = $empFilter;
}

// Employee role: filter by self
if ($role==='employee') {
    $query .= " AND a.emp_id=?";
    $params[] = $userId;
}

$query .= " GROUP BY e.id ORDER BY e.name ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Years
$yearStmt = $conn->query("SELECT DISTINCT YEAR(date) as year FROM attendance ORDER BY year DESC");
$years = $yearStmt->fetchAll(PDO::FETCH_COLUMN);

// Months
$months = [
    1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
    7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Summary</title>
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#eef2f7; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.container { max-width: 1100px; }
.card { border-radius: 15px; }
.scroll-table { max-height:500px; overflow-y:auto; }
h2 { font-weight:700; }
.status-present { background:#d1e7dd; color:#0f5132; padding:3px 10px; border-radius:5px; font-weight:600; }
.status-absent { background:#f8d7da; color:#842029; padding:3px 10px; border-radius:5px; font-weight:600; }
.status-leave { background:#fff3cd; color:#664d03; padding:3px 10px; border-radius:5px; font-weight:600; }
.btn-back { position:absolute; top:20px; left:20px; }
</style>
</head>
<body>
<div class="container mt-5 position-relative">

<a href="<?= $role === 'admin' ? 'admin_dashboard.php' : 'employee_dashboard.php' ?>" 
   class="btn btn-secondary btn-back">⬅ Back</a>

<h2 class="mb-4 text-center fw-bold">📋 Attendance Summary</h2>

<!-- Filters -->
<div class="card mb-4 p-3 shadow-sm">
<form method="GET" class="row g-3 align-items-end">
    <div class="col-auto">
        <label>Year</label>
        <select name="year" class="form-select">
            <?php foreach($years as $year): ?>
                <option value="<?= $year ?>" <?= $year == $yearFilter ? 'selected':'' ?>><?= $year ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label>Month</label>
        <select name="month" class="form-select">
            <?php foreach($months as $num=>$name): ?>
                <option value="<?= $num ?>" <?= $num==$monthFilter?'selected':'' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if($role==='admin'): ?>
    <div class="col-auto">
        <label>Employee</label>
        <select name="employee" class="form-select">
            <option value="">All</option>
            <?php foreach($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>" <?= $empFilter==$emp['id']?'selected':'' ?>><?= htmlspecialchars($emp['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Filter</button>
    </div>
</form>
</div>

<!-- Attendance Table -->
<div class="card shadow-lg border-0 scroll-table">
<div class="card-body p-0">
<table class="table table-hover align-middle mb-0">
<thead class="table-dark sticky-top">
<tr>
    <th>Employee</th>
    <th>Present</th>
    <th>Absent</th>
    <th>On Leave</th>
</tr>
</thead>
<tbody>
<?php if(!empty($records)): ?>
    <?php foreach($records as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><span class="status-present"><?= $row['present_days'] ?></span></td>
        <td><span class="status-absent"><?= $row['absent_days'] ?></span></td>
        <td><span class="status-leave"><?= $row['leave_days'] ?></span></td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr>
    <td colspan="4" class="text-center text-muted">No records found</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

</div>
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
