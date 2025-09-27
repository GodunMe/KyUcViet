<?php
/**
 * API lấy danh sách bảo tàng gần vị trí người dùng
 * 
 * Trả về danh sách 15 bảo tàng gần nhất với vị trí người dùng
 * Yêu cầu: latitude, longitude
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

// Lấy tọa độ người dùng
$userLat = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
$userLng = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;

// Ghi log dữ liệu nhận được
error_log("Received location data: latitude=$userLat, longitude=$userLng");

// Nếu không có tọa độ, trả về lỗi
if ($userLat == 0 || $userLng == 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Không thể xác định vị trí của bạn. Vui lòng cho phép truy cập vị trí trên trình duyệt.'
    ]);
    exit;
}

// Lấy ID người dùng
$userId = $_SESSION['UserToken'];

try {
    // Truy vấn danh sách bảo tàng
    $stmt = $conn->prepare("SELECT MuseumID, MuseumName, Address, Latitude, Longitude FROM museum");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $museums = [];
    
    // Lấy ngày hiện tại
    $today = date('Y-m-d');
    
    while ($row = $result->fetch_assoc()) {
        // Tính khoảng cách từ người dùng đến bảo tàng
        $distance = calculateDistance(
            $userLat,
            $userLng,
            $row['Latitude'],
            $row['Longitude']
        );
        
        // Kiểm tra lịch sử check-in trong ngày
        $checkStmt = $conn->prepare("
            SELECT COUNT(*) AS checkin_count, MAX(CheckinTime) AS last_checkin 
            FROM checkins 
            WHERE UserToken = ? AND MuseumID = ? AND DATE(CheckinTime) = ?
        ");
        $checkStmt->bind_param("sis", $userId, $row['MuseumID'], $today);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $checkInfo = $checkResult->fetch_assoc();
        
        // Kiểm tra số lần check-in còn lại và thời gian chờ
        $maxCheckinPerDay = 1; // Giới hạn 1 lần check-in mỗi ngày cho mỗi bảo tàng
        $minTimeBetweenCheckins = 30 * 60; // 30 phút giữa các lần check-in
        
        $remainingCheckins = $maxCheckinPerDay - $checkInfo['checkin_count'];
        $waitTime = 0;
        
        if ($checkInfo['last_checkin'] !== null) {
            $lastCheckinTime = strtotime($checkInfo['last_checkin']);
            $currentTime = time();
            $timeDiff = $currentTime - $lastCheckinTime;
            
            if ($timeDiff < $minTimeBetweenCheckins) {
                $waitTime = $minTimeBetweenCheckins - $timeDiff;
            }
        }
        
        // Thêm thông tin bảo tàng vào danh sách
        $museums[] = [
            'id' => $row['MuseumID'],
            'name' => $row['MuseumName'],
            'address' => $row['Address'],
            'latitude' => $row['Latitude'],
            'longitude' => $row['Longitude'],
            'distance' => round($distance), // Làm tròn khoảng cách
            'remainingCheckins' => $remainingCheckins,
            'waitTime' => $waitTime
        ];
    }
    
    // Sắp xếp các bảo tàng theo khoảng cách (gần nhất đầu tiên)
    usort($museums, function($a, $b) {
        return $a['distance'] - $b['distance'];
    });
    
    // Kiểm tra nếu không có bảo tàng nào
    if (empty($museums)) {
        error_log("No museums found in database");
        echo json_encode([
            'success' => false,
            'error' => 'Không tìm thấy bảo tàng nào trong cơ sở dữ liệu'
        ]);
        exit;
    }

    // Lấy 5 bảo tàng gần nhất
    $nearbyMuseums = array_slice($museums, 0, 5);
    
    // Ghi log kết quả
    error_log("Found " . count($nearbyMuseums) . " nearby museums. Closest distance: " . 
              (isset($nearbyMuseums[0]) ? $nearbyMuseums[0]['distance'] . "m" : "none"));
    
    // Thêm tọa độ người dùng vào kết quả để dễ debug
    foreach ($nearbyMuseums as &$museum) {
        $museum['userLat'] = $userLat;
        $museum['userLng'] = $userLng;
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'museums' => $nearbyMuseums
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi khi lấy danh sách bảo tàng: ' . $e->getMessage()
    ]);
}

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
?>