<?php
/**
 * API kiểm tra quy tắc check-in (Enhanced for Approval Workflow)
 * Kiểm tra xem user có thể check-in tại bảo tàng này không
 * Bao gồm: giới hạn 1/ngày/bảo tàng, 7 ngày chờ, tối đa 2 bảo tàng/ngày
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
    // Lấy quy tắc check-in của bảo tàng (Enhanced rules)
    $stmt = $conn->prepare("
        SELECT 
            MaxCheckinsPerDay, 
            WaitDaysBetweenCheckins, 
            MaxPointsPerCheckin,
            MaxCheckinsPerUserPerMuseumPerWeek,
            MaxMuseumsPerUserPerDay,
            IsActive
        FROM museum_checkin_rules 
        WHERE MuseumID = ? AND IsActive = TRUE
    ");
    $stmt->bind_param("i", $museumId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Nếu không có quy tắc, sử dụng mặc định theo kế hoạch mới
        $maxPerDay = 1;
        $daysBetweenRevisit = 7;
        $maxPointsPerCheckin = 10;
        $maxPerWeek = 1;
        $maxMuseumsPerDay = 2;
    } else {
        $rules = $result->fetch_assoc();
        $maxPerDay = $rules['MaxCheckinsPerDay'];
        $daysBetweenRevisit = $rules['WaitDaysBetweenCheckins'];
        $maxPointsPerCheckin = $rules['MaxPointsPerCheckin'];
        $maxPerWeek = $rules['MaxCheckinsPerUserPerMuseumPerWeek'];
        $maxMuseumsPerDay = $rules['MaxMuseumsPerUserPerDay'];
    }
    
    $today = date('Y-m-d');
    $canCheckin = true;
    $messages = [];
    
    // 1. Kiểm tra số lần check-in hôm nay tại bảo tàng này
    $stmt = $conn->prepare("
        SELECT COUNT(*) as today_count 
        FROM checkins 
        WHERE UserToken = ? AND MuseumID = ? AND DATE(CheckinTime) = ?
    ");
    $stmt->bind_param("sis", $userToken, $museumId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $todayCount = $result->fetch_assoc()['today_count'];
    
    if ($todayCount >= $maxPerDay) {
        $canCheckin = false;
        $messages[] = "Đã đạt giới hạn {$maxPerDay} lần check-in/ngày tại bảo tàng này";
    }
    
    // 2. Kiểm tra thời gian chờ 7 ngày giữa các lần check-in
    $stmt = $conn->prepare("
        SELECT MAX(CheckinTime) as last_checkin 
        FROM checkins 
        WHERE UserToken = ? AND MuseumID = ?
    ");
    $stmt->bind_param("si", $userToken, $museumId);
    $stmt->execute();
    $result = $stmt->get_result();
    $lastCheckin = $result->fetch_assoc()['last_checkin'];
    
    if ($lastCheckin) {
        $daysSinceLastCheckin = (time() - strtotime($lastCheckin)) / (24 * 60 * 60);
        if ($daysSinceLastCheckin < $daysBetweenRevisit) {
            $canCheckin = false;
            $waitDays = ceil($daysBetweenRevisit - $daysSinceLastCheckin);
            $messages[] = "Cần chờ {$waitDays} ngày nữa mới có thể check-in lại tại bảo tàng này";
        }
    }
    
    // 3. Kiểm tra giới hạn số bảo tàng khác nhau trong ngày (2 museums max/day)
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT MuseumID) as museums_today 
        FROM checkins 
        WHERE UserToken = ? AND DATE(CheckinTime) = ?
    ");
    $stmt->bind_param("ss", $userToken, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $museumsToday = $result->fetch_assoc()['museums_today'];
    
    // Nếu chưa check-in ở bảo tàng này hôm nay, kiểm tra có vượt quá 2 bảo tàng không
    if ($todayCount == 0 && $museumsToday >= $maxMuseumsPerDay) {
        $canCheckin = false;
        $messages[] = "Đã đạt giới hạn {$maxMuseumsPerDay} bảo tàng khác nhau/ngày";
    }
    
    // 4. Kiểm tra giới hạn tuần (1 lần/tuần/bảo tàng)
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime('sunday this week'));
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as week_count 
        FROM checkins 
        WHERE UserToken = ? AND MuseumID = ? 
        AND DATE(CheckinTime) BETWEEN ? AND ?
    ");
    $stmt->bind_param("siss", $userToken, $museumId, $weekStart, $weekEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    $weekCount = $result->fetch_assoc()['week_count'];
    
    if ($weekCount >= $maxPerWeek) {
        $canCheckin = false;
        $messages[] = "Đã đạt giới hạn {$maxPerWeek} lần check-in/tuần tại bảo tàng này";
    }
    
    $response = [
        'success' => true,
        'canCheckin' => $canCheckin,
        'message' => implode('. ', $messages),
        'rules' => [
            'maxPerDay' => $maxPerDay,
            'daysBetweenRevisit' => $daysBetweenRevisit,
            'maxPointsPerCheckin' => $maxPointsPerCheckin,
            'maxMuseumsPerDay' => $maxMuseumsPerDay,
            'maxPerWeek' => $maxPerWeek
        ],
        'currentStatus' => [
            'todayCount' => $todayCount,
            'museumsToday' => $museumsToday,
            'weekCount' => $weekCount,
            'daysSinceLastCheckin' => $lastCheckin ? round($daysSinceLastCheckin, 1) : null
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi: ' . $e->getMessage()]);
}

$conn->close();
?>