<?php
$host='127.0.0.1';
$dbname='office_employee_mgmt';
$username='root';
$password='';
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('DB Connection failed: '.$e->getMessage());
}
?>
