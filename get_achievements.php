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

// --- New achievement checks added (checkin counts, score thresholds, coin placeholder, all-achievements) ---
// Resolve IDs for newly added achievements (names must match create_achievements.sql)
$checkin5Name = 'Sống chọn câu truyện I';
$checkin10Name = 'Sống chọn câu truyện II';
$checkin20Name = 'Sống chọn câu truyện III';
$points100Name = 'Năng động, tích cực';
$points500Name = 'Lịch sử trong tay';
$firstCoinName = 'Bí mật bảo tàng';
$heroName = 'Anh Hùng Bảo Tàng';

$checkin5Id = getAchievementIdByName($conn, $checkin5Name);
$checkin10Id = getAchievementIdByName($conn, $checkin10Name);
$checkin20Id = getAchievementIdByName($conn, $checkin20Name);
$points100Id = getAchievementIdByName($conn, $points100Name);
$points500Id = getAchievementIdByName($conn, $points500Name);
$firstCoinId = getAchievementIdByName($conn, $firstCoinName);
$heroId = getAchievementIdByName($conn, $heroName);

// 4) Check checkin counts (count distinct museums visited via checkins)
// Assumption: a "check-in 5/10/20 bảo tàng" means distinct MuseumID entries. If you prefer counting rows instead, change COUNT(DISTINCT MuseumID) to COUNT(*)
$checkinCount = 0;
$cStmt = $conn->prepare("SELECT COUNT(DISTINCT MuseumID) AS cnt FROM checkins WHERE UserToken = ?");
if ($cStmt) {
    $cStmt->bind_param('s', $userToken);
    $cStmt->execute();
    $cRow = $cStmt->get_result()->fetch_assoc();
    $checkinCount = isset($cRow['cnt']) ? intval($cRow['cnt']) : 0;
}

if ($checkinCount >= 5 && $checkin5Id) awardAchievement($conn, $userToken, $checkin5Id);
if ($checkinCount >= 10 && $checkin10Id) awardAchievement($conn, $userToken, $checkin10Id);
if ($checkinCount >= 20 && $checkin20Id) awardAchievement($conn, $userToken, $checkin20Id);

// 5) Check user score thresholds
$userScore = 0;
$sStmt = $conn->prepare("SELECT COALESCE(Score, Score, 0) AS sc FROM users WHERE UserToken = ? LIMIT 1");
if ($sStmt) {
    $sStmt->bind_param('s', $userToken);
    $sStmt->execute();
    $sRow = $sStmt->get_result()->fetch_assoc();
    if ($sRow) $userScore = intval($sRow['sc']);
}

if ($userScore >= 100 && $points100Id) awardAchievement($conn, $userToken, $points100Id);
if ($userScore >= 500 && $points500Id) awardAchievement($conn, $userToken, $points500Id);

// 6) Coin pickup: placeholder — per request we skip checking for "Bí mật bảo tàng" here
// (If you want automatic awarding when a coin pickup event exists, add a query against the appropriate table.)

// 7) Anh Hùng Bảo Tàng: award if user has all other achievements (exclude this hero if user doesn't already have it)
if ($heroId) {
    // Total achievements in system
    $totStmt = $conn->prepare("SELECT COUNT(*) AS total FROM achievements");
    $totalAchievements = 0;
    if ($totStmt) {
        $totStmt->execute();
        $totRow = $totStmt->get_result()->fetch_assoc();
        $totalAchievements = isset($totRow['total']) ? intval($totRow['total']) : 0;
    }

    // Count user's achievements
    $uaStmt = $conn->prepare("SELECT COUNT(*) AS userCount FROM user_achievements WHERE UserToken = ?");
    $userAchCount = 0;
    if ($uaStmt) {
        $uaStmt->bind_param('s', $userToken);
        $uaStmt->execute();
        $uaRow = $uaStmt->get_result()->fetch_assoc();
        $userAchCount = isset($uaRow['userCount']) ? intval($uaRow['userCount']) : 0;
    }

    // Check if user already has hero
    $hasHero = false;
    $hStmt = $conn->prepare("SELECT 1 FROM user_achievements WHERE UserToken = ? AND AchievementID = ? LIMIT 1");
    if ($hStmt) {
        $hStmt->bind_param('si', $userToken, $heroId);
        $hStmt->execute();
        $hasHero = (bool) $hStmt->get_result()->fetch_assoc();
    }

    // If user doesn't have hero yet, require userAchCount >= totalAchievements - 1 (i.e., has every other achievement)
    if (!$hasHero && $totalAchievements > 0) {
        if ($userAchCount >= ($totalAchievements - 1)) {
            awardAchievement($conn, $userToken, $heroId);
        }
    }
}

// 4) Fetch all achievements with earned flag for this user
$sql = "SELECT a.ID, a.Name, a.Description, a.Icon, ua.CreatedAt, CASE WHEN ua.UserToken IS NULL THEN 0 ELSE 1 END AS earned
        FROM achievements a
        LEFT JOIN user_achievements ua ON ua.AchievementID = a.ID AND ua.UserToken = ?
        ORDER BY a.ID ASC";
$stmt = $conn->prepare($sql);
$achievements = array();
if ($stmt) {
    $stmt->bind_param('s', $userToken);
    $stmt->execute();
    $res = $stmt->get_result();
    $achievements = $res->fetch_all(MYSQLI_ASSOC);
}

// Build earned list ordered by CreatedAt desc and a complete list
$earned = array();
 $all = array();
foreach ($achievements as $a) {
    $a['earned'] = isset($a['earned']) && intval($a['earned']) === 1;
    $all[] = $a;
    if ($a['earned']) $earned[] = $a;
}

// keep $all in the original order returned by the query (ID asc)
// Sort earned by CreatedAt desc if CreatedAt exists
usort($earned, function($x, $y){
    $tx = isset($x['CreatedAt']) ? strtotime($x['CreatedAt']) : 0;
    $ty = isset($y['CreatedAt']) ? strtotime($y['CreatedAt']) : 0;
    return $ty - $tx;
});

// Build top-3 display: prefer earned ones, then fill with next all achievements
$topThree = array();
foreach ($earned as $e) {
    if (count($topThree) >= 3) break;
    $topThree[] = $e;
}
if (count($topThree) < 3) {
    foreach ($all as $a) {
        if (count($topThree) >= 3) break;
        // skip if already in topThree
        $found = false;
        foreach ($topThree as $t) if ($t['ID'] == $a['ID']) { $found = true; break; }
        if ($found) continue;
        $topThree[] = $a;
    }
}

// Render HTML fragment: show top 3 and a toggle to view all achievements
echo '<div class="achievements-container">';
echo '<div class="achievement-grid achievement-grid-3">';
foreach ($topThree as $a) {
    $name = htmlspecialchars($a['Name']);
    $desc = htmlspecialchars($a['Description']);
    $icon = !empty($a['Icon']) ? '/' . ltrim($a['Icon'], '/') : '';
    $cls = $a['earned'] ? 'earned' : 'locked';

    echo '<div class="achievement-card ' . $cls . '">';
    if ($icon) echo '<img src="' . $icon . '" alt="' . $name . '" class="achievement-icon">';
    else echo '<div class="achievement-icon">🏅</div>';
    echo '<div class="achievement-meta">';
    echo '<div class="achievement-name">' . $name . '</div>';
    if (!empty($desc)) echo '<div class="achievement-desc">' . $desc . '</div>';
    echo '</div>';
    echo '</div>';
}
echo '</div>'; // .achievement-grid-3

// Toggle button and full list (hidden by default)
// Reduce top margins so the expanded panel appears closer to the achievements header
// Slightly reduced margins so the expanded panel appears closer to the top-3
echo '<div class="achievement-actions" style="margin-top:2px;">';
echo '<button id="showAllAchievementsBtn" class="username-modal-button">Xem tất cả thành tích</button>';
echo '</div>';

echo '<div id="allAchievementsPanel" class="all-achievements-panel" style="display:none;margin-top:2px;">';
// Avoid duplicating the top-three items: show only remaining achievements inside the panel
echo '<div class="achievement-grid">';
// compute IDs in topThree
$topThreeIds = array_map(function($i){ return intval($i['ID']); }, $topThree);
$remaining = array_filter($all, function($a) use ($topThreeIds) {
    return !in_array(intval($a['ID']), $topThreeIds);
});
foreach ($remaining as $a) {
    $name = htmlspecialchars($a['Name']);
    $desc = htmlspecialchars($a['Description']);
    $icon = !empty($a['Icon']) ? '/' . ltrim($a['Icon'], '/') : '';
    $cls = $a['earned'] ? 'earned' : 'locked';

    echo '<div class="achievement-card ' . $cls . '">';
    if ($icon) echo '<img src="' . $icon . '" alt="' . $name . '" class="achievement-icon">';
    else echo '<div class="achievement-icon">🏅</div>';
    echo '<div class="achievement-meta">';
    echo '<div class="achievement-name">' . $name . '</div>';
    if (!empty($desc)) echo '<div class="achievement-desc">' . $desc . '</div>';
    echo '</div>';
    echo '</div>';
}
echo '</div>'; // .achievement-grid
// Add bottom hide button inside the panel so it appears after all achievements
echo '<div style="margin-top:6px; text-align:left;">';
echo '<button id="hideAllAchievementsBtn" class="username-modal-button">Ẩn bớt</button>';
echo '</div>';
echo '</div>'; // #allAchievementsPanel

echo '</div>'; // .achievements-container

?>