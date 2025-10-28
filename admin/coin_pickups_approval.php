<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['UserToken'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userToken = $_SESSION['UserToken'];
$stmt = $conn->prepare("SELECT Role FROM users WHERE UserToken = ?");
$stmt->bind_param("s", $userToken);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

function isAdmin($role) {
    return in_array(strtolower($role), ['admin', 'administrator']);
}

if (!isAdmin($user['Role'])) {
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (empty($action)) {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit;
}

function getPendingPickups($conn) {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $stmt = $conn->prepare("SELECT cp.id as pickup_id, cp.coin_id, cp.user_token, u.Username as username, u.Score as user_total_points, lc.museum_id, m.MuseumName as museum_name, m.Address as museum_address, cp.photo_path, cp.status, cp.points_awarded, cp.created_at, lc.spawn_time, TIMESTAMPDIFF(MINUTE, lc.spawn_time, cp.created_at) as pickup_delay_minutes FROM coin_pickups cp JOIN users u ON cp.user_token = u.UserToken JOIN lucky_coins lc ON cp.coin_id = lc.id JOIN museum m ON lc.museum_id = m.MuseumID WHERE cp.status = 'pending' ORDER BY cp.created_at ASC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $pickups = [];
    while ($row = $result->fetch_assoc()) {
        $row['photo_url'] = '/' . $row['photo_path'];
        $pickups[] = $row;
    }
    $stmt->close();
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM coin_pickups WHERE status = 'pending'");
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    $countStmt->close();
    echo json_encode(['success' => true, 'pickups' => $pickups, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
}

function getPickupsByStatus($conn) {
    $status = $_GET['status'] ?? 'pending';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $validStatuses = ['pending', 'approved', 'rejected'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        return;
    }
    $stmt = $conn->prepare("SELECT cp.id as pickup_id, cp.coin_id, cp.user_token, u.Username as username, u.Score as user_total_points, lc.museum_id, m.MuseumName as museum_name, m.Address as museum_address, cp.photo_path, cp.status, cp.points_awarded, cp.reject_reason, cp.created_at, lc.spawn_time, TIMESTAMPDIFF(MINUTE, lc.spawn_time, cp.created_at) as pickup_delay_minutes FROM coin_pickups cp JOIN users u ON cp.user_token = u.UserToken JOIN lucky_coins lc ON cp.coin_id = lc.id JOIN museum m ON lc.museum_id = m.MuseumID WHERE cp.status = ? ORDER BY cp.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $status, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $pickups = [];
    while ($row = $result->fetch_assoc()) {
        $row['photo_url'] = '/' . $row['photo_path'];
        $pickups[] = $row;
    }
    $stmt->close();
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM coin_pickups WHERE status = ?");
    $countStmt->bind_param("s", $status);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    $countStmt->close();
    echo json_encode(['success' => true, 'pickups' => $pickups, 'total' => $total, 'status' => $status, 'limit' => $limit, 'offset' => $offset]);
}

function approvePickup($conn) {
    $pickupId = isset($_POST['pickup_id']) ? intval($_POST['pickup_id']) : 0;
    if ($pickupId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid pickup ID']);
        return;
    }
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT cp.user_token, cp.points_awarded, cp.status, u.Username as username FROM coin_pickups cp JOIN users u ON cp.user_token = u.UserToken WHERE cp.id = ?");
        $stmt->bind_param("i", $pickupId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception('Pickup not found');
        }
        $pickup = $result->fetch_assoc();
        $stmt->close();
        if ($pickup['status'] !== 'pending') {
            throw new Exception('Pickup already processed');
        }
        $userToken = $pickup['user_token'];
        $points = $pickup['points_awarded'];
        $username = $pickup['username'];
        $updateStmt = $conn->prepare("UPDATE coin_pickups SET status = 'approved' WHERE id = ?");
        $updateStmt->bind_param("i", $pickupId);
        $updateStmt->execute();
        $updateStmt->close();
        $pointsStmt = $conn->prepare("UPDATE users SET Score = Score + ? WHERE UserToken = ?");
        $pointsStmt->bind_param("is", $points, $userToken);
        $pointsStmt->execute();
        $pointsStmt->close();
        $getPointsStmt = $conn->prepare("SELECT Score FROM users WHERE UserToken = ?");
        $getPointsStmt->bind_param("s", $userToken);
        $getPointsStmt->execute();
        $pointsResult = $getPointsStmt->get_result();
        $newPoints = $pointsResult->fetch_assoc()['Score'];
        $getPointsStmt->close();
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Pickup approved successfully', 'pickup_id' => $pickupId, 'username' => $username, 'points_awarded' => $points, 'new_total_points' => $newPoints]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function rejectPickup($conn) {
    $pickupId = isset($_POST['pickup_id']) ? intval($_POST['pickup_id']) : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    if ($pickupId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid pickup ID']);
        return;
    }
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT cp.status, u.Username as username FROM coin_pickups cp JOIN users u ON cp.user_token = u.UserToken WHERE cp.id = ?");
        $stmt->bind_param("i", $pickupId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception('Pickup not found');
        }
        $pickup = $result->fetch_assoc();
        $stmt->close();
        if ($pickup['status'] !== 'pending') {
            throw new Exception('Pickup already processed');
        }
        $username = $pickup['username'];
        $updateStmt = $conn->prepare("UPDATE coin_pickups SET status = 'rejected', reject_reason = ? WHERE id = ?");
        $updateStmt->bind_param("si", $reason, $pickupId);
        $updateStmt->execute();
        $updateStmt->close();
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Pickup rejected successfully', 'pickup_id' => $pickupId, 'username' => $username, 'reason' => $reason]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getPickupDetail($conn) {
    $pickupId = isset($_GET['pickup_id']) ? intval($_GET['pickup_id']) : 0;
    if ($pickupId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid pickup ID']);
        return;
    }
    $stmt = $conn->prepare("SELECT cp.id as pickup_id, cp.coin_id, cp.user_token, u.Username as username, u.Score as user_total_points, lc.museum_id, m.MuseumName as museum_name, m.Address as museum_address, m.Latitude as museum_lat, m.Longitude as museum_lng, cp.photo_path, cp.status, cp.points_awarded, cp.reject_reason, cp.created_at, lc.spawn_time, TIMESTAMPDIFF(MINUTE, lc.spawn_time, cp.created_at) as pickup_delay_minutes, TIMESTAMPDIFF(SECOND, lc.spawn_time, NOW()) as coin_age_seconds FROM coin_pickups cp JOIN users u ON cp.user_token = u.UserToken JOIN lucky_coins lc ON cp.coin_id = lc.id JOIN museum m ON lc.museum_id = m.MuseumID WHERE cp.id = ?");
    $stmt->bind_param("i", $pickupId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Pickup not found']);
        return;
    }
    $pickup = $result->fetch_assoc();
    $stmt->close();
    $pickup['photo_url'] = '/' . $pickup['photo_path'];
    $pickup['was_coin_expired'] = $pickup['pickup_delay_minutes'] > 10;
    echo json_encode(['success' => true, 'pickup' => $pickup]);
}

function getApprovalStats($conn) {
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count, SUM(points_awarded) as total_points FROM coin_pickups GROUP BY status");
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total_points_awarded' => 0];
    while ($row = $result->fetch_assoc()) {
        $stats[$row['status']] = $row['count'];
        if ($row['status'] === 'approved') {
            $stats['total_points_awarded'] = $row['total_points'] ?? 0;
        }
    }
    $stmt->close();
    $todayStmt = $conn->prepare("SELECT COUNT(*) as today_pickups, SUM(CASE WHEN status = 'approved' THEN points_awarded ELSE 0 END) as today_points FROM coin_pickups WHERE DATE(created_at) = CURDATE()");
    $todayStmt->execute();
    $todayResult = $todayStmt->get_result();
    $todayStats = $todayResult->fetch_assoc();
    $todayStmt->close();
    $stats['today_pickups'] = $todayStats['today_pickups'] ?? 0;
    $stats['today_points'] = $todayStats['today_points'] ?? 0;
    $activeCoinsStmt = $conn->prepare("SELECT COUNT(*) as active_coins FROM lucky_coins WHERE spawn_time >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $activeCoinsStmt->execute();
    $activeCoinsResult = $activeCoinsStmt->get_result();
    $stats['active_coins'] = $activeCoinsResult->fetch_assoc()['active_coins'];
    $activeCoinsStmt->close();
    echo json_encode(['success' => true, 'stats' => $stats]);
}

try {
    switch ($action) {
        case 'getPending': getPendingPickups($conn); break;
        case 'getPickups': getPickupsByStatus($conn); break;
        case 'approve': approvePickup($conn); break;
        case 'reject': rejectPickup($conn); break;
        case 'getDetail': getPickupDetail($conn); break;
        case 'getStats': getApprovalStats($conn); break;
        default: echo json_encode(['success' => false, 'error' => 'Unknown action']); break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
