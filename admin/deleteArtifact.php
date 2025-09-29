<?php
require_once __DIR__ . '/../auth_check.php';
require_once "../db.php";

// helper xóa file an toàn (chỉ trong uploads/ hoặc artifact_detail/)
function safe_unlink($relativePath) {
    if (empty($relativePath)) return;
    $full = realpath(__DIR__ . "/../" . ltrim($relativePath, "/"));
    if (!$full) return;

    $uploadsDir = realpath(__DIR__ . "/../uploads");
    $detailDir  = realpath(__DIR__ . "/../artifact_detail");

    if ((strpos($full, $uploadsDir) === 0 || strpos($full, $detailDir) === 0) && file_exists($full)) {
        @unlink($full);
    }
}

if (!isset($_GET['id'])) {
    die("Artifact ID missing");
}
$id = (int)$_GET['id'];

// lấy thông tin để biết đường dẫn file
$stmt = $conn->prepare("SELECT Image, artifact_detail FROM artifact WHERE ArtifactID=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$artifact = $res->fetch_assoc();
$stmt->close();

if ($artifact) {
    // xóa file ảnh nếu có
    if (!empty($artifact['Image'])) {
        safe_unlink($artifact['Image']);
    }
    // xóa file chi tiết nếu có
    if (!empty($artifact['artifact_detail'])) {
        safe_unlink($artifact['artifact_detail']);
    }

    // xóa record DB
    $stmt = $conn->prepare("DELETE FROM artifact WHERE ArtifactID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: artifacts.php");
exit;
