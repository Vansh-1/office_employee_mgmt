<?php
session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// If admin, allow add/edit/delete
$isAdmin = ($_SESSION['role'] === 'admin');

// Handle new announcement (Admin only)
if ($isAdmin && isset($_POST['add_announcement'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    if (!empty($title) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO announcements (title, message, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$title, $message]);
    }
}

// Handle delete (Admin only)
if ($isAdmin && isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
}

// Fetch announcements
$stmt = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Announcements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h2 class="mb-4 text-center">📢 Announcements</h2>

    <!-- Admin can add announcements -->
    <?php if ($isAdmin): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Add New Announcement</h5>
                <form method="POST">
                    <div class="mb-3">
                        <input type="text" name="title" class="form-control" placeholder="Title" required>
                    </div>
                    <div class="mb-3">
                        <textarea name="message" class="form-control" rows="3" placeholder="Message" required></textarea>
                    </div>
                    <button type="submit" name="add_announcement" class="btn btn-primary">Post</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Announcements list -->
    <?php if (count($announcements) > 0): ?>
        <?php foreach ($announcements as $a): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($a['title']) ?></h5>
                    <p class="card-text"><?= nl2br(htmlspecialchars($a['message'])) ?></p>
                    <small class="text-muted">Posted on <?= $a['created_at'] ?></small>
                    <?php if ($isAdmin): ?>
                        <a href="?delete=<?= $a['id'] ?>" class="btn btn-sm btn-danger float-end"
                           onclick="return confirm('Delete this announcement?')">Delete</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info text-center">No announcements yet.</div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="<?= $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'employee_dashboard.php' ?>" 
           class="btn btn-secondary">⬅ Back to Dashboard</a>
    </div>
</div>

</body>
</html>
