<?php
require_once 'config.php';

session_start();
header('Content-Type: application/json');

$conn = getConnection();
$action = $_POST['action'] ?? '';

// ===== ĐĂNG NHẬP =====
if($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if(!$username || !$password) {
        echo json_encode(['status' => 0, 'msg' => 'Thiếu thông tin']);
        exit;
    }

    // Kiểm tra admin
    if($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = true;
        
        echo json_encode([
            'status' => 1,
            'msg' => 'Đăng nhập admin thành công',
            'username' => $username,
            'balance' => 0,
            'is_admin' => true
        ]);
        exit;
    }

    // Kiểm tra user thường
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()) {
        if(password_verify($password, $row['password'])) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['is_admin'] = false;
            
            echo json_encode([
                'status' => 1,
                'msg' => 'Đăng nhập thành công',
                'username' => $row['username'],
                'balance' => $row['money'],
                'is_admin' => false
            ]);
        } else {
            echo json_encode(['status' => 0, 'msg' => 'Sai mật khẩu']);
        }
    } else {
        echo json_encode(['status' => 0, 'msg' => 'Tài khoản không tồn tại']);
    }
    $stmt->close();
}

// ===== ĐĂNG KÝ =====
else if($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if(!$username || !$password) {
        echo json_encode(['status' => 0, 'msg' => 'Thiếu thông tin']);
        exit;
    }

    if(!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        echo json_encode(['status' => 0, 'msg' => 'Tên đăng nhập chỉ chữ cái và số']);
        exit;
    }

    if(strlen($password) < 6) {
        echo json_encode(['status' => 0, 'msg' => 'Mật khẩu tối thiểu 6 ký tự']);
        exit;
    }

    // Kiểm tra username đã tồn tại
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        echo json_encode(['status' => 0, 'msg' => 'Tên đăng nhập đã tồn tại']);
        exit;
    }

    // Tạo tài khoản
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users(username, password, money, created_at) VALUES (?, ?, 0, NOW())");
    $stmt->bind_param("ss", $username, $hashed_password);

    if($stmt->execute()) {
        echo json_encode([
            'status' => 1,
            'msg' => 'Đăng ký thành công',
            'username' => $username
        ]);
    } else {
        echo json_encode(['status' => 0, 'msg' => 'Lỗi tạo tài khoản']);
    }
    $stmt->close();
}

// ===== KIỂM TRA ĐĂNG NHẬP =====
else if($action === 'check') {
    if(isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
        $is_admin = $_SESSION['is_admin'] ?? false;
        
        if($is_admin) {
            echo json_encode([
                'status' => 1,
                'username' => $username,
                'balance' => 0,
                'is_admin' => true
            ]);
        } else {
            $stmt = $conn->prepare("SELECT money FROM users WHERE username=?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($row = $result->fetch_assoc()) {
                echo json_encode([
                    'status' => 1,
                    'username' => $username,
                    'balance' => $row['money'],
                    'is_admin' => false
                ]);
            } else {
                echo json_encode(['status' => 0]);
            }
            $stmt->close();
        }
    } else {
        echo json_encode(['status' => 0]);
    }
}

// ===== ĐĂNG XUẤT =====
else if($action === 'logout') {
    session_unset();
    session_destroy();
    echo json_encode(['status' => 1, 'msg' => 'Đã đăng xuất']);
}

else {
    echo json_encode(['status' => 0, 'msg' => 'Invalid action']);
}

$conn->close();
?>
