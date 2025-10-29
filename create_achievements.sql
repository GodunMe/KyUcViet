-- Create achievements table and user_achievements mapping table
-- Field names use capitalized-first-word (ID, Name, Description, Icon, CreatedAt)
-- Use utf8mb4 for multilingual text

CREATE TABLE IF NOT EXISTS `achievements` (
  `ID` INT NOT NULL AUTO_INCREMENT,
  `Name` NVARCHAR(255) NOT NULL,
  `Description` NVARCHAR(1000) DEFAULT NULL,
  `Icon` VARCHAR(255) DEFAULT NULL,
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_achievements` (
  `UserToken` VARCHAR(255) NOT NULL,
  `AchievementID` INT NOT NULL,
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`UserToken`, `AchievementID`),
  INDEX (`AchievementID`),
  CONSTRAINT `fk_user_achiev_achiev` FOREIGN KEY (`AchievementID`) REFERENCES `achievements`(`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `achievements` (`Name`, `Description`, `Icon`) VALUES
('Dấu Ấn Đầu Tiên', 'Hoàn thành câu hỏi của một bảo tàng bất kỳ', 'uploads/icon/first_quiz.png'),
('Nhà Khám Phá Văn Hóa', 'Check-in lần đầu tiên', 'uploads/icon/first_checkin.png'),
('Lịch sử đẹp đẽ', 'Lần đầu thay đổi avatar', 'uploads/icon/first_avatar.png'),

-- Helpful query: list achievements for a user
-- Replace ? with the user's token
-- SELECT a.ID, a.Name, a.Description, a.Icon, ua.CreatedAt
-- FROM achievements a
-- JOIN user_achievements ua ON ua.AchievementID = a.ID
-- WHERE ua.UserToken = ?
