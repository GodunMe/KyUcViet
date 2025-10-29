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

// 1) CHECK-IN BASED ACHIEVEMENTS
// thresholds -> achievement names (must match entries in achievements table)
$checkinMilestones = [
    1 => 'Khám phá đầu tiên',
    3 => 'Tập sự - 3 lần check-in',
    5 => 'Chuyên gia - 5 lần check-in',
    10 => 'Bậc thầy - 10 lần check-in'
];

// Count user's check-ins (across all museums)
$checkinSql = "SELECT COUNT(*) AS cnt FROM checkins WHERE UserToken = ?";
$checkinStmt = $conn->prepare($checkinSql);
$checkinStmt->bind_param('s', $userToken);
$checkinStmt->execute();
$checkinRes = $checkinStmt->get_result()->fetch_assoc();
$checkinCount = intval($checkinRes['cnt']);

// Determine highest milestone achieved
$achievedCheckinMilestone = 0;
foreach ($checkinMilestones as $threshold => $name) {
    if ($checkinCount >= $threshold) $achievedCheckinMilestone = $threshold;
}

if ($achievedCheckinMilestone > 0) {
    $achName = $checkinMilestones[$achievedCheckinMilestone];
    $achId = getAchievementIdByName($conn, $achName);
    if ($achId) {
        // Award highest milestone
        awardAchievement($conn, $userToken, $achId);
        // Remove lower-tier check-in achievements (thresholds < achievedCheckinMilestone)
        $toRemoveNames = [];
        foreach ($checkinMilestones as $threshold => $name) {
            if ($threshold < $achievedCheckinMilestone) $toRemoveNames[] = $name;
        }
        if (!empty($toRemoveNames)) {
            $idsToRemove = [];
            foreach ($toRemoveNames as $n) {
                $id = getAchievementIdByName($conn, $n);
                if ($id) $idsToRemove[] = $id;
            }
            if (!empty($idsToRemove)) deleteUserAchievementsByIds($conn, $userToken, $idsToRemove);
        }
    }
}

// 2) LEADERBOARD ACHIEVEMENTS (Top 1-3)
// Compute user's rank based on Score (lower rank number is better)
$scoreSql = "SELECT Score FROM users WHERE UserToken = ? LIMIT 1";
$sStmt = $conn->prepare($scoreSql);
$sStmt->bind_param('s', $userToken);
$sStmt->execute();
$sRow = $sStmt->get_result()->fetch_assoc();
if ($sRow) {
    $userScore = intval($sRow['Score']);
    // rank = 1 + number of users with Score > userScore
    $rankSql = "SELECT COUNT(*) + 1 AS rank FROM users WHERE Score > ?";
    $rStmt = $conn->prepare($rankSql);
    $rStmt->bind_param('i', $userScore);
    $rStmt->execute();
    $rRow = $rStmt->get_result()->fetch_assoc();
    $userRank = intval($rRow['rank']);

    if ($userRank <= 3) {
        $topName = 'Top ' . $userRank . ' Bảng xếp hạng';
        $topId = getAchievementIdByName($conn, $topName);
        if ($topId) {
            awardAchievement($conn, $userToken, $topId);
            // Remove lower leaderboard tiers (e.g., if rank=2 remove Top 3)
            $lowerRanks = [];
            for ($r = $userRank + 1; $r <= 3; $r++) $lowerRanks[] = 'Top ' . $r . ' Bảng xếp hạng';
            $idsToRemove = [];
            foreach ($lowerRanks as $n) {
                $id = getAchievementIdByName($conn, $n);
                if ($id) $idsToRemove[] = $id;
            }
            if (!empty($idsToRemove)) deleteUserAchievementsByIds($conn, $userToken, $idsToRemove);
        }
    } else {
        // If user previously had Top1/2/3 but now no longer at that rank, do NOT remove automatically.
        // (Optional) If you want to revoke leaderboard achievements when rank drops, implement deletion here.
    }
}

// 3) Optionally: other achievements (e.g., quiz completion) are awarded elsewhere (doquiz.php). You can add checks here if desired.

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