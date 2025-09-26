<?php
include 'db.php';

header('Content-Type: application/json');

// Get museum ID from URL parameter
$museumId = isset($_GET['museumId']) ? intval($_GET['museumId']) : 0;

if ($museumId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid museum ID'
    ]);
    exit;
}

try {
    // Get artifacts for the museum
    $artifactQuery = "SELECT ArtifactID, MuseumID, ArtifactName, Description, Image, MimeType FROM artifact WHERE MuseumID = ? ORDER BY ArtifactID ASC";
    $artifactStmt = $conn->prepare($artifactQuery);
    $artifactStmt->bind_param("i", $museumId);
    $artifactStmt->execute();
    $artifactResult = $artifactStmt->get_result();
    
    $artifacts = [];
    while ($row = $artifactResult->fetch_assoc()) {
        // Convert relative path to web-accessible path
        if (!empty($row['Image'])) {
            // If it's not a full URL
            if (!str_starts_with($row['Image'], 'http')) {
                // Clean the path
                $path = ltrim($row['Image'], '/');
                
                // If path doesn't start with uploads/, assume it's a filename in uploads/artifacts/
                if (!str_starts_with($path, 'uploads/')) {
                    $path = 'uploads/artifacts/' . basename($path);
                }
                
                // Add leading slash for web access
                $row['Image'] = '/' . $path;
            }
        } else {
            // Default artifact image if no image specified
            $row['Image'] = '/uploads/artifacts/default.png';
        }
        
        // Debug: Add original path for troubleshooting
        $row['OriginalImage'] = $row['Image'];
        $artifacts[] = $row;
    }
    
    // Return response
    echo json_encode([
        'success' => true,
        'artifacts' => $artifacts,
        'count' => count($artifacts)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>