<?php
/**
 * API check-in với upload ảnh (Enhanced for Approval Workflow)
 * Updated to work with new approval system and PendingPoints/ActualPoints
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
$caption = isset($_POST['caption']) ? trim($_POST['caption']) : ''; // Đổi từ status thành caption

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
    
    // Lấy quy tắc điểm của bảo tàng
    $stmt = $conn->prepare("SELECT MaxPointsPerCheckin FROM museum_checkin_rules WHERE MuseumID = ?");
    $stmt->bind_param("i", $museumId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $maxPoints = 10; // Default
    if ($result->num_rows > 0) {
        $rules = $result->fetch_assoc();
        $maxPoints = $rules['MaxPointsPerCheckin'];
    }
    
    // Lưu check-in với hệ thống approval mới
    $stmt = $conn->prepare("
        INSERT INTO checkins (
            UserToken, MuseumID, Latitude, Longitude, 
            Caption, ApprovalStatus, PendingPoints, 
            ActualPoints, CheckinTime, Points
        ) VALUES (?, ?, ?, ?, ?, 'none', ?, 0, NOW(), 0)
    ");
    $stmt->bind_param("siddsi", $userToken, $museumId, $latitude, $longitude, $caption, $maxPoints);
    
    if ($stmt->execute()) {
        $checkinId = $conn->insert_id;
        
        // Lưu thông tin ảnh vào database từ photoPaths đã upload
        $uploadedPhotos = [];
        
        foreach ($photoPaths as $index => $photoData) {
            $photoPath = $photoData['path'];
            $uploadOrder = $index + 1;
            $photoCaption = isset($photoData['caption']) ? $photoData['caption'] : '';
            
            // Insert vào bảng checkin_photos với UserToken
            $stmt = $conn->prepare("
                INSERT INTO checkin_photos (CheckinID, UserToken, PhotoPath, Caption, UploadOrder) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssi", $checkinId, $userToken, $photoPath, $photoCaption, $uploadOrder);
            
            if ($stmt->execute()) {
                $uploadedPhotos[] = $photoPath;
            } else {
                error_log("Failed to save photo to database: " . $conn->error);
            }
        }
        
        // Cập nhật daily limits tracking
        $today = date('Y-m-d');
        
        // Kiểm tra xem đã có record trong daily_checkin_limits chưa
        $stmt = $conn->prepare("
            SELECT CheckinCount 
            FROM daily_checkin_limits 
            WHERE UserToken = ? AND MuseumID = ? AND CheckinDate = ?
        ");
        $stmt->bind_param("sis", $userToken, $museumId, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Cập nhật count hiện tại
            $stmt = $conn->prepare("
                UPDATE daily_checkin_limits 
                SET CheckinCount = CheckinCount + 1, LastCheckinTime = NOW()
                WHERE UserToken = ? AND MuseumID = ? AND CheckinDate = ?
            ");
            $stmt->bind_param("sis", $userToken, $museumId, $today);
            $stmt->execute();
        } else {
            // Tạo record mới
            $stmt = $conn->prepare("
                INSERT INTO daily_checkin_limits (UserToken, MuseumID, CheckinDate, CheckinCount, LastCheckinTime)
                VALUES (?, ?, ?, 1, NOW())
            ");
            $stmt->bind_param("sis", $userToken, $museumId, $today);
            $stmt->execute();
        }
        
        // Tạo audit trail record
        $stmt = $conn->prepare("
            INSERT INTO checkin_status_history (CheckinID, OldStatus, NewStatus, OldPoints, NewPoints, Reason)
            VALUES (?, NULL, 'none', 0, ?, 'Initial check-in created')
        ");
        $stmt->bind_param("ii", $checkinId, $maxPoints);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Check-in thành công tại ' . $museum['MuseumName'] . '. Chờ admin duyệt để nhận điểm.',
            'checkinId' => $checkinId,
            'pendingPoints' => $maxPoints,
            'actualPoints' => 0,
            'approvalStatus' => 'none',
            'photos' => $uploadedPhotos,
            'note' => 'Check-in đang chờ duyệt. Bạn sẽ nhận được điểm sau khi admin phê duyệt.'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Lỗi lưu check-in: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi: ' . $e->getMessage()]);
}

$conn->close();
?>