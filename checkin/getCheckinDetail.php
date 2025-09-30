<?php
/**
 * API Get Check-in Detail
 * Returns detailed information about a specific check-in including photos
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../db.php';
session_start();

try {
    // Check if user is logged in
    if (!isset($_SESSION['UserToken'])) {
        throw new Exception('Unauthorized: Please login first');
    }

    // Get checkin ID from request
    $checkinId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$checkinId) {
        throw new Exception('Checkin ID is required');
    }

    $userToken = $_SESSION['UserToken'];

    // Get check-in details with museum information
    $stmt = $conn->prepare("
        SELECT 
            c.CheckinID,
            c.UserToken,
            c.MuseumID,
            c.Latitude,
            c.Longitude,
            c.Status,
            c.CheckinTime,
            c.Points,
            m.MuseumName,
            m.Address as MuseumAddress,
            m.Description as MuseumDescription,
            u.Username
        FROM checkins c
        JOIN museum m ON c.MuseumID = m.MuseumID
        JOIN users u ON c.UserToken = u.UserToken
        WHERE c.CheckinID = ? AND c.UserToken = ?
    ");
    
    $stmt->bind_param("is", $checkinId, $userToken);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Check-in not found or access denied');
    }
    
    $checkin = $result->fetch_assoc();
    
    // Check if this was the first check-in of the day at this museum
    $checkinDate = date('Y-m-d', strtotime($checkin['CheckinTime']));
    $museumId = intval($checkin['MuseumID']);  // Ensure MuseumID is integer
    
    // Count ALL check-ins at THIS SPECIFIC MUSEUM on the same date
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_checkins_today
        FROM checkins 
        WHERE UserToken = ? 
        AND MuseumID = ? 
        AND DATE(CheckinTime) = ?
    ");
    
    $stmt->bind_param("sis", $userToken, $museumId, $checkinDate);
    $stmt->execute();
    $countResult = $stmt->get_result();
    $countData = $countResult->fetch_assoc();
    
    // Find the order of this specific check-in within the day AT THIS MUSEUM
    $stmt = $conn->prepare("
        SELECT COUNT(*) as checkins_before_this
        FROM checkins 
        WHERE UserToken = ? 
        AND MuseumID = ? 
        AND DATE(CheckinTime) = ?
        AND CheckinTime < ?
    ");
    
    $stmt->bind_param("siss", $userToken, $museumId, $checkinDate, $checkin['CheckinTime']);
    $stmt->execute();
    $orderResult = $stmt->get_result();
    $orderData = $orderResult->fetch_assoc();
    
    // This is first check-in if there are no check-ins before it on the same day AT THIS MUSEUM
    $isFirstCheckinToday = ($orderData['checkins_before_this'] == 0);
    $pointsEarned = $isFirstCheckinToday ? intval($checkin['Points']) : 0;
    
    // Enhanced debug log
    error_log("Debug Checkin Detail: CheckinID={$checkinId}, UserToken={$userToken}, MuseumID={$museumId}, Date={$checkinDate}, TotalTodayAtThisMuseum={$countData['total_checkins_today']}, CheckinsBeforeThisAtThisMuseum={$orderData['checkins_before_this']}, IsFirstAtThisMuseumToday={$isFirstCheckinToday}, PointsInDB={$checkin['Points']}, PointsEarned={$pointsEarned}");
    
    // Get photos for this check-in
    $stmt = $conn->prepare("
        SELECT 
            PhotoID,
            PhotoPath,
            Caption,
            UploadOrder
        FROM checkin_photos 
        WHERE CheckinID = ? 
        ORDER BY UploadOrder ASC
    ");
    
    $stmt->bind_param("i", $checkinId);
    $stmt->execute();
    $photosResult = $stmt->get_result();
    
    $photos = [];
    while ($photo = $photosResult->fetch_assoc()) {
        $photos[] = [
            'id' => $photo['PhotoID'],
            'path' => $photo['PhotoPath'],
            'caption' => $photo['Caption'],
            'order' => $photo['UploadOrder']
        ];
    }
    
    // Get museum media for header image
    $stmt = $conn->prepare("
        SELECT 
            file_path,
            mime_type,
            file_name
        FROM museum_media 
        WHERE MuseumId = ? 
        ORDER BY id ASC 
        LIMIT 1
    ");
    
    $stmt->bind_param("i", $checkin['MuseumID']);
    $stmt->execute();
    $mediaResult = $stmt->get_result();
    
    $museumImage = null;
    if ($mediaResult->num_rows > 0) {
        $media = $mediaResult->fetch_assoc();
        $museumImage = $media['file_path'];
    }
    
    // Format the response
    $checkinDetail = [
        'id' => $checkin['CheckinID'],
        'user' => [
            'token' => $checkin['UserToken'],
            'name' => $checkin['Username']
        ],
        'museum' => [
            'id' => $checkin['MuseumID'],
            'name' => $checkin['MuseumName'],
            'address' => $checkin['MuseumAddress'],
            'description' => $checkin['MuseumDescription'],
            'image' => $museumImage
        ],
        'location' => [
            'latitude' => floatval($checkin['Latitude']),
            'longitude' => floatval($checkin['Longitude'])
        ],
        'checkin' => [
            'time' => $checkin['CheckinTime'],
            'status' => $checkin['Status'],
            'points' => intval($checkin['Points']),
            'pointsEarned' => $pointsEarned,
            'isFirstCheckinToday' => $isFirstCheckinToday,
            'timeFormatted' => formatTime($checkin['CheckinTime'])
        ],
        'photos' => $photos,
        'stats' => [
            'totalPhotos' => count($photos),
            'hasStatus' => !empty($checkin['Status'])
        ]
    ];

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Check-in detail retrieved successfully',
        'data' => $checkinDetail
    ]);

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ]);
}

/**
 * Format timestamp to readable format
 */
function formatTime($timestamp) {
    $now = new DateTime();
    $checkinTime = new DateTime($timestamp);
    $diff = $now->diff($checkinTime);
    
    if ($diff->days > 0) {
        if ($diff->days == 1) {
            return 'Hôm qua lúc ' . $checkinTime->format('H:i');
        } else {
            return $checkinTime->format('d/m/Y H:i');
        }
    } elseif ($diff->h > 0) {
        return $diff->h . ' giờ trước';
    } elseif ($diff->i > 0) {
        return $diff->i . ' phút trước';
    } else {
        return 'Vừa xong';
    }
}

$conn->close();
?>