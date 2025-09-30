-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 30, 2025 at 06:48 AM
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

CREATE TABLE `option` (
  `OptionID` int(11) NOT NULL,
  `QuestionID` int(11) DEFAULT NULL,
  `TEXT` varchar(255) NOT NULL,
  `isCorrect` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `option`
--

INSERT INTO `option` (`OptionID`, `QuestionID`, `TEXT`, `isCorrect`) VALUES
(1, 1, '10 chiếc', 0),
(2, 1, '12 chiếc', 0),
(3, 1, '14 chiếc', 1),
(4, 1, '16 chiếc', 0),
(5, 2, 'Hạ 14 máy bay Mỹ', 0),
(6, 2, 'Hạ B-52 bằng tên lửa không đối không', 1),
(7, 2, 'Bắn rơi trực thăng vận tải', 0),
(8, 2, 'Thực hiện nhiều chuyến bay trinh sát', 0),
(9, 3, 'Chiến dịch Điện Biên Phủ trên không năm 1972', 0),
(10, 3, 'Chiến dịch Tây Nguyên năm 1975', 0),
(11, 3, 'Chiến dịch Hồ Chí Minh năm 1975, tiến vào Dinh Độc Lập', 1),
(12, 3, 'Trận Vạn Tường năm 1965', 0),
(13, 4, '15/4/1975', 0),
(14, 4, '21/4/1975', 0),
(15, 4, '22/4/1975', 1),
(16, 4, '30/4/1975', 0),
(17, 5, 'F-4 Phantom II', 0),
(18, 5, 'F-8A', 1),
(19, 5, 'B-52', 0),
(20, 5, 'F-105D', 0),
(21, 6, 'Đoàn tàu không số', 1),
(22, 6, 'Hải đội 413', 0),
(23, 6, 'Đoàn 125', 0),
(24, 6, 'Đoàn 759', 0),
(25, 7, 'Pháo cao xạ 57mm', 0),
(26, 7, 'Pháo cao xạ 37mm', 1),
(27, 7, 'Pháo cao xạ 100mm', 0),
(28, 7, 'Pháo cao xạ 85mm', 0),
(29, 8, 'Người có thành tích xuất sắc trong chiến đấu và công tác', 1),
(30, 8, 'Tất cả công dân Việt Nam', 0),
(31, 8, 'Người nước ngoài có công với cách mạng Việt Nam', 0),
(32, 8, 'Các nhà khoa học nghiên cứu lịch sử quân sự', 0),
(33, 9, 'Thời kỳ Pháp thuộc', 0),
(34, 9, 'Thời đại Hùng Vương - An Dương Vương', 1),
(35, 9, 'Thời kỳ chiến tranh chống Mỹ', 0),
(36, 9, 'Thời kỳ hiện đại', 0);

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

CREATE TABLE `question` (
  `QuestionID` int(11) NOT NULL,
  `QuizID` int(11) DEFAULT NULL,
  `QuestionText` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `question`
--

INSERT INTO `question` (`QuestionID`, `QuizID`, `QuestionText`) VALUES
(1, 1, 'Máy bay MiG-21 số hiệu 4324 Câu hỏi: Chiếc MiG-21 số hiệu 4324 đã bắn rơi bao nhiêu máy bay Mỹ trong kháng chiến chống Mỹ?'),
(2, 2, 'Thành tích nổi bật nhất của MiG-21 số hiệu 5121 trong lịch sử chiến tranh Việt Nam là gì?'),
(3, 3, 'Xe tăng T-54B số hiệu 843 đã gắn liền với sự kiện lịch sử nào?'),
(4, 4, 'Bản đồ Quyết tâm Chiến dịch Hồ Chí Minh được hoàn thành và duyệt lần cuối vào ngày nào?'),
(5, 5, 'Pháo cao xạ 100mm của tự vệ Đống Đa đã bắn rơi máy bay Mỹ nào vào ngày 11/9/1972?'),
(6, 6, 'Tàu HQ-671 là hiện vật duy nhất còn lại của lực lượng nào?'),
(7, 7, 'Chiếc mũ cứng của Liệt sĩ Nguyễn Hữu Toái gắn liền với trận địa pháo nào?'),
(8, 8, 'Huy hiệu Bác Hồ được Chủ tịch Hồ Chí Minh tặng thưởng cho ai?'),
(9, 9, 'Những hiện vật vũ khí văn hóa Đông Sơn thể hiện giai đoạn lịch sử nào của dân tộc Việt Nam?');

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--

CREATE TABLE `quiz` (
  `QuizID` int(11) NOT NULL,
  `MuseumID` int(11) DEFAULT NULL,
  `Explaination` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz`
--

INSERT INTO `quiz` (`QuizID`, `MuseumID`, `Explaination`) VALUES
(1, 3, '14 chiếc'),
(2, 3, 'Hạ B-52 bằng tên lửa không đối không'),
(3, 3, 'Chiến dịch Hồ Chí Minh năm 1975, tiến vào Dinh Độc Lập'),
(4, 3, '22/4/1975'),
(5, 3, 'F-8A'),
(6, 3, 'Đoàn tàu không số'),
(7, 3, 'Pháo cao xạ 37mm'),
(8, 3, 'Người có thành tích xuất sắc trong chiến đấu và công tác'),
(9, 3, 'Thời đại Hùng Vương - An Dương Vương');

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
  MODIFY `OptionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `QuestionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `quiz`
--
ALTER TABLE `quiz`
  MODIFY `QuizID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
