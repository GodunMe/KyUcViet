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
DROP TABLE IF EXISTS checkin_photos;
DROP TABLE IF EXISTS checkin_likes;
DROP TABLE IF EXISTS checkin_comments;
DROP TABLE IF EXISTS user_friends;
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

-- Bảng quy tắc check-in cho từng bảo tàng
CREATE TABLE museum_checkin_rules (
    MuseumID INT PRIMARY KEY,
    MaxCheckinPerDay INT NOT NULL DEFAULT 2, -- Số lần check-in tối đa mỗi ngày tại một bảo tàng
    MinTimeBetweenCheckins INT NOT NULL DEFAULT 1800, -- Thời gian tối thiểu giữa các lần check-in (30 phút = 1800 giây)
    DaysBetweenRevisit INT NOT NULL DEFAULT 3, -- Số ngày chờ trước khi có thể check-in lại
    CONSTRAINT fk_rules_museum FOREIGN KEY (MuseumID) REFERENCES museum(MuseumID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng giới hạn check-in hàng ngày theo user
CREATE TABLE daily_checkin_limits (
    UserToken VARCHAR(64) NOT NULL,
    CheckinDate DATE NOT NULL,
    MuseumsVisitedCount INT NOT NULL DEFAULT 0, -- Số bảo tàng khác nhau đã check-in trong ngày
    LastResetTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Thời điểm cuối cùng đặt lại bộ đếm
    PRIMARY KEY (UserToken, CheckinDate),
    CONSTRAINT fk_limits_user FOREIGN KEY (UserToken) REFERENCES users(UserToken) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- PHẦN 2: THÊM DỮ LIỆU BẢO TÀNG MẪU
-- -----------------------------------------------------------------------------

-- LƯU Ý: Đây là dữ liệu mẫu để test tính năng check-in
-- Để quản lý bảo tàng chính thức, vui lòng sử dụng trang quản trị /admin/museums.php

-- Có thể xóa các bảo tàng cũ nếu muốn reset (BỎ COMMENT DÒNG DƯỚI NẾU CẦN)
-- DELETE FROM museum WHERE MuseumID > 5; -- Giữ lại các bảo tàng có sẵn từ exe201(data).sql

-- Thêm các bảo tàng mẫu với tọa độ GPS thực tế tại Việt Nam
INSERT INTO museum (MuseumName, Address, Description, Latitude, Longitude) VALUES
-- === BẢO TÀNG TẠI HÀ NỘI ===
('Bảo tàng Lịch sử Quốc gia Việt Nam', '1 Phạm Ngũ Lão, Hoàn Kiếm, Hà Nội', 'Bảo tàng Lịch sử Quốc gia Việt Nam là nơi lưu giữ và trưng bày các hiện vật lịch sử, văn hóa từ thời tiền sử đến hiện đại.', 21.0277644, 105.8524475),
('Bảo tàng Phụ nữ Việt Nam', '36 Lý Thường Kiệt, Hoàn Kiếm, Hà Nội', 'Bảo tàng giới thiệu lịch sử, vai trò và đóng góp của phụ nữ Việt Nam trong sự phát triển của đất nước.', 21.0235556, 105.8498333),
('Bảo tàng Mỹ thuật Việt Nam', '66 Nguyễn Thái Học, Ba Đình, Hà Nội', 'Bảo tàng trưng bày các tác phẩm nghệ thuật của Việt Nam từ xưa đến nay.', 21.0308983, 105.8430419),

-- === BẢO TÀNG TẠI TP HỒ CHÍ MINH ===
('Bảo tàng Lịch sử Thành phố Hồ Chí Minh', '2 Nguyễn Bỉnh Khiêm, Bến Nghé, Quận 1, TP HCM', 'Bảo tàng lưu giữ và trưng bày các hiện vật lịch sử của thành phố Hồ Chí Minh.', 10.7875833, 106.7028889),
('Bảo tàng Chứng tích Chiến tranh', '28 Võ Văn Tần, Quận 3, TP HCM', 'Bảo tàng trưng bày các hiện vật, hình ảnh về cuộc chiến tranh Việt Nam.', 10.7794476, 106.6926891),
('Bảo tàng Mỹ thuật TP HCM', '97A Phó Đức Chính, Nguyễn Thái Bình, Quận 1, TP HCM', 'Bảo tàng trưng bày các tác phẩm nghệ thuật.', 10.7703095, 106.7013041),
('Bảo tàng Áo dài', '206 Lê Thị Riêng, Thới An, Quận 12, TP HCM', 'Bảo tàng giới thiệu lịch sử và phát triển của áo dài Việt Nam qua các thời kỳ.', 10.8556064, 106.6463534),
('Bảo tàng Thế giới Cà phê', '6 Cách Mạng Tháng 8, Bến Thành, Quận 1, TP HCM', 'Bảo tàng giới thiệu về lịch sử và văn hóa cà phê Việt Nam và thế giới.', 10.7715368, 106.6939521),

-- === BẢO TÀNG TẠI ĐÀ NẴNG ===
('Bảo tàng Điêu khắc Chăm Đà Nẵng', '2 2 Tháng 9, Bình Hiên, Hải Châu, Đà Nẵng', 'Bảo tàng lưu giữ và trưng bày các tác phẩm điêu khắc của người Chăm.', 16.0479015, 108.2241904),
('Bảo tàng Đà Nẵng', '24 Đường Trần Phú, Hải Châu, Đà Nẵng', 'Bảo tàng giới thiệu về lịch sử và văn hóa thành phố Đà Nẵng.', 16.0782778, 108.2213611),

-- === BẢO TÀNG TẠI HUẾ ===
('Bảo tàng Cổ vật Cung đình Huế', '3 Lê Trực, Phú Hậu, Thừa Thiên Huế', 'Bảo tàng trưng bày các hiện vật thời nhà Nguyễn.', 16.4717105, 107.5818003),

-- === BẢO TÀNG TẠI HỘI AN ===
('Bảo tàng Văn hóa Dân gian Hội An', '33 Nguyễn Thái Học, Minh An, Hội An, Quảng Nam', 'Bảo tàng giới thiệu về đời sống, phong tục của người dân Hội An xưa.', 15.8775737, 108.3285149),

-- === BẢO TÀNG VÙNG KHÁC ===
('Bảo tàng Quảng Ninh', 'Đường Trần Quốc Nghiễn, Hồng Gai, Hạ Long, Quảng Ninh', 'Bảo tàng giới thiệu về lịch sử, văn hóa và thiên nhiên tỉnh Quảng Ninh.', 20.9519444, 107.0772222);

-- === BẢO TÀNG TEST GẦN VỊ TRÍ HÀ NỘI ===
-- Các bảo tàng này có tọa độ gần Hà Nội để dễ test chức năng check-in
INSERT INTO museum (MuseumName, Address, Description, Latitude, Longitude) VALUES
('Bảo tàng Test 1', 'Gần vị trí hiện tại khoảng 100m', 'Bảo tàng test để kiểm tra tính năng check-in - Khoảng cách gần.', 21.027000, 105.852000),
('Bảo tàng Test 2', 'Gần vị trí hiện tại khoảng 300m', 'Bảo tàng test để kiểm tra tính năng check-in - Khoảng cách trung bình.', 21.026500, 105.854000),
('Bảo tàng Test 3', 'Gần vị trí hiện tại khoảng 500m', 'Bảo tàng test để kiểm tra tính năng check-in - Khoảng cách xa.', 21.028000, 105.850000);

-- -----------------------------------------------------------------------------
-- PHẦN 3: THIẾT LẬP QUY TẮC CHECK-IN
-- -----------------------------------------------------------------------------

-- Thêm quy tắc check-in mặc định cho tất cả bảo tàng
-- Quy tắc: Tối đa 2 lần check-in/ngày, cách nhau ít nhất 30 phút, 3 ngày mới được check-in lại
INSERT INTO museum_checkin_rules (MuseumID, MaxCheckinPerDay, MinTimeBetweenCheckins, DaysBetweenRevisit)
SELECT MuseumID, 2, 1800, 3 FROM museum
WHERE MuseumID NOT IN (SELECT MuseumID FROM museum_checkin_rules);

-- -----------------------------------------------------------------------------
-- HOÀN TẤT THIẾT LẬP
-- -----------------------------------------------------------------------------

-- Hiển thị thông tin sau khi chạy script
SELECT 'Database setup completed successfully!' AS Status;
SELECT COUNT(*) AS TotalMuseums FROM museum;
SELECT COUNT(*) AS CheckinRulesConfigured FROM museum_checkin_rules;

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