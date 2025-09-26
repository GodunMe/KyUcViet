<?php
session_start();
require 'db.php'; // file kết nối tới MySQL

// Kiểm tra session UserToken có tồn tại không
if (!isset($_SESSION['UserToken'])) {
    die("Bạn chưa đăng nhập hoặc phiên đã hết hạn!");
}

$token = $_SESSION['UserToken']; // Lấy token từ session
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Kiểm tra mật khẩu mới và xác nhận mật khẩu
    if ($new_password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp!";
    } elseif (strlen($new_password) < 6) {
        $error = "Mật khẩu phải ít nhất 6 ký tự!";
    } else {
        // Hash mật khẩu mới
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

        // Cập nhật PASSWORD và isFirstLogin = 0 theo UserToken
        $stmt = $conn->prepare("UPDATE users SET PASSWORD = ?, isFirstLogin = 0 WHERE UserToken = ?");
        $stmt->bind_param("ss", $hashedPassword, $token);

        if ($stmt->execute()) {
            $_SESSION['isFirstLogin'] = 0;
            $success = "Đổi mật khẩu thành công! Đang chuyển hướng về trang chủ...";
            // Redirect về index.php sau 2 giây
            header("refresh:2;url=index.php");
        } else {
            $error = "Có lỗi xảy ra, vui lòng thử lại.";
        }

        $stmt->close();
    }
}
?>

<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đổi mật khẩu - Ký Ức Việt</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/png" href="logo.PNG" />
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background-color: #0a1128;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      flex-direction: column;
    }

    h2.title {
      color: white;
      margin-bottom: 30px;
    }

    .change-box {
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.3);
      width: 320px;
      text-align: center;
    }

    .change-box h3 {
      margin-bottom: 20px;
      color: #0a1128;
    }

    .error-msg, .success-msg {
      font-size: 14px;
      margin-bottom: 15px;
    }

    .error-msg { color: red; }
    .success-msg { color: green; }

    .input-box {
      position: relative;
      margin-bottom: 15px;
    }

    .input-box input {
      width: 96%;
      padding: 12px 0px 12px 12px;
      border: 1px solid #ccc;
      border-radius: 6px;
      outline: none;
      font-size: 14px;
    }

    .input-box i {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #888;
    }

    .btn {
      width: 100%;
      background-color: #ff3b30;
      color: white;
      border: none;
      padding: 12px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      transition: 0.3s;
    }

    .btn:hover {
      background-color: #e23228;
    }
  </style>
</head>
<body>
  <h2 class="title">Smart NFC - Ký Ức Việt</h2>
  <div class="change-box">
    <h3>Đổi mật khẩu</h3>

    <?php
      if (!empty($error)) echo "<p class='error-msg'>$error</p>";
      if (!empty($success)) echo "<p class='success-msg'>$success</p>";
    ?>

    <form method="POST">
      <div class="input-box">
        <input type="password" id="newPassword" name="new_password" placeholder="Mật khẩu mới" required>
        <i class="fa-solid fa-eye" id="toggleNewPassword"></i>
      </div>
      <div class="input-box">
        <input type="password" id="confirmPassword" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
        <i class="fa-solid fa-eye" id="toggleConfirmPassword"></i>
      </div>
      <button type="submit" class="btn">Cập nhật mật khẩu</button>
    </form>
  </div>

  <script>
    const togglePassword = (inputId, iconId) => {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(iconId);

      icon.addEventListener("click", () => {
        const type = input.type === "password" ? "text" : "password";
        input.type = type;

        if (type === "text") {
          icon.classList.remove("fa-eye");
          icon.classList.add("fa-eye-slash");
        } else {
          icon.classList.remove("fa-eye-slash");
          icon.classList.add("fa-eye");
        }
      });
    }

    togglePassword("newPassword", "toggleNewPassword");
    togglePassword("confirmPassword", "toggleConfirmPassword");
  </script>
</body>
</html>
