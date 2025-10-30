-- ===================================================================
-- VIETNAMESE MEMORIES RANKING SYSTEM DATABASE SETUP
-- Version: 1.0
-- Created: October 28, 2025
-- Description: Setup tables for ranking system, achievements, and user activities
-- ===================================================================

-- ===================================================================
-- 1. USER RANKS TABLE
-- ===================================================================
CREATE TABLE IF NOT EXISTS user_ranks (
    RankID INT PRIMARY KEY AUTO_INCREMENT,
    RankName VARCHAR(50) NOT NULL,
    RankNameVi VARCHAR(50) NOT NULL,
    MinPoints INT NOT NULL,
    MaxPoints INT,
    BonusMultiplier DECIMAL(3,2) DEFAULT 1.00,
    Icon VARCHAR(10),
    Color VARCHAR(7),
    BGColor VARCHAR(7),
    Benefits TEXT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default ranks data
INSERT INTO user_ranks (RankName, RankNameVi, MinPoints, MaxPoints, BonusMultiplier, Icon, Color, BGColor, Benefits) VALUES
('Iron', 'Sắt', 0, 99, 1.00, '🔩', '#666666', '#f5f5f5', 'Quyền lợi cơ bản'),
('Bronze', 'Đồng', 100, 299, 1.05, '🥉', '#CD7F32', '#fff8dc', '+5% bonus điểm checkin và quiz'),
('Silver', 'Bạc', 300, 699, 1.10, '🥈', '#C0C0C0', '#f8f8ff', '+10% bonus điểm, badge bạc đặc biệt'),
('Gold', 'Vàng', 700, 1499, 1.15, '🥇', '#FFD700', '#fffacd', '+15% bonus điểm, avatar frame vàng'),
('Platinum', 'Bạch Kim', 1500, 2999, 1.20, '💎', '#E5E4E2', '#f0f8ff', '+20% bonus điểm, premium badge'),
('Emerald', 'Lục Bảo', 3000, 5999, 1.25, '💚', '#50C878', '#f0fff0', '+25% bonus điểm, tính năng độc quyền'),
('Ruby', 'Hồng Ngọc', 6000, 11999, 1.30, '💖', '#E0115F', '#fff0f5', '+30% bonus điểm, VIP status'),
('Diamond', 'Kim Cương', 12000, NULL, 1.35, '💎', '#B9F2FF', '#f0ffff', '+35% bonus điểm, ultimate status');

-- ===================================================================
-- 2. ACHIEVEMENTS TABLE
-- ===================================================================
CREATE TABLE IF NOT EXISTS achievements (
    AchievementID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    NameVi VARCHAR(100) NOT NULL,
    Description TEXT,
    DescriptionVi TEXT,
    Icon VARCHAR(10),
    Points INT DEFAULT 0,
    RequirementType ENUM('checkin_count', 'quiz_score', 'quiz_perfect', 'streak_days', 'museums_visited', 'total_points', 'rank_achieved') NOT NULL,
    RequirementValue INT,
    IsOneTime BOOLEAN DEFAULT TRUE,
    Rarity ENUM('common', 'rare', 'epic', 'legendary') DEFAULT 'common',
    Color VARCHAR(7) DEFAULT '#4CAF50',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default achievements
INSERT INTO achievements (Name, NameVi, Description, DescriptionVi, Icon, Points, RequirementType, RequirementValue, Rarity, Color) VALUES
-- Checkin achievements
('First Steps', 'Khám phá đầu tiên', 'Complete your first check-in', 'Hoàn thành check-in đầu tiên', '🏛️', 25, 'checkin_count', 1, 'common', '#4CAF50'),
('Explorer', 'Thám hiểm', 'Complete 5 check-ins', 'Hoàn thành 5 lần check-in', '📍', 50, 'checkin_count', 5, 'common', '#4CAF50'),
('Adventurer', 'Nhà thám hiểm', 'Complete 15 check-ins', 'Hoàn thành 15 lần check-in', '🎒', 100, 'checkin_count', 15, 'rare', '#FF9800'),
('Museum Expert', 'Chuyên gia bảo tàng', 'Complete 30 check-ins', 'Hoàn thành 30 lần check-in', '🎯', 200, 'checkin_count', 30, 'epic', '#9C27B0'),
('Master Explorer', 'Bậc thầy khám phá', 'Complete 50 check-ins', 'Hoàn thành 50 lần check-in', '👑', 350, 'checkin_count', 50, 'legendary', '#F44336'),

-- Quiz achievements
('Quiz Rookie', 'Tân binh quiz', 'Answer 3/5 quiz questions correctly', 'Trả lời đúng 3/5 câu hỏi quiz', '📝', 20, 'quiz_score', 3, 'common', '#4CAF50'),
('Quiz Expert', 'Chuyên gia quiz', 'Answer 5/5 quiz questions correctly', 'Trả lời đúng 5/5 câu hỏi quiz', '🧠', 50, 'quiz_perfect', 1, 'rare', '#FF9800'),
('Quiz Master', 'Bậc thầy quiz', 'Get 10 perfect quiz scores', 'Đạt 10 lần quiz hoàn hảo', '🎓', 200, 'quiz_perfect', 10, 'epic', '#9C27B0'),

-- Streak achievements
('Dedicated', 'Người tận tụy', 'Check-in for 3 consecutive days', 'Check-in liên tiếp 3 ngày', '📅', 75, 'streak_days', 3, 'common', '#4CAF50'),
('Committed', 'Người kiên trì', 'Check-in for 7 consecutive days', 'Check-in liên tiếp 7 ngày', '🔥', 150, 'streak_days', 7, 'rare', '#FF9800'),
('Unstoppable', 'Không thể ngăn cản', 'Check-in for 30 consecutive days', 'Check-in liên tiếp 30 ngày', '⚡', 500, 'streak_days', 30, 'legendary', '#F44336'),

-- Museum exploration achievements
('Museum Visitor', 'Khách tham quan', 'Visit 3 different museums', 'Tham quan 3 bảo tàng khác nhau', '🏢', 100, 'museums_visited', 3, 'common', '#4CAF50'),
('Museum Explorer', 'Nhà khám phá bảo tàng', 'Visit 5 different museums', 'Tham quan 5 bảo tàng khác nhau', '🗺️', 200, 'museums_visited', 5, 'rare', '#FF9800'),
('Museum Connoisseur', 'Chuyên gia bảo tàng', 'Visit 10 different museums', 'Tham quan 10 bảo tàng khác nhau', '🎨', 400, 'museums_visited', 10, 'epic', '#9C27B0'),

-- Rank achievements
('Bronze Achiever', 'Đạt hạng Đồng', 'Reach Bronze rank', 'Đạt cấp bậc Đồng', '🥉', 50, 'rank_achieved', 2, 'common', '#CD7F32'),
('Silver Achiever', 'Đạt hạng Bạc', 'Reach Silver rank', 'Đạt cấp bậc Bạc', '🥈', 100, 'rank_achieved', 3, 'rare', '#C0C0C0'),
('Gold Achiever', 'Đạt hạng Vàng', 'Reach Gold rank', 'Đạt cấp bậc Vàng', '🥇', 200, 'rank_achieved', 4, 'epic', '#FFD700'),
('Platinum Achiever', 'Đạt hạng Bạch Kim', 'Reach Platinum rank', 'Đạt cấp bậc Bạch Kim', '💎', 300, 'rank_achieved', 5, 'epic', '#E5E4E2'),
('Diamond Achiever', 'Đạt hạng Kim Cương', 'Reach Diamond rank', 'Đạt cấp bậc Kim Cương', '💎', 500, 'rank_achieved', 8, 'legendary', '#B9F2FF');

-- ===================================================================
-- 3. USER ACHIEVEMENTS TABLE
-- ===================================================================
CREATE TABLE IF NOT EXISTS user_achievements (
    UserToken VARCHAR(100),
    AchievementID INT,
    EarnedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (UserToken, AchievementID),
    FOREIGN KEY (AchievementID) REFERENCES achievements(AchievementID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 4. USER ACTIVITIES LOG TABLE
-- ===================================================================
CREATE TABLE IF NOT EXISTS user_activities (
    ActivityID INT PRIMARY KEY AUTO_INCREMENT,
    UserToken VARCHAR(100),
    ActivityType ENUM('checkin', 'quiz', 'achievement', 'rank_up', 'login', 'coin_pickup') NOT NULL,
    Title VARCHAR(200),
    Description TEXT,
    Points INT DEFAULT 0,
    MuseumID INT NULL,
    AchievementID INT NULL,
    RankID INT NULL,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_token (UserToken),
    INDEX idx_activity_type (ActivityType),
    INDEX idx_created_at (CreatedAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 5. UPDATE USERS TABLE
-- ===================================================================
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS CurrentRank INT DEFAULT 1,
ADD COLUMN IF NOT EXISTS TotalCheckins INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS TotalQuizzes INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS PerfectQuizzes INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS StreakDays INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS LastActiveDate DATE,
ADD COLUMN IF NOT EXISTS MuseumsVisited INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS LastStreakDate DATE,
ADD COLUMN IF NOT EXISTS TotalCoinsPickedUp INT DEFAULT 0;

-- Add foreign key for CurrentRank
ALTER TABLE users ADD CONSTRAINT fk_users_current_rank 
FOREIGN KEY (CurrentRank) REFERENCES user_ranks(RankID);

-- ===================================================================
-- 6. CREATE INDEXES FOR PERFORMANCE
-- ===================================================================
CREATE INDEX idx_user_ranks_points ON user_ranks(MinPoints, MaxPoints);
CREATE INDEX idx_achievements_type ON achievements(RequirementType, RequirementValue);
CREATE INDEX idx_users_score ON users(Score);
CREATE INDEX idx_users_rank ON users(CurrentRank);

-- ===================================================================
-- 7. SCORING SYSTEM CONFIGURATION TABLE
-- ===================================================================
CREATE TABLE IF NOT EXISTS scoring_config (
    ConfigID INT PRIMARY KEY AUTO_INCREMENT,
    ActionType ENUM('checkin', 'quiz_perfect', 'quiz_good', 'quiz_fair', 'daily_streak', 'weekly_streak', 'monthly_streak') NOT NULL,
    BasePoints INT NOT NULL,
    Description VARCHAR(200),
    IsActive BOOLEAN DEFAULT TRUE,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert scoring configuration
INSERT INTO scoring_config (ActionType, BasePoints, Description) VALUES
('checkin', 25, 'Điểm cơ bản cho mỗi lần check-in thành công'),
('quiz_perfect', 50, 'Điểm cho quiz hoàn hảo (5/5 câu đúng)'),
('quiz_good', 30, 'Điểm cho quiz tốt (4/5 câu đúng)'),
('quiz_fair', 15, 'Điểm cho quiz khá (3/5 câu đúng)'),
('daily_streak', 10, 'Bonus điểm cho streak hàng ngày'),
('weekly_streak', 100, 'Bonus điểm cho streak 7 ngày'),
('monthly_streak', 500, 'Bonus điểm cho streak 30 ngày');

-- ===================================================================
-- DEMO DATA (Optional - for testing)
-- ===================================================================

-- Update existing users to have ranks based on their current scores
UPDATE users SET CurrentRank = (
    SELECT RankID FROM user_ranks 
    WHERE users.Score >= MinPoints 
    AND (MaxPoints IS NULL OR users.Score <= MaxPoints)
    ORDER BY MinPoints DESC
    LIMIT 1
) WHERE CurrentRank IS NULL OR CurrentRank = 0;

-- ===================================================================
-- VIEWS FOR EASY DATA ACCESS
-- ===================================================================

-- View for user profile with rank information
CREATE OR REPLACE VIEW user_profile_view AS
SELECT 
    u.UserToken,
    u.Username,
    u.Score,
    u.CurrentRank,
    ur.RankName,
    ur.RankNameVi,
    ur.Icon as RankIcon,
    ur.Color as RankColor,
    ur.BGColor as RankBGColor,
    ur.BonusMultiplier,
    u.TotalCheckins,
    u.TotalQuizzes,
    u.PerfectQuizzes,
    u.StreakDays,
    u.MuseumsVisited,
    u.LastActiveDate,
    u.avatar,
    u.Role,
    -- Calculate progress to next rank
    CASE 
        WHEN ur_next.MinPoints IS NULL THEN 100
        ELSE ROUND(((u.Score - ur.MinPoints) / (ur_next.MinPoints - ur.MinPoints)) * 100, 1)
    END as ProgressToNextRank,
    ur_next.RankNameVi as NextRankName,
    ur_next.MinPoints as NextRankMinPoints
FROM users u
LEFT JOIN user_ranks ur ON u.CurrentRank = ur.RankID
LEFT JOIN user_ranks ur_next ON ur_next.RankID = ur.RankID + 1;

-- View for leaderboard
CREATE OR REPLACE VIEW leaderboard_view AS
SELECT 
    u.UserToken,
    u.Username,
    u.Score,
    ur.RankNameVi,
    ur.Icon as RankIcon,
    ur.Color as RankColor,
    u.avatar,
    u.Role,
    u.TotalCheckins,
    u.MuseumsVisited,
    ROW_NUMBER() OVER (ORDER BY u.Score DESC) as RankPosition
FROM users u
LEFT JOIN user_ranks ur ON u.CurrentRank = ur.RankID
WHERE u.Score > 0
ORDER BY u.Score DESC;

-- ===================================================================
-- SUCCESS MESSAGE
-- ===================================================================
SELECT 'Vietnamese Memories Ranking System installed successfully!' as Status,
       (SELECT COUNT(*) FROM user_ranks) as RanksCreated,
       (SELECT COUNT(*) FROM achievements) as AchievementsCreated,
       (SELECT COUNT(*) FROM scoring_config) as ScoringRulesCreated;