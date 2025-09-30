-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 30, 2025 at 07:04 AM
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
(36, 9, 'Thời kỳ hiện đại', 0),
(37, 10, 'Nguyễn Đức Lô trong Chiến dịch Biên giới 1950', 1),
(38, 10, 'Trần Văn Trà trong Chiến dịch Hồ Chí Minh 1975', 0),
(39, 10, 'Lê Trọng Huyên trong Kháng chiến chống Mỹ', 0),
(40, 10, 'Võ Nguyên Giáp trong Chiến dịch Điện Biên Phủ', 0),
(41, 11, 'Hành quân vượt núi, ngủ hầm, ăn cơm vắt giữa mưa bom bão đạn', 1),
(42, 11, 'Tham gia chiến dịch Hồ Chí Minh năm 1975', 0),
(43, 11, 'Bắn rơi máy bay B-52 trên bầu trời Hà Nội', 0),
(44, 11, 'Tham gia chiến dịch Trung Lào năm 1954', 0),
(45, 12, 'Bắn rơi hai máy bay B-52 trên bầu trời Hà Nội', 0),
(46, 12, 'Bắn rơi máy bay vận tải C-130', 0),
(47, 12, 'Bắn rơi hai máy bay B-52 trên bầu trời Hà Nội', 1),
(48, 12, 'Bắn rơi máy bay trinh sát không người lái', 0),
(49, 13, 'Bắn rơi máy bay Mỹ tại Hà Nội', 0),
(50, 13, 'Giữ vững trận địa, tiêu diệt nhiều địch tại Na Kham, Trung Lào năm 1954', 1),
(51, 13, 'Tham gia chiến dịch Biên giới năm 1950', 0),
(52, 13, 'Bảo vệ cầu Thị Nghè trong chiến dịch Hồ Chí Minh', 0),
(53, 14, 'Tạ Quang Bạo', 0),
(54, 14, 'Phạm Mười', 0),
(55, 14, 'Nguyễn Hải', 1),
(56, 14, 'Mô Lô Kai', 0),
(57, 15, 'Đồng Tháp Mười', 0),
(58, 15, 'Sông Ba (An Khê, Gia Lai)', 1),
(59, 15, 'Trường Sơn', 0),
(60, 15, 'Tây Nguyên', 0),
(61, 16, 'Cành lá ngụy trang', 0),
(62, 16, 'Lựu đạn', 0),
(63, 16, 'Súng', 1),
(64, 16, 'Cờ đỏ sao vàng', 0),
(65, 17, 'Anh hùng Núp (Đinh Núp)', 1),
(66, 17, 'Anh hùng Nguyễn Văn Cốc', 0),
(67, 17, 'Anh hùng Văn Tiến Dũng', 0),
(68, 17, 'Anh hùng Lê Công Thành', 0);

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
(9, 9, 'Những hiện vật vũ khí văn hóa Đông Sơn thể hiện giai đoạn lịch sử nào của dân tộc Việt Nam?'),
(10, 10, 'Chiếc áo trấn thủ do Bác Hồ tặng thuộc về chiến sĩ nào có thành tích xuất sắc trong chiến dịch nào?'),
(11, 11, 'Chiếc ba lô của Đại tá Lê Khắc Phấn trong Chiến dịch Điện Biên Phủ gắn liền với hình ảnh nào sau đây?'),
(12, 12, 'Bệ phóng tên lửa SAM-2 trưng bày tại Bảo tàng Chiến thắng B-52 đã lập chiến công gì nổi bật trong tháng 12 năm 1972?'),
(13, 13, 'Khẩu súng trung liên của Anh hùng Cao Thế Chiến gắn liền với chiến công nào?'),
(14, 14, 'Tác phẩm điêu khắc \'Du kích Đồng Tháp\' của nhà điêu khắc nào được trưng bày tại Bảo tàng Lịch sử Quân sự Việt Nam?'),
(15, 15, 'Bức tượng \'Cô gái vót chông\' lấy cảm hứng từ vùng đất nào?'),
(16, 16, 'Tác phẩm \'Mẹ Trường Sơn\' thể hiện hình ảnh người mẹ dân tộc thiểu số ôm con và cầm gì trong tay?'),
(17, 17, 'Tượng \'Tiếng cồng Tây Nguyên\' của Mô Lô Kai lấy cảm hứng từ hình tượng anh hùng nào?');

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
(9, 3, 'Thời đại Hùng Vương - An Dương Vương'),
(10, 3, 'Nguyễn Đức Lô trong Chiến dịch Biên giới 1950'),
(11, 3, 'Hành quân vượt núi, ngủ hầm, ăn cơm vắt giữa mưa bom bão đạn'),
(12, 3, 'Bắn rơi hai máy bay B-52 trên bầu trời Hà Nội'),
(13, 3, 'Giữ vững trận địa, tiêu diệt nhiều địch tại Na Kham, Trung Lào năm 1954'),
(14, 3, 'Nguyễn Hải'),
(15, 3, 'Sông Ba (An Khê, Gia Lai)'),
(16, 3, 'Súng'),
(17, 3, 'Anh hùng Núp (Đinh Núp)');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
