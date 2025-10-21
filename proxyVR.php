<?php
// proxyVR.php

// Cho phép CORS tạm thời
header('Access-Control-Allow-Origin: *');

// Kiểm tra URL cần proxy
if (!isset($_GET['url']) || empty($_GET['url'])) {
    http_response_code(400);
    echo "Thiếu tham số url.";
    exit;
}

$url = $_GET['url'];

// Chỉ cho phép proxy tới domain hợp lệ
$allowedDomains = ['vr360.yoolife.vn'];
$parsed = parse_url($url);
$host = $parsed['host'] ?? '';

if (!in_array($host, $allowedDomains)) {
    http_response_code(403);
    echo "Domain không được phép.";
    exit;
}

// Gửi request đến trang gốc
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); // giả lập browser
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpcode >= 400) {
    http_response_code($httpcode);
    echo "Không thể tải nội dung ($httpcode)";
    exit;
}

// Trả lại nội dung trang gốc
header("Content-Type: $contentType");
echo $response;
?>
