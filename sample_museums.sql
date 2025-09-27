-- Thêm các bảo tàng mẫu để test chức năng check-in (CHỈ DÙNG CHO TEST)
-- Tạo ngày: 2025-09-27
--
-- LƯU Ý: Đây chỉ là dữ liệu mẫu để test tính năng check-in
-- Để quản lý bảo tàng chính thức, vui lòng sử dụng trang quản trị /admin/museums.php

-- Xóa các bảo tàng cũ (nếu muốn)
-- DELETE FROM museum;

-- Đặt lại auto increment (nếu xóa dữ liệu cũ)
-- ALTER TABLE museum AUTO_INCREMENT = 1;

-- Thêm các bảo tàng mẫu với tọa độ GPS thực tế tại Việt Nam
INSERT INTO museum (MuseumName, Address, Description, Latitude, Longitude) VALUES
-- Hà Nội
('Bảo tàng Lịch sử Quốc gia Việt Nam', '1 Phạm Ngũ Lão, Hoàn Kiếm, Hà Nội', 'Bảo tàng Lịch sử Quốc gia Việt Nam là nơi lưu giữ và trưng bày các hiện vật lịch sử, văn hóa từ thời tiền sử đến hiện đại.', 21.0277644, 105.8524475),
('Bảo tàng Phụ nữ Việt Nam', '36 Lý Thường Kiệt, Hoàn Kiếm, Hà Nội', 'Bảo tàng giới thiệu lịch sử, vai trò và đóng góp của phụ nữ Việt Nam trong sự phát triển của đất nước.', 21.0235556, 105.8498333),
('Bảo tàng Dân tộc học Việt Nam', 'Đường Nguyễn Văn Huyên, Cầu Giấy, Hà Nội', 'Bảo tàng lưu giữ và giới thiệu về văn hóa các dân tộc Việt Nam.', 21.0405739, 105.7985709),
('Bảo tàng Hồ Chí Minh', '19 Ngọc Hà, Ba Đình, Hà Nội', 'Bảo tàng tưởng nhớ và tôn vinh Chủ tịch Hồ Chí Minh.', 21.0367432, 105.8314998),
('Bảo tàng Mỹ thuật Việt Nam', '66 Nguyễn Thái Học, Ba Đình, Hà Nội', 'Bảo tàng trưng bày các tác phẩm nghệ thuật của Việt Nam từ xưa đến nay.', 21.0308983, 105.8430419),

-- TP Hồ Chí Minh
('Bảo tàng Lịch sử Thành phố Hồ Chí Minh', '2 Nguyễn Bỉnh Khiêm, Bến Nghé, Quận 1, TP HCM', 'Bảo tàng lưu giữ và trưng bày các hiện vật lịch sử của thành phố Hồ Chí Minh.', 10.7875833, 106.7028889),
('Bảo tàng Chứng tích Chiến tranh', '28 Võ Văn Tần, Quận 3, TP HCM', 'Bảo tàng trưng bày các hiện vật, hình ảnh về cuộc chiến tranh Việt Nam.', 10.7794476, 106.6926891),
('Bảo tàng Mỹ thuật TP HCM', '97A Phó Đức Chính, Nguyễn Thái Bình, Quận 1, TP HCM', 'Bảo tàng trưng bày các tác phẩm nghệ thuật.', 10.7703095, 106.7013041),
('Bảo tàng Áo dài', '206 Lê Thị Riêng, Thới An, Quận 12, TP HCM', 'Bảo tàng giới thiệu lịch sử và phát triển của áo dài Việt Nam qua các thời kỳ.', 10.8556064, 106.6463534),
('Bảo tàng Thế giới Cà phê', '6 Cách Mạng Tháng 8, Bến Thành, Quận 1, TP HCM', 'Bảo tàng giới thiệu về lịch sử và văn hóa cà phê Việt Nam và thế giới.', 10.7715368, 106.6939521),

-- Đà Nẵng
('Bảo tàng Điêu khắc Chăm Đà Nẵng', '2 2 Tháng 9, Bình Hiên, Hải Châu, Đà Nẵng', 'Bảo tàng lưu giữ và trưng bày các tác phẩm điêu khắc của người Chăm.', 16.0479015, 108.2241904),
('Bảo tàng Đà Nẵng', '24 Đường Trần Phú, Hải Châu, Đà Nẵng', 'Bảo tàng giới thiệu về lịch sử và văn hóa thành phố Đà Nẵng.', 16.0782778, 108.2213611),

-- Huế
('Bảo tàng Cổ vật Cung đình Huế', '3 Lê Trực, Phú Hậu, Thừa Thiên Huế', 'Bảo tàng trưng bày các hiện vật thời nhà Nguyễn.', 16.4717105, 107.5818003),

-- Hội An
('Bảo tàng Văn hóa Dân gian Hội An', '33 Nguyễn Thái Học, Minh An, Hội An, Quảng Nam', 'Bảo tàng giới thiệu về đời sống, phong tục của người dân Hội An xưa.', 15.8775737, 108.3285149),

-- Khu vực khác
('Bảo tàng Quảng Ninh', 'Đường Trần Quốc Nghiễn, Hồng Gai, Hạ Long, Quảng Ninh', 'Bảo tàng giới thiệu về lịch sử, văn hóa và thiên nhiên tỉnh Quảng Ninh.', 20.9519444, 107.0772222);

-- Thêm một số bảo tàng gần vị trí test (thay đổi tọa độ này theo vị trí của bạn)
-- Ví dụ: Các tọa độ này là xung quanh khu vực Hà Nội
INSERT INTO museum (MuseumName, Address, Description, Latitude, Longitude) VALUES
('Bảo tàng Test 1', 'Gần vị trí hiện tại khoảng 100m', 'Bảo tàng test để kiểm tra tính năng check-in.', 21.027000, 105.852000),
('Bảo tàng Test 2', 'Gần vị trí hiện tại khoảng 300m', 'Bảo tàng test để kiểm tra tính năng check-in.', 21.026500, 105.854000),
('Bảo tàng Test 3', 'Gần vị trí hiện tại khoảng 500m', 'Bảo tàng test để kiểm tra tính năng check-in.', 21.028000, 105.850000);

-- Thêm các quy tắc check-in cho bảo tàng mới
INSERT INTO museum_checkin_rules (MuseumID, MaxCheckinPerDay, MinTimeBetweenCheckins, DaysBetweenRevisit)
SELECT MuseumID, 2, 1800, 3 FROM museum
WHERE MuseumID NOT IN (SELECT MuseumID FROM museum_checkin_rules);