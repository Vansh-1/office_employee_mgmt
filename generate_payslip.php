<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $amount = $_POST['amount'];

    // Fetch employee name
    $stmt = $conn->prepare("SELECT name FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($emp) {
        $empName = $emp['name'];
        $fileName = "payslips/" . strtolower(str_replace(" ", "_", $empName)) . "_{$month}_{$year}.pdf";

        // Create PDF (basic using FPDF)
        require 'fpdf/fpdf.php';
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10,'Company XYZ - Payslip',0,1,'C');
        $pdf->Ln(10);

        $pdf->SetFont('Arial','',12);
        $pdf->Cell(0,10,"Employee: $empName",0,1);
        $pdf->Cell(0,10,"Month: $month $year",0,1);
        $pdf->Cell(0,10,"Amount: Rs. $amount",0,1);

        $pdf->Output('F', $fileName);

        // Save into DB
        $stmt = $conn->prepare("INSERT INTO payslips (employee_id, month, year, amount, file_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$employee_id, $month, $year, $amount, $fileName]);

        header("Location: payslips.php?msg=Payslip+generated+successfully");
        exit;
    } else {
        $error = "Invalid employee!";
    }
}

// Fetch employees for dropdown
$employees = $conn->query("SELECT id, name FROM employees")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Payslip</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border-radius: 15px;
        }
        .btn-custom {
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 600;
        }
        .back-btn {
            position: absolute;
            top: 20px;
            left: 30px;
        }
    </style>
</head>
<body>
    <!-- Back Button -->
    <a href="payslips.php" class="btn btn-outline-secondary back-btn">⬅ Back</a>

    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow-lg border-0 w-50">
            <div class="card-body p-5">
                <h3 class="mb-4 text-center fw-bold text-primary">📝 Generate Payslip</h3>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">-- Select Employee --</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Month</label>
                        <input type="text" name="month" class="form-control" placeholder="e.g. July" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount (Rs.)</label>
                        <input type="number" name="amount" class="form-control" required>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-success btn-custom">✅ Generate Payslip</button>
                        <a href="payslips.php" class="btn btn-secondary btn-custom">View Payslips</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
