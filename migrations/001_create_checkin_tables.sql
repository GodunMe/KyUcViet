-- Migration: Cập nhật cơ sở dữ liệu cho tính năng check-in mới
-- File: 001_create_checkin_tables.sql
-- Mô tả: Tạo và cập nhật các bảng cho tính năng check-in theo yêu cầu mới

-- 1. Cập nhật bảng checkins để thêm các cột mới
ALTER TABLE checkins 
ADD COLUMN IF NOT EXISTS Privacy ENUM('public', 'friends', 'private') DEFAULT 'public' AFTER Status;

-- 2. Tạo bảng checkin_photos nếu chưa tồn tại
CREATE TABLE IF NOT EXISTS checkin_photos (
    PhotoID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    PhotoPath VARCHAR(255) NOT NULL,
    Caption TEXT,
    UploadOrder INT NOT NULL DEFAULT 1,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE,
    INDEX idx_checkin_order (CheckinID, UploadOrder)
);

-- 3. Tạo bảng museum_checkin_rules nếu chưa tồn tại
CREATE TABLE IF NOT EXISTS museum_checkin_rules (
    MuseumID INT PRIMARY KEY,
    MaxCheckinPerDay INT NOT NULL DEFAULT 2,
    MinTimeBetweenCheckins INT NOT NULL DEFAULT 1800, -- 30 phút = 1800 giây
    DaysBetweenRevisit INT NOT NULL DEFAULT 3,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (MuseumID) REFERENCES museum(MuseumID) ON DELETE CASCADE
);

-- 4. Tạo bảng daily_checkin_limits nếu chưa tồn tại
CREATE TABLE IF NOT EXISTS daily_checkin_limits (
    UserToken VARCHAR(255) NOT NULL,
    CheckinDate DATE NOT NULL,
    MuseumsVisitedCount INT NOT NULL DEFAULT 0,
    LastResetTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (UserToken, CheckinDate),
    FOREIGN KEY (UserToken) REFERENCES users(UserToken) ON DELETE CASCADE,
    INDEX idx_date (CheckinDate)
);

-- 5. Tạo bảng checkin_likes cho tính năng xã hội
CREATE TABLE IF NOT EXISTS checkin_likes (
    LikeID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    UserToken VARCHAR(255) NOT NULL,
    LikeTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (CheckinID, UserToken),
    FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE,
    FOREIGN KEY (UserToken) REFERENCES users(UserToken) ON DELETE CASCADE,
    INDEX idx_checkin (CheckinID),
    INDEX idx_user (UserToken)
);

-- 6. Tạo bảng checkin_comments cho tính năng xã hội
CREATE TABLE IF NOT EXISTS checkin_comments (
    CommentID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    UserToken VARCHAR(255) NOT NULL,
    Comment TEXT NOT NULL,
    CommentTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE,
    FOREIGN KEY (UserToken) REFERENCES users(UserToken) ON DELETE CASCADE,
    INDEX idx_checkin (CheckinID),
    INDEX idx_user (UserToken),
    INDEX idx_time (CommentTime)
);

-- 7. Tạo bảng user_friends cho quản lý bạn bè
CREATE TABLE IF NOT EXISTS user_friends (
    FriendshipID INT AUTO_INCREMENT PRIMARY KEY,
    UserToken1 VARCHAR(255) NOT NULL,
    UserToken2 VARCHAR(255) NOT NULL,
    Status ENUM('pending', 'accepted', 'rejected', 'blocked') NOT NULL DEFAULT 'pending',
    RequestTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_friendship (UserToken1, UserToken2),
    FOREIGN KEY (UserToken1) REFERENCES users(UserToken) ON DELETE CASCADE,
    FOREIGN KEY (UserToken2) REFERENCES users(UserToken) ON DELETE CASCADE,
    INDEX idx_user1 (UserToken1),
    INDEX idx_user2 (UserToken2),
    INDEX idx_status (Status)
);

-- 8. Thêm quy tắc mặc định cho tất cả bảo tàng hiện có
INSERT IGNORE INTO museum_checkin_rules (MuseumID, MaxCheckinPerDay, MinTimeBetweenCheckins, DaysBetweenRevisit)
SELECT MuseumID, 2, 1800, 3 FROM museum;

-- 9. Tạo index cho bảng checkins để cải thiện hiệu suất
CREATE INDEX IF NOT EXISTS idx_checkins_user_date ON checkins(UserToken, CheckinTime);
CREATE INDEX IF NOT EXISTS idx_checkins_museum_date ON checkins(MuseumID, CheckinTime);
CREATE INDEX IF NOT EXISTS idx_checkins_privacy ON checkins(Privacy);

-- 10. Cập nhật điểm check-in mặc định nếu chưa có
UPDATE checkins SET Points = 50 WHERE Points IS NULL OR Points = 0;