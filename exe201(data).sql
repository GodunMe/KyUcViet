-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 26, 2025 at 01:17 PM
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

--
-- Dumping data for table `artifact`
--

INSERT INTO `artifact` (`ArtifactID`, `MuseumID`, `ArtifactName`, `Description`, `Image`, `MimeType`) VALUES
(5, 3, 'Máy bay tiêm kích MiG-21 số hiệu 4324', 'Cơn ác mộng, nỗi khiếp sợ của giặc lái Mỹ\r\n\r\nChiếc tiêm kích MiG-21 số hiệu 4324 được treo trang trọng trên cao ngay giữa sảnh chính của Bảo tàng, kết hợp màn hình LED rộng trình chiếu các video clip, hình ảnh danh lam thắng cảnh khắp mọi miền Tổ quốc, tạo cảm giác như máy bay đang xuất kích bảo vệ bầu trời Tổ quốc, gây ấn tượng đặc biệt với mọi người.\r\n\r\nĐây là chiếc máy bay chiến đấu được gắn nhiều ngôi sao nhất của Không quân Nhân dân Việt Nam - 14 ngôi sao tượng trưng cho 14 chiến công bắn rơi máy bay Mỹ trên bầu trời Việt Nam. \r\n\r\nTheo hồ sơ hiện vật của BTLSQSVN, tiêm kích 4324 đã lập công lớn góp phần đánh bại chiến lược “Chiến tranh cục bộ” đánh phá miền Bắc lần thứ nhất giai đoạn 1965 - 1968 của đế quốc Mỹ. Chỉ trong năm 1967, 9 phi công thuộc Trung đoàn Không quân 921, Quân chủng PK-KQ lần lượt lái chiếc 4324 không chiến, bắn rơi 14 máy bay Mỹ. Các phi công 2 lần bắn rơi máy bay Mỹ gồm: Lê Trọng Huyên, Phạm Thanh Ngân, Nguyễn Hồng Nhị, Nguyễn Văn Cốc, Nguyễn Đăng Kính.\r\n\r\nXác suất tiêu diệt mục tiêu của chiếc 4324 được đánh giá tốt nhất trong những phi đội MiG-21 của Việt Nam khi đó: Đối đầu địch 22 lần, xạ kích 16 lần và tiêu diệt tới 14 máy bay Mỹ, nó trở thành cơn ác mộng, nỗi khiếp sợ của giặc lái Mỹ.', '/uploads/artifacts/2_BVQG.png', 'image/png'),
(6, 3, 'Máy bay MiG-21 số hiệu 5121', 'Tiêm kích duy nhất trên thế giới hạ B-52 bằng tên lửa không đối không\r\n\r\nĐược đặt gần máy bay 4324 bên trong khu chuyên đề kháng chiến chống Mỹ, cứu nước là bảo vật quốc gia MiG-21 số hiệu 5121 với những chiến tích vô song trong lịch sử quân sự thế giới.\r\n\r\nĐây là chiếc MiG-21 do Trung tướng Phạm Tuân, Anh hùng lực lượng vũ trang nhân dân điều khiển bắn rơi máy bay B-52 của không quân Mỹ. Đêm ngày 27/12/1972, phi công Phạm Tuân lái chiếc 5121 xuất kích từ sân bay Yên Bái đến vùng trời Sơn La thì phát hiện B-52, anh liền lái máy bay vượt qua đội hình tiêm kích F-14 yểm trợ B-52, điều chỉnh đường ngắm và bắn liền hai quả tên lửa. “Pháo đài bay” được coi là bất khả xâm phạm, biểu tượng của cái gọi là “Không lực Hoa Kỳ” trúng đạn, bốc cháy ngùn ngụt rồi đâm sầm xuống đất.\r\n\r\nĐây là lần đầu tiên Không quân Việt Nam bắn rơi B-52, góp phần đập tan cuộc tập kích đường không chiến lược quy mô lớn bằng máy bay B-52 của Mỹ vào Hà Nội, Hải Phòng trong 12 ngày đêm tháng 12 năm 1972.\r\n\r\nCho đến nay, MiG-21 số hiệu 5121 là chiếc máy bay tiêm kích duy nhất trên thế giới bắn hạ được B-52 bằng tên lửa không đối không. Ngoài kỳ tích bắn rơi “siêu pháo đài bay” B-52, chiếc 5121 còn bắn rơi thêm 4 chiếc máy bay của Mỹ do hai phi công Vũ Đình Rạng và Đinh Tôn điều khiển. Chiếc MiG-21 số hiệu 5121 được Thủ tướng Chính phủ công nhận là bảo vật quốc gia ngày 01/10/2012.', '/uploads/artifacts/3_BVQG.png', 'image/png'),
(7, 3, 'Xe tăng T-54B số hiệu 843 ', 'Xe tăng T-54B số hiệu 843 - Huyền thoại “thần tốc, táo bạo và quyết thắng”\r\n\r\nTrong hồ sơ lưu trữ tại BTLSQSVN, lịch sử, chiến công của xe tăng huyền thoại T-54B số hiệu 843 dài tới gần 10 trang giấy. Theo đó, chiếc xe thuộc Đại đội 4, Tiểu đoàn 1, Lữ đoàn Xe tăng 203, Quân đoàn 2, được lái bởi kíp lái “thép”: Trung úy Bùi Quang Thận, Đại đội trưởng kiêm trưởng xe; Hạ sĩ, lái xe Lữ Văn Hỏa; Trung sĩ, pháo thủ số 1 Thái Bá Minh và Hạ sĩ, pháo thủ số 2 Nguyễn Văn Kỷ.\r\n\r\nTừ tháng 3/1975, kíp tăng T54B số hiệu 843 được lệnh tiên phong trong các chiến dịch giải phóng Huế, Đà Nẵng, sau đó, tiến công Sài Gòn - Gia Định. Xe tăng 843 nằm trong đội hình đánh thọc sâu, phá vỡ thế phòng ngự địch, mở đường vượt cầu Sài Gòn. Tại cầu Thị Nghè, xe tăng 843 bắn cháy 2 xe thiết giáp M41 và M113 của địch, tiến thẳng đến Dinh Độc lập.\r\n\r\n11h30 ngày 30/4/1975, Trung úy Bùi Quang Thận từ xe tăng 843 lao lên cắm lá cờ Quân giải phóng tung bay trên nóc Dinh Độc lập, đặt dấu chấm hết đối với chế độ Ngụy quyền Sài Gòn, chấm dứt chia cắt, non sông thu về một mối, thỏa ước nguyện của Bác Hồ và toàn Đảng, toàn dân tộc.\r\n\r\nHồ sơ khẳng định: “Xe tăng T54B số hiệu 843 là hiện vật ghi dấu chiến công to lớn của quân và dân ta trong Chiến dịch Hồ Chí Minh - Chiến dịch có ý nghĩa quyết định, kết thúc cuộc kháng chiến chống Mỹ kéo dài 21 năm của dân tộc ta. Miền Nam giải phóng, đất nước độc lập, thống nhất, nhân dân ta bước vào thời kỳ phát triển dân giàu, nước mạnh, dân chủ, văn minh”.\r\n\r\nDù trải qua nhiều trận đánh và huấn luyện sẵn sàng chiến đấu, đến nay xe tăng 843 là một trong số ít hiện vật vẫn còn hoạt động. Ngày 01/10/2012, xe tăng 843 được Thủ trướng Chính phủ công nhận là bảo vật quốc gia.', '/uploads/artifacts/ouuroiwuroiweufh9283752387535023.png', 'image/png'),
(8, 3, 'Bản đồ Quyết tâm Chiến dịch Hồ Chí Minh', 'Bản đồ Quyết tâm Chiến dịch Hồ Chí Minh - Kết tinh nghệ thuật quân sự Việt Nam\r\n\r\nBảo vật quốc gia tiếp theo tại BTLSQSVN là Bản đồ Quyết tâm Chiến dịch Hồ Chí Minh được tác nghiệp tại Sở chỉ huy Chiến dịch Hồ Chí Minh, đóng tại Tà Thiết, Lộc Ninh, Tây Ninh (nay thuộc tỉnh Bình Phước). Tấm bản đồ được hoàn thiện sau nhiều lần chỉnh lý, bổ sung, xin ý kiến của Bộ chỉ huy Chiến dịch, Quân ủy Trung ương và Bộ Chính trị về kế hoạch tác chiến của Chiến dịch.\r\n\r\nBản đồ hình chữ nhật, dài 185,5 cm, rộng 170 cm, can 12 mảnh, có chữ “Quyết tâm Chiến dịch Hồ Chí Minh”, được cán bộ tác chiến thể hiện trên bản đồ miền Nam Việt Nam, dưới sự chỉ đạo của các đồng chí: Đại tướng Văn Tiến Dũng, Tổng Tham mưu trưởng Quân đội Nhân dân Việt Nam, Tư lệnh Chiến dịch; Phạm Hùng, Bí thư Trung ương Cục miền Nam, Chính ủy Chiến dịch. Ngày 22/4/1975, Bộ chỉ huy Chiến dịch duyệt Quyết tâm lần cuối cùng. Đại tướng Văn Tiến Dũng và đồng chí Phạm Hùng cùng ký tên lên bản đồ. \r\n\r\nTrên bản đồ, các mũi tên màu đỏ thể hiện hướng tiến công của các cánh quân tiến về giải phóng Sài Gòn - Gia Định. Cụ thể:\r\n\r\nHướng Bắc, Quân đoàn 1 có nhiệm vụ đánh chiếm căn cứ Phú Lợi, tiêu diệt Sư đoàn 5 của địch, tiếp đó đánh chiếm Bộ Tổng tham mưu địch.\r\n\r\nHướng Đông, Quân đoàn 4 có nhiệm vụ tiêu diệt sở chỉ huy Bộ tư lệnh Quân đoàn 3 và Sư đoàn 18 của địch ở Biên Hòa, sau đó thọc sâu vào nội thành đánh chiếm Dinh Độc Lập.\r\n\r\nHướng Đông Nam, Quân đoàn 2 có nhiệm vụ đánh chiếm Bà Rịa, căn cứ Nước Trong, Long Bình, chặn đường rút chạy của địch trên sông Lòng Tàu, sau đó phát triển vào nội thành cùng Quân đoàn 4 đánh chiếm Dinh Độc Lập. \r\n\r\nHướng Tây Bắc, Quân đoàn 3 có nhiệm vụ đánh chiếm Đồng Dù, tiêu diệt Sư đoàn 25 của địch, đánh chiếm sân bay Tân Sơn Nhất và cùng Quân đoàn 1 đánh chiếm Bộ Tổng tham mưu địch.\r\n\r\nHướng Tây Nam, Đoàn 232 có nhiệm vụ tiêu diệt Sư đoàn 22 địch, cắt đường số 4, sau đó đánh thọc sâu chiếm Biệt khu Thủ đô, Tổng nha cảnh sát của địch.\r\n\r\nỞ ngoại thành, các đơn vị đặc công và lực lượng vũ trang tại chỗ có nhiệm vụ đánh và giữ các cầu quan trọng, dẫn đường cho các binh chủng chủ lực đánh chiếm các mục tiêu ở nội thành, phát động quần chúng nổi dậy giành chính quyền cơ sở.\r\n\r\nBản đồ Quyết tâm Chiến dịch Hồ Chí Minh là hiện vật thể hiện thành quả lao động sáng tạo, trí tuệ tập thể của Bộ chỉ huy Chiến dịch, kết tinh nghệ thuật quân sự Việt Nam, là một trong những yếu tố quyết định sự phát triển và thắng lợi của Chiến dịch Hồ Chí Minh lịch sử.\r\n\r\nTấm bản đồ được Đại tướng Văn Tiến Dũng lưu giữ từ năm 1975 đến năm 1990, sau đó trao tặng BTLSQSVN, nhân dịp kỷ niệm 15 năm Ngày giải phóng miền Nam, thống nhất đất nước. Bản đồ Quyết tâm Chiến dịch Hồ Chí Minh được Thủ tướng Chính phủ công nhận Bảo vật quốc gia vào ngày 14/1/2015.', '/uploads/artifacts/6_BVQG.png', 'image/png');

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
(1, 3, 'Bao tang Lich su Quan su Viẹt Nam', 'image/png', '/uploads/museums/Bao_tang_Lich_su_Quan_su_Viet_Nam.png'),
(2, 3, 'Bao tang Lich su Quan su Viet Nam', 'image/png', '/uploads/museums/17327143207601.png'),
(3, 4, 'Bao tang Ho Chi Minh', 'image/png', '/uploads/museums/bao_tang_ho_chi_minh.png'),
(4, 5, 'Bao tang Dan toc hoc', 'image/png', '/uploads/museums/bao_tang_dan_toc_hoc.png'),
(5, 3, 'Bao tang Lich su Quan su Viet Nam', 'video/mp4', '/uploads/museums/Một_vòng_Bảo_tàng_Lịch_sử_Quân_sự_Việt_Nam.mp4');

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
  `userNumber` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PASSWORD` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Role` enum('Admin','Customer','CustomerPre') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Customer',
  `Score` int DEFAULT '0',
  `FailedLoginAttempts` int DEFAULT '0',
  `STATUS` enum('Active','Locked') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `isFirstLogin` tinyint(1) NOT NULL DEFAULT '1',
  `LockTimestamp` datetime DEFAULT NULL,
  `avatar` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserToken`, `Username`, `userNumber`, `PASSWORD`, `Role`, `Score`, `FailedLoginAttempts`, `STATUS`, `isFirstLogin`, `LockTimestamp`, `avatar`) VALUES
('1', 'Phạm Xuân Dương', NULL, '$2y$10$B7a6ZAuaWD3FfAlchcU7zOLY.mbefvs1sGn61qzfun1vnCtgMjRee', 'CustomerPre', 888, 0, 'Active', 0, NULL, 'avatar/avatar.png');

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
  MODIFY `ArtifactID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
