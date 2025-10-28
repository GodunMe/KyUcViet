<?php
/**
 * Get Coin Pickup History API
 * 
 * Purpose: Return user's coin pickup history with status
 * Returns: JSON array of user's coin pickups
 * Usage: Called by map.html to show pickup history modal
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../db.php';
session_start();

// =====================================================
// Check Authentication
// =====================================================
if (!isset($_SESSION['UserToken'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'message' => 'Vui lòng đăng nhập để xem lịch sử'
    ], JSON_PRETTY_PRINT);
    exit;
}

$userToken = $_SESSION['UserToken'];

// =====================================================
// Get User's Coin Pickup History
// =====================================================
try {
    $sql = "
        SELECT 
            cp.id as pickup_id,
            cp.coin_id,
            lc.museum_id,
            m.MuseumName as museum_name,
            m.Address as museum_address,
            cp.created_at as pickup_time,
            cp.photo_path,
            cp.status,
            cp.points_awarded,
            cp.reject_reason,
            lc.spawn_time as coin_spawn_time,
            CASE 
                WHEN cp.status = 'pending' THEN '⏳ Chờ duyệt'
                WHEN cp.status = 'approved' THEN '✅ Đã duyệt'
                WHEN cp.status = 'rejected' THEN '❌ Bị từ chối'
                ELSE cp.status
            END as status_label
        FROM coin_pickups cp
        LEFT JOIN lucky_coins lc ON cp.coin_id = lc.id
        LEFT JOIN museum m ON lc.museum_id = m.MuseumID
        WHERE cp.user_token = ?
        ORDER BY cp.created_at DESC
        LIMIT 50
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userToken);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $pickupTime = new DateTime($row['pickup_time']);
        
        $history[] = [
            'pickup_id' => (int)$row['pickup_id'],
            'coin_id' => (int)$row['coin_id'],
            'museum_id' => $row['museum_id'] ? (int)$row['museum_id'] : null,
            'museum_name' => $row['museum_name'] ?: 'Không xác định',
            'museum_address' => $row['museum_address'],
            'pickup_time' => $pickupTime->format('d/m/Y H:i'),
            'pickup_time_full' => $pickupTime->format('Y-m-d H:i:s'),
            'photo_path' => $row['photo_path'],
            'points_awarded' => (int)$row['points_awarded'],
            'status' => $row['status'],
            'status_label' => $row['status_label'],
            'reject_reason' => $row['reject_reason'],
            'coin_spawn_time' => $row['coin_spawn_time'] ? (new DateTime($row['coin_spawn_time']))->format('d/m/Y H:i') : null
        ];
    }
    
    $stmt->close();
    
    // Return response
    echo json_encode([
        'success' => true,
        'count' => count($history),
        'history' => $history,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
