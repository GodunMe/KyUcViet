-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 29, 2025 at 06:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
-- Table structure for table `option`
--


INSERT INTO `question` (`QuestionID`, `QuizID`, `QuestionText`) VALUES
(1, 1, 'Bảo tàng Lịch sử Quân sự Việt Nam có tên gọi thân quen là gì?'),
(2, 2, 'Bảo tàng Lịch sử Quân sự Việt Nam thành lập năm nào?'),
(3, 3, 'Tòa tháp nổi tiếng nằm trong khuôn viên bảo tàng là gì?'),
(4, 4, 'Trong bảo tàng có trưng bày chiếc máy bay nào gắn liền với chiến thắng Điện Biên Phủ trên không?'),
(5, 5, 'Bảo tàng Lịch sử Quân sự Việt Nam nằm trên đường nào của Hà Nội?'),
(6, 6, 'Chiếc xe tăng nào được trưng bày, từng húc đổ cổng Dinh Độc Lập ngày 30/4/1975?'),
(7, 7, 'Trong bảo tàng có trưng bày chiếc pháo cao xạ gắn liền với chiến dịch nào?'),
(8, 8, 'Bảo tàng Lịch Sử Quân sự Việt Nam hiện là 1 trong bao nhiêu bảo tàng hạng 1 quốc gia?'),
(9, 9, 'Ngoài trưng bày vũ khí, bảo tàng còn có chuyên đề gì đặc biệt?'),
(10, 10, 'Bảo tàng Hồ Chí Minh khánh thành vào ngày nào?'),
(11, 11, 'Bảo tàng Hồ Chí Minh nằm ở đâu?'),
(12, 12, 'Hình dáng kiến trúc của bảo tàng tượng trưng cho loài hoa nào?'),
(13, 13, 'Ai là Tổng Bí thư đọc diễn văn khánh thành bảo tàng năm 1990?'),
(14, 14, 'Bảo tàng được xây dựng nhân kỷ niệm bao nhiêu năm ngày sinh của Bác Hồ?'),
(15, 15, 'Phần trưng bày cố định của bảo tàng tập trung vào nội dung gì?'),
(16, 16, 'Ngoài hiện vật, bảo tàng còn sử dụng phương tiện gì để minh họa?'),
(17, 17, 'Công trình bảo tàng được coi là biểu tượng của tình hữu nghị Việt Nam với nước nào?'),
(18, 18, 'Bảo tàng Dân tộc học Việt Nam chính thức mở cửa vào năm nào?'),
(19, 19, 'Bảo tàng Dân tộc học Việt Nam trưng bày về bao nhiêu dân tộc?'),
(20, 20, 'Khu trưng bày ngoài trời của bảo tàng nổi bật với gì?'),
(21, 21, 'Ai là người hỗ trợ thiết kế chính của bảo tàng?'),
(22, 22, 'Ngoài trưng bày thường xuyên, bảo tàng còn tổ chức gì?'),
(23, 23, 'Bảo tàng được coi là “ngôi nhà chung” của?');

--
-- Dumping data for table `option`
--

INSERT INTO `option` (`OptionID`, `QuestionID`, `TEXT`, `isCorrect`) VALUES
(1, 1, 'Bảo tàng Cờ đỏ sao vàng', 0),
(2, 1, 'Bảo tàng Quân sự', 1),
(3, 1, 'Bảo tàng Quân đội', 0),
(4, 1, 'Bảo tàng Hồ Gươm', 0),
(5, 2, '1945', 0),
(6, 2, '1956', 1),
(7, 2, '1960', 0),
(8, 2, '1975', 0),
(9, 3, 'Tháp Bút', 0),
(10, 3, 'Cột cờ Hà Nội', 1),
(11, 3, 'Tháp Rùa', 0),
(12, 3, 'Tháp Hòa Phong', 0),
(13, 4, 'MIG-21', 1),
(14, 4, 'MIG-17', 0),
(15, 4, 'SU-22', 0),
(16, 4, 'F-5', 0),
(17, 5, 'Điện Biên Phủ', 1),
(18, 5, 'Tràng Tiền', 0),
(19, 5, 'Lê Duẩn', 0),
(20, 5, 'Hoàng Diệu', 0),
(21, 6, 'T-34', 0),
(22, 6, 'T-54B', 1),
(23, 6, 'T-90', 0),
(24, 6, 'PT-76', 0),
(25, 7, 'Chiến dịch Biên giới 1950', 0),
(26, 7, 'Chiến dịch Điện Biên Phủ 1954', 1),
(27, 7, 'Chiến dịch Hồ Chí Minh', 0),
(28, 7, 'Chiến dịch Tây Nguyên', 0),
(29, 8, '10', 0),
(30, 8, '12', 1),
(31, 8, '15', 0),
(32, 8, '20', 0),
(33, 9, 'Nghệ thuật quân sự Việt Nam qua các thời kỳ', 1),
(34, 9, 'Văn hóa ẩm thực quân đội', 0),
(35, 9, 'Đồng phục quân đội các nước', 0),
(36, 9, 'Lịch sử hàng hải', 0),
(37, 10, '2/9/1990', 1),
(38, 10, '19/5/1985', 0),
(39, 10, '30/4/1975', 0),
(40, 10, '2/9/1945', 0),
(41, 11, 'Quận Hoàn Kiếm', 0),
(42, 11, 'Quận Ba Đình', 1),
(43, 11, 'Quận Hai Bà Trưng', 0),
(44, 11, 'Quận Tây Hồ', 0),
(45, 12, 'Hoa sen', 1),
(46, 12, 'Hoa đào', 0),
(47, 12, 'Hoa mai', 0),
(48, 12, 'Hoa cúc', 0),
(49, 13, 'Nguyễn Văn Linh', 1),
(50, 13, 'Đỗ Mười', 0),
(51, 13, 'Trường Chinh', 0),
(52, 13, 'Lê Khả Phiêu', 0),
(53, 14, '110 năm', 0),
(54, 14, '120 năm', 0),
(55, 14, '90 năm', 0),
(56, 14, '100 năm', 1),
(57, 15, 'Chiến thắng Điện Biên Phủ', 0),
(58, 15, 'Lịch sử Thăng Long – Hà Nội', 0),
(59, 15, 'Cuộc đời và sự nghiệp của Chủ tịch Hồ Chí Minh', 1),
(60, 15, 'Văn hóa Việt Nam', 0),
(61, 16, 'Game 3D', 0),
(62, 16, 'Âm nhạc hiện đại', 0),
(63, 16, 'Mô hình, phim tư liệu', 1),
(64, 16, 'Trò chơi thực tế ảo', 0),
(65, 17, 'Liên Xô (Nga)', 1),
(66, 17, 'Pháp', 0),
(67, 17, 'Trung Quốc', 0),
(68, 17, 'Cuba', 0),
(69, 18, '2005', 0),
(70, 18, '2000', 0),
(71, 18, '1997', 1),
(72, 18, '1995', 0),
(73, 19, '45', 0),
(74, 19, '54', 1),
(75, 19, '63', 0),
(76, 19, '70', 0),
(77, 20, 'Hang động', 0),
(78, 20, 'Hồ nước', 0),
(79, 20, 'Làng cổ', 0),
(80, 20, 'Nhà sàn, nhà dài, nhà rông', 1),
(81, 21, 'KTS Nguyễn Cao Luyện', 0),
(82, 21, 'KTS Hà Đức Lịnh', 0),
(83, 21, 'KTS Tạ Mỹ Duật', 0),
(84, 21, 'KTS người Pháp – Veronique Dollfus', 1),
(85, 22, 'Hội chợ thương mại', 0),
(86, 22, 'Triển lãm công nghệ', 0),
(87, 22, 'Trình diễn nghệ thuật dân gian', 1),
(88, 22, 'Các hội thảo quốc tế', 0),
(89, 23, 'Du khách quốc tế', 0),
(90, 23, 'Sinh viên', 0),
(91, 23, 'Các dân tộc Việt Nam', 1),
(92, 23, 'Các nhà khoa học', 0);

-- --------------------------------------------------------

--
--
--
-- Dumping data for table `question`
--

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz`
--

INSERT INTO `quiz` (`QuizID`, `MuseumID`, `Explaination`) VALUES
(1, 3, 'Quiz kiểm tra kiến thức về hiện vật trong bảo tàng Lịch Sử Quân sự Việt Nam'),
(2, 3, 'Quiz kiểm tra kiến thức lịch sử bảo tàng'),
(3, 3, 'Quiz kiểm tra kiến thức về hiện vật trong bảo tàng Lịch Sử Quân sự Việt Nam'),
(4, 3, 'Quiz kiểm tra kiến thức về hiện vật trong bảo tàng Lịch Sử Quân sự Việt Nam'),
(5, 3, 'Quiz kiểm tra kiến thức về bảo tàng'),
(6, 3, 'Quiz kiểm tra kiến thức về hiện vật trong bảo tàng Lịch Sử Quân sự Việt Nam'),
(7, 3, 'Quiz kiểm tra kiến thức về hiện vật trong bảo tàng Lịch Sử Quân sự Việt Nam'),
(8, 3, 'Quiz kiểm tra kiến thức về bảo tàng'),
(9, 3, 'Quiz kiểm tra kiến thức về hiện vật trong bảo tàng Lịch Sử Quân sự Việt Nam'),
(10, 4, 'Quiz kiểm tra kiến thức về bảo tàng'),
(11, 4, 'Quiz kiểm tra kiến thức về bảo tàng'),
(12, 4, 'Quiz kiểm tra kiến thức về bảo tàng'),
(13, 4, 'Quiz kiểm tra kiến thức lịch sử'),
(14, 4, 'Quiz kiểm tra kiến thức về bảo tàng'),
(15, 4, 'Quiz kiểm tra kiến thức về hiện vật trong bảo tàng'),
(16, 4, 'Quiz kiểm tra kiến thức về hiện vật trong bảo tàng'),
(17, 4, 'Quiz kiểm tra kiến thức về bảo tàng'),
(18, 5, 'Quiz kiểm tra kiến thức về bảo tàng'),
(19, 5, 'Quiz kiểm tra kiến thức về bảo tàng'),
(20, 5, 'Quiz kiểm tra kiến thức về hiện vật trong bảo tàng'),
(21, 5, 'Quiz kiểm tra kiến thức về bảo tàng'),
(22, 5, 'Quiz kiểm tra kiến thức về bảo tàng'),
(23, 5, 'Quiz kiểm tra kiến thức về bảo tàng');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `option`
--
ALTER TABLE `option`
  MODIFY `OptionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `QuestionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `quiz`
--
ALTER TABLE `quiz`
  MODIFY `QuizID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
