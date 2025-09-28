<?php
/**
 * API check-in với upload ảnh
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

if (!$museumId || !$latitude || !$longitude) {
    echo json_encode(['success' => false, 'error' => 'Thiếu thông tin check-in']);
    exit;
}

// Kiểm tra có ảnh không
if (!isset($_FILES['photos']) || empty($_FILES['photos']['name'][0])) {
    echo json_encode(['success' => false, 'error' => 'Cần ít nhất 1 ảnh để check-in']);
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
    
    // Lưu check-in đơn giản
    $stmt = $conn->prepare("INSERT INTO checkins (UserToken, MuseumID, Latitude, Longitude, CheckinTime) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("sidd", $userToken, $museumId, $latitude, $longitude);
    
    if ($stmt->execute()) {
        $checkinId = $conn->insert_id;
        
        // Upload và lưu ảnh
        $uploadedPhotos = [];
        $uploadDir = '../uploads/checkins/';
        
        // Tạo folder nếu chưa có
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        foreach ($_FILES['photos']['tmp_name'] as $index => $tmpName) {
            if (is_uploaded_file($tmpName)) {
                $originalName = $_FILES['photos']['name'][$index];
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $newFileName = $userToken . '_' . time() . '_' . uniqid() . '.' . $extension;
                $uploadPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($tmpName, $uploadPath)) {
                    $uploadedPhotos[] = 'uploads/checkins/' . $newFileName;
                    
                    // Lưu thông tin ảnh vào database (nếu có bảng checkin_photos)
                    // $stmt = $conn->prepare("INSERT INTO checkin_photos (CheckinID, PhotoPath) VALUES (?, ?)");
                    // $stmt->bind_param("is", $checkinId, $uploadPath);
                    // $stmt->execute();
                }
            }
        }
        
        // Cộng điểm
        $stmt = $conn->prepare("UPDATE users SET Score = Score + 10 WHERE UserToken = ?");
        $stmt->bind_param("s", $userToken);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Check-in thành công tại ' . $museum['MuseumName'],
            'checkinId' => $checkinId,
            'points' => 10,
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