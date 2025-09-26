-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 26, 2025 at 04:06 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `exe201`
--

-- --------------------------------------------------------

--
-- Table structure for table `artifact`
--

CREATE TABLE `artifact` (
  `ArtifactID` int NOT NULL,
  `MuseumID` int DEFAULT NULL,
  `ArtifactName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Description` text COLLATE utf8mb4_unicode_ci,
  `Image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `MimeType` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `museum`
--

CREATE TABLE `museum` (
  `MuseumID` int NOT NULL,
  `MuseumName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Description` text COLLATE utf8mb4_unicode_ci,
  `Latitude` decimal(10,7) NOT NULL,
  `Longitude` decimal(10,7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `museum`
--

INSERT INTO `museum` (`MuseumID`, `MuseumName`, `Address`, `Description`, `Latitude`, `Longitude`) VALUES
(3, 'Bảo tàng Lịch Sử Quân sự Việt Nam', 'Km6+500, CT03, Xuân Phương, Hà Nội', 'Bảo tàng Lịch sử Quân sự Việt Nam là một trong các bảo tàng quốc gia và đứng đầu trong hệ thống Bảo tàng Quân đội, hiện đang lưu giữ, trưng bày hơn 15 vạn tài liệu, hiện vật, trong đó có nhiều sưu tập độc đáo và 4 Bảo vật Quốc gia, gồm máy bay MiG-21 số hiệu 4324, máy bay MiG-21 số hiệu 5121, Bản đồ Quyết tâm chiến dịch Hồ Chí Minh và xe tăng T-54B số hiệu 843.', 21.0086136, 105.7556136),
(4, 'Bảo tàng Hồ Chí Minh', '19 P. Ngọc Hà, Đội Cấn, Ba Đình, Hà Nội 100000, Việt Nam', 'Bảo tàng Hồ Chí Minh là bảo tàng ở Hà Nội tập trung chủ yếu vào việc trưng bày những hiện vật, tư liệu về cuộc đời và con người Hồ Chí Minh. Nằm trong khu vực có nhiều di tích như: Lăng Chủ tịch Hồ Chí Minh, Khu di tích Phủ Chủ tịch, Chùa Một Cột,... tạo thành một quần thể các di tích thu hút khách tham quan trong và ngoài nước. Bảo tàng tọa lạc tại số 19 phố Ngọc Hà, phường Đội Cấn, quận Ba Đình, thành phố Hà Nội, nằm phía sau Lăng Chủ tịch Hồ Chí Minh và cạnh công viên Bách Thảo. Bảo tàng Hồ Chí Minh là bảo tàng lớn và hiện đại nhất Việt Nam.', 21.0372081, 105.8330520),
(5, 'Bảo tàng Dân tộc học', 'Đ. Nguyễn Văn Huyên, Quan Hoa, Cầu Giấy, Hà Nội', 'Ngày 12 tháng 11 năm 1997, tại Hà Nội diễn ra một sự kiện quan trọng: Phó Chủ tịch nước Cộng hòa xã hội chủ nghĩa Việt Nam Nguyễn Thị Bình và Tổng thống Cộng hòa Pháp Jacques Chirac cắt băng khai trương Bảo tàng Dân tộc học Việt Nam.\r\n\r\nTừ tòa Trống đồng giới thiệu 54 dân tộc Việt Nam, Bảo tàng Dân tộc học Việt Nam đã từng bước hoàn thiện khu Vườn Kiến trúc với 10 công trình dân gian đại diện cho các loại hình khác nhau của nhiều dân tộc và vùng văn hóa. Không dừng lại ở giới thiệu về Việt Nam, Bảo tàng xây dựng tòa Cánh diều, trưng bày kết nối với các tộc người ở Đông Nam Á. Và xa hơn thế, các trưng bày vươn ra châu Á, châu Đại Dương, châu Phi và Mỹ Latin, nhờ những sưu tập hiện vật được hiến tặng. Trải qua hành trình hơn 20 năm, cùng với các trưng bày thường xuyên là hàng loạt trưng bày nhất thời, những hoạt động trình diễn văn hóa phi vật thể, các chương trình hoạt động giáo dục trải nghiệm… đã làm cho Bảo tàng Dân tộc học Việt Nam sống động và trở thành một điểm sáng, một điểm tham quan thu hút đông đảo du khách trong nước và quốc tế, được công chúng mến mộ. Trong ba năm liền (2012, 2013, 2014), Bảo tàng Dân tộc học Việt Nam được TripAdvisor, trang web du lịch nổi tiếng thế giới, bình chọn là Bảo tàng xuất sắc, xếp thứ tư trong 25 bảo tàng hấp dẫn nhất châu Á. Ba năm tiếp theo (2015, 2016, 2017), Bảo tàng Dân tộc học Việt Nam được vinh danh là Điểm tham quan du lịch hàng đầu Việt Nam, do Bộ Văn hóa, Thể thao và Du lịch, Tổng cục Du lịch và Hiệp hội Du lịch Việt Nam trao tặng. Thậm chí, ngay trong thời kỳ dịch bệnh Covid-19 ảnh hưởng nặng nề đến Việt Nam và các nước trên thế giới (2020-2021), Bảo tàng Dân tộc học Việt Nam vẫn không ngừng sáng tạo để đưa đến cho công chúng những sản phẩm văn hóa đa dạng, đặc sắc và thích ứng linh hoạt với nhu cầu thay đổi của xã hội, cũng như tình hình “bình thường mới”. Bảo tàng Dân tộc học Việt Nam đã vinh hạnh và tự hào khi là bảo tàng duy nhất ở Việt Nam được Bộ Văn hóa, Thể thao và Du lịch trao tặng Bằng khen vì đã có thành tích xuất sắc trong xây dựng và tổ chức hoạt động du lịch tại địa phương năm 2021.\r\n\r\nĐể đạt được các kết quả đó, trong suốt quá trình hình thành và phát triển, đội ngũ nhân viên của Bảo tàng Dân tộc học Việt Nam luôn hướng theo các quan niệm, tiếp cận phương thức hoạt động mới. Bảo tàng Dân tộc học Việt Nam cũng luôn nhận được sự hỗ trợ có hiệu quả của nhiều chuyên gia, tổ chức trong nước và quốc tế. Quá trình làm việc không mệt mỏi ấy là quá trình cán bộ, nhân viên Bảo tàng tích lũy kiến thức và những trải nghiệm chuyên nghiệp quý báu.', 21.0405575, 105.7986764);

-- --------------------------------------------------------

--
-- Table structure for table `museum_media`
--

CREATE TABLE `museum_media` (
  `id` int NOT NULL,
  `MuseumId` int NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `museum_media`
--

INSERT INTO `museum_media` (`id`, `MuseumId`, `file_name`, `mime_type`, `file_path`) VALUES
(1, 3, 'Bao tang Lich su Quan su Viẹt Nam', 'image/png', '/EXEProject/KyUcViet/uploads/museums/Bao_tang_Lich_su_Quan_su_Viet_Nam.png'),
(2, 3, 'Bao tang Lich su Quan su Viet Nam', 'image/png', '/EXEProject/KyUcViet/uploads/museums/17327143207601.png'),
(3, 4, 'Bao tang Ho Chi Minh', 'image/png', '/EXEProject/KyUcViet/uploads/museums/bao_tang_ho_chi_minh.png'),
(4, 5, 'Bao tang Dan toc hoc', 'image/png', '/EXEProject/KyUcViet/uploads/museums/bao_tang_dan_toc_hoc.png'),
(5, 3, 'Bao tang Lich su Quan su Viet Nam', 'video/mp4', '/EXEProject/KyUcViet/uploads/museums/Một_vòng_Bảo_tàng_Lịch_sử_Quân_sự_Việt_Nam.mp4');

-- --------------------------------------------------------

--
-- Table structure for table `option`
--

CREATE TABLE `option` (
  `OptionID` int NOT NULL,
  `QuestionID` int DEFAULT NULL,
  `TEXT` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isCorrect` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

CREATE TABLE `question` (
  `QuestionID` int NOT NULL,
  `QuizID` int DEFAULT NULL,
  `QuestionText` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--

CREATE TABLE `quiz` (
  `QuizID` int NOT NULL,
  `MuseumID` int DEFAULT NULL,
  `Explaination` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `useranswer`
--

CREATE TABLE `useranswer` (
  `UserAnswerID` int NOT NULL,
  `QuizID` int DEFAULT NULL,
  `QuestionID` int DEFAULT NULL,
  `OptionID` int DEFAULT NULL,
  `AnsweredAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `userName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserToken` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `PASSWORD` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Role` enum('Admin','Customer','CustomerPre') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Customer',
  `Score` int DEFAULT '0',
  `FailedLoginAttempts` int DEFAULT '0',
  `STATUS` enum('Active','Locked') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `LockTimestamp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `artifact`
--
ALTER TABLE `artifact`
  ADD PRIMARY KEY (`ArtifactID`),
  ADD KEY `MuseumID` (`MuseumID`);

--
-- Indexes for table `museum`
--
ALTER TABLE `museum`
  ADD PRIMARY KEY (`MuseumID`);

--
-- Indexes for table `museum_media`
--
ALTER TABLE `museum_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `MuseumId` (`MuseumId`);

--
-- Indexes for table `option`
--
ALTER TABLE `option`
  ADD PRIMARY KEY (`OptionID`),
  ADD KEY `QuestionID` (`QuestionID`);

--
-- Indexes for table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`QuestionID`),
  ADD KEY `QuizID` (`QuizID`);

--
-- Indexes for table `quiz`
--
ALTER TABLE `quiz`
  ADD PRIMARY KEY (`QuizID`),
  ADD KEY `MuseumID` (`MuseumID`);

--
-- Indexes for table `useranswer`
--
ALTER TABLE `useranswer`
  ADD PRIMARY KEY (`UserAnswerID`),
  ADD KEY `QuizID` (`QuizID`),
  ADD KEY `QuestionID` (`QuestionID`),
  ADD KEY `OptionID` (`OptionID`),
  ADD KEY `userName` (`userName`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserToken`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `artifact`
--
ALTER TABLE `artifact`
  MODIFY `ArtifactID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `museum`
--
ALTER TABLE `museum`
  MODIFY `MuseumID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `museum_media`
--
ALTER TABLE `museum_media`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `option`
--
ALTER TABLE `option`
  MODIFY `OptionID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `QuestionID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz`
--
ALTER TABLE `quiz`
  MODIFY `QuizID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `useranswer`
--
ALTER TABLE `useranswer`
  MODIFY `UserAnswerID` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `artifact`
--
ALTER TABLE `artifact`
  ADD CONSTRAINT `artifact_ibfk_1` FOREIGN KEY (`MuseumID`) REFERENCES `museum` (`MuseumID`);

--
-- Constraints for table `museum_media`
--
ALTER TABLE `museum_media`
  ADD CONSTRAINT `museum_media_ibfk_1` FOREIGN KEY (`MuseumId`) REFERENCES `museum` (`MuseumID`) ON DELETE CASCADE;

--
-- Constraints for table `option`
--
ALTER TABLE `option`
  ADD CONSTRAINT `option_ibfk_1` FOREIGN KEY (`QuestionID`) REFERENCES `question` (`QuestionID`);

--
-- Constraints for table `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `question_ibfk_1` FOREIGN KEY (`QuizID`) REFERENCES `quiz` (`QuizID`);

--
-- Constraints for table `quiz`
--
ALTER TABLE `quiz`
  ADD CONSTRAINT `quiz_ibfk_1` FOREIGN KEY (`MuseumID`) REFERENCES `museum` (`MuseumID`);

--
-- Constraints for table `useranswer`
--
ALTER TABLE `useranswer`
  ADD CONSTRAINT `useranswer_ibfk_2` FOREIGN KEY (`QuizID`) REFERENCES `quiz` (`QuizID`),
  ADD CONSTRAINT `useranswer_ibfk_3` FOREIGN KEY (`QuestionID`) REFERENCES `question` (`QuestionID`),
  ADD CONSTRAINT `useranswer_ibfk_4` FOREIGN KEY (`OptionID`) REFERENCES `option` (`OptionID`),
  ADD CONSTRAINT `useranswer_ibfk_5` FOREIGN KEY (`userName`) REFERENCES `users` (`Username`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
