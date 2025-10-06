-- Thêm trường LastUsernameChange vào bảng users
ALTER TABLE users ADD COLUMN LastUsernameChange DATETIME NULL;

-- Tạo bảng lưu lịch sử thay đổi username
CREATE TABLE IF NOT EXISTS username_history (
    HistoryID INT AUTO_INCREMENT PRIMARY KEY,
    UserToken VARCHAR(100) NOT NULL,
    OldUsername VARCHAR(100) NOT NULL,
    ChangeDate DATETIME NOT NULL,
    INDEX(UserToken)
);