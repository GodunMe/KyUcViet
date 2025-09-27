-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 26, 2025 at 12:56 AM
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
  MODIFY `ArtifactID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `museum`
--
ALTER TABLE `museum`
  MODIFY `MuseumID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `museum_media`
--
ALTER TABLE `museum_media`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
