<?php
session_start();
header('Content-Type: application/json');
include '../db.php';

// Kiểm tra người dùng đã đăng nhập
if (!isset($_SESSION['UserToken']) || empty($_SESSION['UserToken'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện chức năng này']);
    exit;
}

$userToken = $_SESSION['UserToken'];

// Kiểm tra file tải lên
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $error = $_FILES['avatar']['error'] ?? 'Không có file được tải lên';
    echo json_encode(['success' => false, 'message' => 'Lỗi khi tải lên file: ' . $error]);
    exit;
}

// Kiểm tra định dạng file
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$fileType = $_FILES['avatar']['type'];
if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file hình ảnh (JPEG, PNG, GIF)']);
    exit;
}

// Kiểm tra kích thước file (giới hạn 2MB)
$maxSize = 2 * 1024 * 1024; // 2MB
if ($_FILES['avatar']['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Kích thước file quá lớn. Giới hạn 2MB']);
    exit;
}

// Tạo thư mục uploads/avatar nếu chưa tồn tại
$uploadDir = '../uploads/avatar/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Lấy extension của file
$fileExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);

// Tạo tên file mới dựa trên userToken để tránh trùng lặp
$newFileName = $userToken . '_' . time() . '.' . $fileExtension;
$targetFilePath = $uploadDir . $newFileName;

// Lấy đường dẫn avatar cũ
$sql = "SELECT avatar FROM users WHERE UserToken = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userToken);
$stmt->execute();
$result = $stmt->get_result();
$oldAvatarPath = null;

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $oldAvatarPath = $row['avatar'];
}

// Cố gắng di chuyển file tải lên
if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFilePath)) {
    // Cập nhật đường dẫn avatar trong database
    $avatarRelativePath = 'uploads/avatar/' . $newFileName; // Đường dẫn tương đối từ thư mục gốc
    
    $sql = "UPDATE users SET avatar = ? WHERE UserToken = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $avatarRelativePath, $userToken);
    
    if ($stmt->execute()) {
        // Xóa file avatar cũ nếu có và không phải avatar mặc định
        if ($oldAvatarPath && $oldAvatarPath != 'avatar/default.png' && file_exists('../' . $oldAvatarPath)) {
            unlink('../' . $oldAvatarPath);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Avatar đã được cập nhật thành công',
            'avatarPath' => $avatarRelativePath,
            'avatarUrl' => '/' . $avatarRelativePath
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật avatar trong cơ sở dữ liệu']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể tải lên file']);
}

$conn->close();
?>