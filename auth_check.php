<?php
// auth_check.php
session_start();

// Nếu chưa có session thì về 404
//if (!isset($_SESSION['UserToken']) || !isset($_SESSION['Username']) || !isset($_SESSION['Role']) || !isset($_SESSION['isFirstLogin'])) {
//    header("Location: 404.php");
//    exit();
//}

// Lấy role và isFirstLogin
$role = isset($_SESSION['Role']) ? $_SESSION['Role'] : null;
$isFirstLogin = isset($_SESSION['isFirstLogin']) ? $_SESSION['isFirstLogin'] : 0;

// Nếu đang truy cập login.php?token=... mà đã có Role → về index.php
if (strpos($_SERVER['PHP_SELF'], 'login.php') !== false && isset($_GET['token'])) {
    if (!empty($role)) {
        header("Location: index.php");
        exit();
    }
}

// Nếu đang truy cập admin mà không phải Admin → về index.php
if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
    if ($role !== 'Admin') {
        header("Location: ../index.php");
        exit();
    }
}

// Nếu isFirstLogin = 1 và không phải trang đổi mật khẩu → redirect sang change_password.php
if ($isFirstLogin == 1 && strpos($_SERVER['PHP_SELF'], 'change_password.php') === false) {
    header("Location: ../profile/change_password.php");
    exit();
}
?>
