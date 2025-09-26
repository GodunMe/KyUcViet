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
        if (!empty($row['Image']) && !str_starts_with($row['Image'], 'http')) {
            // Remove leading slash if exists, then add it back
            $path = ltrim($row['Image'], '/');
            
            // If path doesn't start with uploads/, assume it's in uploads/artifacts/
            if (!str_starts_with($path, 'uploads/')) {
                $path = 'uploads/artifacts/' . basename($path);
            }
            
            // Add leading slash for web access
            $row['Image'] = '/' . $path;
        } else if (empty($row['Image'])) {
            // Default artifact image
            $row['Image'] = '/uploads/artifacts/default.png';
        }
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