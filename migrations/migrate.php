<?php
/**
 * Database Migration Manager
 * 
 * Công cụ này quản lý và áp dụng các migration SQL để cập nhật cấu trúc cơ sở dữ liệu
 * Sử dụng: php migrations/migrate.php
 */

// Kết nối database
require_once __DIR__ . '/../db.php';

echo "=== Công cụ quản lý Migration Database ===\n\n";

// Tạo bảng migrations nếu chưa tồn tại (để theo dõi các migrations đã chạy)
$conn->query("
CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (migration_name)
);");

// Lấy danh sách tất cả các file migration trong thư mục
$migrationFiles = glob(__DIR__ . '/*.sql');
sort($migrationFiles); // Sắp xếp theo thứ tự tên file

if (empty($migrationFiles)) {
    echo "Không tìm thấy file migration nào.\n";
    exit;
}

echo "Tìm thấy " . count($migrationFiles) . " migration files.\n\n";

// Lấy danh sách các migrations đã chạy
$executedMigrations = [];
$result = $conn->query("SELECT migration_name FROM migrations");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $executedMigrations[] = $row['migration_name'];
    }
}

// Chạy từng migration nếu chưa được thực thi
foreach ($migrationFiles as $migrationFile) {
    $migrationName = basename($migrationFile);
    
    if (in_array($migrationName, $executedMigrations)) {
        echo "✓ Migration đã thực thi: {$migrationName}\n";
        continue;
    }
    
    echo "Đang thực thi migration: {$migrationName}... ";
    
    try {
        // Đọc nội dung file SQL
        $sql = file_get_contents($migrationFile);
        
        // Thực thi từng câu lệnh SQL
        if ($conn->multi_query($sql)) {
            // Xử lý từng kết quả
            do {
                // Lấy kết quả của câu lệnh hiện tại
                $conn->store_result();
                
                // Kiểm tra có lỗi không
                if ($conn->error) {
                    throw new Exception($conn->error);
                }
            } while ($conn->more_results() && $conn->next_result());
            
            // Đánh dấu migration đã được thực thi
            $stmt = $conn->prepare("INSERT INTO migrations (migration_name) VALUES (?)");
            $stmt->bind_param("s", $migrationName);
            $stmt->execute();
            
            echo "THÀNH CÔNG\n";
        }
    } catch (Exception $e) {
        echo "THẤT BẠI\n";
        echo "Lỗi: " . $e->getMessage() . "\n";
        exit;
    }
}

echo "\nHoàn tất quá trình migration.\n";

// Tạo thư mục uploads/checkins nếu chưa tồn tại
$uploadDir = __DIR__ . '/../uploads/checkins';
if (!file_exists($uploadDir)) {
    if (mkdir($uploadDir, 0777, true)) {
        echo "Đã tạo thư mục uploads/checkins\n";
    } else {
        echo "Không thể tạo thư mục uploads/checkins. Vui lòng tạo thủ công.\n";
    }
}

$conn->close();
?>