<?php
/**
 * Kiểm tra xem người dùng có đủ điều kiện để check-in hay không
 * Các điều kiện bao gồm:
 * - Không check-in vào cùng một bảo tàng quá 1 lần trong ngày
 * - Không check-in quá 5 bảo tàng khác nhau trong một ngày
 * - Khoảng thời gian giữa các lần check-in là ít nhất 30 phút
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
    // Kiểm tra xem đã check-in vào bảo tàng này trong ngày hôm nay chưa
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM checkins WHERE UserToken = ? AND MuseumID = ? AND DATE(CheckinTime) = ?");
    $stmt->bind_param("sis", $userId, $museumId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode([
            'success' => false,
            'canCheckin' => false,
            'reason' => 'Bạn đã check-in vào bảo tàng này hôm nay rồi'
        ]);
        exit;
    }
    
    // Kiểm tra xem đã check-in vào bao nhiêu bảo tàng khác nhau trong ngày hôm nay
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT MuseumID) as count FROM checkins WHERE UserToken = ? AND DATE(CheckinTime) = ?");
    $stmt->bind_param("ss", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] >= 5) {
        echo json_encode([
            'success' => false,
            'canCheckin' => false,
            'reason' => 'Bạn đã đạt giới hạn check-in 5 bảo tàng khác nhau trong một ngày'
        ]);
        exit;
    }
    
    // Kiểm tra thời gian check-in gần nhất
    $stmt = $conn->prepare("SELECT MAX(CheckinTime) as last_checkin FROM checkins WHERE UserToken = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['last_checkin'] !== null) {
        $lastCheckinTime = strtotime($row['last_checkin']);
        $currentTime = strtotime($now);
        $timeDifference = ($currentTime - $lastCheckinTime) / 60; // Chênh lệch tính bằng phút
        
        if ($timeDifference < 30) {
            echo json_encode([
                'success' => false,
                'canCheckin' => false,
                'reason' => 'Bạn phải đợi ít nhất 30 phút giữa các lần check-in. Vui lòng đợi thêm ' . round(30 - $timeDifference) . ' phút nữa.'
            ]);
            exit;
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
    echo json_encode([
        'success' => true,
        'canCheckin' => true,
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