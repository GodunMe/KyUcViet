<?php
// get_achievements.php
// Checks user eligibility (check-ins and leaderboard rank), awards achievements if applicable,
// and returns an HTML fragment listing the user's highest achievements.

session_start();
require_once 'db.php';

// Get user token from session or GET (for testing)
$userToken = isset($_SESSION['UserToken']) ? $_SESSION['UserToken'] : (isset($_GET['token']) ? $_GET['token'] : '');
if (!$userToken) {
    http_response_code(400);
    echo "<p>No user token provided.</p>";
    exit;
}

// Helper: get achievement ID by name
function getAchievementIdByName($conn, $name) {
    $sql = "SELECT ID FROM achievements WHERE Name = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    return $row ? intval($row['ID']) : null;
}

// Helper: award achievement (insert if missing) and remove lower tiers if requested
function awardAchievement($conn, $userToken, $achievementId) {
    if (!$achievementId) return false;
    // Insert ignore to avoid duplicates
    $ins = $conn->prepare("INSERT IGNORE INTO user_achievements (UserToken, AchievementID) VALUES (?, ?)");
    if (!$ins) return false;
    $ins->bind_param('si', $userToken, $achievementId);
    $ins->execute();
    return $ins->affected_rows >= 0;
}

// Helper: delete achievements by IDs for a user
function deleteUserAchievementsByIds($conn, $userToken, $ids) {
    if (empty($ids)) return;
    // build placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    // we will prepare a dynamic statement using types
    $types = str_repeat('i', count($ids));
    $sql = "DELETE FROM user_achievements WHERE UserToken = ? AND AchievementID IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return;
    // bind params dynamically
    $bind_names[] = 's' . $types; // first param types string begins with 's' for UserToken
    // but mysqli bind_param requires arguments passed by reference and separate types string
    // We'll build call_user_func_array style
    $params = array();
    $params[] = & $bind_names[0];
    // We need to set bind_names[0] to the type string
    $bind_names[0] = 's' . $types;
    // Push userToken and ids
    $params[] = & $userToken;
    foreach ($ids as $i => $id) {
        $params[] = & $ids[$i];
    }
    // Unfortunately, building dynamic bind is verbose; fallback: delete using IN with literal ints (safe because IDs are ints)
    $idsEscaped = array_map('intval', $ids);
    $sql2 = "DELETE FROM user_achievements WHERE UserToken = ? AND AchievementID IN (" . implode(',', $idsEscaped) . ")";
    $stmt2 = $conn->prepare($sql2);
    if (!$stmt2) return;
    $stmt2->bind_param('s', $userToken);
    $stmt2->execute();
}

// Simplified achievement checks — only the three achievements defined in create_achievements.sql
// Names must match entries in create_achievements.sql exactly.
$firstQuizName = 'Dấu Ấn Đầu Tiên';           // uploads/icon/first_quiz.png
$firstCheckinName = 'Nhà Khám Phá Văn Hóa';   // uploads/icon/first_checkin.png
$firstAvatarName = 'Lịch sử đẹp đẽ';          // uploads/icon/first_avatar.png

// Resolve IDs for the three achievements
$firstQuizId = getAchievementIdByName($conn, $firstQuizName);
$firstCheckinId = getAchievementIdByName($conn, $firstCheckinName);
$firstAvatarId = getAchievementIdByName($conn, $firstAvatarName);

// 1) Check if user has any checkin
$hasCheckin = false;
$chkStmt = $conn->prepare("SELECT 1 FROM checkins WHERE UserToken = ? LIMIT 1");
if ($chkStmt) {
    $chkStmt->bind_param('s', $userToken);
    $chkStmt->execute();
    $chkRes = $chkStmt->get_result();
    $hasCheckin = (bool) $chkRes->fetch_assoc();
}

// 2) Check if user changed avatar (avatar != 'avatar/default.png')
$hasChangedAvatar = false;
$uStmt = $conn->prepare("SELECT avatar FROM users WHERE UserToken = ? LIMIT 1");
if ($uStmt) {
    $uStmt->bind_param('s', $userToken);
    $uStmt->execute();
    $uRow = $uStmt->get_result()->fetch_assoc();
    if ($uRow) {
        $avatar = isset($uRow['avatar']) ? ltrim($uRow['avatar'], '/') : '';
        if ($avatar !== '' && $avatar !== 'avatar/default.png') {
            $hasChangedAvatar = true;
        }
    }
}

// 3) Check if user completed any quiz (table: user_do_quiz)
$hasCompletedQuiz = false;
$qStmt = $conn->prepare("SELECT 1 FROM user_do_quiz WHERE UserToken = ? LIMIT 1");
if ($qStmt) {
    $qStmt->bind_param('s', $userToken);
    $qStmt->execute();
    $qRes = $qStmt->get_result();
    $hasCompletedQuiz = (bool) $qRes->fetch_assoc();
}

// Award the three achievements when conditions met
if ($hasCompletedQuiz && $firstQuizId) {
    awardAchievement($conn, $userToken, $firstQuizId);
}
if ($hasCheckin && $firstCheckinId) {
    awardAchievement($conn, $userToken, $firstCheckinId);
}
if ($hasChangedAvatar && $firstAvatarId) {
    awardAchievement($conn, $userToken, $firstAvatarId);
}

// Remove any user achievements that are NOT one of the three tracked achievements
$keepIds = array_filter([$firstQuizId, $firstCheckinId, $firstAvatarId], function($v){ return is_int($v) || (is_numeric($v) && intval($v)>0); });
if (!empty($keepIds)) {
    $keepInts = array_map('intval', $keepIds);
    $sqlDel = "DELETE FROM user_achievements WHERE UserToken = ? AND AchievementID NOT IN (" . implode(',', $keepInts) . ")";
    $delStmt = $conn->prepare($sqlDel);
    if ($delStmt) {
        $delStmt->bind_param('s', $userToken);
        $delStmt->execute();
    }
} else {
    // If none of the three achievements exist in DB, remove all user achievements to keep only controlled set
    $delAll = $conn->prepare("DELETE FROM user_achievements WHERE UserToken = ?");
    if ($delAll) {
        $delAll->bind_param('s', $userToken);
        $delAll->execute();
    }
}

// 4) Fetch and render user's achievements (only those present in user_achievements)
$sql = "SELECT a.ID, a.Name, a.Description, a.Icon, ua.CreatedAt
        FROM achievements a
        JOIN user_achievements ua ON ua.AchievementID = a.ID
        WHERE ua.UserToken = ?
        ORDER BY ua.CreatedAt DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $userToken);
$stmt->execute();
$result = $stmt->get_result();
$achievements = $result->fetch_all(MYSQLI_ASSOC);

// Debug: Hiển thị thông tin achievements
echo "<!-- Debug: Achievement data -->\n";
foreach ($achievements as $achievement) {
    echo "<!-- Achievement: " . print_r($achievement, true) . " -->\n";
}

// Render HTML fragment
if (empty($achievements)) {
    echo '<div class="activity-list"><p>Người dùng chưa có thành tựu.</p></div>';
    exit;
}

echo '<div class="activity-list">';
foreach ($achievements as $a) {
    $name = htmlspecialchars($a['Name']);
    $desc = htmlspecialchars($a['Description']);
    $icon = !empty($a['Icon']) ? '/' . $a['Icon'] : null;
    
    echo '<div class="activity-item">';
    // Activity icon section with icon image or fallback emoji
    if ($icon) {
        echo '<div class="activity-icon">';
        echo '<img src="' . $icon . '" 
             alt="' . $name . '" 
             style="width:32px;height:32px;object-fit:cover;border-radius:4px;">';
        echo '</div>';
    } else {
        echo '<div class="activity-icon">🏅</div>';
    }
    // Activity info section
    echo '<div class="activity-info">';
    echo '<p><strong title="' . $name . '">' . $name . '</strong></p>';
    if (!empty($desc)) {
        echo '<p title="' . $desc . '">' . $desc . '</p>';
    }
    echo '</div>';
    echo '</div>';
}
echo '</div>';

?>