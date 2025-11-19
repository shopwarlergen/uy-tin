<?php
header('Access-Control-Allow-Origin: https://shopwarlergen.github.io');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

session_start();

if(isset($_SESSION['username'])) {
    // ⚠️ THAY ĐỔI THÔNG TIN DATABASE
    $conn = new mysqli("localhost", "root", "", "webshop");
    $conn->set_charset("utf8");
    
    if ($conn->connect_error) {
        echo json_encode(['status' => 0, 'msg' => 'Lỗi database']);
        exit;
    }
    
    // Lấy số dư mới nhất
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT money FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()) {
        echo json_encode([
            'status' => 1,
            'username' => $username,
            'balance' => $row['money']
        ]);
    } else {
        echo json_encode(['status' => 0, 'msg' => 'Không tìm thấy user']);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 0, 'msg' => 'Chưa đăng nhập']);
}
?>
