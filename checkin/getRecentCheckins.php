<?php
/**
 * API lấy danh sách check-in gần đây của người dùng
 * 
 * Trả về danh sách các check-in gần đây của người dùng đang đăng nhập
 * Phản hồi: { success: true/false, checkins: [...] }
 */

header('Content-Type: application/json');

// Kết nối database
require_once '../db.php';

// Khởi tạo session nếu chưa có
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['UserToken'])) {
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập để sử dụng tính năng này']);
    exit;
}

$userToken = $_SESSION['UserToken'];

// Lấy tham số phân trang (nếu có)
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// Giới hạn số lượng tối đa có thể lấy
if ($limit > 50) $limit = 50;

// Truy vấn danh sách check-in gần đây (Enhanced for Approval System)
$stmt = $conn->prepare("
    SELECT 
        c.CheckinID,
        c.MuseumID,
        m.MuseumName,
        c.CheckinTime,
        c.Caption,
        c.ApprovalStatus,
        c.PendingPoints,
        c.ActualPoints,
        c.ProcessedAt,
        c.DeniedReason,
        c.Points,  -- Giữ để backward compatibility
        c.Latitude,
        c.Longitude
    FROM 
        checkins c
    JOIN 
        museum m ON c.MuseumID = m.MuseumID
    WHERE 
        c.UserToken = ?
    ORDER BY 
        c.CheckinTime DESC
    LIMIT ?, ?
");

$stmt->bind_param("sii", $userToken, $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();

$checkins = [];
while ($row = $result->fetch_assoc()) {
    // Lấy tất cả ảnh của check-in này
    $photoStmt = $conn->prepare("
        SELECT PhotoPath 
        FROM checkin_photos 
        WHERE CheckinID = ? 
        ORDER BY UploadOrder
    ");
    $photoStmt->bind_param("i", $row['CheckinID']);
    $photoStmt->execute();
    $photoResult = $photoStmt->get_result();
    
    $photos = [];
    while ($photoRow = $photoResult->fetch_assoc()) {
        $photos[] = $photoRow['PhotoPath'];
    }
    // Định dạng approval status cho hiển thị
    $approvalStatusText = '';
    $statusClass = '';
    switch ($row['ApprovalStatus']) {
        case 'none':
            $approvalStatusText = 'Chờ duyệt';
            $statusClass = 'pending';
            break;
        case 'approved':
            $approvalStatusText = 'Đã duyệt';
            $statusClass = 'approved';
            break;
        case 'denied':
            $approvalStatusText = 'Bị từ chối';
            $statusClass = 'denied';
            break;
    }
    
    // Định dạng thời gian check-in để hiển thị thân thiện
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $checkInTime = new DateTime($row['CheckinTime']);
    $now = new DateTime();
    $interval = $now->diff($checkInTime);
    
    // Debug log
    error_log("Time calculation: CheckinTime={$row['CheckinTime']}, Now=" . $now->format('Y-m-d H:i:s') . ", Diff(days={$interval->days}, hours={$interval->h}, minutes={$interval->i})");
    
    if ($interval->days == 0) {
        if ($interval->h == 0) {
            if ($interval->i == 0) {
                $friendlyTime = "Vừa xong";
            } else {
                $friendlyTime = $interval->i . " phút trước";
            }
        } else {
            $friendlyTime = $interval->h . " giờ trước";
        }
    } else if ($interval->days == 1) {
        $friendlyTime = "Hôm qua, " . $checkInTime->format('H:i');
    } else if ($interval->days < 7) {
        $friendlyTime = $interval->days . " ngày trước";
    } else {
        $friendlyTime = $checkInTime->format('d/m/Y H:i');
    }
    
    $checkins[] = [
        'CheckinID' => $row['CheckinID'],
        'MuseumID' => $row['MuseumID'],
        'MuseumName' => $row['MuseumName'],
        'CheckinTime' => $row['CheckinTime'],
        'FriendlyTime' => $friendlyTime,
        'Caption' => $row['Caption'],  // Đổi từ Status
        'ApprovalStatus' => $row['ApprovalStatus'],
        'ApprovalStatusText' => $approvalStatusText,
        'StatusClass' => $statusClass,
        'PendingPoints' => $row['PendingPoints'],
        'ActualPoints' => $row['ActualPoints'],
        'Points' => $row['Points'],  // Backward compatibility
        'ProcessedAt' => $row['ProcessedAt'],
        'DeniedReason' => $row['DeniedReason'],
        'Latitude' => $row['Latitude'],
        'Longitude' => $row['Longitude'],
        'photos' => $photos
    ];
}

echo json_encode([
    'success' => true,
    'checkins' => $checkins
]);