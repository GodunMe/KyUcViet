<?php
/**
 * API tạo bài đăng check-in
 * 
 * Lưu thông tin check-in vào cơ sở dữ liệu và liên kết với ảnh đã tải lên
 * Yêu cầu: museumId, latitude, longitude, status, photoPath, privacy
 * Phản hồi: { success: true/false, checkinId: [id của check-in vừa tạo], points: [điểm thưởng] }
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

// Kiểm tra dữ liệu đầu vào
$requiredParams = ['museumId', 'latitude', 'longitude', 'status', 'photoPath'];
$missingParams = [];

foreach ($requiredParams as $param) {
    if (!isset($_POST[$param])) {
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
$museumId = intval($_POST['museumId']);
$latitude = floatval($_POST['latitude']);
$longitude = floatval($_POST['longitude']);
$status = $_POST['status'];
$photoPath = $_POST['photoPath'];
$privacy = isset($_POST['privacy']) ? $_POST['privacy'] : 'public';

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
    
    // 2. Thêm ảnh check-in
    $stmt = $conn->prepare("
        INSERT INTO checkin_photos (CheckinID, PhotoPath, UploadOrder)
        VALUES (?, ?, 1)
    ");
    $stmt->bind_param("is", $checkinId, $photoPath);
    $stmt->execute();
    
    // 3. Tính điểm thưởng (20 điểm cho mỗi lần check-in)
    $points = 20;
    
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