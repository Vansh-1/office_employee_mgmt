<?php
require 'config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, max-age=0');

$empId = (int)($_GET['emp_id'] ?? 0);
if ($empId <= 0) {
    echo json_encode(['items'=>[], 'lastTs'=>null]);
    exit;
}

// Optional incremental fetch: items strictly newer than this timestamp
$sinceRaw = $_GET['since'] ?? null;
$sinceTs = null;
if ($sinceRaw) {
    try {
        $sinceTs = (new DateTimeImmutable($sinceRaw))->format('Y-m-d H:i:s');
    } catch (Throwable $e) {
        $sinceTs = null; // ignore invalid
    }
}

// Select only necessary columns; limit results
if ($sinceTs) {
    $stmt = $conn->prepare("SELECT id, title, message, created_at FROM announcements WHERE created_at > ? ORDER BY created_at ASC LIMIT 100");
    $stmt->execute([$sinceTs]);
} else {
    $stmt = $conn->query("SELECT id, title, message, created_at FROM announcements ORDER BY created_at DESC LIMIT 50");
}
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compute unread flags for returned IDs only
$ids = array_column($rows, 'id');
$unreadMap = [];
if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$empId], $ids);
    $unreadStmt = $conn->prepare("SELECT announcement_id FROM announcement_reads WHERE employee_id=? AND read_at IS NULL AND announcement_id IN ($placeholders)");
    $unreadStmt->execute($params);
    foreach ($unreadStmt->fetchAll(PDO::FETCH_COLUMN) as $aid) {
        $unreadMap[(int)$aid] = true;
    }
}

$resultItems = [];
$lastTs = $sinceTs;
foreach ($rows as $a) {
    $createdAt = $a['created_at'];
    if ($lastTs === null || $createdAt > $lastTs) { $lastTs = $createdAt; }
    $resultItems[] = [
        'id' => (int)$a['id'],
        'title' => htmlspecialchars($a['title']),
        'message' => htmlspecialchars($a['message']),
        'date' => date('d M Y', strtotime($createdAt)),
        'created_at' => $createdAt,
        'unread' => !empty($unreadMap[(int)$a['id']])
    ];
}

// Mark fetched items as read for this employee (only those that were unread)
if (!empty($unreadMap)) {
    $idsToMark = array_keys($unreadMap);
    $placeholders = implode(',', array_fill(0, count($idsToMark), '?'));
    $params = array_merge([$empId], $idsToMark);
    $markReadStmt = $conn->prepare("UPDATE announcement_reads SET read_at = NOW() WHERE employee_id = ? AND announcement_id IN ($placeholders) AND read_at IS NULL");
    $markReadStmt->execute($params);
}

echo json_encode(['items' => $resultItems, 'lastTs' => $lastTs]);
