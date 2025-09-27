<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

include 'db.php';

// For demo purposes, using hardcoded user ID
// In real app, get from session or authentication
$userId = 1; // Replace with actual user ID from session

try {
    $sql = "SELECT points FROM users WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $points = $row['points'] ?: 0; // If null, default to 0
    } else {
        $points = 0; // User not found, default to 0
    }
    
    echo json_encode(['points' => (int)$points]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi khi lấy điểm: ' . $e->getMessage(), 'points' => 0]);
} finally {
    $conn->close();
}
?>