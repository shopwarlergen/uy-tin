<?php
// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'webshop');

// Tạo kết nối
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
    
    if ($conn->connect_error) {
        die(json_encode(['status' => 0, 'msg' => 'Lỗi kết nối database']));
    }
    
    return $conn;
}

// Cấu hình Thesieure
define('PARTNER_ID', '28186057286');
define('PARTNER_KEY', '8985dac6ce1a699863b4c3e2f2086fed');

// Admin account
define('ADMIN_USERNAME', 'warlergen');
define('ADMIN_PASSWORD', 'MG@M*XZ14tQM@sm1%bCp9z%wGgtdEXvT'); // Hash trong production!
?>
