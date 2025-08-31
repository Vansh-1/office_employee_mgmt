<?php
require 'config.php';

$empId = $_GET['emp_id'] ?? 0;
if(!$empId) exit(json_encode([]));

// Fetch all announcements
$announcements = $conn->query('SELECT * FROM announcements ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

// Fetch unread announcements for this employee
$unreadStmt = $conn->prepare("SELECT announcement_id FROM announcement_reads WHERE employee_id=? AND read_at IS NULL");
$unreadStmt->execute([$empId]);
$unreadIds = $unreadStmt->fetchAll(PDO::FETCH_COLUMN);

$result = [];
foreach($announcements as $a){
    $result[] = [
        'id' => $a['id'],
        'title' => htmlspecialchars($a['title']),
        'message' => htmlspecialchars($a['message']),
        'date' => date('d M Y', strtotime($a['date'])),
        'unread' => in_array($a['id'], $unreadIds)
    ];
}

// Mark all unread as read
$markReadStmt = $conn->prepare("UPDATE announcement_reads SET read_at = NOW() WHERE employee_id = ? AND read_at IS NULL");
$markReadStmt->execute([$empId]);

header('Content-Type: application/json');
echo json_encode($result);
