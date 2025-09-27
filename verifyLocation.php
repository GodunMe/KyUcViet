<?php
/**
 * API Xác thực vị trí người dùng
 * 
 * Kiểm tra xem vị trí người dùng có trong phạm vi của bảo tàng không
 * Yêu cầu: userLat, userLng, museumId
 * Phản hồi: { success: true/false, distance: [khoảng cách tính bằng mét], withinRange: true/false, museumDetails: {...} }
 */

header('Content-Type: application/json');

// Kết nối database
require_once 'db.php';

// Khởi tạo session nếu chưa có
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['UserToken'])) {
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập để sử dụng tính năng này']);
    exit;
}

// Kiểm tra dữ liệu đầu vào
$requiredParams = ['userLat', 'userLng', 'museumId'];
$missingParams = [];

foreach ($requiredParams as $param) {
    if (!isset($_POST[$param])) {
        $missingParams[] = $param;
    }
}

if (!empty($missingParams)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Thiếu thông tin: ' . implode(', ', $missingParams)
    ]);
    exit;
}

// Lấy dữ liệu đầu vào
$userLat = floatval($_POST['userLat']);
$userLng = floatval($_POST['userLng']);
$museumId = intval($_POST['museumId']);

// Kiểm tra hợp lệ
if ($userLat == 0 || $userLng == 0 || $museumId == 0) {
    echo json_encode([
        'success' => false, 
        'error' => 'Dữ liệu không hợp lệ'
    ]);
    exit;
}

// Truy vấn thông tin bảo tàng
$stmt = $conn->prepare("SELECT MuseumName, Latitude, Longitude, Address FROM museum WHERE MuseumID = ?");
$stmt->bind_param("i", $museumId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false, 
        'error' => 'Không tìm thấy bảo tàng'
    ]);
    exit;
}

$museum = $result->fetch_assoc();

// Tính khoảng cách giữa người dùng và bảo tàng
$distance = calculateDistance(
    $userLat, 
    $userLng, 
    $museum['Latitude'], 
    $museum['Longitude']
);

// Kiểm tra xem có trong phạm vi cho phép không (50m)
$maxAllowedDistance = 50; // mét
$withinRange = ($distance <= $maxAllowedDistance);

// Trả về kết quả
echo json_encode([
    'success' => true,
    'distance' => round($distance), // làm tròn khoảng cách
    'withinRange' => $withinRange,
    'museumDetails' => [
        'id' => $museumId,
        'name' => $museum['MuseumName'],
        'latitude' => $museum['Latitude'],
        'longitude' => $museum['Longitude'],
        'address' => $museum['Address']
    ],
    'maxAllowedDistance' => $maxAllowedDistance
]);

/**
 * Hàm tính khoảng cách giữa hai điểm theo tọa độ GPS
 * Sử dụng công thức Haversine để tính khoảng cách trên mặt cầu
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Bán kính trái đất tính bằng mét
    
    $lat1Rad = deg2rad($lat1);
    $lon1Rad = deg2rad($lon1);
    $lat2Rad = deg2rad($lat2);
    $lon2Rad = deg2rad($lon2);
    
    $latDelta = $lat2Rad - $lat1Rad;
    $lonDelta = $lon2Rad - $lon1Rad;
    
    $a = sin($latDelta/2) * sin($latDelta/2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($lonDelta/2) * sin($lonDelta/2);
         
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c;
}