<?php
require_once __DIR__ . '/../auth_check.php';
require_once "../db.php";

// --- Lấy token từ query ---
if (!isset($_GET['token'])) {
    die("User token is required.");
}
$token = $_GET['token'];

// --- Lấy thông tin user hiện tại ---
$stmt = $conn->prepare("SELECT * FROM users WHERE UserToken = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}

// --- Cập nhật user ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username       = trim($_POST["username"]);
    $userNumber     = trim($_POST["usernumber"]);
    $role           = $_POST["role"];
    $status         = $_POST["status"];
    $score          = intval($_POST["score"]);
    $failedLogins   = intval($_POST["failedloginattempts"]);
    $isFirstLogin   = isset($_POST["isfirstlogin"]) ? 1 : 0;
    $lockTimestamp  = !empty($_POST["locktimestamp"]) ? $_POST["locktimestamp"] : null;

    // Avatar (nếu upload file mới thì thay, nếu không giữ nguyên)
    $avatar = $user['avatar'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/../uploads/avatars/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $safeName = uniqid("av_") . "." . $ext;
        $dest = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
            $avatar = "/uploads/avatars/" . $safeName;
        }
    }

    // Cập nhật DB (không động vào PASSWORD)
    $sql = "UPDATE users 
            SET Username=?, userNumber=?, Role=?, Score=?, FailedLoginAttempts=?, STATUS=?, isFirstLogin=?, LockTimestamp=?, avatar=?
            WHERE UserToken=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssiiissss",
        $username,
        $userNumber,
        $role,
        $score,
        $failedLogins,
        $status,
        $isFirstLogin,
        $lockTimestamp,
        $avatar,
        $token
    );
    $stmt->execute();
    $stmt->close();

    header("Location: users.php");
    exit;
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
    <h2>Edit User</h2>
    <a href="users.php" class="btn btn-secondary mb-3">← Back to User List</a>

    <form method="post" enctype="multipart/form-data" class="border p-3 bg-light">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['Username']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">User Number</label>
            <input type="text" name="usernumber" class="form-control" value="<?= htmlspecialchars($user['userNumber']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select" required>
                <option value="Admin" <?= $user['Role']=="Admin"?"selected":"" ?>>Admin</option>
                <option value="Customer" <?= $user['Role']=="Customer"?"selected":"" ?>>Customer</option>
                <option value="CustomerPre" <?= $user['Role']=="CustomerPre"?"selected":"" ?>>CustomerPre</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                <option value="Active" <?= $user['STATUS']=="Active"?"selected":"" ?>>Active</option>
                <option value="Locked" <?= $user['STATUS']=="Locked"?"selected":"" ?>>Locked</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Score</label>
            <input type="number" name="score" class="form-control" value="<?= htmlspecialchars($user['Score']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Failed Login Attempts</label>
            <input type="number" name="failedloginattempts" class="form-control" value="<?= htmlspecialchars($user['FailedLoginAttempts']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Is First Login</label>
            <input type="checkbox" name="isfirstlogin" value="1" <?= $user['isFirstLogin'] ? "checked" : "" ?>>
        </div>
        <div class="mb-3">
            <label class="form-label">Lock Timestamp</label>
            <input type="datetime-local" name="locktimestamp" class="form-control" 
                   value="<?= $user['LockTimestamp'] ? date('Y-m-d\TH:i', strtotime($user['LockTimestamp'])) : "" ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Avatar</label><br>
            <?php if ($user['avatar']): ?>
                <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" style="width:80px;height:80px;object-fit:cover;border-radius:50%;"><br>
            <?php endif; ?>
            <input type="file" name="avatar" class="form-control mt-2">
        </div>
        <button type="submit" class="btn btn-success">Save Changes</button>
    </form>
</body>
</html>
