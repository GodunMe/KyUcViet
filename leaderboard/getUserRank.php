<?php
require_once "../db.php";
header('Content-Type: application/json; charset=utf-8');

// ✅ Lấy dữ liệu từ request
// Bạn có thể gửi bằng fetch('getUserRank.php?username=xxx') hoặc body JSON
$username = $_GET['username'] ?? null;

// ⚠️ Kiểm tra username
if (!$username) {
    echo json_encode(["error" => "Thiếu tham số username"]);
    exit;
}

// 1️⃣ Lấy điểm của user hiện tại
$stmt = $conn->prepare("SELECT Score FROM users WHERE Username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($score);
if (!$stmt->fetch()) {
    echo json_encode(["error" => "Không tìm thấy user"]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// 2️⃣ Lấy danh sách tất cả user sắp xếp theo điểm giảm dần
$query = "SELECT Username, Score FROM users ORDER BY Score DESC";
$result = $conn->query($query);

$rank = 1;
$userRank = null;
$previousScore = null;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($previousScore !== null && $row['Score'] < $previousScore) {
            $rank++;
        }
        $previousScore = $row['Score'];

        if ($row['Username'] === $username) {
            $userRank = $rank;
            break;
        }
    }
}

if ($userRank === null) {
    echo json_encode(["error" => "Không xác định được hạng"]);
    $conn->close();
    exit;
}

// 3️⃣ Tính điểm cần để lên hạng cao hơn
$stmt = $conn->prepare("SELECT Score FROM users WHERE Score > ? ORDER BY Score ASC LIMIT 1");
$stmt->bind_param("i", $score);
$stmt->execute();
$stmt->bind_result($nextScore);
if ($stmt->fetch()) {
    $need = $nextScore - $score;
} else {
    $need = 0;
}
$stmt->close();

// 4️⃣ Trả kết quả
echo json_encode([
    "username" => $username,
    "score" => $score,
    "rank" => $userRank,
    "need" => $need
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
