<?php
/**
 * Get Lucky Coins API
 * 
 * Purpose: Return all active lucky coins for map display
 * Returns: JSON array of active coins with museum details
 * Usage: Called by map.html to show coin markers
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../db.php';

// =====================================================
// Configuration
// =====================================================
define('COIN_LIFETIME_MINUTES', 1440); // Coin lifetime: 1 day (24 hours = 1440 minutes)

// =====================================================
// Helper Functions
// =====================================================

/**
 * Get all active lucky coins with museum details
 */
function getActiveLuckyCoins($conn) {
    $sql = "
        SELECT 
            lc.id as coin_id,
            lc.museum_id,
            lc.spawn_time,
            m.MuseumName as museum_name,
            m.Latitude as latitude,
            m.Longitude as longitude,
            m.Address as address,
            TIMESTAMPDIFF(SECOND, lc.spawn_time, NOW()) as elapsed_seconds,
            (? * 60 - TIMESTAMPDIFF(SECOND, lc.spawn_time, NOW())) as remaining_seconds
        FROM lucky_coins lc
        INNER JOIN museum m ON lc.museum_id = m.MuseumID
        WHERE lc.spawn_time >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ORDER BY lc.spawn_time DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $lifetime = COIN_LIFETIME_MINUTES;
    $stmt->bind_param("ii", $lifetime, $lifetime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $coins = [];
    while ($row = $result->fetch_assoc()) {
        // Format spawn time
        $spawnTime = new DateTime($row['spawn_time']);
        
        // Calculate expiry time
        $expiryTime = clone $spawnTime;
        $expiryTime->modify('+' . COIN_LIFETIME_MINUTES . ' minutes');
        
        $coins[] = [
            'coin_id' => (int)$row['coin_id'],
            'museum_id' => (int)$row['museum_id'],
            'museum_name' => $row['museum_name'],
            'address' => $row['address'],
            'latitude' => (float)$row['latitude'],
            'longitude' => (float)$row['longitude'],
            'spawn_time' => $spawnTime->format('Y-m-d H:i:s'),
            'expiry_time' => $expiryTime->format('Y-m-d H:i:s'),
            'remaining_seconds' => max(0, (int)$row['remaining_seconds']),
            'remaining_minutes' => max(0, ceil($row['remaining_seconds'] / 60)),
            'is_expiring_soon' => $row['remaining_seconds'] < 120 // < 2 minutes
        ];
    }
    
    $stmt->close();
    return $coins;
}

/**
 * Check if user has already picked up this coin
 */
function hasUserPickedCoin($conn, $userToken, $coinId) {
    if (!$userToken || !$coinId) return false;
    
    $sql = "SELECT COUNT(*) as count FROM coin_pickups WHERE user_token = ? AND coin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $userToken, $coinId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

/**
 * Get user token from session (if logged in)
 */
function getCurrentUserToken() {
    session_start();
    return isset($_SESSION['UserToken']) ? $_SESSION['UserToken'] : null;
}

// =====================================================
// Main Execution
// =====================================================

try {
    // Get active coins
    $coins = getActiveLuckyCoins($conn);
    
    // Get current user token (if logged in)
    $userToken = getCurrentUserToken();
    
    // Add user-specific info to each coin
    if ($userToken) {
        foreach ($coins as &$coin) {
            $coin['already_picked'] = hasUserPickedCoin($conn, $userToken, $coin['coin_id']);
        }
    } else {
        // Not logged in
        foreach ($coins as &$coin) {
            $coin['already_picked'] = false;
        }
    }
    
    // Return response
    echo json_encode([
        'success' => true,
        'count' => count($coins),
        'coins' => $coins,
        'user_logged_in' => $userToken !== null,
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
