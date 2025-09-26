-- SQL để tạo bảng markers cho chức năng map
CREATE TABLE IF NOT EXISTS markers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('museum', 'historical', 'cultural', 'tourist', 'other') DEFAULT 'other',
    description TEXT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    address TEXT,
    created_by INT(11) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_location (latitude, longitude),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
);

-- Thêm dữ liệu mẫu
INSERT INTO markers (name, type, description, latitude, longitude, address) VALUES
('Bảo tàng Hồ Chí Minh', 'museum', 'Bảo tàng về cuộc đời và sự nghiệp của Chủ tịch Hồ Chí Minh', 21.0368, 105.8342, '19 Ngọc Hà, Ba Đình, Hà Nội'),
('Bảo tàng Lịch sử Quân sự Việt Nam', 'museum', 'Trưng bày hiện vật về lịch sử quân sự Việt Nam', 21.0245, 105.8412, '28A Điện Biên Phủ, Ba Đình, Hà Nội'),
('Văn Miếu - Quốc Tử Giám', 'historical', 'Ngôi đền đầu tiên ở Hà Nội, được xây dựng vào năm 1070', 21.0267, 105.8356, '58 Quốc Tử Giám, Đống Đa, Hà Nội'),
('Bảo tàng Dân tộc học Việt Nam', 'museum', 'Giới thiệu về văn hóa các dân tộc Việt Nam', 21.0335, 105.8435, 'Nguyễn Du, Hai Bà Trưng, Hà Nội'),
('Chùa Một Cột', 'historical', 'Ngôi chùa nổi tiếng với kiến trúc độc đáo một cột', 21.0362, 105.8346, 'Chùa Một Cột, Ba Đình, Hà Nội');