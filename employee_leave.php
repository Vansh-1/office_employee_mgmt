<?php
session_start();
require 'config.php';

// Ensure only employees can access
if (!isset($_SESSION['employee'])) {
    header("Location: login.php");
    exit;
}

$employee_id = $_SESSION['employee'];

// Handle leave application form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    $stmt = $conn->prepare("INSERT INTO leave_applications (employee_id, leave_type, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$employee_id, $leave_type, $start_date, $end_date, $reason]);

    echo "<script>alert('Leave application submitted successfully!');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Application</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Apply for Leave</h2>
    <form method="post">
        <label>Leave Type:</label>
        <select name="leave_type" required>
            <option value="Sick">Sick</option>
            <option value="Casual">Casual</option>
            <option value="Paid">Paid</option>
        </select><br><br>

        <label>Start Date:</label>
        <input type="date" name="start_date" required><br><br>

        <label>End Date:</label>
        <input type="date" name="end_date" required><br><br>

        <label>Reason:</label>
        <textarea name="reason" required></textarea><br><br>

        <button type="submit">Submit</button>
    </form>

    <h2>My Leave Applications</h2>
    <table border="1" cellpadding="10">
        <tr>
            <th>Type</th>
            <th>From</th>
            <th>To</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Applied At</th>
        </tr>
        <?php
        $stmt = $conn->prepare("SELECT * FROM leave_applications WHERE employee_id = ? ORDER BY applied_at DESC");
        $stmt->execute([$employee_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>
                <td>{$row['leave_type']}</td>
                <td>{$row['start_date']}</td>
                <td>{$row['end_date']}</td>
                <td>{$row['reason']}</td>
                <td>{$row['status']}</td>
                <td>{$row['applied_at']}</td>
            </tr>";
        }
        ?>
    </table>
</body>
</html>
