-- Migration: Add checkin functionality tables
-- Date: 2025-09-27

-- Bảng check-in chính
CREATE TABLE IF NOT EXISTS checkins (
    CheckinID INT AUTO_INCREMENT PRIMARY KEY,
    UserToken VARCHAR(255) NOT NULL,
    MuseumID INT NOT NULL,
    Latitude DECIMAL(10,7) NOT NULL,
    Longitude DECIMAL(10,7) NOT NULL,
    Status TEXT,
    CheckinTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Points INT DEFAULT 0,
    Privacy ENUM('public', 'friends', 'private') DEFAULT 'public',
    FOREIGN KEY (UserToken) REFERENCES users(UserToken),
    FOREIGN KEY (MuseumID) REFERENCES museum(MuseumID)
);

-- Bảng lưu nhiều ảnh cho mỗi check-in
CREATE TABLE IF NOT EXISTS checkin_photos (
    PhotoID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    PhotoPath VARCHAR(255) NOT NULL,
    Caption TEXT,
    UploadOrder INT NOT NULL,
    FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE
);

-- Bảng cho like/reaction
CREATE TABLE IF NOT EXISTS checkin_likes (
    LikeID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    UserToken VARCHAR(255) NOT NULL,
    LikeTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (CheckinID, UserToken),
    FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE,
    FOREIGN KEY (UserToken) REFERENCES users(UserToken)
);

-- Bảng cho comment
CREATE TABLE IF NOT EXISTS checkin_comments (
    CommentID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    UserToken VARCHAR(255) NOT NULL,
    Comment TEXT NOT NULL,
    CommentTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE,
    FOREIGN KEY (UserToken) REFERENCES users(UserToken)
);

-- Bảng cho kết bạn (để hỗ trợ tính năng bạn bè)
CREATE TABLE IF NOT EXISTS user_friends (
    FriendshipID INT AUTO_INCREMENT PRIMARY KEY,
    UserToken1 VARCHAR(255) NOT NULL,
    UserToken2 VARCHAR(255) NOT NULL,
    Status ENUM('pending', 'accepted', 'rejected', 'blocked') NOT NULL,
    RequestTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (UserToken1, UserToken2),
    FOREIGN KEY (UserToken1) REFERENCES users(UserToken),
    FOREIGN KEY (UserToken2) REFERENCES users(UserToken)
);

-- Bảng quy tắc check-in
CREATE TABLE IF NOT EXISTS museum_checkin_rules (
    MuseumID INT PRIMARY KEY,
    MaxCheckinPerDay INT NOT NULL DEFAULT 2, -- Số lần check-in tối đa mỗi ngày tại một bảo tàng
    MinTimeBetweenCheckins INT NOT NULL DEFAULT 1800, -- Thời gian tối thiểu giữa các lần check-in (30 phút = 1800 giây)
    DaysBetweenRevisit INT NOT NULL DEFAULT 3, -- Số ngày chờ trước khi có thể check-in lại
    FOREIGN KEY (MuseumID) REFERENCES museum(MuseumID)
);

-- Bảng giới hạn check-in hàng ngày
CREATE TABLE IF NOT EXISTS daily_checkin_limits (
    UserToken VARCHAR(255) NOT NULL,
    CheckinDate DATE NOT NULL,
    MuseumsVisitedCount INT NOT NULL DEFAULT 0, -- Số bảo tàng khác nhau đã check-in trong ngày
    LastResetTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Thời điểm cuối cùng đặt lại bộ đếm
    PRIMARY KEY (UserToken, CheckinDate),
    FOREIGN KEY (UserToken) REFERENCES users(UserToken)
);

-- Thêm dữ liệu mẫu cho quy tắc check-in của tất cả bảo tàng hiện có
INSERT INTO museum_checkin_rules (MuseumID, MaxCheckinPerDay, MinTimeBetweenCheckins, DaysBetweenRevisit)
SELECT MuseumID, 2, 1800, 3 FROM museum
ON DUPLICATE KEY UPDATE 
    MaxCheckinPerDay = VALUES(MaxCheckinPerDay),
    MinTimeBetweenCheckins = VALUES(MinTimeBetweenCheckins),
    DaysBetweenRevisit = VALUES(DaysBetweenRevisit);

-- Tạo thư mục để lưu ảnh check-in (SQL không thể tạo thư mục, cần thực hiện bằng PHP hoặc thủ công)
-- Cần tạo thư mục: uploads/checkins