<?php
require_once "db.php";

$sql = "SELECT id, mime_type FROM museum_media ORDER BY id ASC";
$res = $conn->query($sql);

$media = [];
while ($row = $res->fetch_assoc()) {
    $media[] = [
        "id" => $row["id"],
        "mime_type" => $row["mime_type"]
    ];
}

header("Content-Type: application/json");
echo json_encode($media);

$conn->close();
