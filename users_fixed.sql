-- Modified users.sql for localhost import
-- Fixed invalid UserToken and sync Role with production

-- First, backup existing users table
-- CREATE TABLE users_backup AS SELECT * FROM users;

-- Update Role enum to exactly match production
ALTER TABLE users MODIFY COLUMN Role enum('Admin','Standard','Premium','VIP') NOT NULL;

-- Clear existing data (keep backup above)
DELETE FROM users;

-- Insert production users with exact same roles as production
INSERT INTO `users` (`UserToken`, `Username`, `userNumber`, `PASSWORD`, `Role`, `Score`, `FailedLoginAttempts`, `STATUS`, `isFirstLogin`, `LockTimestamp`, `avatar`) VALUES
('0a2362553d3407ee8675fbd4ab545253', 'Nguyễn Thị Thúy', '0357443582', '$2y$10$cuylqBOfYLp9SGFE7iA6kuYOm6AZ5m7wY6g6HNRrVlJ6gUBgBotYS', 'Premium', 0, 0, 'Active', 0, NULL, NULL),
('1', 'Phạm Xuân Dương', '', '$2y$10$B7a6ZAuaWD3FfAlchcU7zOLY.mbefvs1sGn61qzfun1vnCtgMjRee', 'VIP', 988, 0, 'Active', 0, NULL, 'uploads/avatar/1_1761490337.jpg'),
('105a05cae1c61b1092ad754f6aef6efd', 'Lường Trung Hiếu', '0865618217', '$2y$10$amZ6TIXWwfOKgWUOnera6enPypqCubuD9Xw3V6JT1trVxekGP2Eam', 'VIP', 280, 0, 'Active', 1, NULL, NULL),
('16e505c5582cb42a3688684447853d13', 'Nguyễn Minh Nghĩa', '0394998647', '$2y$10$eKIZG9/57QaU3fMF2AQZaeXl6vYaJ0U7n2ybxIsiTiD8dlF/b7rAa', 'VIP', 220, 0, 'Active', 1, NULL, NULL),
('1d7b51911357af2e0967e442f0c8ae43', 'Nguyễn Thị Thanh Huyền', '0965799918', '$2y$10$rnYLgiyEbvM9mxGPsknG4O4ryIsp/31ndA3Jjcl7mLrHMbcPZDE22', 'VIP', 180, 0, 'Active', 1, NULL, NULL),
('3', 'Admin', '', '$2y$10$AWdj/ziETvTQDq20CedScuKxuoi.EpflzM3UAYqtU1o5.o0ykBq.e', 'Admin', 500, 0, 'Active', 0, NULL, 'uploads/avatar/3_1761056210.png'),
('4032a440d84232af87a56a27fd27b33d', 'Nguyễn Nhật Linh 2', '0562993974', '$2y$10$CjeXErw6bMyvOfCHhxm0QeCIsLwiR6DcJgMlPlHyZvRooAiARss.G', 'Premium', 150, 0, 'Active', 1, NULL, NULL),
('436fdb9ea8c6d2851e0bde635043d69f', 'Vũ Văn Quang', '0394827165', '$2y$10$2qhBWbkhVdxth2JQow2Io.G2T7V3APCS9tJUSWWm0qxp3p86OR/iy', 'VIP', 120, 0, 'Active', 1, NULL, NULL),
('52d11e2d5d872ef0cdeb7697954c5a14', 'Phạm Mạnh Đạt', '0816686099', '$2y$10$54FzajiHlQ3uOmjVBO6BKeYcBAPve0.kE/bPq2KaaFXmOfMTAngnO', 'VIP', 100, 0, 'Active', 1, NULL, NULL),
-- Fixed invalid UserToken
('558cd53cab9ca54ec9697648d972928b6', 'tester1', '', '$2y$10$Jt4p/IfVegsJRBukmv1Ch.39wJ4nQoRVe/les7L/Rx.V7sHC8cQBG', 'VIP', 50, 0, 'Active', 0, NULL, NULL),
('6dcd3d87272d4a5806e4fcc072acfcfb', 'Vũ Văn Hậu', '08685052194', '$2y$10$7qMbWcool0zGVZSrcNNmIO29AnpWXlOZbadsBsXjNeWoIMCSpXAVm', 'Premium', 80, 0, 'Active', 1, NULL, NULL),
('6dfe013e726ca6e753dfa485c43136f6', 'Phạm Công Minh', '0904315568', '$2y$10$Lex.KRDnyJPIfoPAe9olY.smiYbEW32zJgtdJdaGgtBZyiDuovaWi', 'VIP', 90, 0, 'Active', 1, NULL, NULL),
('8042307241655e67c0b2007077bb5563', 'Trần Thị Thùy Trang', '0364815337', '$2y$10$Y2nrLGiXmcpFojflPrxW3O4q958TA7saiOaPKkdJ3xSN3ikS2Tx6S', 'Premium', 70, 0, 'Active', 1, NULL, NULL),
('84f9da62f910e8acb6b86954a177a7ce', 'Phạm Đức Mạnh', '0989884797', '$2y$10$qXY3qMoSrvxAS1di308aue65PkiIwCY23dtnBhIzvmTxDzLZ1sna.', 'Premium', 60, 0, 'Active', 1, NULL, NULL),
('8686', 'Ký Ức Việt', '', '$2y$10$EQgH968/lYrlNdtCE8/h8Ou9H79l5vxE.3K/gcpC5ja1gP4wqlE0u', 'VIP', 8686, 0, 'Active', 0, NULL, NULL),
('8753ffa022011f2efef997222be4ac38', 'Hoàng Thị Tâm', '0973992103', '$2y$10$37lzzwQuVcnlCdGicboOyucUzqBZWPA79.HAbr78wkpm5Ek5mmgyK', 'VIP', 85, 0, 'Active', 1, NULL, NULL),
('8a546f0237c41a0a1815eb8adb464daa', 'Mai Xuân', '0978236633', '$2y$10$w4YepLH78bZy4oyJpAnwzOqzltdn63KzooMBxOLDfQ4ikwlnwS7TS', 'VIP', 75, 0, 'Active', 1, NULL, NULL),
('8c90d2997b69847562e5e1dd73f28833', 'Trịnh Duy Đức', '0865709343', '$2y$10$rizM/wzXiUreAcVIMdum0uJGXAeBtCF5Z.0lDhIDiWoSSykZIETNq', 'VIP', 65, 0, 'Active', 1, NULL, NULL),
('95d89335db0ce1ba25c2a792f7f78c2e', 'Nguyễn Nhật Linh', '0562993974', '$2y$10$He4lUcDtut7u.5vPm2GpReqjU1sJjoqcmK1QNDUbMR4g0.uF6f3oO', 'VIP', 55, 0, 'Active', 1, NULL, NULL),
('967ccc1434d8b761675b99bb7ad99e02', 'Lê Thành Vinh', '0328939752', '$2y$10$TGVp/9r0Jw.Nr4aikVBO5uPvUjo5wLUxrJu6AnaCt5XpH0ByWMXzi', 'Premium', 45, 0, 'Active', 1, NULL, NULL),
('a1d59c53ac47c85d452c5b049742544d', 'Hương Giang', '0969643882', '$2y$10$py7FReicktzX51YVS0/DneUlPKjyTkY2RJIrZWV49yX6gtGx9kSkO', 'VIP', 40, 0, 'Active', 1, NULL, NULL),
('cd38a22c22bd0ccab9831102bbcf1f3a', 'Nguyễn Như Bình', '0367464988', '$2y$10$2PrW0JxRotNCBBQVlXHyXudUVIHr.8HiR9/nH.y1lv9B67izQ5nJG', 'VIP', 35, 0, 'Active', 1, NULL, NULL),
('d571bc27d427a68c69efa0becad2e2ba', 'Trần Văn Trung', '0905678221', '$2y$10$ELXLt/s3Ucy1fBm26zGT9uGWxnp/Sq.zQM7XqunvzuJYVwyLxrI6K', 'VIP', 30, 0, 'Active', 1, NULL, NULL),
('db05840a31cf7f6ef3442db7144ef154', 'Đào Đức Hiếu', '0789241792', '$2y$10$x.lXpO3y0//qborSkA.LnuETRz.TsAxYf4/i.BxpXwGdO6bm3QcKu', 'VIP', 25, 0, 'Active', 1, NULL, NULL),
('f943ea0b99f31be7ea1a6795c55fe1ef', 'Ngô Văn Quang', '0819250396', '$2y$10$FNN7DY5k3CoyB.IX0V3mI./.CygODt.zOAktFSmGr01VoXcWoiAJW', 'Premium', 20, 0, 'Active', 1, NULL, NULL);

-- Verify import and show leaderboard distribution
SELECT COUNT(*) as TotalUsers, 
       SUM(CASE WHEN Score > 0 THEN 1 ELSE 0 END) as UsersWithScore,
       MAX(Score) as MaxScore,
       AVG(Score) as AvgScore
FROM users;

-- Show role distribution (should match production exactly)
SELECT Role, COUNT(*) as Count 
FROM users 
GROUP BY Role 
ORDER BY Count DESC;

-- Show top 10 for leaderboard
SELECT UserToken, Username, Role, Score 
FROM users 
ORDER BY Score DESC 
LIMIT 10;