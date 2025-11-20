<?php
// File này đặt trong thư mục api/
// Giúp bypass CORS khi gọi từ GitHub Pages

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Lấy action từ query string
$action = $_GET['action'] ?? '';

// Định tuyến đến file PHP tương ứng
switch($action) {
    case 'register':
        include 'user_register.php';
        break;
    case 'login':
        include 'user_login.php';
        break;
    case 'check_login':
        include 'check_login.php';
        break;
    case 'logout':
        include 'user_logout.php';
        break;
    case 'naptien':
        include 'naptien.php';
        break;
    case 'check_napthe':
        include 'check_napthe.php';
        break;
    case 'order':
        include 'order_bloxfruits.php';
        break;
    default:
        echo json_encode(['status' => 0, 'msg' => 'Invalid action']);
}
?>
