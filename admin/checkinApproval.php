<?php
/**
 * Admin API for Check-in Approval Management
 * Allows admin to approve, deny, or manage check-in submissions
 */
header('Content-Type: application/json');
require_once '../db.php';
session_start();

// Admin authentication check
if (!isset($_SESSION['UserToken'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Please login first']);
    exit;
}

// Check if user is admin based on Role
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
if (!isAdmin($user['Role'])) {
    echo json_encode(['success' => false, 'error' => 'Admin access required. Current role: ' . $user['Role']]);
    exit;
}

/**
 * Function to check if user is admin based on Role
 */
function isAdmin($role) {
    $adminRoles = ['Admin', 'admin', 'ADMIN', 'Administrator', 'administrator'];
    return in_array($role, $adminRoles);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'getPending':
            getPendingCheckins($conn);
            break;
            
        case 'approve':
            approveCheckin($conn, $userToken);
            break;
            
        case 'deny':
            denyCheckin($conn, $userToken);
            break;
            
        case 'getDetail':
            getCheckinDetail($conn);
            break;
            
        case 'getStats':
            getApprovalStats($conn);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}

/**
 * Get pending check-ins for admin review
 */
function getPendingCheckins($conn) {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    $stmt = $conn->prepare("
        SELECT 
            c.CheckinID,
            c.UserToken,
            u.Username,
            c.MuseumID,
            m.MuseumName,
            c.CheckinTime,
            c.Caption,
            c.PendingPoints,
            c.Latitude,
            c.Longitude,
            COUNT(cp.PhotoID) as PhotoCount
        FROM checkins c
        JOIN users u ON c.UserToken = u.UserToken
        JOIN museum m ON c.MuseumID = m.MuseumID
        LEFT JOIN checkin_photos cp ON c.CheckinID = cp.CheckinID
        WHERE c.ApprovalStatus = 'none'
        GROUP BY c.CheckinID
        ORDER BY c.CheckinTime ASC
        LIMIT ?, ?
    ");
    
    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $checkins = [];
    while ($row = $result->fetch_assoc()) {
        $checkins[] = [
            'id' => $row['CheckinID'],
            'user' => [
                'token' => $row['UserToken'],
                'name' => $row['Username']
            ],
            'museum' => [
                'id' => $row['MuseumID'],
                'name' => $row['MuseumName']
            ],
            'checkinTime' => $row['CheckinTime'],
            'caption' => $row['Caption'],
            'pendingPoints' => $row['PendingPoints'],
            'photoCount' => $row['PhotoCount'],
            'timeAgo' => getTimeAgo($row['CheckinTime'])
        ];
    }
    
    // Get total count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM checkins WHERE ApprovalStatus = 'none'");
    $stmt->execute();
    $totalResult = $stmt->get_result();
    $total = $totalResult->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $checkins,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'hasMore' => ($offset + $limit) < $total
        ]
    ]);
}

/**
 * Approve a check-in
 */
function approveCheckin($conn, $adminToken) {
    $checkinId = isset($_POST['checkinId']) ? intval($_POST['checkinId']) : 0;
    $approvedPoints = isset($_POST['approvedPoints']) ? intval($_POST['approvedPoints']) : 0;
    $adminNote = isset($_POST['adminNote']) ? trim($_POST['adminNote']) : '';
    
    if (!$checkinId) {
        echo json_encode(['success' => false, 'error' => 'Check-in ID required']);
        return;
    }
    
    // Get current check-in details
    $stmt = $conn->prepare("
        SELECT UserToken, PendingPoints, ApprovalStatus 
        FROM checkins 
        WHERE CheckinID = ?
    ");
    $stmt->bind_param("i", $checkinId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Check-in not found']);
        return;
    }
    
    $checkin = $result->fetch_assoc();
    
    if ($checkin['ApprovalStatus'] !== 'none') {
        echo json_encode(['success' => false, 'error' => 'Check-in already processed']);
        return;
    }
    
    // Use pending points if approved points not specified
    if ($approvedPoints <= 0) {
        $approvedPoints = $checkin['PendingPoints'];
    }
    
    $conn->begin_transaction();
    
    try {
        // Update check-in status
        $stmt = $conn->prepare("
            UPDATE checkins 
            SET ApprovalStatus = 'approved', 
                ActualPoints = ?, 
                ProcessedAt = NOW(), 
                ProcessedBy = ?
            WHERE CheckinID = ?
        ");
        $stmt->bind_param("isi", $approvedPoints, $adminToken, $checkinId);
        $stmt->execute();
        
        // Add points to user account
        $stmt = $conn->prepare("
            UPDATE users 
            SET Score = Score + ? 
            WHERE UserToken = ?
        ");
        $stmt->bind_param("is", $approvedPoints, $checkin['UserToken']);
        $stmt->execute();
        
        // Create audit trail
        $stmt = $conn->prepare("
            INSERT INTO checkin_status_history 
            (CheckinID, OldStatus, NewStatus, OldPoints, NewPoints, AdminToken, Reason)
            VALUES (?, 'none', 'approved', 0, ?, ?, ?)
        ");
        $reason = $adminNote ?: 'Approved by admin';
        $stmt->bind_param("iiss", $checkinId, $approvedPoints, $adminToken, $reason);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Check-in approved successfully',
            'pointsAwarded' => $approvedPoints
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Failed to approve: ' . $e->getMessage()]);
    }
}

/**
 * Deny a check-in
 */
function denyCheckin($conn, $adminToken) {
    $checkinId = isset($_POST['checkinId']) ? intval($_POST['checkinId']) : 0;
    $denyReason = isset($_POST['denyReason']) ? trim($_POST['denyReason']) : '';
    
    if (!$checkinId) {
        echo json_encode(['success' => false, 'error' => 'Check-in ID required']);
        return;
    }
    
    if (empty($denyReason)) {
        echo json_encode(['success' => false, 'error' => 'Deny reason required']);
        return;
    }
    
    // Get current check-in details
    $stmt = $conn->prepare("
        SELECT ApprovalStatus 
        FROM checkins 
        WHERE CheckinID = ?
    ");
    $stmt->bind_param("i", $checkinId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Check-in not found']);
        return;
    }
    
    $checkin = $result->fetch_assoc();
    
    if ($checkin['ApprovalStatus'] !== 'none') {
        echo json_encode(['success' => false, 'error' => 'Check-in already processed']);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Update check-in status
        $stmt = $conn->prepare("
            UPDATE checkins 
            SET ApprovalStatus = 'denied', 
                DeniedReason = ?, 
                ProcessedAt = NOW(), 
                ProcessedBy = ?
            WHERE CheckinID = ?
        ");
        $stmt->bind_param("ssi", $denyReason, $adminToken, $checkinId);
        $stmt->execute();
        
        // Create audit trail
        $stmt = $conn->prepare("
            INSERT INTO checkin_status_history 
            (CheckinID, OldStatus, NewStatus, OldPoints, NewPoints, AdminToken, Reason)
            VALUES (?, 'none', 'denied', 0, 0, ?, ?)
        ");
        $stmt->bind_param("iss", $checkinId, $adminToken, $denyReason);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Check-in denied successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Failed to deny: ' . $e->getMessage()]);
    }
}

/**
 * Get detailed check-in information for admin review
 */
function getCheckinDetail($conn) {
    $checkinId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$checkinId) {
        echo json_encode(['success' => false, 'error' => 'Check-in ID required']);
        return;
    }
    
    // Get check-in details
    $stmt = $conn->prepare("
        SELECT 
            c.CheckinID,
            c.UserToken,
            u.Username,
            u.Email,
            c.MuseumID,
            m.MuseumName,
            m.Address,
            c.CheckinTime,
            c.Caption,
            c.ApprovalStatus,
            c.PendingPoints,
            c.ActualPoints,
            c.ProcessedAt,
            c.ProcessedBy,
            c.DeniedReason,
            c.Latitude,
            c.Longitude
        FROM checkins c
        JOIN users u ON c.UserToken = u.UserToken
        JOIN museum m ON c.MuseumID = m.MuseumID
        WHERE c.CheckinID = ?
    ");
    
    $stmt->bind_param("i", $checkinId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Check-in not found']);
        return;
    }
    
    $checkin = $result->fetch_assoc();
    
    // Get photos
    $stmt = $conn->prepare("
        SELECT PhotoPath, Caption, UploadOrder 
        FROM checkin_photos 
        WHERE CheckinID = ? 
        ORDER BY UploadOrder
    ");
    $stmt->bind_param("i", $checkinId);
    $stmt->execute();
    $photosResult = $stmt->get_result();
    
    $photos = [];
    while ($photo = $photosResult->fetch_assoc()) {
        $photos[] = $photo;
    }
    
    // Get status history
    $stmt = $conn->prepare("
        SELECT 
            OldStatus, NewStatus, OldPoints, NewPoints, 
            AdminToken, Reason, ChangedAt
        FROM checkin_status_history 
        WHERE CheckinID = ? 
        ORDER BY ChangedAt DESC
    ");
    $stmt->bind_param("i", $checkinId);
    $stmt->execute();
    $historyResult = $stmt->get_result();
    
    $history = [];
    while ($historyRow = $historyResult->fetch_assoc()) {
        $history[] = $historyRow;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'checkin' => $checkin,
            'photos' => $photos,
            'history' => $history
        ]
    ]);
}

/**
 * Get approval statistics
 */
function getApprovalStats($conn) {
    $stmt = $conn->prepare("
        SELECT 
            ApprovalStatus,
            COUNT(*) as count,
            SUM(CASE WHEN ApprovalStatus = 'approved' THEN ActualPoints ELSE 0 END) as totalPointsAwarded
        FROM checkins 
        GROUP BY ApprovalStatus
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stats = [
        'none' => 0,
        'approved' => 0,
        'denied' => 0,
        'totalPointsAwarded' => 0
    ];
    
    while ($row = $result->fetch_assoc()) {
        $stats[$row['ApprovalStatus']] = $row['count'];
        if ($row['ApprovalStatus'] === 'approved') {
            $stats['totalPointsAwarded'] = $row['totalPointsAwarded'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

/**
 * Helper function to format time ago
 */
function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'vừa xong';
    } elseif ($time < 3600) {
        return round($time / 60) . ' phút trước';
    } elseif ($time < 86400) {
        return round($time / 3600) . ' giờ trước';
    } else {
        return round($time / 86400) . ' ngày trước';
    }
}

$conn->close();
?>