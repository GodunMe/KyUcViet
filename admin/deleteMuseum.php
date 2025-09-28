<?php
require_once __DIR__ . '/../auth_check.php';
require_once "../db.php";

if (!isset($_GET['id'])) {
    die("Museum ID required");
}
$id = intval($_GET['id']);

// Xóa media trước
$stmt = $conn->prepare("SELECT file_path FROM museum_media WHERE MuseumId=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $file = __DIR__ . "/.." . $row['file_path'];
    if (file_exists($file)) {
        unlink($file);
    }
}
$stmt->close();

$conn->query("DELETE FROM museum_media WHERE MuseumId=$id");

// Xóa museum
$stmt = $conn->prepare("DELETE FROM museum WHERE MuseumId=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: museums.php");
exit;
