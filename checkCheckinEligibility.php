<?php
/**
 * Kiểm tra xem người dùng có đủ điều kiện để check-in hay không
 * Các điều kiện mới bao gồm:
 * - Tối đa 2 lần check-in/ngày tại một bảo tàng
 * - Giữa 2 lần check-in tại cùng bảo tàng phải cách nhau ít nhất 30 phút
 * - Tối đa 2 bảo tàng khác nhau mỗi ngày
 * - Sau 3 ngày, người dùng mới có thể check-in lại tại bảo tàng đã check-in đủ số lần
 */

header('Content-Type: application/json');

// Kết nối database
require_once 'db.php';

// Khởi tạo session nếu chưa có
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['UserToken'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Bạn cần đăng nhập để sử dụng tính năng này'
    ]);
    exit;
}

// Kiểm tra có ID bảo tàng hay không
if (!isset($_POST['museumId'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Thiếu thông tin bảo tàng'
    ]);
    exit;
}

$userId = $_SESSION['UserToken'];
$museumId = (int)$_POST['museumId'];

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Lấy ngày hiện tại (định dạng YYYY-MM-DD)
$today = date('Y-m-d');

// Lấy thời gian hiện tại (định dạng YYYY-MM-DD HH:MM:SS)
$now = date('Y-m-d H:i:s');

try {
    // 1. Kiểm tra số lần check-in tại bảo tàng này trong ngày hôm nay
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM checkins WHERE UserToken = ? AND MuseumID = ? AND DATE(CheckinTime) = ?");
    $stmt->bind_param("sis", $userId, $museumId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $todayCheckinsAtMuseum = $row['count'];
    
    if ($todayCheckinsAtMuseum >= 2) {
        // Kiểm tra xem có thể check-in lại sau 3 ngày không
        $stmt = $conn->prepare("SELECT MIN(CheckinTime) as first_checkin FROM checkins WHERE UserToken = ? AND MuseumID = ? AND DATE(CheckinTime) = ?");
        $stmt->bind_param("sis", $userId, $museumId, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['first_checkin']) {
            $firstCheckinDate = date('Y-m-d', strtotime($row['first_checkin']));
            $canCheckinAgainDate = date('Y-m-d', strtotime($firstCheckinDate . ' + 3 days'));
            
            echo json_encode([
                'success' => true,
                'canCheckin' => false,
                'remainingToday' => 0,
                'message' => "Bạn đã check-in đủ 2 lần tại bảo tàng này hôm nay. Có thể check-in lại vào ngày " . date('d/m/Y', strtotime($canCheckinAgainDate))
            ]);
            exit;
        }
    }
    
    // 2. Kiểm tra số bảo tàng khác nhau đã check-in trong ngày
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT MuseumID) as count FROM checkins WHERE UserToken = ? AND DATE(CheckinTime) = ?");
    $stmt->bind_param("ss", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $differentMuseumsToday = $row['count'];
    
    // Kiểm tra xem bảo tàng hiện tại đã được check-in hôm nay chưa
    $isNewMuseumToday = $todayCheckinsAtMuseum == 0;
    
    if ($isNewMuseumToday && $differentMuseumsToday >= 2) {
        echo json_encode([
            'success' => true,
            'canCheckin' => false,
            'remainingToday' => 0,
            'message' => 'Bạn đã check-in tại 2 bảo tàng khác nhau hôm nay. Có thể check-in tại các bảo tàng khác vào ngày mai.'
        ]);
        exit;
    }
    
    // 3. Kiểm tra thời gian giữa các lần check-in tại cùng bảo tàng
    if ($todayCheckinsAtMuseum > 0) {
        $stmt = $conn->prepare("SELECT MAX(CheckinTime) as last_checkin FROM checkins WHERE UserToken = ? AND MuseumID = ? AND DATE(CheckinTime) = ?");
        $stmt->bind_param("sis", $userId, $museumId, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['last_checkin']) {
            $lastCheckinTime = strtotime($row['last_checkin']);
            $currentTime = strtotime($now);
            $timeDifference = ($currentTime - $lastCheckinTime) / 60; // Phút
            
            if ($timeDifference < 30) {
                $waitTime = 30 - $timeDifference;
                echo json_encode([
                    'success' => true,
                    'canCheckin' => false,
                    'remainingToday' => 2 - $todayCheckinsAtMuseum,
                    'message' => "Bạn cần đợi thêm " . ceil($waitTime) . " phút nữa để có thể check-in lại tại bảo tàng này."
                ]);
                exit;
            }
        }
    }
    
    // Lấy thông tin bảo tàng để trả về
    $stmt = $conn->prepare("SELECT MuseumName FROM museum WHERE MuseumID = ?");
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

    // Nếu mọi điều kiện đều thỏa mãn, cho phép check-in
    $remainingCheckinsToday = 2 - $todayCheckinsAtMuseum;
    
    echo json_encode([
        'success' => true,
        'canCheckin' => true,
        'remainingToday' => $remainingCheckinsToday,
        'museum' => [
            'id' => $museumId,
            'name' => $museum['MuseumName']
        ],
        'message' => 'Bạn có thể check-in'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi khi kiểm tra điều kiện check-in: ' . $e->getMessage()
    ]);
}