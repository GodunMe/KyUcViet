<?php
require_once "../db.php";

// Lấy top 10 người có điểm cao nhất
$sql = "SELECT Username, Role, Score, avatar FROM users ORDER BY Score DESC LIMIT 10";
$result = $conn->query($sql);

$leaderboard = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Nếu avatar rỗng thì dùng avatar mặc định
        if (empty($row['avatar'])) {
            $row['avatar'] = "../avatar/default.png";
        }
        $leaderboard[] = $row;
    }
}

// Trả về JSON với header chuẩn
header('Content-Type: application/json; charset=utf-8');
echo json_encode($leaderboard, JSON_UNESCAPED_UNICODE);
$conn->close();