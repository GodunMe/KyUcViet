-- Script cập nhật database sau khi merge từ main
-- Tạo ngày: 2025-09-27
-- Đã sửa các lỗi ràng buộc khóa ngoại

-- Bảng marker đã được loại bỏ theo yêu cầu

-- Đảm bảo rằng các bảng check-in được tạo đúng
-- Bảng check-in chính
DROP TABLE IF EXISTS checkin_photos;
DROP TABLE IF EXISTS checkin_likes;
DROP TABLE IF EXISTS checkin_comments;
DROP TABLE IF EXISTS user_friends;
DROP TABLE IF EXISTS museum_checkin_rules;
DROP TABLE IF EXISTS daily_checkin_limits;
DROP TABLE IF EXISTS checkins;

-- Tạo bảng checkins
CREATE TABLE checkins (
    CheckinID INT AUTO_INCREMENT PRIMARY KEY,
    UserToken VARCHAR(64) NOT NULL,
    MuseumID INT NOT NULL,
    Latitude DECIMAL(10,7) NOT NULL,
    Longitude DECIMAL(10,7) NOT NULL,
    Status TEXT,
    CheckinTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Points INT DEFAULT 0,
    Privacy ENUM('public', 'friends', 'private') DEFAULT 'public',
    INDEX idx_user (UserToken),
    INDEX idx_museum (MuseumID),
    CONSTRAINT fk_checkins_user FOREIGN KEY (UserToken) REFERENCES users(UserToken) ON DELETE CASCADE,
    CONSTRAINT fk_checkins_museum FOREIGN KEY (MuseumID) REFERENCES museum(MuseumID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng lưu nhiều ảnh cho mỗi check-in
CREATE TABLE checkin_photos (
    PhotoID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    PhotoPath VARCHAR(255) NOT NULL,
    Caption TEXT,
    UploadOrder INT NOT NULL,
    INDEX idx_checkin (CheckinID),
    CONSTRAINT fk_photos_checkin FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng cho like/reaction
CREATE TABLE checkin_likes (
    LikeID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    UserToken VARCHAR(64) NOT NULL,
    LikeTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (CheckinID, UserToken),
    INDEX idx_checkin_like (CheckinID),
    INDEX idx_user_like (UserToken),
    CONSTRAINT fk_likes_checkin FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE,
    CONSTRAINT fk_likes_user FOREIGN KEY (UserToken) REFERENCES users(UserToken) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng cho comment
CREATE TABLE checkin_comments (
    CommentID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    UserToken VARCHAR(64) NOT NULL,
    Comment TEXT NOT NULL,
    CommentTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_checkin_comment (CheckinID),
    INDEX idx_user_comment (UserToken),
    CONSTRAINT fk_comments_checkin FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user FOREIGN KEY (UserToken) REFERENCES users(UserToken) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng cho kết bạn (để hỗ trợ tính năng bạn bè)
CREATE TABLE user_friends (
    FriendshipID INT AUTO_INCREMENT PRIMARY KEY,
    UserToken1 VARCHAR(64) NOT NULL,
    UserToken2 VARCHAR(64) NOT NULL,
    Status ENUM('pending', 'accepted', 'rejected', 'blocked') NOT NULL,
    RequestTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (UserToken1, UserToken2),
    INDEX idx_user1 (UserToken1),
    INDEX idx_user2 (UserToken2),
    CONSTRAINT fk_friends_user1 FOREIGN KEY (UserToken1) REFERENCES users(UserToken) ON DELETE CASCADE,
    CONSTRAINT fk_friends_user2 FOREIGN KEY (UserToken2) REFERENCES users(UserToken) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng quy tắc check-in
CREATE TABLE museum_checkin_rules (
    MuseumID INT PRIMARY KEY,
    MaxCheckinPerDay INT NOT NULL DEFAULT 2, -- Số lần check-in tối đa mỗi ngày tại một bảo tàng
    MinTimeBetweenCheckins INT NOT NULL DEFAULT 1800, -- Thời gian tối thiểu giữa các lần check-in (30 phút = 1800 giây)
    DaysBetweenRevisit INT NOT NULL DEFAULT 3, -- Số ngày chờ trước khi có thể check-in lại
    CONSTRAINT fk_rules_museum FOREIGN KEY (MuseumID) REFERENCES museum(MuseumID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng giới hạn check-in hàng ngày
CREATE TABLE daily_checkin_limits (
    UserToken VARCHAR(64) NOT NULL,
    CheckinDate DATE NOT NULL,
    MuseumsVisitedCount INT NOT NULL DEFAULT 0, -- Số bảo tàng khác nhau đã check-in trong ngày
    LastResetTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Thời điểm cuối cùng đặt lại bộ đếm
    PRIMARY KEY (UserToken, CheckinDate),
    CONSTRAINT fk_limits_user FOREIGN KEY (UserToken) REFERENCES users(UserToken) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu mẫu cho markers đã được loại bỏ theo yêu cầu

-- Thêm quy tắc check-in mặc định cho các bảo tàng nếu chưa có
INSERT INTO museum_checkin_rules (MuseumID, MaxCheckinPerDay, MinTimeBetweenCheckins, DaysBetweenRevisit)
SELECT MuseumID, 2, 1800, 3 FROM museum
WHERE NOT EXISTS (SELECT 1 FROM museum_checkin_rules WHERE museum_checkin_rules.MuseumID = museum.MuseumID);