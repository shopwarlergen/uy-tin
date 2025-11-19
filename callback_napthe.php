<?php
// File này sẽ nhận callback từ Thesieure
header('Content-Type: application/json');

// ⚠️ THAY API KEY CỦA BẠN Ở ĐÂY
$partner_key = '8985dac6ce1a699863b4c3e2f2086fed';

// Lấy dữ liệu từ callback
$status      = $_GET['status'] ?? '';       // 1=thành công, 2=sai mệnh giá, 3=thẻ lỗi, 99=chờ xử lý
$message     = $_GET['message'] ?? '';
$request_id  = $_GET['request_id'] ?? '';
$declared_value = $_GET['declared_value'] ?? 0; // Mệnh giá khai báo
$value       = $_GET['value'] ?? 0;         // Mệnh giá thực
$amount      = $_GET['amount'] ?? 0;        // Số tiền thực nhận (đã trừ phí)
$code        = $_GET['code'] ?? '';         // PIN
$serial      = $_GET['serial'] ?? '';       // SERI
$telco       = $_GET['telco'] ?? '';
$trans_id    = $_GET['trans_id'] ?? '';
$callback_sign = $_GET['callback_sign'] ?? '';

// Xác thực callback_sign để đảm bảo request từ Thesieure
$sign = md5($partner_key . $code . $serial);
if($sign !== $callback_sign){
    echo json_encode(['status' => 0, 'msg' => 'Invalid signature']);
    exit;
}

// ⚠️ THAY ĐỔI THÔNG TIN DATABASE CỦA BẠN Ở ĐÂY
$conn = new mysqli("localhost", "root", "", "webshop");
// VD với InfinityFree:
// $conn = new mysqli("sql123.epizy.com", "epiz_12345", "password_của_bạn", "epiz_12345_webshop");

$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['status' => 0, 'msg' => 'Database error']);
    exit;
}

// Kiểm tra request_id có tồn tại không
$stmt = $conn->prepare("SELECT username, status FROM history_napthe WHERE request_id=?");
$stmt->bind_param("s", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    echo json_encode(['status' => 0, 'msg' => 'Request not found']);
    exit;
}

$row = $result->fetch_assoc();
$username = $row['username'];
$currentStatus = $row['status'];
$stmt->close();

// Nếu đã xử lý rồi thì không xử lý nữa (tránh callback trùng)
if($currentStatus == 'SUCCESS' || $currentStatus == 'FAILED'){
    echo json_encode(['status' => 1, 'msg' => 'Already processed']);
    exit;
}

// Xử lý theo trạng thái
if($status == '1'){
    // ========== THÀNH CÔNG - CỘNG TIỀN ==========
    $realAmount = (int)$amount;
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // Cộng tiền vào tài khoản
        $stmt = $conn->prepare("UPDATE users SET money = money + ? WHERE username=?");
        $stmt->bind_param("is", $realAmount, $username);
        $stmt->execute();
        $stmt->close();
        
        // Cập nhật trạng thái thẻ
        $stmt = $conn->prepare("
            UPDATE history_napthe 
            SET status='SUCCESS', real_amount=?, message=?, trans_id=?, updated_at=NOW()
            WHERE request_id=?
        ");
        $stmt->bind_param("isss", $realAmount, $message, $trans_id, $request_id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode(['status' => 1, 'msg' => 'Success']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 0, 'msg' => 'Transaction failed']);
    }
    
} elseif($status == '2'){
    // ========== SAI MỆNH GIÁ - CỘNG TIỀN THEO MỆNH GIÁ THỰC ==========
    $realAmount = (int)$amount;
    
    $conn->begin_transaction();
    
    try {
        if($realAmount > 0){
            $stmt = $conn->prepare("UPDATE users SET money = money + ? WHERE username=?");
            $stmt->bind_param("is", $realAmount, $username);
            $stmt->execute();
            $stmt->close();
        }
        
        $stmt = $conn->prepare("
            UPDATE history_napthe 
            SET status='WRONG_AMOUNT', real_amount=?, message=?, trans_id=?, updated_at=NOW()
            WHERE request_id=?
        ");
        $stmt->bind_param("isss", $realAmount, $message, $trans_id, $request_id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode(['status' => 1, 'msg' => 'Wrong amount processed']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 0, 'msg' => 'Transaction failed']);
    }
    
} elseif($status == '3'){
    // ========== THẺ LỖI - KHÔNG CỘNG TIỀN ==========
    $stmt = $conn->prepare("
        UPDATE history_napthe 
        SET status='FAILED', message=?, trans_id=?, updated_at=NOW()
        WHERE request_id=?
    ");
    $stmt->bind_param("sss", $message, $trans_id, $request_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['status' => 1, 'msg' => 'Card invalid']);
    
} elseif($status == '99'){
    // ========== ĐANG CHỜ XỬ LÝ - GIỮ NGUYÊN TRẠNG THÁI ==========
    echo json_encode(['status' => 1, 'msg' => 'Pending']);
    
} else {
    echo json_encode(['status' => 0, 'msg' => 'Unknown status']);
}

$conn->close();
?>
