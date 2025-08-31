<?php
session_start();
require 'config.php';

// Role-based check (match dashboard)
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee'){
    header("Location: login.php");
    exit;
}

$empId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Announcements - Employee</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: #f4f6f9; padding: 30px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.card-announcement { border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 15px; transition: transform 0.2s ease; }
.card-announcement:hover { transform: translateY(-3px); }
.card-announcement .card-header { background: #ffc107; color: #212529; font-weight: 600; font-size: 1.2rem; display:flex; justify-content:space-between; align-items:center; }
.card-announcement .card-body { font-size: 1rem; }
.unread { border-left: 5px solid #0d6efd; }
.topbar { background: #fff; padding: 10px 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; }
</style>
</head>
<body>
<div class="container">
<div class="topbar mb-3">
    <a href="employee_dashboard.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    <h4 class="mb-0">📣 Announcements</h4>
</div>

<div id="announcements-container"></div>
</div>

<script>
const empId = <?= json_encode($empId) ?>;

function fetchAnnouncements() {
    fetch('fetch_announcements.php?emp_id=' + empId)
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById('announcements-container');
        container.innerHTML = '';
        if(data.length === 0){
            container.innerHTML = '<p class="text-muted">No announcements yet.</p>';
            return;
        }
        data.forEach(a => {
            const unreadClass = a.unread ? 'unread' : '';
            const badge = a.unread ? '<span class="badge bg-primary ms-2">New</span>' : '';
            const card = `
                <div class="card card-announcement ${unreadClass}">
                    <div class="card-header">
                        ${a.title} ${badge}
                    </div>
                    <div class="card-body">
                        ${a.message.replace(/\n/g, '<br>')}
                        <div class="text-end text-muted mt-2">${a.date}</div>
                    </div>
                </div>
            `;
            container.innerHTML += card;
        });
    });
}

// Initial fetch
fetchAnnouncements();

// Fetch every 10 seconds
setInterval(fetchAnnouncements, 10000);
</script>
</body>
</html>
