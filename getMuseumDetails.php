<?php
include 'db.php';

header('Content-Type: application/json');

// Khởi tạo session nếu chưa có
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['UserToken'])) {
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập để sử dụng tính năng này']);
    exit;
}

// Kiểm tra xem có thông tin mã check-in hoặc ID bảo tàng không
$museumId = 0;
$code = '';

// Kiểm tra nếu có ID bảo tàng
if (isset($_GET['id'])) {
    $museumId = intval($_GET['id']);
} 
// Kiểm tra nếu có mã check-in từ POST
elseif (isset($_POST['code'])) {
    $code = trim($_POST['code']);
    // Xử lý mã check-in
    if (is_numeric($code)) {
        // Nếu mã là số, coi đó là ID bảo tàng
        $museumId = intval($code);
    }
}

if ($museumId <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Mã check-in hoặc ID bảo tàng không hợp lệ'
    ]);
    exit;
}

try {
    // Get museum details
    $museumQuery = "SELECT MuseumID, MuseumName, Address, Description, Latitude, Longitude FROM museum WHERE MuseumID = ?";
    $museumStmt = $conn->prepare($museumQuery);
    $museumStmt->bind_param("i", $museumId);
    $museumStmt->execute();
    $museumResult = $museumStmt->get_result();
    
    if ($museumResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Không tìm thấy bảo tàng'
        ]);
        exit;
    }
    
    $museum = $museumResult->fetch_assoc();
    
    // Get museum media
    $mediaQuery = "SELECT id, MuseumId, file_name, mime_type, file_path FROM museum_media WHERE MuseumId = ? ORDER BY id ASC";
    $mediaStmt = $conn->prepare($mediaQuery);
    $mediaStmt->bind_param("i", $museumId);
    $mediaStmt->execute();
    $mediaResult = $mediaStmt->get_result();
    
    $media = [];
    while ($row = $mediaResult->fetch_assoc()) {
        if (isset($row['mime_type']) && strpos($row['mime_type'], 'video/') === 0) {
            // file_path là file_id của video
            if (!empty($row['file_path'])) {
                $row['google_drive_url'] = 'https://drive.google.com/file/d/' . $row['file_path'] . '/preview';
            }
            unset($row['file_path']); // Không trả về file_path cho video
        } else {
            // Ảnh: file_path là đường dẫn như cũ
            if (!empty($row['file_path'])) {
                if (!str_starts_with($row['file_path'], 'http')) {
                    $path = ltrim($row['file_path'], '/');
                    $row['file_path'] = '/' . $path;
                    if (!str_starts_with($row['file_path'], '/uploads/')) {
                        $row['file_path'] = '/uploads/museums/' . basename($row['file_path']);
                    }
                }
            } else {
                $row['file_path'] = '/uploads/museums/default.png';
            }
        }
        $media[] = $row;
    }
    
    // Return response
    echo json_encode([
        'success' => true,
        'museum' => [
            'id' => $museum['MuseumID'],
            'name' => $museum['MuseumName'],
            'address' => $museum['Address'],
            'description' => $museum['Description'],
            'latitude' => $museum['Latitude'],
            'longitude' => $museum['Longitude']
        ],
        'media' => $media
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()
    ]);
}

$conn->close();
?>