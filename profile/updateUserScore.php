<?php
/**
 * Update User Score and Handle Ranking System
 * 
 * This API handles:
 * - Adding points to user score
 * - Checking for rank upgrades
 * - Awarding achievements
 * - Logging activities
 * - Applying rank bonus multipliers
 * 
 * @author Vietnamese Memories Team
 * @version 1.0
 * @since 2025-10-28
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['UserToken']) || empty($_SESSION['UserToken'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['actionType']) || !isset($input['basePoints'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters: actionType and basePoints'
    ]);
    exit;
}

$userToken = $_SESSION['UserToken'];
$actionType = $input['actionType']; // 'checkin', 'quiz', etc.
$basePoints = (int)$input['basePoints'];
$museumId = isset($input['museumId']) ? (int)$input['museumId'] : null;
$quizScore = isset($input['quizScore']) ? (int)$input['quizScore'] : null;
$description = isset($input['description']) ? $input['description'] : '';

try {
    $conn->begin_transaction();
    
    // Get current user data with rank info
    $userSql = "
        SELECT u.*, ur.BonusMultiplier, ur.RankNameVi as CurrentRankName
        FROM users u 
        LEFT JOIN user_ranks ur ON u.CurrentRank = ur.RankID 
        WHERE u.UserToken = ?
    ";
    $userStmt = $conn->prepare($userSql);
    $userStmt->bind_param("s", $userToken);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    
    if (!$userResult || $userResult->num_rows == 0) {
        throw new Exception('User not found');
    }
    
    $user = $userResult->fetch_assoc();
    $oldScore = (int)$user['Score'];
    $oldRank = (int)$user['CurrentRank'];
    $bonusMultiplier = (float)$user['BonusMultiplier'];
    
    // Calculate final points with rank bonus
    $finalPoints = floor($basePoints * $bonusMultiplier);
    $newScore = $oldScore + $finalPoints;
    
    // Update user score and activity counters
    $updateSql = "UPDATE users SET Score = ?, LastActiveDate = CURDATE()";
    $updateParams = [$newScore];
    $updateTypes = "i";
    
    // Update specific counters based on action type
    switch ($actionType) {
        case 'checkin':
            $updateSql .= ", TotalCheckins = TotalCheckins + 1";
            if ($museumId) {
                // Check if this is a new museum for the user
                $museumCheckSql = "SELECT COUNT(*) as visited FROM checkins WHERE UserToken = ? AND MuseumID = ?";
                $museumCheckStmt = $conn->prepare($museumCheckSql);
                $museumCheckStmt->bind_param("si", $userToken, $museumId);
                $museumCheckStmt->execute();
                $museumResult = $museumCheckStmt->get_result();
                $isNewMuseum = $museumResult->fetch_assoc()['visited'] == 1; // First time visiting this museum
                
                if ($isNewMuseum) {
                    $updateSql .= ", MuseumsVisited = MuseumsVisited + 1";
                }
            }
            break;
            
        case 'quiz':
            $updateSql .= ", TotalQuizzes = TotalQuizzes + 1";
            if ($quizScore == 5) {
                $updateSql .= ", PerfectQuizzes = PerfectQuizzes + 1";
            }
            break;
    }
    
    $updateSql .= " WHERE UserToken = ?";
    $updateParams[] = $userToken;
    $updateTypes .= "s";
    
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param($updateTypes, ...$updateParams);
    $updateStmt->execute();
    
    // Check for rank upgrade
    $newRankSql = "
        SELECT RankID, RankNameVi, Icon, Color 
        FROM user_ranks 
        WHERE ? >= MinPoints AND (MaxPoints IS NULL OR ? <= MaxPoints)
        ORDER BY MinPoints DESC 
        LIMIT 1
    ";
    $newRankStmt = $conn->prepare($newRankSql);
    $newRankStmt->bind_param("ii", $newScore, $newScore);
    $newRankStmt->execute();
    $newRankResult = $newRankStmt->get_result();
    
    $rankUpgraded = false;
    $newRankInfo = null;
    
    if ($newRankResult && $newRankResult->num_rows > 0) {
        $newRank = $newRankResult->fetch_assoc();
        $newRankId = (int)$newRank['RankID'];
        
        if ($newRankId > $oldRank) {
            // Rank upgraded!
            $rankUpgraded = true;
            $newRankInfo = $newRank;
            
            // Update user's current rank
            $rankUpdateSql = "UPDATE users SET CurrentRank = ? WHERE UserToken = ?";
            $rankUpdateStmt = $conn->prepare($rankUpdateSql);
            $rankUpdateStmt->bind_param("is", $newRankId, $userToken);
            $rankUpdateStmt->execute();
            
            // Log rank up activity
            $rankActivitySql = "
                INSERT INTO user_activities (UserToken, ActivityType, Title, Description, RankID, CreatedAt)
                VALUES (?, 'rank_up', ?, ?, ?, NOW())
            ";
            $rankActivityStmt = $conn->prepare($rankActivitySql);
            $rankTitle = "Lên hạng " . $newRank['RankNameVi'];
            $rankDesc = "Chúc mừng! Bạn đã lên hạng " . $newRank['RankNameVi'];
            $rankActivityStmt->bind_param("sssi", $userToken, $rankTitle, $rankDesc, $newRankId);
            $rankActivityStmt->execute();
            
            // Check for rank achievement
            checkAndAwardAchievement($conn, $userToken, 'rank_achieved', $newRankId);
        }
    }
    
    // Log the main activity
    $activityTitle = generateActivityTitle($actionType, $finalPoints, $museumId, $quizScore);
    $activitySql = "
        INSERT INTO user_activities (UserToken, ActivityType, Title, Description, Points, MuseumID, CreatedAt)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ";
    $activityStmt = $conn->prepare($activitySql);
    $activityStmt->bind_param("sssiii", $userToken, $actionType, $activityTitle, $description, $finalPoints, $museumId);
    $activityStmt->execute();
    
    // Check for achievements based on action
    $newAchievements = checkForAchievements($conn, $userToken, $actionType, $newScore);
    
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'oldScore' => $oldScore,
            'newScore' => $newScore,
            'pointsEarned' => $finalPoints,
            'basePoints' => $basePoints,
            'bonusMultiplier' => $bonusMultiplier,
            'rankUpgraded' => $rankUpgraded,
            'newRank' => $newRankInfo,
            'newAchievements' => $newAchievements
        ],
        'message' => $rankUpgraded ? 
            "Chúc mừng! Bạn đã lên hạng " . $newRankInfo['RankNameVi'] . "!" :
            "Bạn đã nhận được " . $finalPoints . " điểm!"
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error updating score: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}

/**
 * Check and award achievement if conditions are met
 */
function checkAndAwardAchievement($conn, $userToken, $requirementType, $value) {
    // Get achievements user hasn't earned yet
    $achievementSql = "
        SELECT a.* FROM achievements a
        WHERE a.RequirementType = ? AND a.RequirementValue <= ?
        AND NOT EXISTS (
            SELECT 1 FROM user_achievements ua 
            WHERE ua.UserToken = ? AND ua.AchievementID = a.AchievementID
        )
    ";
    
    $achievementStmt = $conn->prepare($achievementSql);
    $achievementStmt->bind_param("sis", $requirementType, $value, $userToken);
    $achievementStmt->execute();
    $achievementResult = $achievementStmt->get_result();
    
    $newAchievements = [];
    
    while ($achievement = $achievementResult->fetch_assoc()) {
        // Award achievement
        $awardSql = "INSERT IGNORE INTO user_achievements (UserToken, AchievementID) VALUES (?, ?)";
        $awardStmt = $conn->prepare($awardSql);
        $awardStmt->bind_param("si", $userToken, $achievement['AchievementID']);
        $awardStmt->execute();
        
        if ($awardStmt->affected_rows > 0) {
            // Log achievement activity
            $activitySql = "
                INSERT INTO user_activities (UserToken, ActivityType, Title, Description, AchievementID, CreatedAt)
                VALUES (?, 'achievement', ?, ?, ?, NOW())
            ";
            $activityStmt = $conn->prepare($activitySql);
            $activityTitle = "Nhận thành tích: " . $achievement['NameVi'];
            $activityDesc = $achievement['DescriptionVi'];
            $activityStmt->bind_param("sssi", $userToken, $activityTitle, $activityDesc, $achievement['AchievementID']);
            $activityStmt->execute();
            
            $newAchievements[] = [
                'id' => $achievement['AchievementID'],
                'name' => $achievement['NameVi'],
                'description' => $achievement['DescriptionVi'],
                'icon' => $achievement['Icon'],
                'points' => $achievement['Points'],
                'rarity' => $achievement['Rarity'],
                'color' => $achievement['Color']
            ];
        }
    }
    
    return $newAchievements;
}

/**
 * Check for all types of achievements
 */
function checkForAchievements($conn, $userToken, $actionType, $newScore) {
    $allNewAchievements = [];
    
    // Get current user stats
    $statsSql = "SELECT TotalCheckins, TotalQuizzes, PerfectQuizzes, MuseumsVisited, StreakDays FROM users WHERE UserToken = ?";
    $statsStmt = $conn->prepare($statsSql);
    $statsStmt->bind_param("s", $userToken);
    $statsStmt->execute();
    $stats = $statsStmt->get_result()->fetch_assoc();
    
    // Check different achievement types
    if ($actionType == 'checkin') {
        $achievements = checkAndAwardAchievement($conn, $userToken, 'checkin_count', $stats['TotalCheckins']);
        $allNewAchievements = array_merge($allNewAchievements, $achievements);
        
        $museumAchievements = checkAndAwardAchievement($conn, $userToken, 'museums_visited', $stats['MuseumsVisited']);
        $allNewAchievements = array_merge($allNewAchievements, $museumAchievements);
    }
    
    if ($actionType == 'quiz') {
        $quizAchievements = checkAndAwardAchievement($conn, $userToken, 'quiz_perfect', $stats['PerfectQuizzes']);
        $allNewAchievements = array_merge($allNewAchievements, $quizAchievements);
    }
    
    // Check total points achievements
    $pointsAchievements = checkAndAwardAchievement($conn, $userToken, 'total_points', $newScore);
    $allNewAchievements = array_merge($allNewAchievements, $pointsAchievements);
    
    return $allNewAchievements;
}

/**
 * Generate activity title based on action type
 */
function generateActivityTitle($actionType, $points, $museumId = null, $quizScore = null) {
    switch ($actionType) {
        case 'checkin':
            return "Check-in thành công (+{$points} điểm)";
        case 'quiz':
            $scoreText = $quizScore ? "({$quizScore}/5 đúng)" : "";
            return "Hoàn thành quiz {$scoreText} (+{$points} điểm)";
        default:
            return "Nhận điểm (+{$points} điểm)";
    }
}
?>