<?php
header('Access-Control-Allow-Origin: https://shopwarlergen.github.io');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

session_start();

if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    echo json_encode([
        'status' => 1,
        'username' => $_SESSION['admin_username'] ?? 'admin'
    ]);
} else {
    echo json_encode([
        'status' => 0,
        'msg' => 'Chưa đăng nhập'
    ]);
}
?>
