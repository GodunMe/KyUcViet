<?php
session_start();
header('Content-Type: application/json');
include '../db.php';

// Kiểm tra người dùng đã đăng nhập
if (!isset($_SESSION['UserToken']) || empty($_SESSION['UserToken'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để xem lịch sử username']);
    exit;
}

$userToken = $_SESSION['UserToken'];

try {
    // Lấy lịch sử username từ cơ sở dữ liệu
    $sql = "SELECT OldUsername, ChangeDate FROM username_history WHERE UserToken = ? ORDER BY ChangeDate DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userToken);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = [
            'username' => $row['OldUsername'],
            'changeDate' => $row['ChangeDate']
        ];
    }
    
    // Kiểm tra thông tin lần thay đổi gần nhất và số ngày còn lại
    $sql = "SELECT LastUsernameChange FROM users WHERE UserToken = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userToken);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $lastChangeDate = $row['LastUsernameChange'];
    
    $daysRemaining = 0;
    $canChangeUsername = true;
    
    if ($lastChangeDate) {
        $lastChangeTimestamp = strtotime($lastChangeDate);
        $currentTimestamp = time();
        $daysSinceLastChange = floor(($currentTimestamp - $lastChangeTimestamp) / (60 * 60 * 24));
        
        if ($daysSinceLastChange < 90) {
            $daysRemaining = 90 - $daysSinceLastChange;
            $canChangeUsername = false;
        }
    }
    
    echo json_encode([
        'success' => true,
        'history' => $history,
        'canChangeUsername' => $canChangeUsername,
        'daysRemaining' => $daysRemaining,
        'lastChangeDate' => $lastChangeDate
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy lịch sử username: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>