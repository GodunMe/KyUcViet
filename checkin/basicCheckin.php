<?php
/**
 * API check-in với upload ảnh
 * Updated to work with new photo upload system
 */
header('Content-Type: application/json');
require_once '../db.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['UserToken'])) {
    echo json_encode(['success' => false, 'error' => 'Cần đăng nhập']);
    exit;
}

// Lấy dữ liệu
$museumId = isset($_POST['museumId']) ? intval($_POST['museumId']) : 0;
$latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
$longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if (!$museumId || !$latitude || !$longitude) {
    echo json_encode(['success' => false, 'error' => 'Thiếu thông tin check-in']);
    exit;
}

// Lấy thông tin photo paths từ session hoặc POST (được set từ upload API)
$photoPaths = isset($_POST['photoPaths']) ? json_decode($_POST['photoPaths'], true) : [];

if (empty($photoPaths)) {
    echo json_encode(['success' => false, 'error' => 'Cần upload ảnh trước khi check-in']);
    exit;
}

$userToken = $_SESSION['UserToken'];

try {
    // Kiểm tra museum tồn tại
    $stmt = $conn->prepare("SELECT MuseumName FROM museum WHERE MuseumID = ?");
    $stmt->bind_param("i", $museumId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Bảo tàng không tồn tại']);
        exit;
    }
    
    $museum = $result->fetch_assoc();
    
    // Lưu check-in với status
    $stmt = $conn->prepare("INSERT INTO checkins (UserToken, MuseumID, Latitude, Longitude, Status, CheckinTime) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sidds", $userToken, $museumId, $latitude, $longitude, $status);
    
    if ($stmt->execute()) {
        $checkinId = $conn->insert_id;
        
        // Lưu thông tin ảnh vào database từ photoPaths đã upload
        $uploadedPhotos = [];
        
        foreach ($photoPaths as $index => $photoData) {
            $photoPath = $photoData['path'];
            $uploadOrder = $index + 1;
            $caption = isset($photoData['caption']) ? $photoData['caption'] : '';
            
            // Insert vào bảng checkin_photos  
            $stmt = $conn->prepare("INSERT INTO checkin_photos (CheckinID, PhotoPath, Caption, UploadOrder) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $checkinId, $photoPath, $caption, $uploadOrder);
            
            if ($stmt->execute()) {
                $uploadedPhotos[] = $photoPath;
            } else {
                error_log("Failed to save photo to database: " . $conn->error);
            }
        }
        
        // Kiểm tra xem đã check-in tại bảo tàng này trong ngày chưa để quyết định cộng điểm
        $today = date('Y-m-d');
        $stmt = $conn->prepare("SELECT COUNT(*) as checkin_count FROM checkins WHERE UserToken = ? AND MuseumID = ? AND DATE(CheckinTime) = ?");
        $stmt->bind_param("sis", $userToken, $museumId, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $isFirstCheckinToday = ($row['checkin_count'] == 1); // Vì check-in đã được insert ở trên
        
        $pointsEarned = 0;
        if ($isFirstCheckinToday) {
            // Cộng điểm chỉ cho lần check-in đầu tiên trong ngày
            $stmt = $conn->prepare("UPDATE users SET Score = Score + 10 WHERE UserToken = ?");
            $stmt->bind_param("s", $userToken);
            $stmt->execute();
            $pointsEarned = 10;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Check-in thành công tại ' . $museum['MuseumName'],
            'checkinId' => $checkinId,
            'points' => $pointsEarned,
            'isFirstToday' => $isFirstCheckinToday,
            'photos' => $uploadedPhotos
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Lỗi lưu check-in: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi: ' . $e->getMessage()]);
}

$conn->close();
?>