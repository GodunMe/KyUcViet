<?php
/**
 * API tải lên ảnh check-in
 * 
 * Nhận một ảnh và lưu vào thư mục uploads/checkins
 * Trả về đường dẫn đến ảnh đã tải lên
 */

header('Content-Type: application/json');

// Kết nối database (không sử dụng trong file này, nhưng cần thiết cho phiên làm việc)
require_once 'db.php';

// Khởi tạo session nếu chưa có
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['UserToken'])) {
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập để sử dụng tính năng này']);
    exit;
}

// Kiểm tra xem có file được tải lên không
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] != UPLOAD_ERR_OK) {
    $error = 'Không có ảnh được tải lên';
    
    if (isset($_FILES['photo'])) {
        switch ($_FILES['photo']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $error = 'Kích thước file vượt quá giới hạn của server';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error = 'Kích thước file vượt quá giới hạn cho phép';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error = 'File tải lên không hoàn chỉnh';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error = 'Không có file nào được tải lên';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error = 'Thiếu thư mục tạm';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error = 'Không thể ghi file';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error = 'Tải lên bị dừng bởi extension';
                break;
        }
    }
    
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

// Xác định thư mục lưu trữ dựa trên type
$type = isset($_POST['type']) ? $_POST['type'] : 'checkin';
$uploadDir = __DIR__ . '/uploads/checkins/'; // Mặc định là checkins

// Đảm bảo thư mục tồn tại
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['success' => false, 'error' => 'Không thể tạo thư mục lưu trữ']);
        exit;
    }
}

// Tạo tên file duy nhất để tránh ghi đè
$userToken = $_SESSION['UserToken'];
$timestamp = time();
$randomString = bin2hex(random_bytes(8)); // 16 ký tự hex ngẫu nhiên
$extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);

// Tạo tên file theo định dạng: usertoken_timestamp_randomstring.extension
$filename = "{$userToken}_{$timestamp}_{$randomString}.{$extension}";
$uploadFile = $uploadDir . $filename;

// Di chuyển file tải lên vào thư mục đích
if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadFile)) {
    // Nén ảnh nếu kích thước quá lớn
    optimizeImage($uploadFile);
    
    echo json_encode([
        'success' => true,
        'photoPath' => 'uploads/checkins/' . $filename,
        'filename' => $filename
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Không thể lưu file ảnh']);
}

/**
 * Hàm tối ưu hóa kích thước ảnh
 * Giảm kích thước ảnh nếu kích thước quá lớn
 */
function optimizeImage($filePath) {
    // Kiểm tra xem file có tồn tại không
    if (!file_exists($filePath)) {
        return false;
    }

    // Lấy thông tin về ảnh
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) {
        return false;
    }
    
    // Kiểm tra kích thước file
    $fileSize = filesize($filePath);
    $maxFileSize = 1024 * 1024; // 1MB
    
    // Nếu kích thước file nhỏ hơn giới hạn, không cần xử lý
    if ($fileSize <= $maxFileSize) {
        return true;
    }
    
    // Xác định loại ảnh
    $mimeType = $imageInfo['mime'];
    
    // Tạo đối tượng ảnh tùy theo loại
    switch ($mimeType) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($filePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($filePath);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($filePath);
            break;
        default:
            return false; // Không hỗ trợ định dạng ảnh
    }
    
    // Giảm chất lượng và lưu lại ảnh
    switch ($mimeType) {
        case 'image/jpeg':
            // Giảm chất lượng JPEG xuống 75%
            return imagejpeg($image, $filePath, 75);
        case 'image/png':
            // Cho PNG, có thể chuyển sang JPG để giảm kích thước
            return imagejpeg($image, $filePath, 75);
        case 'image/gif':
            // Giữ nguyên GIF
            return imagegif($image, $filePath);
    }
    
    return false;
}