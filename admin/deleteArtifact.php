<?php
require_once __DIR__ . '/../auth_check.php';
require_once "../db.php";

if (!isset($_GET['id'])) {
    die("Artifact ID missing");
}
$id = (int)$_GET['id'];

$stmt = $conn->prepare("DELETE FROM artifact WHERE ArtifactID=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: artifacts.php");
exit;
