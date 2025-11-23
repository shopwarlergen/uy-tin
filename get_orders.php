<?php
require_once 'config.php';

session_start();
header('Content-Type: application/json');

// Kiểm tra quyền admin
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['status' => 0, 'msg' => 'Không có quyền truy cập']);
    exit;
}

$conn = getConnection();
$filterStatus = $_GET['status'] ?? '';
$username = $_GET['username'] ?? ''; // Lọc theo user (cho trang account)

// Lấy thống kê tổng quan
$stats = [
    'total' => 0,
    'pending' => 0,
    'processing' => 0,
    'completed' => 0,
    'revenue' => 0
];

// Tổng số đơn hàng
$sql = "SELECT COUNT(*) as total FROM orders_bloxfruits";
if($username) $sql .= " WHERE username='$username'";
$result = $conn->query($sql);
if($result) {
    $stats['total'] = $result->fetch_assoc()['total'];
}

// Đơn đang chờ
$sql = "SELECT COUNT(*) as count FROM orders_bloxfruits WHERE status='PENDING'";
if($username) $sql .= " AND username='$username'";
$result = $conn->query($sql);
if($result) {
    $stats['pending'] = $result->fetch_assoc()['count'];
}

// Đơn đang xử lý
$sql = "SELECT COUNT(*) as count FROM orders_bloxfruits WHERE status='PROCESSING'";
if($username) $sql .= " AND username='$username'";
$result = $conn->query($sql);
if($result) {
    $stats['processing'] = $result->fetch_assoc()['count'];
}

// Đơn hoàn thành
$sql = "SELECT COUNT(*) as count FROM orders_bloxfruits WHERE status='COMPLETED'";
if($username) $sql .= " AND username='$username'";
$result = $conn->query($sql);
if($result) {
    $stats['completed'] = $result->fetch_assoc()['count'];
}

// Tổng doanh thu
$sql = "SELECT SUM(price) as revenue FROM orders_bloxfruits WHERE status='COMPLETED'";
if($username) $sql .= " AND username='$username'";
$result = $conn->query($sql);
if($result) {
    $row = $result->fetch_assoc();
    $stats['revenue'] = $row['revenue'] ?? 0;
}

// Lấy danh sách đơn hàng
$sql = "SELECT * FROM orders_bloxfruits";
$conditions = [];

if($username) {
    $conditions[] = "username='$username'";
}

if($filterStatus && in_array($filterStatus, ['PENDING', 'PROCESSING', 'COMPLETED', 'CANCELLED'])) {
    $conditions[] = "status='$filterStatus'";
}

if(count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY id DESC LIMIT 100";

$result = $conn->query($sql);
$orders = [];

if($result) {
    while($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

$conn->close();

echo json_encode([
    'status' => 1,
    'stats' => $stats,
    'orders' => $orders
]);
?>
