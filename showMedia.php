<?php
require_once "db.php";

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit("Missing id");
}

$id = intval($_GET['id']);
$sql = "SELECT file_data, mime_type FROM museum_media WHERE id = $id LIMIT 1";
$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    header("Content-Type: " . $row["mime_type"]);
    echo $row["file_data"];
} else {
    http_response_code(404);
    echo "Not found";
}
$conn->close();
