<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // User not logged in
    echo json_encode([
        'loggedIn' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $sql = "SELECT UserToken, Username, Role, Score, STATUS, avatar FROM users WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Process avatar path - combine with base path
        $avatarPath = $row['avatar'] ? 'EXEProject/' . $row['avatar'] : 'EXEProject/avatar/default.png';
        
        $userInfo = [
            'loggedIn' => true,
            'userToken' => $row['UserToken'],
            'username' => $row['Username'],
            'role' => $row['Role'],
            'score' => (int)($row['Score'] ?: 0), // If null, default to 0
            'status' => $row['STATUS'],
            'avatar' => $avatarPath,
            'avatarRelative' => $row['avatar'] ?: 'avatar/default.png'
        ];
    } else {
        // User not found, return default values
        $userInfo = [
            'loggedIn' => true, // Session exists but user not found in DB
            'userToken' => null,
            'username' => 'Guest User',
            'role' => 'user',
            'score' => 0,
            'status' => 'inactive',
            'avatar' => 'EXEProject/avatar/default.png',
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