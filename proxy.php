<?php
if (!isset($_GET['lat']) || !isset($_GET['lon'])) {
    http_response_code(400);
    echo "Missing parameters";
    exit;
}

$lat = $_GET['lat'];
$lon = $_GET['lon'];

$url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$lat&lon=$lon";

// Bắt buộc có User-Agent, nếu không Nominatim sẽ từ chối
$opts = [
    "http" => [
        "header" => "User-Agent: KyUcViet/1.0 (your-email@example.com)\r\n"
    ]
];
$context = stream_context_create($opts);
$response = file_get_contents($url, false, $context);

header("Content-Type: application/json; charset=utf-8");
echo $response;
