-- =============================================================================
-- SCRIPT THIẾT LẬP CHỨC NĂNG CHECK-IN CHO VIETNAMESE MEMORIES
-- =============================================================================
-- Gộp từ: update_database_after_merge.sql + sample_museums.sql
-- Tạo ngày: 2025-09-28
-- Mô tả: Script hoàn chỉnh để thiết lập database cho chức năng check-in
-- 
-- Chạy script này để:
-- 1. Tạo các bảng cần thiết cho check-in
-- 2. Thêm dữ liệu bảo tàng mẫu để test
-- 3. Thiết lập quy tắc check-in
-- =============================================================================

-- -----------------------------------------------------------------------------
-- PHẦN 1: TẠO CÁC BẢNG CHO CHỨC NĂNG CHECK-IN
-- -----------------------------------------------------------------------------

-- Xóa các bảng cũ nếu tồn tại (theo thứ tự dependency)
DROP TABLE IF EXISTS checkin_status_history;
DROP TABLE IF EXISTS checkin_photos;
DROP TABLE IF EXISTS daily_checkin_limits;
DROP TABLE IF EXISTS museum_checkin_rules;
DROP TABLE IF EXISTS checkins;

-- Tạo bảng checkins (bảng chính lưu thông tin check-in)
CREATE TABLE checkins (
    CheckinID INT AUTO_INCREMENT PRIMARY KEY,
    UserToken VARCHAR(64) NOT NULL,
    MuseumID INT NOT NULL,
    Latitude DECIMAL(10,7) NOT NULL,
    Longitude DECIMAL(10,7) NOT NULL,
    Caption TEXT,                    -- Đổi từ Status (user comment)
    ApprovalStatus ENUM('none', 'approved', 'denied') DEFAULT 'none',  -- Trạng thái duyệt
    DeniedReason TEXT NULL,          -- Lý do admin từ chối
    PendingPoints INT DEFAULT 0,     -- Điểm chờ duyệt
    ActualPoints INT DEFAULT 0,      -- Điểm thực tế sau approve
    ProcessedAt TIMESTAMP NULL,      -- Thời gian admin xử lý
    ProcessedBy VARCHAR(64) NULL,    -- Admin token xử lý
    CheckinTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Points INT DEFAULT 0,            -- Giữ nguyên cho backward compatibility
    INDEX idx_user (UserToken),
    INDEX idx_museum (MuseumID),
    INDEX idx_approval_status (ApprovalStatus),
    INDEX idx_user_status (UserToken, ApprovalStatus),
    INDEX idx_process_time (ProcessedAt),
    CONSTRAINT fk_checkins_user FOREIGN KEY (UserToken) REFERENCES users(UserToken) ON DELETE CASCADE,
    CONSTRAINT fk_checkins_museum FOREIGN KEY (MuseumID) REFERENCES museum(MuseumID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng lưu nhiều ảnh cho mỗi check-in
CREATE TABLE checkin_photos (
    PhotoID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    UserToken VARCHAR(64) NOT NULL,  -- Thêm UserToken để dễ quản lý
    PhotoPath VARCHAR(255) NOT NULL,
    Caption TEXT,
    UploadOrder INT NOT NULL,
    INDEX idx_checkin (CheckinID),
    INDEX idx_user_photos (UserToken),
    CONSTRAINT fk_photos_checkin FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE,
    CONSTRAINT fk_photos_user FOREIGN KEY (UserToken) REFERENCES users(UserToken) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng lưu lịch sử thay đổi trạng thái check-in (Audit Trail)
CREATE TABLE checkin_status_history (
    HistoryID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    OldStatus ENUM('none', 'approved', 'denied'),
    NewStatus ENUM('none', 'approved', 'denied'),
    OldPoints INT DEFAULT 0,
    NewPoints INT DEFAULT 0,
    AdminToken VARCHAR(64),
    Reason TEXT,
    ChangedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_checkin (CheckinID),
    INDEX idx_admin (AdminToken),
    INDEX idx_time (ChangedAt),
    CONSTRAINT fk_history_checkin FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng quy tắc check-in cho từng bảo tàng (Enhanced validation rules)
CREATE TABLE museum_checkin_rules (
    RuleID INT AUTO_INCREMENT PRIMARY KEY,
    MuseumID INT NOT NULL,
    MaxCheckinsPerDay INT DEFAULT 1, -- Maximum check-ins per day per user per museum
    WaitDaysBetweenCheckins INT DEFAULT 7, -- Days user must wait between check-ins at same museum
    MaxPointsPerCheckin INT DEFAULT 10, -- Maximum points user can earn per check-in
    MaxCheckinsPerUserPerMuseumPerWeek INT DEFAULT 1, -- Weekly limit per user per museum
    MaxMuseumsPerUserPerDay INT DEFAULT 2, -- Maximum different museums user can check-in per day
    IsActive BOOLEAN DEFAULT TRUE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_museum (MuseumID),
    INDEX idx_active (IsActive),
    CONSTRAINT fk_rules_museum FOREIGN KEY (MuseumID) REFERENCES museum(MuseumID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng giới hạn check-in theo ngày (Enhanced daily limits)
CREATE TABLE daily_checkin_limits (
    LimitID INT AUTO_INCREMENT PRIMARY KEY,
    UserToken VARCHAR(64) NOT NULL,
    CheckinDate DATE NOT NULL,
    MuseumID INT NOT NULL,
    CheckinCount INT DEFAULT 0,
    MuseumCount INT DEFAULT 1, -- Track how many different museums checked in today
    LastCheckinTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_museum_date (UserToken, MuseumID, CheckinDate),
    INDEX idx_user_date (UserToken, CheckinDate),
    INDEX idx_museum_date (MuseumID, CheckinDate),
    INDEX idx_checkin_time (LastCheckinTime),
    CONSTRAINT fk_limits_museum FOREIGN KEY (MuseumID) REFERENCES museum(MuseumID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- PHẦN 2: THÊM DỮ LIỆU BẢO TÀNG MẪU (CHỈ HÀ NỘI - TRUNG TÂM)
-- -----------------------------------------------------------------------------

-- LƯU Ý: Đây là dữ liệu mẫu để test tính năng check-in
-- Chỉ thêm các bảo tàng ở Hà Nội gần trung tâm để dễ test

-- Có thể xóa các bảo tàng test cũ nếu muốn reset (BỎ COMMENT DÒNG DƯỚI NẾU CẦN)
-- DELETE FROM museum WHERE MuseumName LIKE 'Bảo tàng Test%';

-- Thêm các bảo tàng ở trung tâm Hà Nội để test
INSERT IGNORE INTO museum (MuseumName, Address, Description, Latitude, Longitude) VALUES
-- === BẢO TÀNG TRUNG TÂM HÀ NỘI ===
('Bảo tàng Lịch sử Quốc gia', '1 Phạm Ngũ Lão, Hoàn Kiếm, Hà Nội', 'Bảo tàng Lịch sử Quốc gia là nơi lưu giữ và trưng bày các hiện vật lịch sử, văn hóa từ thời tiền sử đến hiện đại của dân tộc Việt Nam.', 21.0277644, 105.8524475),
('Bảo tàng Phụ nữ Việt Nam', '36 Lý Thường Kiệt, Hoàn Kiếm, Hà Nội', 'Bảo tàng giới thiệu lịch sử, vai trò và đóng góp của phụ nữ Việt Nam trong sự phát triển của đất nước qua các thời kỳ.', 21.0235556, 105.8498333),
('Bảo tàng Cách mạng Việt Nam', '25 Tông Đản, Hoàn Kiếm, Hà Nội', 'Bảo tàng trưng bày về quá trình đấu tranh cách mạng của nhân dân Việt Nam từ 1858 đến 1945.', 21.0242778, 105.8547222),

-- === BẢO TÀNG TEST GẦN TRUNG TÂM HÀ NỘI ===
-- Các bảo tàng test với tọa độ gần khu vực Hoàn Kiếm để dễ test chức năng check-in
('Bảo tàng Test Hoàn Kiếm', 'Gần Hồ Hoàn Kiếm, Hà Nội', 'Bảo tàng test gần Hồ Hoàn Kiếm để kiểm tra tính năng check-in - Khoảng cách 200m.', 21.0285, 105.8542),
('Bảo tàng Test Phố Cổ', 'Khu phố cổ, Hoàn Kiếm, Hà Nội', 'Bảo tàng test trong khu phố cổ để kiểm tra tính năng check-in - Khoảng cách 300m.', 21.0308, 105.8530),
('Bảo tàng Test Đông Xuân', 'Gần chợ Đông Xuân, Hoàn Kiếm, Hà Nội', 'Bảo tàng test gần chợ Đông Xuân để kiểm tra tính năng check-in - Khoảng cách 400m.', 21.0350, 105.8490);

-- -----------------------------------------------------------------------------
-- PHẦN 3: QUY TẮC CHECK-IN & CẬP NHẬT (Enhanced Rules)
-- -----------------------------------------------------------------------------

-- Thêm quy tắc check-in mặc định cho tất cả bảo tàng theo kế hoạch mới
-- Quy tắc mới: 1 lần/ngày/bảo tàng, chờ 7 ngày giữa các lần, tối đa 2 bảo tàng/ngày
INSERT INTO museum_checkin_rules (MuseumID, MaxCheckinsPerDay, WaitDaysBetweenCheckins, MaxPointsPerCheckin, MaxCheckinsPerUserPerMuseumPerWeek, MaxMuseumsPerUserPerDay)
SELECT MuseumID, 1, 7, 10, 1, 2 FROM museum
WHERE MuseumID NOT IN (SELECT MuseumID FROM museum_checkin_rules);

-- Cập nhật quy tắc cho các bảo tàng đã có rules (nếu có)
UPDATE museum_checkin_rules 
SET 
    MaxCheckinsPerDay = 1,                    -- Tối đa 1 lần/ngày/bảo tàng
    WaitDaysBetweenCheckins = 7,              -- Chờ 7 ngày giữa các lần check-in
    MaxPointsPerCheckin = 10,                 -- Tối đa 10 điểm/lần check-in
    MaxCheckinsPerUserPerMuseumPerWeek = 1,   -- 1 lần/tuần/bảo tàng
    MaxMuseumsPerUserPerDay = 2,              -- Tối đa 2 bảo tàng khác nhau/ngày
    IsActive = TRUE,
    UpdatedAt = CURRENT_TIMESTAMP
WHERE MuseumID IN (SELECT MuseumID FROM museum);

-- -----------------------------------------------------------------------------
-- HOÀN TẤT THIẾT LẬP
-- -----------------------------------------------------------------------------

-- Hiển thị thông tin sau khi chạy script
SELECT 'Database setup completed successfully!' AS Status;
SELECT COUNT(*) AS TotalMuseums FROM museum;
SELECT COUNT(*) AS CheckinRulesConfigured FROM museum_checkin_rules;

-- Hiển thị các bảo tàng mới được thêm và quy tắc của chúng
SELECT 
    m.MuseumName,
    m.Address,
    r.MaxCheckinsPerDay,
    r.WaitDaysBetweenCheckins,
    r.MaxPointsPerCheckin,
    r.MaxMuseumsPerUserPerDay,
    r.IsActive
FROM museum m 
LEFT JOIN museum_checkin_rules r ON m.MuseumID = r.MuseumID
WHERE m.MuseumName LIKE '%Hà Nội%' OR m.MuseumName LIKE '%Test%'
ORDER BY m.MuseumID;

-- =============================================================================
-- KẾT THÚC SCRIPT
-- =============================================================================
-- 
-- Sau khi chạy script này, bạn có thể:
-- 1. Test chức năng check-in với các bảo tàng mẫu
-- 2. Quản lý bảo tàng qua /admin/museums.php
-- 3. Kiểm tra logs check-in trong bảng checkins và checkin_photos
-- 
-- Lưu ý: Để xóa dữ liệu test, chạy:
-- DELETE FROM museum WHERE MuseumName LIKE 'Bảo tàng Test%';
-- =============================================================================