<?php
// admin/users.php
// Trang quản lý user: hiển thị danh sách + form thêm mới

require_once "../db.php";

// Thêm user mới
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["username"])) {
    $username = trim($_POST["username"]);
    $role = $_POST["role"];

    // default password
    $defaultPassword = "123456";
    $passHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

    // random usertoken 32 hex char
    $usertoken = bin2hex(random_bytes(16));

    $sql = "INSERT INTO users (UserToken, Username, PASSWORD, Role, Score, FailedLoginAttempts, STATUS)
            VALUES (?, ?, ?, ?, 0, 0, 'Active')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $usertoken, $username, $passHash, $role);
    $stmt->execute();
    $stmt->close();
}

// Lấy danh sách user
$res = $conn->query("SELECT Username, Role, STATUS FROM users ORDER BY Username ASC");
$users = $res->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
    <h2>User Management</h2>
    <a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <!-- Form thêm user -->
    <form method="post" class="mb-4">
        <div class="row g-2">
            <div class="col-md-4">
                <input type="text" name="username" placeholder="Username" class="form-control" required>
            </div>
            <div class="col-md-4">
                <select name="role" class="form-select" required>
                    <option value="Admin">Admin</option>
                    <option value="Customer">Customer</option>
                    <option value="CustomerPre">CustomerPre</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Register User</button>
            </div>
        </div>
    </form>

    <!-- Danh sách user -->
    <table class="table table-bordered">
        <thead><tr><th>Username</th><th>Role</th><th>Status</th></tr></thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u["Username"]) ?></td>
                <td><?= htmlspecialchars($u["Role"]) ?></td>
                <td><?= htmlspecialchars($u["STATUS"]) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
