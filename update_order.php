<?php
require_once 'config.php';

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Kiểm tra quyền admin
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['status' => 0, 'msg' => 'Không có quyền']);
    exit;
}

$conn = getConnection();

$order_id = (int)($_POST['order_id'] ?? 0);
$new_status = $_POST['status'] ?? '';
$progress = isset($_POST['progress']) ? (int)$_POST['progress'] : null;

if(!$order_id) {
    echo json_encode(['status' => 0, 'msg' => 'Thiếu order_id']);
    exit;
}

// Validate status
$validStatuses = ['PENDING', 'PROCESSING', 'COMPLETED', 'CANCELLED'];
if($new_status && !in_array($new_status, $validStatuses)) {
    echo json_encode(['status' => 0, 'msg' => 'Trạng thái không hợp lệ']);
    exit;
}

// Cập nhật đơn hàng
if($new_status && $progress !== null) {
    // Cập nhật cả status và progress
    $stmt = $conn->prepare("UPDATE orders_bloxfruits SET status=?, progress=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("sii", $new_status, $progress, $order_id);
} else if($new_status) {
    // Chỉ cập nhật status
    if($new_status === 'COMPLETED') {
        $stmt = $conn->prepare("UPDATE orders_bloxfruits SET status=?, progress=100, updated_at=NOW() WHERE id=?");
    } else {
        $stmt = $conn->prepare("UPDATE orders_bloxfruits SET status=?, updated_at=NOW() WHERE id=?");
    }
    $stmt->bind_param("si", $new_status, $order_id);
} else if($progress !== null) {
    // Chỉ cập nhật progress
    $stmt = $conn->prepare("UPDATE orders_bloxfruits SET progress=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("ii", $progress, $order_id);
} else {
    echo json_encode(['status' => 0, 'msg' => 'Không có gì để cập nhật']);
    exit;
}

$success = $stmt->execute();
$stmt->close();
$conn->close();

if($success) {
    echo json_encode(['status' => 1, 'msg' => 'Cập nhật thành công']);
} else {
    echo json_encode(['status' => 0, 'msg' => 'Lỗi cập nhật']);
}
?>
