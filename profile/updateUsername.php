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

// Kiểm tra dữ liệu đầu vào
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['username']) || empty($input['username'])) {
    echo json_encode(['success' => false, 'message' => 'Username không được để trống']);
    exit;
}

$newUsername = trim($input['username']);

// Kiểm tra độ dài username
if (strlen($newUsername) < 3 || strlen($newUsername) > 20) {
    echo json_encode(['success' => false, 'message' => 'Username phải từ 3-20 ký tự']);
    exit;
}

// Kiểm tra username chỉ chứa các ký tự hợp lệ
if (!preg_match('/^[a-zA-Z0-9_\s]+$/', $newUsername)) {
    echo json_encode(['success' => false, 'message' => 'Username chỉ được chứa chữ cái, số, gạch dưới và khoảng trắng']);
    exit;
}

try {
    
    // Lấy username hiện tại
    $sql = "SELECT Username FROM users WHERE UserToken = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userToken);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentUsername = '';
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $currentUsername = $row['Username'];
    }
    
    // Nếu username mới trùng với username cũ, không cần cập nhật
    if ($currentUsername === $newUsername) {
        echo json_encode(['success' => true, 'message' => 'Username không thay đổi']);
        exit;
    }
    
    // Kiểm tra username đã tồn tại chưa
    $sql = "SELECT COUNT(*) as count FROM users WHERE Username = ? AND UserToken != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $newUsername, $userToken);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Username đã được sử dụng bởi người dùng khác']);
        exit;
    }
    
    // Cập nhật username mới
    $sql = "UPDATE users SET Username = ? WHERE UserToken = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $newUsername, $userToken);
    $stmt->execute();
    
    // Hoàn tất transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Username đã được cập nhật thành công', 'username' => $newUsername]);
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    try {
        $conn->rollback();
    } catch (Exception $rollbackError) {
        // Bỏ qua lỗi rollback nếu có
    }
    
    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật username: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>