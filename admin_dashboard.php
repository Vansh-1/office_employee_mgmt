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
    $pendingLeaves = (int)$conn->query("SELECT COUNT(*) FROM leaves WHERE status='Pending'")->fetchColumn();
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
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
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

        /* ——— Modern enhancements ——— */
        :root {
            --bg: #f4f6f9;
            --card-bg: #ffffff;
            --text: #0f172a;
            --muted: #6b7280;
            --brand-1: #4e73df; /* indigo */
            --brand-2: #1cc88a; /* emerald */
        }
        [data-theme="dark"] {
            --bg: #0b1220;
            --card-bg: #0f172a;
            --text: #e5e7eb;
            --muted: #9ca3af;
        }
        body { background: var(--bg); color: var(--text); }
        .glass { background: var(--card-bg); backdrop-filter: saturate(160%) blur(6px); }
        .hover-lift { transition: transform .15s ease, box-shadow .15s ease; }
        .hover-lift:hover { transform: translateY(-4px); box-shadow: 0 16px 34px rgba(0,0,0,.12) !important; }
        .brand-gradient { background: linear-gradient(135deg, var(--brand-1), var(--brand-2)); }

        .kpi-card { border: none; border-radius: 18px; color: #fff; overflow: hidden; box-shadow: 0 10px 24px rgba(0,0,0,.08); }
        .kpi-card .icon { font-size: 44px; opacity: .95; }
        .kpi-card .value { font-weight: 800; font-size: 2rem; margin-top: 6px; }
        .kpi-card .label { opacity: .9; }

        .action-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px,1fr)); gap: 14px; }
        .action-card { display: flex; align-items: center; gap: 12px; padding: 16px; border-radius: 14px; text-decoration: none; color: var(--text); background: var(--card-bg); box-shadow: 0 8px 22px rgba(0,0,0,.06); border: 1px solid rgba(0,0,0,.04); }
        .action-card:hover { text-decoration: none; }
        .action-card .ic { width: 44px; height: 44px; display: grid; place-items: center; border-radius: 10px; color: #fff; }
        .action-card .t { font-weight: 600; }
        .action-card .d { color: var(--muted); font-size: .9rem; }

        .topbar .search { max-width: 420px; }
        .topbar .form-control { background: var(--card-bg); border-color: rgba(0,0,0,.08); color: var(--text); }
        .topbar .form-control::placeholder { color: var(--muted); }
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
        <a class="nav-link" href="manage_leaves.php"><i class="bi bi-calendar-check"></i> Manage Leave</a>
        <a class="nav-link" href="attendance.php"><i class="bi bi-clipboard-check-fill"></i> Attendance</a>
        <a class="nav-link" href="payslips.php"><i class="bi bi-cash-coin"></i> Payslips</a>
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
                <form class="d-none d-md-flex ms-3 flex-grow-1 search" role="search">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                        <input class="form-control" type="search" placeholder="Search employees, departments…" aria-label="Search">
                    </div>
                </form>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary" id="themeToggle" type="button" aria-label="Toggle theme"><i class="bi bi-moon-stars"></i></button>
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

        <!-- KPI Cards -->
        <div class="row g-4 mb-2">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card brand-gradient p-3 hover-lift">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="icon"><i class="bi bi-people-fill"></i></div>
                            <div class="value"><?= $totEmp ?></div>
                            <div class="label">Total Employees</div>
                        </div>
                        <i class="bi bi-graph-up-arrow fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card p-3 hover-lift" style="background:linear-gradient(135deg,#10b981,#059669);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="icon"><i class="bi bi-building"></i></div>
                            <div class="value"><?= $totDept ?></div>
                            <div class="label">Total Departments</div>
                        </div>
                        <i class="bi bi-diagram-3 fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card p-3 hover-lift" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="icon"><i class="bi bi-hourglass-split"></i></div>
                            <div class="value"><?= $pendingLeaves ?></div>
                            <div class="label">Pending Leaves</div>
                        </div>
                        <i class="bi bi-inboxes fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card p-3 hover-lift" style="background:linear-gradient(135deg,#ef4444,#dc2626);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="icon"><i class="bi bi-megaphone-fill"></i></div>
                            <div class="value"><?= $annCount ?></div>
                            <div class="label">Announcements</div>
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
                    <div class="action-grid mt-2">
                        <a href="manage_employees.php" class="action-card hover-lift">
                            <span class="ic brand-gradient"><i class="bi bi-person-badge"></i></span>
                            <span>
                                <div class="t">Manage Employees</div>
                                <div class="d">Add, edit and remove employees</div>
                            </span>
                        </a>
                        <a href="manage_leaves.php" class="action-card hover-lift">
                            <span class="ic" style="background:#0ea5e9"><i class="bi bi-calendar-check"></i></span>
                            <span>
                                <div class="t">Manage Leave Applications</div>
                                <div class="d">Review and approve requests</div>
                            </span>
                        </a>
                        <a href="payslips.php" class="action-card hover-lift">
                            <span class="ic" style="background:#22c55e"><i class="bi bi-receipt"></i></span>
                            <span>
                                <div class="t">View Payslips</div>
                                <div class="d">Download issued payslips</div>
                            </span>
                        </a>
                        <a href="generate_payslip.php" class="action-card hover-lift">
                            <span class="ic" style="background:#64748b"><i class="bi bi-file-earmark-plus"></i></span>
                            <span>
                                <div class="t">Create Payslip</div>
                                <div class="d">Generate a new payslip</div>
                            </span>
                        </a>
                        <a href="announcements.php" class="action-card hover-lift">
                            <span class="ic" style="background:#f59e0b"><i class="bi bi-megaphone"></i></span>
                            <span>
                                <div class="t">Post Announcement</div>
                                <div class="d">Notify the whole team</div>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcements -->
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

<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebar(){
        document.getElementById('sidebar').classList.toggle('show');
    }
    // Theme toggle with persistence
    (function(){
        const key = 'ems-theme';
        const btn = document.getElementById('themeToggle');
        function apply(theme){
            if (theme === 'dark') document.documentElement.setAttribute('data-theme','dark');
            else document.documentElement.removeAttribute('data-theme');
            btn.innerHTML = theme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
        }
        let theme = localStorage.getItem(key) || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        apply(theme);
        btn.addEventListener('click', ()=>{
            theme = (theme === 'dark') ? 'light' : 'dark';
            localStorage.setItem(key, theme);
            apply(theme);
        });
    })();
</script>
</body>
</html>
