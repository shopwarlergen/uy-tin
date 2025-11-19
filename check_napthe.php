<?php
// CORS - Cho phép GitHub Pages gọi API
header('Access-Control-Allow-Origin: https://shopwarlergen.github.io');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

if(!isset($_SESSION['username'])){
    echo json_encode(['status' => 0, 'msg' => 'Chưa đăng nhập']);
    exit;
}

$username = $_SESSION['username'];
$request_id = $_GET['request_id'] ?? '';

if(!$request_id){
    echo json_encode(['status' => 0, 'msg' => 'Thiếu request_id']);
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

$stmt = $conn->prepare("
    SELECT status, real_amount, message, created_at, updated_at 
    FROM history_napthe 
    WHERE request_id=? AND username=?
");
$stmt->bind_param("ss", $request_id, $username);
$stmt->execute();
$result = $stmt->get_result();

if($row = $result->fetch_assoc()){
    echo json_encode([
        'status' => 1,
        'data' => $row
    ]);
} else {
    echo json_encode(['status' => 0, 'msg' => 'Không tìm thấy giao dịch']);
}

$stmt->close();
$conn->close();
?>
