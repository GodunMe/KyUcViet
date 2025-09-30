-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 27, 2025 at 02:46 PM
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
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `UserToken` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userNumber` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PASSWORD` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Role` enum('Admin','Customer','CustomerPre') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Customer',
  `Score` int DEFAULT '0',
  `FailedLoginAttempts` int DEFAULT '0',
  `STATUS` enum('Active','Locked') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `isFirstLogin` tinyint(1) NOT NULL DEFAULT '1',
  `LockTimestamp` datetime DEFAULT NULL,
  `avatar` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserToken`, `Username`, `userNumber`, `PASSWORD`, `Role`, `Score`, `FailedLoginAttempts`, `STATUS`, `isFirstLogin`, `LockTimestamp`, `avatar`) VALUES
('1', 'Phạm Xuân Dương', NULL, '$2y$10$B7a6ZAuaWD3FfAlchcU7zOLY.mbefvs1sGn61qzfun1vnCtgMjRee', 'CustomerPre', 888, 0, 'Active', 0, NULL, 'avatar/avatar.png'),
('2', 'Tester1', NULL, '$2y$10$czoqxWa.7BspACtVcphEG.lWTIr1xCsjHxFsYHK2Hib5Ctmxq1mZa', 'Customer', 0, 0, 'Active', 0, NULL, 'avatar/avatar.png'),
('3', 'tester2', NULL, '$2y$10$czoqxWa.7BspACtVcphEG.lWTIr1xCsjHxFsYHK2Hib5Ctmxq1mZa', 'Admin', 0, 0, 'Active', 0, NULL, 'avatar/avatar.png');

-- --------------------------------------------------------

--
-- Table structure for table `museum`
--

DROP TABLE IF EXISTS `museum`;
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
(3, 'Bảo tàng Lịch Sử Quân sự Việt Nam', 'Km6+500, CT03, Xuân Phương, Hà Nội', 'Bảo tàng Lịch sử Quân sự Việt Nam là một trong 6 bảo tàng cấp quốc gia và là bảo tàng đầu hệ của hệ thống bảo tàng quân đội, được thành lập ngày 17/7/1956, địa chỉ tại số 28A đường Điện Biên Phủ, quận Ba Đình, thành phố Hà Nội. Bảo tàng hiện đang trưng bày và lưu giữ hàng vạn hiện vật, trong đó có 4 bảo vật quốc gia, bao gồm: (Máy bay MiG-21 số hiệu 4324; Máy bay MiG-21 số hiệu 5121, Tấm Bản đồ Quyết tâm chiến dịch Hồ Chí Minh; Xe tăng T-54B số hiệu 843).', 21.0086136, 105.7556136),
(4, 'Bảo tàng Hồ Chí Minh', '19 P. Ngọc Hà, Đội Cấn, Ba Đình, Hà Nội 100000, Việt Nam', 'Bảo tàng Hồ Chí Minh là bảo tàng ở Hà Nội tập trung chủ yếu vào việc trưng bày những hiện vật, tư liệu về cuộc đời và con người Hồ Chí Minh. Nằm trong khu vực có nhiều di tích như: Lăng Chủ tịch Hồ Chí Minh, Khu di tích Phủ Chủ tịch, Chùa Một Cột,... tạo thành một quần thể các di tích thu hút khách tham quan trong và ngoài nước. Bảo tàng tọa lạc tại số 19 phố Ngọc Hà, phường Đội Cấn, quận Ba Đình, thành phố Hà Nội, nằm phía sau Lăng Chủ tịch Hồ Chí Minh và cạnh công viên Bách Thảo. Bảo tàng Hồ Chí Minh là bảo tàng lớn và hiện đại nhất Việt Nam.', 21.0372081, 105.8330520),
(5, 'Bảo tàng Dân tộc học', 'Đ. Nguyễn Văn Huyên, Quan Hoa, Cầu Giấy, Hà Nội', 'Ngày 12 tháng 11 năm 1997, tại Hà Nội diễn ra một sự kiện quan trọng: Phó Chủ tịch nước Cộng hòa xã hội chủ nghĩa Việt Nam Nguyễn Thị Bình và Tổng thống Cộng hòa Pháp Jacques Chirac cắt băng khai trương Bảo tàng Dân tộc học Việt Nam.', 21.0405575, 105.7986764),
(6, 'Bảo tàng Hà Nội', 'Phạm Hùng, Mễ Trì, Nam Từ Liêm, Hà Nội', 'Để kỷ niệm đại lễ 1000 năm Thăng Long - Hà Nội, một dự án xây dựng mới bảo tàng Hà Nội đã được thực hiện với số tiền đầu tư rất lớn, Bảo tàng Hà Nội mới nằm trong khu vực xây dựng Trung tâm Hội nghị Quốc gia Việt Nam tại phường Mễ Trì, quận Nam Từ Liêm; có kết cấu hình kim tự tháp ngược, trong đó tầng 4 có diện tích lớn nhất, các tầng dưới nhỏ dần. Thiết kế của Liên doanh tư vấn GMP - ILAG (Đức); được xây dựng trên tổng diện tích khoảng 54.000 m², cao 30,7 mét. Công trình gồm 4 tầng nổi và 2 tầng hầm; diện tích xây dựng xấp xỉ 12.000 m², diện tích sàn hơn 30.000 m² (kể cả tầng hầm và tầng mái). Bảo tàng đã được khánh thành vào ngày 6 tháng 10 năm 2010. Ước tính có 50.000 hiện vật được trưng bày tại đây.', 21.0107591, 105.7866290),
(7, 'Bảo tàng Phòng không - Không quân', '173C Đ. Trường Chinh, Khương Mai, Thanh Xuân, Hà Nội', 'Bảo tàng Phòng không – Không quân trực thuộc Cục Chính trị, Quân chủng Phòng không – Không quân, Quân đội nhân dân Việt Nam, tiền thân là Phòng Truyền thống Bộ đội Phòng không thành lập năm 1958. Bảo tàng được xếp hạng Hai trong hệ thống bảo tàng tại Việt Nam. Bảo tàng được xây dựng vào năm 2004, khánh thành ngày 28 tháng 8 năm 2007, là nơi lưu giữ những hình ảnh, tư liệu, hiện vật minh chứng cho quá trình ra đời, xây dựng, chiến đấu, trưởng thành và chiến thắng của bộ đội Phòng không – Không quân Việt Nam. Bảo tàng tọa lạc tại 173C, Trường Chinh, Khương Mai, Thanh Xuân, Hà Nội, Việt Nam.', 21.0040742, 105.8293036);

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--

DROP TABLE IF EXISTS `quiz`;
CREATE TABLE `quiz` (
  `QuizID` int NOT NULL,
  `MuseumID` int DEFAULT NULL,
  `Explaination` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

DROP TABLE IF EXISTS `question`;
CREATE TABLE `question` (
  `QuestionID` int NOT NULL,
  `QuizID` int DEFAULT NULL,
  `QuestionText` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `option`
--

DROP TABLE IF EXISTS `option`;
CREATE TABLE `option` (
  `OptionID` int NOT NULL,
  `QuestionID` int DEFAULT NULL,
  `TEXT` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isCorrect` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `artifact`
--

DROP TABLE IF EXISTS `artifact`;
CREATE TABLE `artifact` (
  `ArtifactID` int NOT NULL,
  `MuseumID` int DEFAULT NULL,
  `ArtifactName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Description` text COLLATE utf8mb4_unicode_ci,
  `Image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `MimeType` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `artifact_detail` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `artifact`
--

INSERT INTO `artifact` (`ArtifactID`, `MuseumID`, `ArtifactName`, `Description`, `Image`, `MimeType`, `artifact_detail`) VALUES
(5, 3, 'Máy bay tiêm kích MiG-21 số hiệu 4324', 'Cơn ác mộng, nỗi khiếp sợ của giặc lái Mỹ\r\n\r\n', '/uploads/artifacts/5.2.png', 'image/png', '/artifact_detail/5.html'),
(6, 3, 'Máy bay MiG-21 số hiệu 5121', 'Tiêm kích duy nhất trên thế giới hạ B-52 bằng tên lửa không đối không\r\n', '/uploads/artifacts/6.png', 'image/png', '/artifact_detail/6.html'),
(7, 3, 'Xe tăng T-54B số hiệu 843 ', 'Xe tăng T-54B số hiệu 843 - Huyền thoại “thần tốc, táo bạo và quyết thắng”\r\n', '/uploads/artifacts/7.png', 'image/png', '/artifact_detail/7.html'),
(8, 3, 'Bản đồ Quyết tâm Chiến dịch Hồ Chí Minh', 'Bản đồ Quyết tâm Chiến dịch Hồ Chí Minh - Kết tinh nghệ thuật quân sự Việt Nam\r\n', '/uploads/artifacts/8.png', 'image/png', '/artifact_detail/8.html'),
(9, 3, 'Hệ thống lưới lửa tầm thấp trưng bày tại Bảo tàng Chiến thắng B-52', 'Khi đến với Bảo tàng Chiến thắng B-52 khách tham quan sẽ được chiêm ngưỡng nhiều hiện vật phong phú và đa dạng:', '/uploads/artifacts/9.1.png', 'image/png', 'artifact_detail/9.html'),
(10, 3, 'HQ-671 – Con tàu duy nhất còn lại của Đoàn tàu không số', '60 năm đã trôi qua, “Đoàn tàu không số” với những chiến công đã đi vào lịch sử đấu tranh dựng nước và giữ nước của dân tộc, nhưng nhân chứng, tài liệu, hiện vật về con đường còn lại không nhiều. Bằng chứng là hiện tại chỉ còn lại duy nhất một con tàu của Đoàn tàu không số ấy. Đó chính là Tàu HQ-671.', '/uploads/artifacts/10.png', 'image/png', 'artifact_detail/10.html'),
(11, 3, 'Mũ cứng tiêu biểu tại Bảo tàng Lịch sử Quân sự Việt Nam', 'Trong bộ sưu tập quân trang của Bảo tàng Lịch sử quân sự Việt Nam có nhiều hiện vật là mũ cứng của bộ đội.', '/uploads/artifacts/11.1.png', 'image/png', '/artifact_detail/11.html'),
(12, 3, 'Huy hiệu Bác Hồ tại triển lãm “Luôn có Bác trong tim”', 'Chúng ta đang sống trong những ngày tháng Năm lịch sử, khi cả dân tộc tưởng nhớ về Ngày sinh của Chủ tịch Hồ Chí Minh – Lãnh tụ kính yêu của dân tộc với niềm tự hào và biết ơn vô hạn.', '/uploads/artifacts/12.1.png', 'image/png', '/artifact_detail/12.html'),
(13, 3, 'Sưu tập vũ khí văn hóa Đông Sơn tại Bảo tàng Lịch sử Quân sự Việt Nam', 'Vũ khí văn hoá Đông Sơn đóng vai trò là các hiện vật quý, có giá trị về lịch sử, văn hoá, khoa học; phản ảnh sự kiện lịch sử, văn hoá, bối cảnh xã hội, trình độ khoa học kỹ thuật từ buổi đầu dựng nước và giữ nước dưới thời đại Hùng Vương - An Dương Vương.', '/uploads/artifacts/13.png', 'image/png', '/artifact_detail/13.html'),
(14, 3, 'Chiếc áo trấn thủ Bác Hồ tặng', 'Trong một chuyến công tác, tôi tình cờ gặp ông Nguyễn Mạnh Hà, con trai cụ Nguyễn Đức Lô, người đã vinh dự được Bác Hồ tặng chiếc áo trấn thủ do có thành tích xuất sắc trong Chiến dịch Biên giới năm 1950.', '/uploads/artifacts/14.1.png', 'image/png', '/artifact_detail/14.html'),
(15, 3, 'Kỷ vật khơi dậy hồi ức Điện Biên', 'Nhiều năm qua, Đại tá Lê Khắc Phấn, nguyên cán bộ Viện Nghiên cứu Quân nhu (Cục Quân nhu, Tổng cục Hậu cần) luôn trân trọng, giữ gìn cẩn thận chiếc ba lô gắn bó với ông trong Chiến dịch Điện Biên Phủ. Để mỗi khi nhìn kỷ vật này, hồi ức về những ngày “khoét núi, ngủ hầm, mưa dầm, cơm vắt” lại được khơi dậy mạnh mẽ trong lòng người chiến sĩ Điện Biên.', '\\uploads\\artifacts\\15.1.png', 'image/png', '/artifact_detail/15.html'),
(16, 3, 'Bệ phóng tên lửa SAM-2 bắn rơi B-52 giữa lòng Hà Nội', 'Trưng bày ngoài trời của Bảo tàng Chiến thắng B-52 (Bộ Tư lệnh Thủ đô) giới thiệu hai bệ phóng tên lửa SAM-2 đã từng bắn rơi tại chỗ hai máy bay B-52 trên bầu trời Hà Nội vào ngày 18 và 27/12/1972. Đây cũng là chiếc B-52 đầu tiên và cuối cùng bị bắn rơi trong chiến thắng oanh liệt “Hà Nội-Điện Biên Phủ trên không” cuối năm 1972 đập tan cuộc tập kích đường không chiến lược bằng B-52 của Mỹ vào Hà Nội, Hải Phòng và một số tỉnh, thành miền Bắc buộc đế quốc Mỹ phải quay trở lại bàn đàm phán với Việt Nam.', '/uploads/artifacts/16.1.png', 'image/png', '/artifact_detail/16.html'),
(17, 3, 'Súng trung liên và chiến công của Anh hùng Cao Thế Chiến', 'Khẩu súng trung liên vẫn còn khá nguyên vẹn hiện đang được trưng bày tại Bảo tàng Binh đoàn Hương Giang (thuộc Bảo tàng, Cục Chính trị Quân đoàn 12). Đây là kỷ vật gắn liền với chiến công của Anh hùng Lực lượng vũ trang nhân dân (LLVTND), liệt sĩ Cao Thế Chiến, sử dụng chiến đấu trong chiến dịch Trung Lào năm 1954.', '/uploads/artifacts/17.3.png', 'image/png', '/artifact_detail/17.html'),
(18, 3, 'Tượng điêu khắc trưng bày tại Bảo tàng Lịch sử Quân sự Việt Nam', 'Tham quan Bảo tàng Lịch sử Quân sự Việt Nam người xem sẽ bắt gặp nhiều loại hình, tài liệu hiện vật phong phú tái hiện lại dòng chảy lịch sử dựng nước và giữ nước của dân tộc; Trong đó, khách tham quan được chiêm ngưỡng rất nhiều tác phẩm hội họa, điêu khắc về đề tài Lực lượng vũ trang,', '/uploads/artifacts/18.3.png', 'image/png', '/artifact_detail/18.html'),
(19, 3, 'Huy hiệu Bác Hồ - Biểu tượng của thời đại', 'Điều đặc biệt trên Huy hiệu Bác Hồ là có hình chân dung của Chủ tịch Hồ Chí Minh và do chính Người trao tặng cho những cá nhân lập thành tích trong chiến đấu, lao động, công tác từ năm 1946 - 1969. Được nhận Huy hiệu Bác Hồ là vinh dự lớn lao của mỗi cá nhân và phần thưởng này trở thành kỷ vật thiêng liêng trong cuộc đời mỗi người.', '\\uploads\\artifacts\\19.4.png', 'image/png', 'artifact_detail/19.html'),
(20, 3, 'Kỷ vật của người anh hùng tại "cánh cửa thép Xuân Lộc"', 'Trong rất nhiều hiện vật, hình ảnh được trưng bày tại Bảo tàng Quân khu 4 có 2 kỉ vật đã thu hút sự chú ý của đông đảo cán bộ, chiến sỹ và khách tham quan. Đó là chiếc bi đông có khắc chữ "LÁI" và chiếc hộp được làm từ mảnh xác máy bay của tiểu đội phó Phạm Văn Lái thuộc Trung đoàn 266, Sư đoàn 341 được phong tặng danh hiệu Anh hùng lực lượng vũ trang nhân dân vì những chiến công xuất sắc tại "cánh cửa thép Xuân Lộc" trong Chiến dịch Hồ Chí Minh.', 'uploads/artifacts/20.3.png', 'image/png', 'uploads/artifact_detail/1759022084_20.html'),
(21, 3, 'Một số cờ thưởng tiêu biểu trong giai đoạn kháng chiến chống thực dân Pháp (1945 – 1954)', 'Trong cuộc kháng chiến chống thực dân Pháp, nhiều loại cờ đã được sử dụng làm giải thưởng để động viên, khích lệ các đơn vị có thành tích trong chiến đấu, phục vụ chiến đấu và xây dựng đất nước. Bảo tàng Lịch sử Quân sự Việt Nam trân trọng giới thiệu một số hiện vật cờ tiêu biểu đang được trưng bày, lưu giữ tại Bảo tàng gắn với nội dung này.', 'uploads/artifacts/1759023229_21.3.png', 'image/png', 'uploads/artifact_detail/1759023229_21.html'),
(23, 3, 'Bảo tàng Lịch sử Quân sự Việt Nam với Mỹ thuật đề tài "Lực lượng vũ trang – Chiến tranh cách mạng"', 'Lịch sử đã trôi qua nhưng sự tích anh hùng của các thế hệ còn lưu vang mãi trong tâm hồn bao thế hệ và là niềm tự hào dân tộc. Qua 60 năm xây dựng và phát triển, Bảo tàng Lịch sử Quân sự Việt Nam mãi xứng đáng với sự khen tặng của Chủ tịch Hồ Chí Minh nhân dịp Người tới thăm và duyệt hệ thống trưng bày của Bảo tàng ngày 12/12/1959: Bảo tàngQuân đội (nay là Bảo tàng Lịch sử Quân sự Việt Nam) là một "cuốn sử sống" lưu truyền mãi mãi, có tác dụng to lớn đến việc giáo dục truyền thống tốt đẹp của dân tộc ta, nhất là thế hệ trẻ.', 'uploads/artifacts/22.png', 'image/png', 'uploads/artifact_detail/1759024401_22.html'),
(24, 3, 'Chủ tịch Hồ Chí Minh với sự nghiệp xây dựng Quân đội nhân dân Việt Nam qua các tài liệu, hiện vật', 'Chủ tịch Hồ Chí Minh người Cha thân yêu của lực lượng vũ trang nhân dân Việt Nam, Người đã sáng lập, giáo dục và rèn luyện Quân đội ta trở thành một quân đội kiểu mới, một quân đội chính quy, tinh nhuệ và từng bước hiện đại. Sự quan tâm đặc biệt của Chủ tịch Hồ Chí Minh đối với Quân đội nhân dân Việt Nam được thể hiện trong rất nhiều các tài liệu, hiện vật lưu giữ tại Bảo tàng Lịch sử Quân sự Việt Nam.', 'uploads/artifacts/1759026090_24.5.png', 'image/png', 'uploads/artifact_detail/1759026090_24.html');

-- --------------------------------------------------------

--
-- Table structure for table `museum_media`
--

DROP TABLE IF EXISTS `museum_media`;
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
(1, 3, 'Bao tang Lich su Quan su Viẹt Nam', 'image/png', '/uploads/museums/Bao_tang_Lich_su_Quan_su_Viet_Nam.png'),
(2, 3, 'Bao tang Lich su Quan su Viet Nam', 'image/png', '/uploads/museums/17327143207601.png'),
(3, 4, 'Bao tang Ho Chi Minh', 'image/png', '/uploads/museums/bao_tang_ho_chi_minh.png'),
(4, 5, 'Bao tang Dan toc hoc', 'image/png', '/uploads/museums/bao_tang_dan_toc_hoc.png'),
(5, 3, 'Bao tang Lich su Quan su Viet Nam', 'video/mp4', '/uploads/museums/Một_vòng_Bảo_tàng_Lịch_sử_Quân_sự_Việt_Nam.mp4'),
(9, 3, 'Bao tang Lich su Quan su Viẹt Nam\r\n', 'image/png', '/uploads/museums/bao-tang-lich-su-quan-su-viet-nam.png'),
(10, 3, 'Bao tang Lich su Quan su Viẹt Nam\r\n', 'image/png', '/uploads/museums/btlsqsvn_2.png'),
(11, 3, 'Bao tang Lich su Quan su Viẹt Nam\r\n', 'video/mp4', '/uploads/museums/Có_gì_tại_Bảo_tàng_Lịch_sử_Quân_sự_Việt_Nam_Hanoi_Review.mp4'),
(12, 3, 'Bao tang Lich su Quan su Viẹt Nam\r\n', 'image/png', '/uploads/museums/bao-tang-lich-su-quan-su-viet-nam-banner.png'),
(13, 3, 'Bao tang Lich su Quan su Viẹt Nam\r\n', 'image/png', '/uploads/museums/bao-tang-lich-su-quan-su-16-3727.png'),
(14, 3, 'Bao tang Lich su Quan su Viẹt Nam\r\n', 'video/mp4', '/uploads/museums/Nét_mới_ở_Bảo_tàng_lịch_sử_ quân_sự_Việt_Nam_Hà_Nội_đẹp_và_chưa_đẹp.mp4'),
(15, 6, 'm_68d9e6920ef5b.png', 'image/jpeg', '/uploads/museums/m_68d9e6920ef5b.png'),
(16, 6, 'Bảo tàng Hà Nội', 'image/png', '/uploads/museums/bao-tang-ha-noi-hoi.png'),
(17, 6, 'Bảo tàng Hà Nội', 'image/png', '/uploads/museums/bao-tang-ha-noi-hoi-1.png'),
(18, 6, 'Bảo tàng Hà Nội', 'image/png', '/uploads/museums/bao-tang-ha-noi-hoi-2.png'),
(19, 6, 'Bảo tàng Hà Nội', 'image/png', '/uploads/museums/bao-tang-ha-noi-hoi-3.png'),
(20, 6, 'Bảo tàng Hà Nội', 'image/png', '/uploads/museums/bao-tang-ha-noi-hoi-4.png'),
(21, 7, 'm_68d9ea6dc1409.png', 'image/jpeg', '/uploads/museums/m_68d9ea6dc1409.png'),
(22, 7, 'Bảo tàng Phòng không - Không quân', 'image/png', '/uploads/museums/bao-tang-phong-khong-khong-quan-1.png'),
(23, 7, 'Bảo tàng Phòng không - Không quân', 'image/png', '/uploads/museums/bao-tang-phong-khong-khong-quan-2.png'),
(24, 7, 'Bảo tàng Phòng không - Không quân', 'image/png', '/uploads/museums/bao-tang-phong-khong-khong-quan-3.png'),
(25, 4, 'Bảo tàng Hồ Chí Minh', 'image/png', '/uploads/museums/bao_tang_ho_chi_minh_1.png'),
(26, 4, 'Bảo tàng Hồ Chí Minh', 'image/png', '/uploads/museums/bao_tang_ho_chi_minh_2.png'),
(27, 4, 'Bảo tàng Hồ Chí Minh', 'image/png', '/uploads/museums/bao_tang_ho_chi_minh_3.png');

-- --------------------------------------------------------

--
-- Table structure for table `useranswer`
--

DROP TABLE IF EXISTS `useranswer`;
CREATE TABLE `useranswer` (
  `UserAnswerID` int NOT NULL,
  `QuizID` int DEFAULT NULL,
  `QuestionID` int DEFAULT NULL,
  `OptionID` int DEFAULT NULL,
  `AnsweredAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UserToken` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserToken`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- Indexes for table `museum`
--
ALTER TABLE `museum`
  ADD PRIMARY KEY (`MuseumID`);

--
-- Indexes for table `quiz`
--
ALTER TABLE `quiz`
  ADD PRIMARY KEY (`QuizID`),
  ADD KEY `MuseumID` (`MuseumID`);

--
-- Indexes for table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`QuestionID`),
  ADD KEY `QuizID` (`QuizID`);

--
-- Indexes for table `option`
--
ALTER TABLE `option`
  ADD PRIMARY KEY (`OptionID`),
  ADD KEY `QuestionID` (`QuestionID`);

--
-- Indexes for table `artifact`
--
ALTER TABLE `artifact`
  ADD PRIMARY KEY (`ArtifactID`),
  ADD KEY `MuseumID` (`MuseumID`);

--
-- Indexes for table `museum_media`
--
ALTER TABLE `museum_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `MuseumId` (`MuseumId`);

--
-- Indexes for table `useranswer`
--
ALTER TABLE `useranswer`
  ADD PRIMARY KEY (`UserAnswerID`),
  ADD KEY `QuizID` (`QuizID`),
  ADD KEY `QuestionID` (`QuestionID`),
  ADD KEY `OptionID` (`OptionID`),
  ADD KEY `fk_useranswer_user` (`UserToken`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `museum`
--
ALTER TABLE `museum`
  MODIFY `MuseumID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `quiz`
--
ALTER TABLE `quiz`
  MODIFY `QuizID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `QuestionID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `option`
--
ALTER TABLE `option`
  MODIFY `OptionID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `artifact`
--
ALTER TABLE `artifact`
  MODIFY `ArtifactID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `museum_media`
--
ALTER TABLE `museum_media`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `useranswer`
--
ALTER TABLE `useranswer`
  MODIFY `UserAnswerID` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `quiz`
--
ALTER TABLE `quiz`
  ADD CONSTRAINT `quiz_ibfk_1` FOREIGN KEY (`MuseumID`) REFERENCES `museum` (`MuseumID`);

--
-- Constraints for table `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `question_ibfk_1` FOREIGN KEY (`QuizID`) REFERENCES `quiz` (`QuizID`);

--
-- Constraints for table `option`
--
ALTER TABLE `option`
  ADD CONSTRAINT `option_ibfk_1` FOREIGN KEY (`QuestionID`) REFERENCES `question` (`QuestionID`);

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
-- Constraints for table `useranswer`
--
ALTER TABLE `useranswer`
  ADD CONSTRAINT `fk_useranswer_user` FOREIGN KEY (`UserToken`) REFERENCES `users` (`UserToken`),
  ADD CONSTRAINT `useranswer_ibfk_2` FOREIGN KEY (`QuizID`) REFERENCES `quiz` (`QuizID`),
  ADD CONSTRAINT `useranswer_ibfk_3` FOREIGN KEY (`QuestionID`) REFERENCES `question` (`QuestionID`),
  ADD CONSTRAINT `useranswer_ibfk_4` FOREIGN KEY (`OptionID`) REFERENCES `option` (`OptionID`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;