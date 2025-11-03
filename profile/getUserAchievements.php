<?php
/**
 * Get User Achievements API
 * Returns all achievements with earned status for the user
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
    // Get all achievements with user's earned status
    $achievementsSql = "
        SELECT 
            a.*,
            CASE WHEN ua.UserToken IS NOT NULL THEN true ELSE false END as isEarned,
            ua.EarnedDate,
            CASE 
                WHEN a.RequirementType = 'checkin_count' THEN u.TotalCheckins
                WHEN a.RequirementType = 'quiz_perfect' THEN u.PerfectQuizzes
                WHEN a.RequirementType = 'quiz_score' THEN u.TotalQuizzes
                WHEN a.RequirementType = 'museums_visited' THEN u.MuseumsVisited
                WHEN a.RequirementType = 'streak_days' THEN u.StreakDays
                WHEN a.RequirementType = 'total_points' THEN u.Score
                WHEN a.RequirementType = 'rank_achieved' THEN u.CurrentRank
                ELSE 0
            END as userProgress
        FROM achievements a
        LEFT JOIN user_achievements ua ON a.AchievementID = ua.AchievementID AND ua.UserToken = ?
        LEFT JOIN users u ON u.UserToken = ?
        ORDER BY 
            CASE a.Rarity 
                WHEN 'common' THEN 1
                WHEN 'rare' THEN 2  
                WHEN 'epic' THEN 3
                WHEN 'legendary' THEN 4
            END,
            a.RequirementValue
    ";
    
    $achievementsStmt = $conn->prepare($achievementsSql);
    $achievementsStmt->bind_param("ss", $userToken, $userToken);
    $achievementsStmt->execute();
    $achievementsResult = $achievementsStmt->get_result();
    
    $achievements = [
        'earned' => [],
        'available' => [],
        'locked' => []
    ];
    
    $stats = [
        'totalAchievements' => 0,
        'earnedCount' => 0,
        'totalPoints' => 0,
        'earnedPoints' => 0
    ];
    
    while ($achievement = $achievementsResult->fetch_assoc()) {
        $stats['totalAchievements']++;
        $stats['totalPoints'] += $achievement['Points'];
        
        // Calculate progress percentage
        $progressPercent = 0;
        if ($achievement['RequirementValue'] > 0) {
            $progressPercent = min(100, ($achievement['userProgress'] / $achievement['RequirementValue']) * 100);
        }
        
        $achievementData = [
            'id' => (int)$achievement['AchievementID'],
            'name' => $achievement['NameVi'],
            'description' => $achievement['DescriptionVi'],
            'icon' => $achievement['Icon'],
            'points' => (int)$achievement['Points'],
            'rarity' => $achievement['Rarity'],
            'color' => $achievement['Color'],
            'requirementType' => $achievement['RequirementType'],
            'requirementValue' => (int)$achievement['RequirementValue'],
            'userProgress' => (int)$achievement['userProgress'],
            'progressPercent' => round($progressPercent, 1),
            'isEarned' => (bool)$achievement['isEarned'],
            'earnedDate' => $achievement['EarnedDate']
        ];
        
        if ($achievement['isEarned']) {
            $achievements['earned'][] = $achievementData;
            $stats['earnedCount']++;
            $stats['earnedPoints'] += $achievement['Points'];
        } else {
            // Check if requirements are close to being met (within 80%)
            if ($progressPercent >= 20) {
                $achievements['available'][] = $achievementData;
            } else {
                $achievements['locked'][] = $achievementData;
            }
        }
    }
    
    // Get recent achievements (last 5 earned)
    $recentSql = "
        SELECT a.NameVi, a.Icon, a.Points, a.Rarity, a.Color, ua.EarnedDate
        FROM user_achievements ua
        JOIN achievements a ON ua.AchievementID = a.AchievementID  
        WHERE ua.UserToken = ?
        ORDER BY ua.EarnedDate DESC
        LIMIT 5
    ";
    $recentStmt = $conn->prepare($recentSql);
    $recentStmt->bind_param("s", $userToken);
    $recentStmt->execute();
    $recentResult = $recentStmt->get_result();
    $recentAchievements = $recentResult->fetch_all(MYSQLI_ASSOC);
    
    // Format earned dates for recent achievements
    foreach ($recentAchievements as &$recent) {
        $recent['timeAgo'] = formatTimeAgo($recent['EarnedDate']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'achievements' => $achievements,
            'stats' => $stats,
            'recent' => $recentAchievements
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading achievements: ' . $e->getMessage()
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