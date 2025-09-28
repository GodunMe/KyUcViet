<?php
require_once __DIR__ . '/../auth_check.php';
require_once "../db.php";

// Xóa user
if (isset($_GET['delete'])) {
    $token = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE UserToken = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();
    header("Location: users.php");
    exit;
}

// Thêm user mới
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["username"])) {
    $username   = trim($_POST["username"]);
    $role       = $_POST["role"];
    $userNumber = trim($_POST["usernumber"]);

    $defaultPassword = "123456";
    $passHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

    $usertoken = bin2hex(random_bytes(16));

    $sql = "INSERT INTO users (UserToken, Username, userNumber, PASSWORD, Role, Score, FailedLoginAttempts, STATUS)
            VALUES (?, ?, ?, ?, ?, 0, 0, 'Active')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $usertoken, $username, $userNumber, $passHash, $role);
    $stmt->execute();
    $stmt->close();
}

// --- Filter search ---
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

if ($search !== "") {
    $like = "%" . $conn->real_escape_string($search) . "%";
    $sql = "SELECT UserToken, Username, userNumber, Role, STATUS 
            FROM users 
            WHERE Username LIKE ? 
               OR userNumber LIKE ? 
               OR UserToken LIKE ?
               OR Role LIKE ?
               OR STATUS LIKE ?
            ORDER BY Username ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $like, $like, $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $users = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $res = $conn->query("SELECT UserToken, Username, userNumber, Role, STATUS FROM users ORDER BY Username ASC");
    $users = $res->fetch_all(MYSQLI_ASSOC);
}
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
            <div class="col-md-3">
                <input type="text" name="username" placeholder="Username" class="form-control" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="usernumber" placeholder="User Number (e.g. phone)" class="form-control">
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select" required>
                    <option value="Admin">Admin</option>
                    <option value="Customer">Customer</option>
                    <option value="CustomerPre">CustomerPre</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Register User</button>
            </div>
        </div>
    </form>

    <!-- Form tìm kiếm -->
    <form method="get" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search by Username, UserNumber, UserToken, Role, Status">
            <button type="submit" class="btn btn-outline-primary">Search</button>
            <a href="users.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </form>

    <!-- Danh sách user -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>UserToken</th>
                <th>Username</th>
                <th>UserNumber</th>
                <th>Role</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u["UserToken"]) ?></td>
                <td><?= htmlspecialchars($u["Username"]) ?></td>
                <td><?= htmlspecialchars($u["userNumber"]) ?></td>
                <td><?= htmlspecialchars($u["Role"]) ?></td>
                <td><?= htmlspecialchars($u["STATUS"]) ?></td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Action
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="editUser.php?token=<?= urlencode($u['UserToken']) ?>">Edit</a></li>
                            <li><a class="dropdown-item text-danger" href="users.php?delete=<?= urlencode($u['UserToken']) ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
