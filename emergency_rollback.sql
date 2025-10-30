-- EMERGENCY ROLLBACK SCRIPT FOR PRODUCTION
-- Restore users table from backup

-- Step 1: Verify backup exists and has data
SELECT 'Checking backup table...' as Status;
SELECT COUNT(*) as BackupRecords, MAX(Score) as MaxScore FROM users_backup;

-- Step 2: Create safety backup of current (wrong) state
DROP TABLE IF EXISTS users_wrong_state;
CREATE TABLE users_wrong_state AS SELECT * FROM users;
SELECT 'Created safety backup of wrong state' as Status;

-- Step 3: Clear current table and restore from backup
DELETE FROM users;
INSERT INTO users SELECT * FROM users_backup;
SELECT 'Restored from backup' as Status;

-- Step 4: Verify restoration
SELECT 'Verification Results:' as Status;
SELECT COUNT(*) as TotalUsers FROM users;
SELECT 'Top 5 users by score:' as Status;
SELECT UserToken, Username, Role, Score 
FROM users 
ORDER BY Score DESC 
LIMIT 5;

-- Step 5: Check for the key users that should be there
SELECT 'Key users check:' as Status;
SELECT UserToken, Username, Score 
FROM users 
WHERE Username IN ('Ký Ức Việt', 'Phạm Xuân Dương', 'Admin')
ORDER BY Score DESC;