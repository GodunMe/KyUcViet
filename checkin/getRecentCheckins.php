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

// Truy vấn danh sách check-in gần đây
$stmt = $conn->prepare("
    SELECT 
        c.CheckinID,
        c.MuseumID,
        m.MuseumName,
        c.CheckinTime,
        c.Status,
        c.Points,
        c.Privacy,
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
    
    // Định dạng thời gian check-in để hiển thị thân thiện
    $checkInTime = new DateTime($row['CheckinTime']);
    $now = new DateTime();
    $interval = $now->diff($checkInTime);
    
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
        'Status' => $row['Status'],
        'Points' => $row['Points'],
        'Privacy' => $row['Privacy'],
        'Latitude' => $row['Latitude'],
        'Longitude' => $row['Longitude'],
        'photos' => $photos
    ];
}

echo json_encode([
    'success' => true,
    'checkins' => $checkins
]);