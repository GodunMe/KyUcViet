<?php
/**
 * Get User Profile API
 * Returns complete user profile with ranking information
 * 
 * @author Vietnamese Memories Team
 * @version 1.0
 * @since 2025-10-28
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['UserToken']) || empty($_SESSION['UserToken'])) {
    echo json_encode([
        'success' => false,
        'loggedIn' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

$userToken = $_SESSION['UserToken'];

try {
    // Get user basic info first (including avatar)
    $userSql = "SELECT UserToken, Username, Role, Score, avatar FROM users WHERE UserToken = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->bind_param("s", $userToken);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    
    if (!$userResult || $userResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'loggedIn' => true,
            'message' => 'User not found'
        ]);
        exit;
    }
    
    $userInfo = $userResult->fetch_assoc();
    
    // Get complete ranking info from view (for ranking data only)
    $profileSql = "SELECT * FROM user_profile_view WHERE UserToken = ?";
    $profileStmt = $conn->prepare($profileSql);
    $profileStmt->bind_param("s", $userToken);
    $profileStmt->execute();
    $profileResult = $profileStmt->get_result();
    
    // Merge user info with ranking info
    $profile = $userInfo; // Start with user info (includes avatar)
    if ($profileResult && $profileResult->num_rows > 0) {
        $rankingInfo = $profileResult->fetch_assoc();
        $profile = array_merge($profile, $rankingInfo); // Merge ranking data
        // Ensure avatar from users table takes precedence
        $profile['avatar'] = $userInfo['avatar'];
    }
    
    // Get user's rank position in leaderboard
    $rankPositionSql = "SELECT RankPosition FROM leaderboard_view WHERE UserToken = ?";
    $rankStmt = $conn->prepare($rankPositionSql);
    $rankStmt->bind_param("s", $userToken);
    $rankStmt->execute();
    $rankResult = $rankStmt->get_result();
    $rankPosition = $rankResult->num_rows > 0 ? $rankResult->fetch_assoc()['RankPosition'] : 'N/A';
    
    // Get recent achievements (last 5)
    $achievementsSql = "
        SELECT a.NameVi, a.Icon, a.Points, a.Rarity, a.Color, ua.EarnedDate
        FROM user_achievements ua
        JOIN achievements a ON ua.AchievementID = a.AchievementID
        WHERE ua.UserToken = ?
        ORDER BY ua.EarnedDate DESC
        LIMIT 5
    ";
    $achievementsStmt = $conn->prepare($achievementsSql);
    $achievementsStmt->bind_param("s", $userToken);
    $achievementsStmt->execute();
    $achievementsResult = $achievementsStmt->get_result();
    $recentAchievements = $achievementsResult->fetch_all(MYSQLI_ASSOC);
    
    // Get recent activities (last 10)
    $activitiesSql = "
        SELECT 
            ActivityType,
            Title,
            Description,
            Points,
            CreatedAt,
            CASE 
                WHEN ActivityType = 'checkin' THEN '📍'
                WHEN ActivityType = 'quiz' THEN '🧠'
                WHEN ActivityType = 'achievement' THEN '🏅'
                WHEN ActivityType = 'rank_up' THEN '⬆️'
                WHEN ActivityType = 'coin_pickup' THEN '🪙'
                ELSE '📋'
            END as Icon
        FROM user_activities
        WHERE UserToken = ?
        ORDER BY CreatedAt DESC
        LIMIT 10
    ";
    $activitiesStmt = $conn->prepare($activitiesSql);
    $activitiesStmt->bind_param("s", $userToken);
    $activitiesStmt->execute();
    $activitiesResult = $activitiesStmt->get_result();
    $recentActivities = $activitiesResult->fetch_all(MYSQLI_ASSOC);
    
    // Format activities with relative time
    foreach ($recentActivities as &$activity) {
        $activity['timeAgo'] = formatTimeAgo($activity['CreatedAt']);
    }
    
    // Prepare avatar path - logic đúng
    if ($profile['avatar'] && !empty(trim($profile['avatar']))) {
        // User có avatar path trong DB → dùng path đó
        $avatarPath = '../' . $profile['avatar'];
    } else {
        // User không có avatar path trong DB → dùng default
        $avatarPath = '../avatar/default.png';
    }
    
    // Debug avatar
    error_log("Avatar debug - Raw: " . ($profile['avatar'] ?? 'NULL') . ", Final Path: " . $avatarPath);
    
    // Calculate next rank info
    $nextRankInfo = null;
    if (isset($profile['NextRankMinPoints']) && $profile['NextRankMinPoints']) {
        $pointsNeeded = $profile['NextRankMinPoints'] - $profile['Score'];
        $nextRankInfo = [
            'name' => $profile['NextRankName'] ?? '',
            'pointsNeeded' => $pointsNeeded,
            'progress' => $profile['ProgressToNextRank'] ?? 0
        ];
    }
    
    // Return complete profile data
    echo json_encode([
        'success' => true,
        'loggedIn' => true,
        'profile' => [
            'userToken' => $profile['UserToken'],
            'username' => $profile['Username'],
            'score' => (int)$profile['Score'],
            'avatar' => $avatarPath,
            'avatarRelative' => $profile['avatar'] && !empty(trim($profile['avatar'])) 
                ? $profile['avatar'] 
                : 'avatar/default.png',
            'role' => $profile['Role'],
            
            // Ranking info
            'rank' => [
                'id' => (int)($profile['CurrentRank'] ?? 1),
                'name' => $profile['RankName'] ?? 'Iron',
                'nameVi' => $profile['RankNameVi'] ?? 'Sắt',
                'icon' => $profile['RankIcon'] ?? '⚪',
                'color' => $profile['RankColor'] ?? '#666666',
                'bgColor' => $profile['RankBGColor'] ?? '#f0f0f0',
                'bonusMultiplier' => (float)($profile['BonusMultiplier'] ?? 1.0)
            ],
            
            // Statistics
            'stats' => [
                'totalCheckins' => (int)($profile['TotalCheckins'] ?? 0),
                'totalQuizzes' => (int)($profile['TotalQuizzes'] ?? 0),
                'perfectQuizzes' => (int)($profile['PerfectQuizzes'] ?? 0),
                'museumVisited' => (int)($profile['MuseumsVisited'] ?? 0),
                'streakDays' => (int)($profile['StreakDays'] ?? 0),
                'leaderboardPosition' => $rankPosition
            ],
            
            // Progress info
            'nextRank' => $nextRankInfo,
            
            // Recent data
            'recentAchievements' => $recentAchievements,
            'recentActivities' => $recentActivities,
            
            // Additional info
            'lastActiveDate' => $profile['LastActiveDate'] ?? null
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'loggedIn' => true,
        'message' => 'Error loading profile: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}

/**
 * Format time ago string in Vietnamese
 */
function formatTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Vừa xong';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' phút trước';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' giờ trước';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        if ($days == 1) return 'Hôm qua';
        return $days . ' ngày trước';
    } else {
        return date('d/m/Y', strtotime($datetime));
    }
}
?>