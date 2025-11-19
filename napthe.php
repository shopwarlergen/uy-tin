<?php
// CORS - Cho phép GitHub Pages gọi API
header('Access-Control-Allow-Origin: https://shopwarlergen.github.io');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

if(!isset($_SESSION['username'])){
    echo json_encode(['status' => 0, 'msg' => 'Bạn cần đăng nhập để nạp tiền']);
    exit;
}

$username = $_SESSION['username'];

// ⚠️ THAY ĐỔI THÔNG TIN DATABASE CỦA BẠN Ở ĐÂY
$conn = new mysqli("localhost", "root", "", "webshop");
// VD với InfinityFree:
// $conn = new mysqli("sql123.epizy.com", "epiz_12345", "password_của_bạn", "epiz_12345_webshop");

$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['status' => 0, 'msg' => 'Lỗi kết nối database']);
    exit;
}

// --- CHẶN SPAM ---
$stmt = $conn->prepare("SELECT created_at FROM history_napthe WHERE username=? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if($row = $result->fetch_assoc()){
    $lastTime = strtotime($row['created_at']);
    $now = time();
    if($now - $lastTime < 60){
        echo json_encode(['status'=>0, 'msg'=>'Vui lòng đợi ít nhất 1 phút giữa các lần nạp']);
        exit;
    }
}
$stmt->close();

// Lấy dữ liệu POST
$telco  = trim($_POST['telco'] ?? '');
$amount = trim($_POST['amount'] ?? '');
$pin    = trim($_POST['pin'] ?? '');
$seri   = trim($_POST['seri'] ?? '');

// Validate input
if(!$telco || !$amount || !$pin || !$seri){
    echo json_encode(['status'=>0, 'msg'=>'Vui lòng điền đầy đủ thông tin']);
    exit;
}

// Validate telco
$validTelcos = ['VIETTEL', 'VINAPHONE', 'MOBIFONE', 'VIETNAMOBILE', 'ZING'];
if(!in_array(strtoupper($telco), $validTelcos)){
    echo json_encode(['status'=>0, 'msg'=>'Nhà mạng không hợp lệ']);
    exit;
}

// Validate amount
$validAmounts = [10000, 20000, 30000, 50000, 100000, 200000, 300000, 500000, 1000000];
if(!in_array((int)$amount, $validAmounts)){
    echo json_encode(['status'=>0, 'msg'=>'Mệnh giá không hợp lệ']);
    exit;
}

// Validate pin và seri
if(!ctype_alnum($pin) || !ctype_alnum($seri)){
    echo json_encode(['status'=>0, 'msg'=>'Mã thẻ hoặc seri không hợp lệ']);
    exit;
}

// ⚠️ THAY API KEY CỦA BẠN Ở ĐÂY
$partner_id  = '28186057286';
$partner_key = '8985dac6ce1a699863b4c3e2f2086fed';

// ⚠️ THAY DOMAIN CỦA BẠN Ở ĐÂY
$callback_url = 'https://yourdomain.com/api/callback_napthe.php';
// VD: 'https://shopcay.epizy.com/api/callback_napthe.php'

// Tạo request_id unique
$request_id = $username . '_' . time() . '_' . rand(1000, 9999);

$postData = [
    'partner_id'  => $partner_id,
    'partner_key' => $partner_key,
    'telco'       => $telco,
    'amount'      => $amount,
    'pin'         => $pin,
    'seri'        => $seri,
    'request_id'  => $request_id,
    'callback_url'=> $callback_url
];

// Lưu vào database với trạng thái PENDING
$stmt = $conn->prepare("
    INSERT INTO history_napthe(username, telco, amount, pin, seri, request_id, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 'PENDING', NOW())
");
$stmt->bind_param("ssisss", $username, $telco, $amount, $pin, $seri, $request_id);
$stmt->execute();
$stmt->close();

// Gửi request đến Thesieure
$ch = curl_init('https://thesieure.com/chargingws/v2');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if($httpCode != 200 || !$response){
    // Cập nhật trạng thái lỗi
    $stmt = $conn->prepare("UPDATE history_napthe SET status='ERROR' WHERE request_id=?");
    $stmt->bind_param("s", $request_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['status' => 0, 'msg' => 'Không thể kết nối đến API nạp thẻ']);
    exit;
}

$result = json_decode($response, true);

if(!isset($result['status']) || $result['status'] != 1){
    // Cập nhật trạng thái lỗi
    $stmt = $conn->prepare("UPDATE history_napthe SET status='FAILED', message=? WHERE request_id=?");
    $msg = $result['message'] ?? 'Lỗi không xác định';
    $stmt->bind_param("ss", $msg, $request_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode([
        'status' => 0,
        'msg'    => $result['message'] ?? 'Nạp thẻ thất bại'
    ]);
    exit;
}

$conn->close();

echo json_encode([
    'status' => 1,
    'msg'    => 'Thẻ đã được gửi lên hệ thống. Vui lòng đợi 1-5 phút để kiểm tra.',
    'request_id' => $request_id
]);
?>
