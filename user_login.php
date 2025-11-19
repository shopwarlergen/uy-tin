<?php
header('Access-Control-Allow-Origin: https://shopwarlergen.github.io');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// ⚠️ THAY ĐỔI THÔNG TIN DATABASE
$conn = new mysqli("localhost", "root", "", "webshop");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['status' => 0, 'msg' => 'Lỗi database']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if(!$username || !$password) {
    echo json_encode(['status' => 0, 'msg' => 'Thiếu thông tin']);
    exit;
}

// Tìm user theo username hoặc email
$stmt = $conn->prepare("SELECT * FROM users WHERE username=? OR email=?");
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if($row = $result->fetch_assoc()) {
    // Kiểm tra mật khẩu
    if(password_verify($password, $row['password'])) {
        // Đăng nhập thành công
        $_SESSION['username'] = $row['username'];
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['login_time'] = time();
        
        echo json_encode([
            'status' => 1,
            'msg' => 'Đăng nhập thành công',
            'username' => $row['username'],
            'balance' => $row['money']
        ]);
    } else {
        echo json_encode(['status' => 0, 'msg' => 'Sai mật khẩu']);
    }
} else {
    echo json_encode(['status' => 0, 'msg' => 'Tài khoản không tồn tại']);
}

$stmt->close();
$conn->close();
?>
