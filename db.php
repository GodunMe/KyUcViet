<?php
$host = "localhost";
$user = "root";       // mặc định Laragon user là root
$pass = "";           // Laragon thường để trống mật khẩu
$dbname = "exe201";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Kết nối thất bại: " . $conn->connect_error]));
}
$conn->set_charset("utf8mb4");
?>
