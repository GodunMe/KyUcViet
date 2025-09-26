<?php
session_start();

// Xóa tất cả session variables
$_SESSION = array();

// Nếu sử dụng session cookie, xóa nó
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Destroy session
session_destroy();

// Redirect về trang index
header("Location: /index.php");
exit();
?>