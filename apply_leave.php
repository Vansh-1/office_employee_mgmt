<?php
session_start();
if (!isset($_SESSION['employee'])) {
    header("Location: login.php");
    exit;
}
require 'config.php';

$empId = $_SESSION['employee'];
$msg = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    $stmt = $conn->prepare("INSERT INTO leave_applications (employee_id, leave_type, start_date, end_date, reason, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, 'Pending', NOW())");
    if ($stmt->execute([$empId, $leave_type, $start_date, $end_date, $reason])) {
        $msg = "<div class='alert alert-success'>Leave application submitted successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger'>Something went wrong. Try again!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Apply Leave</title>
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow p-4">
        <h3>Apply for Leave</h3>
        <?php echo $msg; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Leave Type</label>
                <select name="leave_type" class="form-control" required>
                    <option value="">Select</option>
                    <option value="Casual Leave">Casual Leave</option>
                    <option value="Sick Leave">Sick Leave</option>
                    <option value="Paid Leave">Paid Leave</option>
                    <option value="Unpaid Leave">Unpaid Leave</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Application</button>
            <a href="employee_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </form>
    </div>
</div>
</body>
</html>
