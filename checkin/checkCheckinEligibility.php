<?php
/**
 * API kiểm tra quy tắc check-in
 * Kiểm tra xem user có thể check-in tại bảo tàng này không
 */
header('Content-Type: application/json');
require_once '../db.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['UserToken'])) {
    echo json_encode(['success' => false, 'error' => 'Cần đăng nhập']);
    exit;
}

$museumId = isset($_POST['museumId']) ? intval($_POST['museumId']) : 0;
if (!$museumId) {
    echo json_encode(['success' => false, 'error' => 'Thiếu thông tin bảo tàng']);
    exit;
}

$userToken = $_SESSION['UserToken'];

try {
    // Lấy quy tắc check-in của bảo tàng
    $stmt = $conn->prepare("SELECT MaxCheckinPerDay, DaysBetweenRevisit FROM museum_checkin_rules WHERE MuseumID = ?");
    $stmt->bind_param("i", $museumId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Nếu không có quy tắc, sử dụng mặc định
        $maxPerDay = 10;
        $daysBetweenRevisit = 2;
    } else {
        $rules = $result->fetch_assoc();
        $maxPerDay = $rules['MaxCheckinPerDay'];
        $daysBetweenRevisit = $rules['DaysBetweenRevisit'];
    }
    
    // Kiểm tra số lần check-in hôm nay
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) as today_count FROM checkins WHERE UserToken = ? AND MuseumID = ? AND DATE(CheckinTime) = ?");
    $stmt->bind_param("sis", $userToken, $museumId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $todayCount = $result->fetch_assoc()['today_count'];
    
    // Kiểm tra lần check-in cuối cùng
    $stmt = $conn->prepare("SELECT MAX(CheckinTime) as last_checkin FROM checkins WHERE UserToken = ? AND MuseumID = ?");
    $stmt->bind_param("si", $userToken, $museumId);
    $stmt->execute();
    $result = $stmt->get_result();
    $lastCheckin = $result->fetch_assoc()['last_checkin'];
    
    $canCheckin = true;
    $message = '';
    $remainingToday = $maxPerDay - $todayCount;
    
    // Kiểm tra giới hạn hàng ngày  
    if ($todayCount >= $maxPerDay) {
        $canCheckin = false;
        $message = 'Đã đạt giới hạn ' . $maxPerDay . ' lần check-in/ngày tại bảo tàng này';
        $remainingToday = 0;
    }
    
    // Kiểm tra thời gian chờ giữa các lần check-in
    if ($canCheckin && $lastCheckin) {
        $daysSinceLastCheckin = (time() - strtotime($lastCheckin)) / (24 * 60 * 60);
        if ($daysSinceLastCheckin < $daysBetweenRevisit && date('Y-m-d', strtotime($lastCheckin)) !== $today) {
            $canCheckin = false;
            $waitDays = ceil($daysBetweenRevisit - $daysSinceLastCheckin);
            $message = 'Cần chờ ' . $waitDays . ' ngày nữa mới có thể check-in lại';
        }
    }
    
    echo json_encode([
        'success' => true,
        'canCheckin' => $canCheckin,
        'message' => $message,
        'remainingToday' => $remainingToday,
        'maxPerDay' => $maxPerDay,
        'todayCount' => $todayCount,
        'daysBetweenRevisit' => $daysBetweenRevisit
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi: ' . $e->getMessage()]);
}

$conn->close();
?>