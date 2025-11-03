-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: sql312.infinityfree.com
-- Thời gian đã tạo: Th10 02, 2025 lúc 11:00 PM
-- Phiên bản máy phục vụ: 11.4.7-MariaDB
-- Phiên bản PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `if0_39991375_kyucviet_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `coin_pickups`
--

CREATE TABLE `coin_pickups` (
  `id` int(11) UNSIGNED NOT NULL,
  `coin_id` int(11) UNSIGNED NOT NULL,
  `user_token` varchar(255) NOT NULL,
  `photo_path` varchar(255) NOT NULL COMMENT 'Path to uploaded photo proof',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `points_awarded` int(11) NOT NULL DEFAULT 100 COMMENT 'Points awarded when approved',
  `reject_reason` text DEFAULT NULL COMMENT 'Admin reason for rejection (optional)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores user lucky coin pickups with photo evidence - linked by user token';

--
-- Đang đổ dữ liệu cho bảng `coin_pickups`
--

INSERT INTO `coin_pickups` (`id`, `coin_id`, `user_token`, `photo_path`, `status`, `points_awarded`, `reject_reason`, `created_at`) VALUES
(2, 5, '1', 'uploads/lucky_coins/coin_5_user_c4ca4238_20251101085730.jpg', 'pending', 100, NULL, '2025-11-01 12:57:30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lucky_coins`
--

CREATE TABLE `lucky_coins` (
  `id` int(11) UNSIGNED NOT NULL,
  `museum_id` int(11) NOT NULL,
  `spawn_time` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Thời điểm spawn xu, coin sẽ tồn tại 10 phút'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores lucky coins spawned randomly at museums - each coin lasts 10 minutes, unlimited pickups';

--
-- Đang đổ dữ liệu cho bảng `lucky_coins`
--

INSERT INTO `lucky_coins` (`id`, `museum_id`, `spawn_time`) VALUES
(3, 3, '2025-10-29 21:23:18'),
(5, 3, '2025-11-01 05:57:10'),
(4, 4, '2025-10-29 20:25:27');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `coin_pickups`
--
ALTER TABLE `coin_pickups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coin_id` (`coin_id`),
  ADD KEY `idx_user_token` (`user_token`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Chỉ mục cho bảng `lucky_coins`
--
ALTER TABLE `lucky_coins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_museum_spawn` (`museum_id`,`spawn_time`),
  ADD KEY `idx_spawn_time` (`spawn_time`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `coin_pickups`
--
ALTER TABLE `coin_pickups`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `lucky_coins`
--
ALTER TABLE `lucky_coins`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `coin_pickups`
--
ALTER TABLE `coin_pickups`
  ADD CONSTRAINT `fk_coin_pickups_coin` FOREIGN KEY (`coin_id`) REFERENCES `lucky_coins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_coin_pickups_user` FOREIGN KEY (`user_token`) REFERENCES `users` (`UserToken`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `lucky_coins`
--
ALTER TABLE `lucky_coins`
  ADD CONSTRAINT `fk_lucky_coins_museum` FOREIGN KEY (`museum_id`) REFERENCES `museum` (`MuseumID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
