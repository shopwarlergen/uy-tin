<?php
header('Access-Control-Allow-Origin: https://shopwarlergen.github.io');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

session_start();

// Xóa tất cả session
session_unset();
session_destroy();

echo json_encode([
    'status' => 1,
    'msg' => 'Đăng xuất thành công'
]);
?>
