<?php
session_start();
require 'db.php'; // file kết nối tới MySQL

if (!isset($_GET['token'])) {
    die("Thiếu UserToken!");
}
$token = $_GET['token'];

// Reset tài khoản nếu hết thời gian khóa (dùng MySQL so sánh trực tiếp)
$unlock = $conn->prepare("UPDATE users 
                          SET STATUS = 'Active', FailedLoginAttempts = 0, LockTimestamp = NULL
                          WHERE UserToken = ?
                            AND STATUS = 'Locked' 
                            AND LockTimestamp < (NOW() - INTERVAL 5 MINUTE)");
$unlock->bind_param("s", $token);
$unlock->execute();

// Hàm load lại thông tin user từ DB
function loadUser($conn, $token) {
    $stmt = $conn->prepare("SELECT UserToken, Username, PASSWORD, Role, isFirstLogin, 
                                   FailedLoginAttempts, STATUS, LockTimestamp
                            FROM users WHERE UserToken = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

$user = loadUser($conn, $token);

if (!$user) {
    die("Token không hợp lệ!");
}

$error = "";
$_MAX_FAILED_ATTEMPTS = 5;

// Nếu vẫn Locked sau khi chạy query trên thì báo lỗi
if ($user['STATUS'] === 'Locked') {
    $error = "Tài khoản đã bị khóa. Vui lòng thử lại sau 5 phút.";
}


// Nếu form được submit và user đang Active
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['STATUS'] === 'Active') {
    $password = $_POST['password']; // mật khẩu nhập vào
    $hashFromDB = $user['PASSWORD']; // mật khẩu hash lưu trong DB

    if (password_verify($password, $hashFromDB)) {
    // Reset failed attempts khi login đúng
    $reset = $conn->prepare("UPDATE users 
                             SET FailedLoginAttempts = 0, STATUS = 'Active', LockTimestamp = NULL 
                             WHERE UserToken = ?");
    $reset->bind_param("s", $token);
    $reset->execute();

    // Lưu session
    $_SESSION['UserToken']  = $user['UserToken'];
    $_SESSION['Username']   = $user['Username'];
    $_SESSION['Role']       = $user['Role'];
    $_SESSION['isFirstLogin'] = $user['isFirstLogin'];

    // Nếu là admin → đi thẳng vào dashboard, không cần đổi mật khẩu
    if (strcasecmp($user['Role'], 'Admin') === 0) {
        header("Location: admin/dashboard.php");
        exit();
    }

    // Nếu là user thường và là lần đầu đăng nhập → bắt đổi mật khẩu
    if ($user['isFirstLogin'] == 1) {
        header("Location: change_password.php");
        exit();
    }

    // User thường
    header("Location: index.php");
    exit();

} else {
    // Sai mật khẩu → tăng FailedLoginAttempts
    $attempts = $user['FailedLoginAttempts'] + 1;

    if ($attempts >= $_MAX_FAILED_ATTEMPTS) {
        // Khóa tài khoản
        $update = $conn->prepare("UPDATE users 
                                  SET FailedLoginAttempts = ?, STATUS = 'Locked', LockTimestamp = NOW() 
                                  WHERE UserToken = ?");
        $update->bind_param("is", $attempts, $token);
        $update->execute();
        $error = "Bạn đã nhập sai $_MAX_FAILED_ATTEMPTS lần. Tài khoản đã bị khóa trong 5 phút.";
    } else {
        $update = $conn->prepare("UPDATE users 
                                  SET FailedLoginAttempts = ? 
                                  WHERE UserToken = ?");
        $update->bind_param("is", $attempts, $token);
        $update->execute();
        $error = "Mật khẩu không hợp lệ. Số lần còn lại " . ($_MAX_FAILED_ATTEMPTS - $attempts);
    }
  }
    
}

?>

<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ký Ức Việt</title>
  <!-- Font Awesome CDN -->
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

    .login-box {
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.3);
      width: 320px;
      text-align: center;
    }

    .login-box h3 {
      margin-bottom: 20px;
      color: #0a1128;
    }

    .error-msg {
      color: red;
      font-size: 14px;
      margin-bottom: 15px;
    }

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
  <div class="login-box">
    <h3>Đăng nhập</h3>

    <?php if (!empty($error)) echo "<p class='error-msg'>$error</p>"; ?>

    <form method="POST">
      <div class="input-box">
        <input type="text" value="<?= htmlspecialchars($user['Username']) ?>" readonly>
      </div>
      <div class="input-box">
        <input type="password" id="password" name="password" placeholder="Mật khẩu" required>
        <i class="fa-solid fa-eye" id="togglePassword"></i>
      </div>
      <button type="submit" class="btn">Đăng nhập</button>
    </form>
  </div>

  <script>
    const passwordInput = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");

    togglePassword.addEventListener("click", () => {
      const type = passwordInput.type === "password" ? "text" : "password";
      passwordInput.type = type;
      
      if (type === "text") {
        togglePassword.classList.remove("fa-eye");
        togglePassword.classList.add("fa-eye-slash");
      } else {
        togglePassword.classList.remove("fa-eye-slash");
        togglePassword.classList.add("fa-eye");
      }
    });
  </script>
</body>
</html>
