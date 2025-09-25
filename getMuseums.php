<?php
header('Content-Type: application/json; charset=utf-8');
require_once "db.php";

// Lấy danh sách bảo tàng
$sql = "SELECT MuseumID, MuseumName, Address, Description, Latitude, Longitude FROM museum";
$result = $conn->query($sql);

$museums = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $museumId = $row['MuseumID'];

        // Lấy danh sách media cho từng bảo tàng
        $sqlMedia = "SELECT id, file_name, mime_type 
                     FROM museum_media 
                     WHERE MuseumId = $museumId";
        $resMedia = $conn->query($sqlMedia);

        $media = [];
        if ($resMedia && $resMedia->num_rows > 0) {
            while ($m = $resMedia->fetch_assoc()) {
                // Đường dẫn để tải file (vd: showMedia.php?id=...)
                $media[] = [
                    "id" => $m["id"],
                    "file_name" => $m["file_name"],
                    "mime_type" => $m["mime_type"],
                    "url" => "showMedia.php?id=" . $m["id"]
                ];
            }
        }

        $row["media"] = $media;
        $museums[] = $row;
    }
}

echo json_encode($museums, JSON_UNESCAPED_UNICODE);
$conn->close();
