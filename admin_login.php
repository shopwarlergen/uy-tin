<?php
header('Access-Control-Allow-Origin: https://shopwarlergen.github.io');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// ⚠️ THAY ĐỔI USERNAME VÀ PASSWORD ADMIN Ở ĐÂY
$ADMIN_USERNAME = 'admin';
$ADMIN_PASSWORD = 'admin123'; // ⚠️ NÊN ĐỔI MẬT KHẨU MẠNH HƠN

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if(!$username || !$password) {
    echo json_encode(['status' => 0, 'msg' => 'Thiếu thông tin']);
    exit;
}

// Kiểm tra thông tin đăng nhập
if($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
    // Đăng nhập thành công
    $_SESSION['is_admin'] = true;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_login_time'] = time();
    
    echo json_encode([
        'status' => 1,
        'msg' => 'Đăng nhập thành công',
        'username' => $username
    ]);
} else {
    // Sai thông tin
    echo json_encode([
        'status' => 0,
        'msg' => 'Sai tên đăng nhập hoặc mật khẩu'
    ]);
}
?>
