<?php
require_once 'config.php';

session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['username'])){
    echo json_encode(['status' => 0, 'msg' => 'Bạn cần đăng nhập để đặt dịch vụ']);
    exit;
}

$username = $_SESSION['username'];
$conn = getConnection();

// Lấy dữ liệu từ form
$service = trim($_POST['service'] ?? '');
$price = (int)($_POST['price'] ?? 0);
$game_username = trim($_POST['username'] ?? '');
$game_password = trim($_POST['password'] ?? '');
$note = trim($_POST['note'] ?? '');

// Validate input
if(!$service || !$price || !$game_username || !$game_password){
    echo json_encode(['status' => 0, 'msg' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

// Validate price
if($price <= 0 || $price > 10000000){
    echo json_encode(['status' => 0, 'msg' => 'Giá không hợp lệ']);
    exit;
}

// Kiểm tra số dư của user
$stmt = $conn->prepare("SELECT money FROM users WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if($row = $result->fetch_assoc()){
    $balance = (int)$row['money'];
    
    if($balance < $price){
        echo json_encode([
            'status' => 0, 
            'msg' => 'Số dư không đủ. Bạn cần thêm ' . number_format($price - $balance) . 'đ'
        ]);
        exit;
    }
} else {
    echo json_encode(['status' => 0, 'msg' => 'Không tìm thấy tài khoản']);
    exit;
}
$stmt->close();

// Bắt đầu transaction
$conn->begin_transaction();

try {
    // Trừ tiền khỏi tài khoản
    $stmt = $conn->prepare("UPDATE users SET money = money - ? WHERE username=?");
    $stmt->bind_param("is", $price, $username);
    $stmt->execute();
    $stmt->close();
    
    // Lưu đơn hàng vào database
    $stmt = $conn->prepare("
        INSERT INTO orders_bloxfruits(username, service, price, game_username, game_password, note, status, progress, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'PENDING', 0, NOW())
    ");
    $stmt->bind_param("ssisss", $username, $service, $price, $game_username, $game_password, $note);
    $stmt->execute();
    $order_id = $conn->insert_id;
    $stmt->close();
    
    // Lấy số dư mới
    $stmt = $conn->prepare("SELECT money FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $new_balance = $result->fetch_assoc()['money'];
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'status' => 1,
        'msg' => 'Đặt dịch vụ thành công! Chúng tôi sẽ liên hệ bạn trong vòng 5 phút.',
        'order_id' => $order_id,
        'new_balance' => $new_balance
    ]);
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    echo json_encode(['status' => 0, 'msg' => 'Lỗi xử lý đơn hàng. Vui lòng thử lại!']);
}

$conn->close();
?>
