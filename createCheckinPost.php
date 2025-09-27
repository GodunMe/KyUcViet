<?php
/**
 * API tạo bài đăng check-in mới
 * 
 * Lưu thông tin check-in vào cơ sở dữ liệu và liên kết với nhiều ảnh đã tải lên
 * Input: JSON { museumId, latitude, longitude, status, privacy, photos: [array of photo paths] }
 * Output: { success: true/false, checkinId: [id], points: [points] }
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

// Đọc dữ liệu JSON từ request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false, 
        'error' => 'Dữ liệu đầu vào không hợp lệ'
    ]);
    exit;
}

// Kiểm tra dữ liệu đầu vào
$requiredParams = ['museumId', 'latitude', 'longitude', 'photos'];
$missingParams = [];

foreach ($requiredParams as $param) {
    if (!isset($input[$param])) {
        $missingParams[] = $param;
    }
}

if (!empty($missingParams)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Thiếu thông tin: ' . implode(', ', $missingParams)
    ]);
    exit;
}

// Lấy dữ liệu đầu vào
$userToken = $_SESSION['UserToken'];
$museumId = intval($input['museumId']);
$latitude = floatval($input['latitude']);
$longitude = floatval($input['longitude']);
$status = isset($input['status']) ? $input['status'] : '';
$photos = $input['photos']; // Array of photo paths
$privacy = isset($input['privacy']) ? $input['privacy'] : 'public';

// Kiểm tra photos array
if (!is_array($photos) || empty($photos)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Cần ít nhất 1 ảnh để check-in'
    ]);
    exit;
}

if (count($photos) > 10) {
    echo json_encode([
        'success' => false, 
        'error' => 'Tối đa 10 ảnh cho mỗi lần check-in'
    ]);
    exit;
}

// Kiểm tra quyền riêng tư hợp lệ
if (!in_array($privacy, ['public', 'friends', 'private'])) {
    $privacy = 'public';
}

// Kiểm tra lại tư cách check-in
$eligibilityUrl = "http://{$_SERVER['HTTP_HOST']}/checkCheckinEligibility.php";
$eligibilityData = [
    'museumId' => $museumId
];

// Sử dụng curl để gọi API kiểm tra tư cách
$ch = curl_init($eligibilityUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $eligibilityData);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
curl_close($ch);

$eligibility = json_decode($response, true);

if (!$eligibility['success'] || !$eligibility['canCheckin']) {
    echo json_encode([
        'success' => false, 
        'error' => $eligibility['reason'] ?? 'Bạn không đủ điều kiện để check-in'
    ]);
    exit;
}

// Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
$conn->begin_transaction();

try {
    // 1. Thêm check-in mới
    $stmt = $conn->prepare("
        INSERT INTO checkins (UserToken, MuseumID, Latitude, Longitude, Status, Privacy)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("siddsss", $userToken, $museumId, $latitude, $longitude, $status, $privacy);
    $stmt->execute();
    
    $checkinId = $conn->insert_id;
    
    // 2. Thêm ảnh check-in (hỗ trợ nhiều ảnh)
    $stmt = $conn->prepare("
        INSERT INTO checkin_photos (CheckinID, PhotoPath, UploadOrder)
        VALUES (?, ?, ?)
    ");
    
    foreach ($photos as $index => $photoPath) {
        $uploadOrder = $index + 1;
        $stmt->bind_param("isi", $checkinId, $photoPath, $uploadOrder);
        $stmt->execute();
    }
    
    // 3. Tính điểm thưởng (50 điểm cho mỗi lần check-in + bonus cho nhiều ảnh)
    $basePoints = 50;
    $photoBonus = (count($photos) - 1) * 5; // 5 điểm cho mỗi ảnh thêm
    $points = $basePoints + $photoBonus;
    
    // Cập nhật điểm cho check-in
    $stmt = $conn->prepare("UPDATE checkins SET Points = ? WHERE CheckinID = ?");
    $stmt->bind_param("ii", $points, $checkinId);
    $stmt->execute();
    
    // 4. Cập nhật điểm người dùng
    $stmt = $conn->prepare("UPDATE users SET Points = Points + ? WHERE UserToken = ?");
    $stmt->bind_param("is", $points, $userToken);
    $stmt->execute();
    
    // 5. Kiểm tra và cập nhật giới hạn check-in hàng ngày
    $today = date('Y-m-d');
    
    // Kiểm tra xem đã check-in tại bảo tàng này hôm nay chưa
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS visit_count 
        FROM checkins 
        WHERE UserToken = ? AND MuseumID = ? AND DATE(CheckinTime) = ?
    ");
    $stmt->bind_param("sis", $userToken, $museumId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $visitInfo = $result->fetch_assoc();
    
    // Nếu đây là lần check-in đầu tiên tại bảo tàng này hôm nay, cập nhật số bảo tàng đã ghé thăm
    if ($visitInfo['visit_count'] <= 1) { // ≤ 1 vì chúng ta vừa thêm 1 lần check-in
        // Kiểm tra xem có bản ghi giới hạn hàng ngày không
        $stmt = $conn->prepare("
            SELECT * FROM daily_checkin_limits 
            WHERE UserToken = ? AND CheckinDate = ?
        ");
        $stmt->bind_param("ss", $userToken, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Tạo mới bản ghi giới hạn hàng ngày
            $stmt = $conn->prepare("
                INSERT INTO daily_checkin_limits (UserToken, CheckinDate, MuseumsVisitedCount) 
                VALUES (?, ?, 1)
            ");
            $stmt->bind_param("ss", $userToken, $today);
            $stmt->execute();
        } else {
            // Cập nhật bản ghi hiện có
            $stmt = $conn->prepare("
                UPDATE daily_checkin_limits 
                SET MuseumsVisitedCount = MuseumsVisitedCount + 1 
                WHERE UserToken = ? AND CheckinDate = ?
            ");
            $stmt->bind_param("ss", $userToken, $today);
            $stmt->execute();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Trả về thành công
    echo json_encode([
        'success' => true,
        'checkinId' => $checkinId,
        'points' => $points,
        'message' => 'Check-in thành công! Bạn nhận được ' . $points . ' điểm.'
    ]);
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi khi tạo check-in: ' . $e->getMessage()
    ]);
}