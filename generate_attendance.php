<?php
require 'config.php'; // Make sure this connects to your DB

// Fetch all real employee IDs from the database
$employees = [];
$stmt = $conn->query("SELECT id FROM employees");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $employees[] = $row['id'];
}

if (empty($employees)) {
    die("❌ No employees found in the database. Please add employees first.");
}

// Attendance statuses and probabilities
$statuses = ['Present', 'Absent', 'Leave'];
$weights = [0.8, 0.15, 0.05]; // 80% Present, 15% Absent, 5% Leave

$start_date = new DateTime('2020-01-01');
$end_date = new DateTime('2025-12-31');

$interval = new DateInterval('P1D');
$period = new DatePeriod($start_date, $interval, $end_date);

$inserted = 0;

foreach ($period as $date) {
    $day = $date->format('Y-m-d');

    foreach ($employees as $emp_id) {
        // Random status based on weights
        $rand = mt_rand() / mt_getrandmax();
        if ($rand < $weights[0]) {
            $status = 'Present';
        } elseif ($rand < $weights[0] + $weights[1]) {
            $status = 'Absent';
        } else {
            $status = 'Leave';
        }

        // Check if attendance already exists for this employee/date
        $check = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE emp_id = ? AND date = ?");
        $check->execute([$emp_id, $day]);
        if ($check->fetchColumn() == 0) {
            // Insert attendance
            $stmt = $conn->prepare("INSERT INTO attendance (emp_id, date, status, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$emp_id, $day, $status]);
            $inserted++;
        }
    }
}

echo "✅ Attendance generated for $inserted records from 2020-01-01 to 2025-12-31.";
?>
