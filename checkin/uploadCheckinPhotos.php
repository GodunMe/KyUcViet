<?php
/**
 * API Upload Photos for Check-in
 * Handles multiple photo uploads for check-in functionality
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start session to check authentication
session_start();

try {
    // Check if user is logged in
    if (!isset($_SESSION['UserToken'])) {
        throw new Exception('Unauthorized: Please login first');
    }

    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Check if files were uploaded
    if (!isset($_FILES['photos']) || empty($_FILES['photos']['name'][0])) {
        throw new Exception('No photos uploaded');
    }

    $userToken = $_SESSION['UserToken'];
    $uploadDir = '../uploads/checkins/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }

    // Validate and process uploaded files
    $uploadedFiles = [];
    $maxFiles = 5; // Maximum 5 photos per check-in
    $maxFileSize = 5 * 1024 * 1024; // 5MB per file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    $fileCount = count($_FILES['photos']['name']);
    
    // Check maximum file limit
    if ($fileCount > $maxFiles) {
        throw new Exception("Maximum {$maxFiles} photos allowed per check-in");
    }

    // Process each uploaded file
    for ($i = 0; $i < $fileCount; $i++) {
        $fileName = $_FILES['photos']['name'][$i];
        $fileSize = $_FILES['photos']['size'][$i];
        $fileTmpName = $_FILES['photos']['tmp_name'][$i];
        $fileType = $_FILES['photos']['type'][$i];
        $fileError = $_FILES['photos']['error'][$i];

        // Skip empty files
        if (empty($fileName)) {
            continue;
        }

        // Check for upload errors
        if ($fileError !== UPLOAD_ERR_OK) {
            throw new Exception("Upload error for file {$fileName}: " . getUploadErrorMessage($fileError));
        }

        // Validate file size
        if ($fileSize > $maxFileSize) {
            throw new Exception("File {$fileName} is too large. Maximum size is 5MB");
        }

        // Validate file type
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("File {$fileName} has invalid type. Only JPG, PNG, and GIF are allowed");
        }

        // Get file extension
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception("File {$fileName} has invalid extension");
        }

        // Validate if it's actually an image
        $imageInfo = getimagesize($fileTmpName);
        if ($imageInfo === false) {
            throw new Exception("File {$fileName} is not a valid image");
        }

        // Generate unique filename
        $timestamp = time();
        $randomHash = substr(md5(uniqid(rand(), true)), 0, 10);
        $newFileName = "{$userToken}_{$timestamp}_{$randomHash}.{$fileExtension}";
        $targetPath = $uploadDir . $newFileName;

        // Move uploaded file to target directory
        if (!move_uploaded_file($fileTmpName, $targetPath)) {
            throw new Exception("Failed to save file {$fileName}");
        }

        // Add to uploaded files array
        $uploadedFiles[] = [
            'originalName' => $fileName,
            'savedName' => $newFileName,
            'path' => '/uploads/checkins/' . $newFileName,
            'size' => $fileSize,
            'type' => $fileType,
            'order' => $i + 1
        ];
    }

    // Check if any files were successfully uploaded
    if (empty($uploadedFiles)) {
        throw new Exception('No valid photos were uploaded');
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Photos uploaded successfully',
        'data' => [
            'totalFiles' => count($uploadedFiles),
            'files' => $uploadedFiles
        ]
    ]);

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ]);
}

/**
 * Get human-readable upload error message
 */
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'File exceeds upload_max_filesize directive';
        case UPLOAD_ERR_FORM_SIZE:
            return 'File exceeds MAX_FILE_SIZE directive';
        case UPLOAD_ERR_PARTIAL:
            return 'File was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
}
?>