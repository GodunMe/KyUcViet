<?php
/**
 * Pickup Lucky Coin API
 * 
 * Purpose: Handle coin pickup with photo evidence
 * Method: POST (multipart/form-data for photo upload)
 * Returns: JSON response
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../db.php';

// =====================================================
// Configuration
// =====================================================
define('COIN_LIFETIME_MINUTES', 10);
define('UPLOAD_DIR', __DIR__ . '/../uploads/lucky_coins/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);

// =====================================================
// Helper Functions
// =====================================================

/**
 * Validate user is logged in
 */
function validateUserLoggedIn($conn) {
    if (!isset($_SESSION['UserToken']) || empty($_SESSION['UserToken'])) {
        return [
            'success' => false,
            'error' => 'unauthorized',
            'message' => 'Bạn cần đăng nhập để nhặt xu may mắn'
        ];
    }
    return null;
}

/**
 * Validate coin exists and is still active
 */
function validateCoin($conn, $coinId) {
    $sql = "
        SELECT lc.*, m.MuseumName, m.Latitude, m.Longitude
        FROM lucky_coins lc
        INNER JOIN museum m ON lc.museum_id = m.MuseumID
        WHERE lc.id = ? 
        AND lc.spawn_time >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ";
    
    $stmt = $conn->prepare($sql);
    $lifetime = COIN_LIFETIME_MINUTES;
    $stmt->bind_param("ii", $coinId, $lifetime);
    $stmt->execute();
    $result = $stmt->get_result();
    $coin = $result->fetch_assoc();
    $stmt->close();
    
    if (!$coin) {
        return [
            'error' => [
                'success' => false,
                'error' => 'coin_expired',
                'message' => 'Xu may mắn đã hết hạn hoặc không tồn tại'
            ],
            'coin' => null
        ];
    }
    
    return ['error' => null, 'coin' => $coin];
}

/**
 * Check if user already picked this coin
 */
function checkDuplicatePickup($conn, $userToken, $coinId) {
    $sql = "SELECT id FROM coin_pickups WHERE user_token = ? AND coin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $userToken, $coinId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();

    if ($exists) {
        return [
            'success' => false,
            'error' => 'already_picked',
            'message' => 'Bạn đã nhặt xu này rồi!'
        ];
    }

    return null;
}

/**
 * Calculate distance between two GPS coordinates (Haversine formula)
 * Returns distance in meters
 */
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371000; // Earth radius in meters
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng/2) * sin($dLng/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    $distance = $earthRadius * $c;
    
    return round($distance, 2);
}

/**
 * Validate user location (must be within 100m of museum)
 */
function validateUserLocation($userLat, $userLng, $museumLat, $museumLng) {
    $maxDistance = 50000; // 100 meters
    
    $distance = calculateDistance($userLat, $userLng, $museumLat, $museumLng);
    
    if ($distance > $maxDistance) {
        return [
            'success' => false,
            'error' => 'too_far',
            'message' => "Bạn phải ở gần bảo tàng (trong bán kính {$maxDistance}m) để nhặt xu!\nKhoảng cách hiện tại: " . round($distance) . "m",
            'distance' => $distance
        ];
    }
    
    return ['success' => true, 'distance' => $distance];
}

/**
 * Validate uploaded photo
 */
function validatePhoto() {
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        return [
            'error' => [
                'success' => false,
                'error' => 'no_photo',
                'message' => 'Vui lòng chụp ảnh bằng chứng'
            ],
            'file' => null
        ];
    }
    
    $file = $_FILES['photo'];
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return [
            'error' => [
                'success' => false,
                'error' => 'file_too_large',
                'message' => 'Ảnh quá lớn (tối đa 5MB)'
            ],
            'file' => null
        ];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_TYPES)) {
        return [
            'error' => [
                'success' => false,
                'error' => 'invalid_type',
                'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, WEBP)'
            ],
            'file' => null
        ];
    }
    
    return ['error' => null, 'file' => $file];
}

/**
 * Save uploaded photo
 */
function savePhoto($file, $userToken, $coinId) {
    // Create upload directory if not exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Generate unique filename using token hash
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $tokenHash = substr(md5($userToken), 0, 8);
    $filename = sprintf('coin_%d_user_%s_%s.%s', $coinId, $tokenHash, date('YmdHis'), $extension);
    
    $filepath = UPLOAD_DIR . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'error' => [
                'success' => false,
                'error' => 'upload_failed',
                'message' => 'Không thể lưu ảnh'
            ],
            'path' => null
        ];
    }
    
    // Return relative path for DB storage
    $relativePath = 'uploads/lucky_coins/' . $filename;
    return ['error' => null, 'path' => $relativePath];
}

/**
 * Create coin pickup record
 */
function createPickupRecord($conn, $userToken, $coinId, $photoPath) {
    $sql = "INSERT INTO coin_pickups (coin_id, user_token, photo_path, status, created_at) VALUES (?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $coinId, $userToken, $photoPath);

    if (!$stmt->execute()) {
        $err = $conn->error;
        $stmt->close();
        return [
            'success' => false,
            'error' => 'db_error',
            'message' => 'Không thể tạo yêu cầu nhặt xu: ' . $err
        ];
    }

    $pickupId = $stmt->insert_id;
    $stmt->close();

    return [
        'success' => true,
        'pickup_id' => $pickupId
    ];
}

// =====================================================
// Main Execution
// =====================================================

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'method_not_allowed',
            'message' => 'Chỉ chấp nhận POST request'
        ]);
        exit;
    }
    
    // Validate user logged in
    $error = validateUserLoggedIn($conn);
    if ($error) {
        http_response_code(401);
        echo json_encode($error);
        exit;
    }
    
    // Get user token from session
    $userToken = $_SESSION['UserToken'];
    
    // Get coin_id from POST
    $coinId = isset($_POST['coin_id']) ? (int)$_POST['coin_id'] : 0;
    
    if (!$coinId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'missing_coin_id',
            'message' => 'Thiếu thông tin coin_id'
        ]);
        exit;
    }
    
    // Validate coin exists and is active
    $validation = validateCoin($conn, $coinId);
    if ($validation['error']) {
        http_response_code(400);
        echo json_encode($validation['error']);
        exit;
    }
    $coin = $validation['coin'];
    
    // Validate user location (GPS check)
    $userLat = isset($_POST['user_lat']) ? floatval($_POST['user_lat']) : null;
    $userLng = isset($_POST['user_lng']) ? floatval($_POST['user_lng']) : null;
    
    if ($userLat === null || $userLng === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'missing_location',
            'message' => 'Thiếu thông tin vị trí GPS. Vui lòng bật GPS và thử lại!'
        ]);
        exit;
    }
    
    $museumLat = floatval($coin['Latitude']);
    $museumLng = floatval($coin['Longitude']);
    
    $locationCheck = validateUserLocation($userLat, $userLng, $museumLat, $museumLng);
    if (!$locationCheck['success']) {
        http_response_code(400);
        echo json_encode($locationCheck);
        exit;
    }
    $distance = $locationCheck['distance'];
    
    // Check duplicate pickup
    $error = checkDuplicatePickup($conn, $userToken, $coinId);
    if ($error) {
        http_response_code(400);
        echo json_encode($error);
        exit;
    }
    
    // Validate photo
    $validation = validatePhoto();
    if ($validation['error']) {
        http_response_code(400);
        echo json_encode($validation['error']);
        exit;
    }
    $file = $validation['file'];
    
    // Save photo
    $saveResult = savePhoto($file, $userToken, $coinId);
    if ($saveResult['error']) {
        http_response_code(500);
        echo json_encode($saveResult['error']);
        exit;
    }
    $photoPath = $saveResult['path'];
    
    // Create pickup record
    $result = createPickupRecord($conn, $userToken, $coinId, $photoPath);
    
    if (!$result['success']) {
        http_response_code(500);
        echo json_encode($result);
        exit;
    }
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Nhặt xu thành công! Đang chờ admin duyệt.',
        'pickup_id' => $result['pickup_id'],
        'coin_id' => $coinId,
        'museum_name' => $coin['MuseumName'],
        'status' => 'pending',
        'points_pending' => 100,
        'distance' => round($distance)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'internal_error',
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
