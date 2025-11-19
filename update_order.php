<?php
header('Access-Control-Allow-Origin: https://shopwarlergen.github.io');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');

session_start();

// ⚠️ BẢO MẬT: Chỉ admin mới cập nhật được
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['status' => 0, 'msg' => 'Vui lòng đăng nhập admin']);
    exit;
}

// ⚠️ THAY THÔNG TIN DATABASE
$conn = new mysqli("localhost", "root", "", "webshop");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['status' => 0, 'msg' => 'Lỗi database']);
    exit;
}

$order_id = (int)($_POST['order_id'] ?? 0);
$new_status = $_POST['status'] ?? '';

if(!$order_id || !$new_status) {
    echo json_encode(['status' => 0, 'msg' => 'Thiếu thông tin']);
    exit;
}

// Validate status
$validStatuses = ['PENDING', 'PROCESSING', 'COMPLETED', 'CANCELLED'];
if(!in_array($new_status, $validStatuses)) {
    echo json_encode(['status' => 0, 'msg' => 'Trạng thái không hợp lệ']);
    exit;
}

// Cập nhật trạng thái
if($new_status === 'COMPLETED') {
    $stmt = $conn->prepare("UPDATE orders_bloxfruits SET status=?, completed_at=NOW() WHERE id=?");
} else {
    $stmt = $conn->prepare("UPDATE orders_bloxfruits SET status=? WHERE id=?");
}

$stmt->bind_param("si", $new_status, $order_id);
$success = $stmt->execute();
$stmt->close();

if($success) {
    echo json_encode(['status' => 1, 'msg' => 'Cập nhật thành công']);
} else {
    echo json_encode(['status' => 0, 'msg' => 'Lỗi cập nhật']);
}

$conn->close();
?>
