<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

include 'db.php';

// Debug: Check session content
$debugInfo = [
    'session_exists' => isset($_SESSION['UserToken']),
    'session_value' => $_SESSION['UserToken'] ?? 'NOT_SET',
    'all_sessions' => $_SESSION
];

// Check if user is logged in
if (!isset($_SESSION['UserToken']) || empty($_SESSION['UserToken'])) {
    // User not logged in
    echo json_encode([
        'loggedIn' => false,
        'message' => 'User not logged in',
        'debug' => $debugInfo
    ]);
    exit;
}

$userToken = $_SESSION['UserToken'];

try {
    $sql = "SELECT UserToken, Username, Role, Score, STATUS, avatar FROM users WHERE UserToken = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userToken);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Process avatar path - use relative path for client
        $avatarPath = $row['avatar'] ? $row['avatar'] : 'avatar/default.png';
        
        $userInfo = [
            'loggedIn' => true,
            'userToken' => $row['UserToken'],
            'username' => $row['Username'],
            'role' => $row['Role'],
            'score' => (int)($row['Score'] ?: 0), // If null, default to 0
            'status' => $row['STATUS'],
            'avatar' => $avatarPath,
            'avatarRelative' => $avatarPath
        ];
    } else {
        // User not found, return default values
        $userInfo = [
            'loggedIn' => false, // User not found in DB - treat as not logged in
            'userToken' => null,
            'username' => 'Guest User',
            'role' => 'user',
            'score' => 0,
            'status' => 'inactive',
            'avatar' => 'avatar/default.png',
            'avatarRelative' => 'avatar/default.png'
        ];
    }
    
    echo json_encode($userInfo);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'loggedIn' => false,
        'error' => 'Lỗi khi lấy thông tin user: ' . $e->getMessage(),
        'message' => 'Server error'
    ]);
} finally {
    $conn->close();
}
?>