<?php
header('Access-Control-Allow-Origin: https://shopwarlergen.github.io');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ⚠️ THAY ĐỔI THÔNG TIN DATABASE
$conn = new mysqli("localhost", "root", "", "webshop");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['status' => 0, 'msg' => 'Lỗi database']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validate input
if(!$username || !$email || !$password) {
    echo json_encode(['status' => 0, 'msg' => 'Thiếu thông tin']);
    exit;
}

// Validate username (chỉ chữ cái và số)
if(!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
    echo json_encode(['status' => 0, 'msg' => 'Tên đăng nhập chỉ được chứa chữ cái và số']);
    exit;
}

// Validate email
if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 0, 'msg' => 'Email không hợp lệ']);
    exit;
}

// Validate password length
if(strlen($password) < 6) {
    echo json_encode(['status' => 0, 'msg' => 'Mật khẩu phải có ít nhất 6 ký tự']);
    exit;
}

// Kiểm tra username đã tồn tại chưa
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    echo json_encode(['status' => 0, 'msg' => 'Tên đăng nhập đã tồn tại']);
    exit;
}
$stmt->close();

// Kiểm tra email đã tồn tại chưa
$stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    echo json_encode(['status' => 0, 'msg' => 'Email đã được sử dụng']);
    exit;
}
$stmt->close();

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Tạo tài khoản mới
$stmt = $conn->prepare("INSERT INTO users(username, email, password, money, created_at) VALUES (?, ?, ?, 0, NOW())");
$stmt->bind_param("sss", $username, $email, $hashed_password);

if($stmt->execute()) {
    echo json_encode([
        'status' => 1,
        'msg' => 'Đăng ký thành công',
        'username' => $username
    ]);
} else {
    echo json_encode([
        'status' => 0, 
        'msg' => 'Lỗi tạo tài khoản: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
