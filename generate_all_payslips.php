<?php
session_start();
require 'config.php';

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

// Config: basic salary and allowances (adjust if needed)
$basic = 50000;
$allowances = 10000;
$deductions_fixed = 5000;

// Get all employees
$employees = $conn->query("SELECT id, name FROM employees")->fetchAll(PDO::FETCH_ASSOC);

// Months range: 2020-01 to now
$start = new DateTime('2020-01-01');
$end   = new DateTime(); // today
$end->modify('first day of next month'); // include current month

$totalRecords = 0;

foreach ($employees as $emp) {
    $period = new DatePeriod($start, new DateInterval('P1M'), $end);
    foreach ($period as $dt) {
        $year = $dt->format("Y");
        $month = $dt->format("m");

        // Count attendance
        $stmt = $conn->prepare("SELECT 
            SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN status='Leave' THEN 1 ELSE 0 END) as leave_days,
            COUNT(*) as total_days
            FROM attendance
            WHERE emp_id=? AND YEAR(date)=? AND MONTH(date)=?");
        $stmt->execute([$emp['id'],$year,$month]);
        $att = $stmt->fetch(PDO::FETCH_ASSOC);

        $total_working_days = $att['total_days'] ?: 22; // default 22 days if no attendance
        $present_days = $att['present_days'] ?: 0;

        $net_pay = round($basic * ($present_days/$total_working_days) + $allowances - $deductions_fixed,2);

        // Insert if not exists (check by employee, month, year)
        $insert = $conn->prepare("INSERT IGNORE INTO payslips (employee_id, month, year, salary)
                                  VALUES (?,?,?,?)");
        $insert->execute([$emp['id'], $month, $year, $net_pay]);

        $totalRecords++;
    }
}

echo "✅ Payslips generated/updated for {$totalRecords} records from 2020-01 to now.";
