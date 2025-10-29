<?php
/**
 * Lucky Coins Spawner Script
 * 
 * Purpose: Randomly spawn lucky coins at museums
 * Usage:
 *   - Manual: php spawn_lucky_coins.php
 *   - Cron (Linux): Add to crontab: * * * * * php /path/to/spawn_lucky_coins.php
 *   - Task Scheduler (Windows): Create task to run this script
 * 
 * Configuration:
 *   - Set how many coins to spawn per run
 *   - Auto-cleanup expired coins (older than 10 minutes)
 */

require_once __DIR__ . '/../db.php';

// =====================================================
// Configuration
// =====================================================
define('COINS_TO_SPAWN', 1);        // Number of coins to spawn per run
define('COIN_LIFETIME_MINUTES', 1440); // Coin lifetime: 1 day (24 hours = 1440 minutes)
define('LOG_ENABLED', true);         // Enable/disable logging

// =====================================================
// Helper Functions
// =====================================================

/**
 * Log message with timestamp
 */
function logMessage($message) {
    if (!LOG_ENABLED) return;
    
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] {$message}\n";
}

/**
 * Get all active museums
 */
function getActiveMuseums($conn) {
    $sql = "SELECT MuseumID as id, MuseumName as name FROM museum";
    $result = $conn->query($sql);
    
    if (!$result) {
        logMessage("ERROR: Failed to fetch museums - " . $conn->error);
        return [];
    }
    
    $museums = [];
    while ($row = $result->fetch_assoc()) {
        $museums[] = $row;
    }
    
    return $museums;
}

/**
 * Check if museum already has an active coin (spawned < 10 min ago)
 */
function hasActiveCoin($conn, $museumId) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM lucky_coins 
        WHERE museum_id = ? 
        AND spawn_time >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    
    $stmt->bind_param("ii", $museumId, $lifetime = COIN_LIFETIME_MINUTES);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

/**
 * Spawn a lucky coin at specified museum
 */
function spawnCoin($conn, $museumId, $museumName) {
    $stmt = $conn->prepare("INSERT INTO lucky_coins (museum_id, spawn_time) VALUES (?, NOW())");
    $stmt->bind_param("i", $museumId);
    
    if ($stmt->execute()) {
        $coinId = $stmt->insert_id;
        $stmt->close();
        logMessage("✅ Spawned coin ID {$coinId} at museum '{$museumName}' (ID: {$museumId})");
        return true;
    } else {
        logMessage("❌ Failed to spawn coin at museum '{$museumName}' - " . $stmt->error);
        $stmt->close();
        return false;
    }
}

/**
 * Cleanup expired coins (older than 10 minutes)
 */
function cleanupExpiredCoins($conn) {
    $sql = "DELETE FROM lucky_coins WHERE spawn_time < DATE_SUB(NOW(), INTERVAL ? MINUTE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $lifetime = COIN_LIFETIME_MINUTES);
    
    if ($stmt->execute()) {
        $deleted = $stmt->affected_rows;
        $stmt->close();
        
        if ($deleted > 0) {
            logMessage("🧹 Cleaned up {$deleted} expired coin(s)");
        }
        return $deleted;
    } else {
        logMessage("❌ Cleanup failed - " . $stmt->error);
        $stmt->close();
        return 0;
    }
}

/**
 * Get current active coins count
 */
function getActiveCoinsCount($conn) {
    $sql = "SELECT COUNT(*) as count FROM lucky_coins WHERE spawn_time >= DATE_SUB(NOW(), INTERVAL ? MINUTE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $lifetime = COIN_LIFETIME_MINUTES);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'];
}

// =====================================================
// Main Execution
// =====================================================

logMessage("========================================");
logMessage("Lucky Coins Spawner - Starting");
logMessage("========================================");

// Step 1: Cleanup expired coins first
logMessage("Step 1: Cleaning up expired coins...");
$cleanedUp = cleanupExpiredCoins($conn);

// Step 2: Get active museums
logMessage("Step 2: Fetching active museums...");
$museums = getActiveMuseums($conn);

if (empty($museums)) {
    logMessage("⚠️  No active museums found. Exiting.");
    $conn->close();
    exit(0);
}

logMessage("Found " . count($museums) . " active museum(s)");

// Step 3: Spawn coins randomly
logMessage("Step 3: Spawning {$coinsToSpawn} coin(s)...", $coinsToSpawn = COINS_TO_SPAWN);

$spawnedCount = 0;
$attempts = 0;
$maxAttempts = count($museums) * 3; // Prevent infinite loop

while ($spawnedCount < COINS_TO_SPAWN && $attempts < $maxAttempts) {
    $attempts++;
    
    // Pick random museum
    $randomMuseum = $museums[array_rand($museums)];
    $museumId = $randomMuseum['id'];
    $museumName = $randomMuseum['name'];
    
    // Check if museum already has active coin
    if (hasActiveCoin($conn, $museumId)) {
        logMessage("⏭️  Museum '{$museumName}' already has an active coin, skipping...");
        continue;
    }
    
    // Spawn coin
    if (spawnCoin($conn, $museumId, $museumName)) {
        $spawnedCount++;
    }
}

if ($spawnedCount < COINS_TO_SPAWN) {
    logMessage("⚠️  Only spawned {$spawnedCount}/{$coinsToSpawn} coins (all museums may have active coins)");
}

// Step 4: Summary
logMessage("========================================");
logMessage("Summary:");
logMessage("  - Cleaned up: {$cleanedUp} expired coin(s)");
logMessage("  - Spawned: {$spawnedCount} new coin(s)");
logMessage("  - Currently active: " . getActiveCoinsCount($conn) . " coin(s)");
logMessage("========================================");
logMessage("Lucky Coins Spawner - Completed");
logMessage("========================================");

$conn->close();
?>
