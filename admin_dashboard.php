<?php
session_start();
require 'config.php';

// Role-based access control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$adminName = $_SESSION['username'] ?? 'Admin';

// ----- Dashboard stats -----
$totEmp  = (int)$conn->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$totDept = (int)$conn->query("SELECT COUNT(*) FROM departments")->fetchColumn();

// Optional counts (safe if tables do not exist)
$pendingLeaves = 0;
$annCount = 0;
try {
    $pendingLeaves = (int)$conn->query("SELECT COUNT(*) FROM leaves WHERE status='pending'")->fetchColumn();
} catch (Throwable $e) {}
try {
    $annCount = (int)$conn->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
} catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Employee Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root{
            --sidebar-w: 260px;
        }
        body{ background:#f4f6f9; font-family:system-ui,-apple-system,"Segoe UI",Roboto,Ubuntu,"Helvetica Neue",Arial,sans-serif; }
        /* Sidebar */
        .sidebar{
            position:fixed; inset:0 auto 0 0; width:var(--sidebar-w);
            background:linear-gradient(180deg,#111827 0%, #1f2937 100%); color:#fff; z-index:1020;
            box-shadow: 4px 0 24px rgba(0,0,0,.15);
        }
        .brand{
            font-weight:700; letter-spacing:.5px; padding:20px 18px; border-bottom:1px solid rgba(255,255,255,.1);
        }
        .nav-link{
            color:#cbd5e1 !important; border-radius:10px; margin:6px 12px; padding:10px 14px;
        }
        .nav-link:hover, .nav-link.active{ background:#0ea5e9; color:#fff !important; }
        .nav-link i{ width:22px; }
        /* Content wrapper */
        .content{
            margin-left:var(--sidebar-w);
            min-height:100vh;
        }
        /* Topbar */
        .topbar{
            position:sticky; top:0; z-index:1010;
            background:#ffffff; border-bottom:1px solid #e5e7eb;
        }
        .topbar .navbar{ padding:.8rem 1rem; }
        /* Cards */
        .card-stat{
            border:none; border-radius:16px; color:#fff;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
            overflow:hidden;
        }
        .grad-primary{ background:linear-gradient(135deg,#3b82f6,#2563eb); }
        .grad-success{ background:linear-gradient(135deg,#10b981,#059669); }
        .grad-warning{ background:linear-gradient(135deg,#f59e0b,#d97706); }
        .grad-danger{  background:linear-gradient(135deg,#ef4444,#dc2626); }
        .card-stat .icon{
            font-size:42px; opacity:.9;
        }
        .card-stat h3{ font-weight:800; margin:6px 0 0; }
        /* Panels */
        .panel{
            background:#fff; border:none; border-radius:16px; padding:20px;
            box-shadow: 0 10px 30px rgba(0,0,0,.06);
        }
        .section-title{ font-weight:700; }
        .quick-actions .btn{
            border-radius:12px; box-shadow:0 6px 16px rgba(0,0,0,.06);
        }
        /* Responsive */
        @media (max-width: 991.98px){
            :root{ --sidebar-w: 0px; }
            .sidebar{ transform:translateX(-100%); transition: transform .3s ease; }
            .sidebar.show{ transform:translateX(0); }
            .content{ margin-left:0; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside id="sidebar" class="sidebar">
    <div class="brand d-flex align-items-center justify-content-between">
        <span>⚙️ Admin Panel</span>
        <button class="btn btn-sm btn-outline-light d-lg-none" onclick="toggleSidebar()"><i class="bi bi-x-lg"></i></button>
    </div>
    <nav class="mt-2">
        <a class="nav-link active" href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a class="nav-link" href="manage_employees.php"><i class="bi bi-people-fill"></i> Manage Employees</a>
        <a class="nav-link" href="attendance.php"><i class="bi bi-clipboard-check-fill"></i> Attendance</a>
        <a class="nav-link" href="payslips.php"><i class="bi bi-cash-coin"></i> Payslips</a>
        <a class="nav-link" href="generate_payslip.php"><i class="bi bi-file-earmark-text-fill"></i> Generate Payslip</a>
        <a class="nav-link" href="announcements.php"><i class="bi bi-megaphone-fill"></i> Announcements</a>
        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
</aside>

<!-- Content -->
<div class="content">
    <!-- Topbar -->
    <div class="topbar">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary d-lg-none" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                    <button class="btn btn-outline-secondary" onclick="history.back()">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                </div>
                <span class="fs-5 fw-semibold">Admin Dashboard</span>
                <div class="ms-auto">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($adminName) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <!-- Main -->
    <div class="container-fluid p-4">

        <!-- Stat Cards -->
        <div class="row g-4 mb-2">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card-stat grad-primary p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="icon"><i class="bi bi-people-fill"></i></div>
                            <h3><?= $totEmp ?></h3>
                            <div>Total Employees</div>
                        </div>
                        <i class="bi bi-graph-up-arrow fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card-stat grad-success p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="icon"><i class="bi bi-building"></i></div>
                            <h3><?= $totDept ?></h3>
                            <div>Total Departments</div>
                        </div>
                        <i class="bi bi-diagram-3 fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card-stat grad-warning p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="icon"><i class="bi bi-hourglass-split"></i></div>
                            <h3><?= $pendingLeaves ?></h3>
                            <div>Pending Leaves</div>
                        </div>
                        <i class="bi bi-inboxes fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card-stat grad-danger p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="icon"><i class="bi bi-megaphone-fill"></i></div>
                            <h3><?= $annCount ?></h3>
                            <div>Announcements</div>
                        </div>
                        <i class="bi bi-bell fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-4">
            <div class="col-12">
                <div class="panel">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="section-title mb-0">🚀 Quick Actions</h5>
                    </div>
                    <div class="d-grid gap-2 mt-3 quick-actions">
                        <a href="manage_employees.php" class="btn btn-primary"><i class="bi bi-person-badge"></i> Manage Employees</a>
                        <a href="payslips.php" class="btn btn-success"><i class="bi bi-receipt"></i> View Payslips</a>
                        <a href="generate_payslip.php" class="btn btn-info text-white"><i class="bi bi-file-earmark-plus"></i> Create Payslip</a>
                        <a href="announcements.php" class="btn btn-warning"><i class="bi bi-megaphone"></i> Post Announcement</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcements (optional simple list) -->
        <div class="row g-4 mt-1">
            <div class="col-12">
                <div class="panel">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="section-title mb-0">📣 Latest Announcements</h5>
                        <a href="announcements.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <p class="text-muted mb-0 mt-2">Keep your team updated with important news and events.</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Mobile sidebar toggle
    function toggleSidebar(){
        const sb = document.getElementById('sidebar');
        sb.classList.toggle('show');
    }
</script>
</body>
</html>
