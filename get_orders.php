<?php
header('Access-Control-Allow-Origin: https://shopwarlergen.github.io');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

session_start();

// ⚠️ BẢO MẬT: Chỉ admin mới xem được
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['status' => 0, 'msg' => 'Vui lòng đăng nhập admin']);
    exit;
}

// ⚠️ THAY ĐỔI THÔNG TIN DATABASE CỦA BẠN Ở ĐÂY
$conn = new mysqli("localhost", "root", "", "webshop");
// VD với InfinityFree:
// $conn = new mysqli("sql123.epizy.com", "epiz_12345", "password_của_bạn", "epiz_12345_webshop");

$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['status' => 0, 'msg' => 'Lỗi kết nối database']);
    exit;
}

$filterStatus = $_GET['status'] ?? '';

// Lấy thống kê tổng quan
$stats = [
    'total' => 0,
    'pending' => 0,
    'processing' => 0,
    'completed' => 0,
    'revenue' => 0
];

// Tổng số đơn hàng
$result = $conn->query("SELECT COUNT(*) as total FROM orders_bloxfruits");
if($result) {
    $stats['total'] = $result->fetch_assoc()['total'];
}

// Đơn đang chờ
$result = $conn->query("SELECT COUNT(*) as count FROM orders_bloxfruits WHERE status='PENDING'");
if($result) {
    $stats['pending'] = $result->fetch_assoc()['count'];
}

// Đơn đang xử lý
$result = $conn->query("SELECT COUNT(*) as count FROM orders_bloxfruits WHERE status='PROCESSING'");
if($result) {
    $stats['processing'] = $result->fetch_assoc()['count'];
}

// Đơn hoàn thành
$result = $conn->query("SELECT COUNT(*) as count FROM orders_bloxfruits WHERE status='COMPLETED'");
if($result) {
    $stats['completed'] = $result->fetch_assoc()['count'];
}

// Tổng doanh thu (chỉ tính đơn hoàn thành)
$result = $conn->query("SELECT SUM(price) as revenue FROM orders_bloxfruits WHERE status='COMPLETED'");
if($result) {
    $row = $result->fetch_assoc();
    $stats['revenue'] = $row['revenue'] ?? 0;
}

// Lấy danh sách đơn hàng
$sql = "SELECT * FROM orders_bloxfruits";

// Lọc theo trạng thái nếu có
if($filterStatus && in_array($filterStatus, ['PENDING', 'PROCESSING', 'COMPLETED', 'CANCELLED'])) {
    $sql .= " WHERE status='$filterStatus'";
}

$sql .= " ORDER BY id DESC LIMIT 100"; // Giới hạn 100 đơn gần nhất

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
