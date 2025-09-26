<?php
include 'db.php';

header('Content-Type: application/json');

// Get museum ID from URL parameter
$museumId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($museumId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid museum ID'
    ]);
    exit;
}

try {
    // Get museum details
    $museumQuery = "SELECT MuseumID, MuseumName, Address, Description, Latitude, Longitude FROM museum WHERE MuseumID = ?";
    $museumStmt = $conn->prepare($museumQuery);
    $museumStmt->bind_param("i", $museumId);
    $museumStmt->execute();
    $museumResult = $museumStmt->get_result();
    
    if ($museumResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Museum not found'
        ]);
        exit;
    }
    
    $museum = $museumResult->fetch_assoc();
    
    // Get museum media
    $mediaQuery = "SELECT id, MuseumId, file_name, mime_type, file_path FROM museum_media WHERE MuseumId = ? ORDER BY id ASC";
    $mediaStmt = $conn->prepare($mediaQuery);
    $mediaStmt->bind_param("i", $museumId);
    $mediaStmt->execute();
    $mediaResult = $mediaStmt->get_result();
    
    $media = [];
    while ($row = $mediaResult->fetch_assoc()) {
        // Ensure file_path is properly formatted for web access
        if (!empty($row['file_path'])) {
            // If it's not a full URL, make it a relative path from web root
            if (!str_starts_with($row['file_path'], 'http')) {
                // Remove leading slash if exists, then add it back for consistency
                $path = ltrim($row['file_path'], '/');
                $row['file_path'] = '/' . $path;
                
                // If path doesn't start with uploads/, assume it's in uploads/museums/
                if (!str_starts_with($row['file_path'], '/uploads/')) {
                    $row['file_path'] = '/uploads/museums/' . basename($row['file_path']);
                }
            }
        } else {
            // Default image if no file_path
            $row['file_path'] = '/uploads/museums/default.png';
        }
        $media[] = $row;
    }
    
    // Return response
    echo json_encode([
        'success' => true,
        'museum' => $museum,
        'media' => $media
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>