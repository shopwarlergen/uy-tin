<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['username'])){
    echo json_encode(['status' => 0, 'msg' => 'Bạn cần đăng nhập để nạp tiền']);
    exit;
}

$username = $_SESSION['username'];

// Kết nối database
$conn = new mysqli("localhost", "root", "", "webshop");
$conn->set_charset("utf8");

// --- CHẶN SPAM ---
// Lấy thời gian nạp cuối cùng
$check = $conn->query("SELECT created_at FROM history_napthe WHERE username='$username' ORDER BY id DESC LIMIT 1");
if($row = $check->fetch_assoc()){
    $lastTime = strtotime($row['created_at']);
    $now = time();
    if($now - $lastTime < 60){ // < 60 giây → chặn
        echo json_encode(['status'=>0, 'msg'=>'Vui lòng đợi ít nhất 1 phút giữa các lần nạp']);
        exit;
    }
}

// Lấy dữ liệu POST
$telco  = $_POST['telco'] ?? '';
$amount = $_POST['amount'] ?? '';
$pin    = $_POST['pin'] ?? '';
$seri   = $_POST['seri'] ?? '';

if(!$telco || !$amount || !$pin || !$seri){
    echo json_encode(['status'=>0, 'msg'=>'Vui lòng điền đầy đủ thông tin']);
    exit;
}

// API Thesieure
$partner_id  = '28186057286';
$partner_key = '8985dac6ce1a699863b4c3e2f2086fed';

$postData = [
    'partner_id' => $partner_id,
    'partner_key'=> $partner_key,
    'telco'      => $telco,
    'amount'     => $amount,
    'pin'        => $pin,
    'seri'       => $seri,
];

// Gửi request
$ch = curl_init('https://thesieure.com/chargingws/v2');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if($result['status'] != 1){
    echo json_encode([
        'status' => 0,
        'msg'    => $result['message'] ?? 'Nạp thẻ thất bại'
    ]);
    exit;
}

// Cộng tiền
$realAmount = $result['amount'];

$conn->query("UPDATE users SET money = money + $realAmount WHERE username='$username'");

// Lưu lịch sử nạp
$conn->query("
    INSERT INTO history_napthe(username, telco, amount, pin, seri, real_amount, created_at)
    VALUES ('$username', '$telco', '$amount', '$pin', '$seri', '$realAmount', NOW())
");

echo json_encode([
    'status' => 1,
    'msg'    => "Nạp thẻ thành công! +$realAmount VND vào tài khoản"
]);
?>
