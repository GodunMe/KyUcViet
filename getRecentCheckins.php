<?php
/**
 * API lấy danh sách check-in gần đây của người dùng
 * 
 * Trả về danh sách các check-in gần đây của người dùng đang đăng nhập
 * Phản hồi: { success: true/false, checkins: [...] }
 */

header('Content-Type: application/json');

// Kết nối database
require_once 'db.php';

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
        c.Latitude,
        c.Longitude,
        p.PhotoPath
    FROM 
        checkins c
    JOIN 
        museum m ON c.MuseumID = m.MuseumID
    LEFT JOIN 
        checkin_photos p ON c.CheckinID = p.CheckinID
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
        'id' => $row['CheckinID'],
        'museumId' => $row['MuseumID'],
        'museumName' => $row['MuseumName'],
        'checkInTime' => $row['CheckinTime'],
        'friendlyTime' => $friendlyTime,
        'status' => $row['Status'],
        'points' => $row['Points'],
        'latitude' => $row['Latitude'],
        'longitude' => $row['Longitude'],
        'photoPath' => $row['PhotoPath']
    ];
}

echo json_encode([
    'success' => true,
    'checkins' => $checkins
]);