<?php
require_once __DIR__ . '/../auth_check.php';
require_once "../db.php";

// --- Thêm museum ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["museum_name"])) {
    $name = $_POST["museum_name"];
    $address = $_POST["address"];
    $description = $_POST["description"];
    $lat = $_POST["latitude"];
    $lng = $_POST["longitude"];

    $sql = "INSERT INTO museum (MuseumName, Address, Description, Latitude, Longitude)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdd", $name, $address, $description, $lat, $lng);
    $stmt->execute();
    $museumId = $stmt->insert_id;
    $stmt->close();

    // --- Xử lý file upload ---
    if (isset($_FILES["media"]) && $_FILES["media"]["error"] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/../uploads/museums/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $origName = basename($_FILES["media"]["name"]);
        $ext = pathinfo($origName, PATHINFO_EXTENSION);
        $safeName = uniqid("m_") . "." . $ext;
        $dest = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES["media"]["tmp_name"], $dest)) {
            $mime = mime_content_type($dest);
            $webPath = "/uploads/museums/" . $safeName;

            $sql2 = "INSERT INTO museum_media (MuseumId, file_name, mime_type, file_path)
                     VALUES (?, ?, ?, ?)";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("isss", $museumId, $safeName, $mime, $webPath);
            $stmt2->execute();
            $stmt2->close();
        }
    }
}

// --- Lấy danh sách museum ---
$res = $conn->query("SELECT MuseumId, MuseumName, Address FROM museum ORDER BY MuseumId DESC");
$museums = $res->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Museum Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
    <h2>Museum Management</h2>
    <a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <!-- Form thêm museum -->
    <form method="post" enctype="multipart/form-data" class="mb-4">
        <input type="text" name="museum_name" class="form-control mb-2" placeholder="Museum Name" required>
        <input type="text" name="address" class="form-control mb-2" placeholder="Address" required>
        <textarea name="description" class="form-control mb-2" placeholder="Description"></textarea>
        <div class="row mb-2">
            <div class="col"><input type="text" name="latitude" class="form-control" placeholder="Latitude"></div>
            <div class="col"><input type="text" name="longitude" class="form-control" placeholder="Longitude"></div>
        </div>
        <input type="file" name="media" class="form-control mb-2">
        <button type="submit" class="btn btn-success">Add Museum</button>
    </form>

    <!-- Danh sách museum -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Museum Name</th>
                <th>Address</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($museums as $m): ?>
            <tr>
                <td><?= $m["MuseumId"] ?></td>
                <td><?= htmlspecialchars($m["MuseumName"]) ?></td>
                <td><?= htmlspecialchars($m["Address"]) ?></td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Action
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="editMuseum.php?id=<?= $m["MuseumId"] ?>">Edit</a></li>
                            <li>
                                <a class="dropdown-item text-danger" href="deleteMuseum.php?id=<?= $m["MuseumId"] ?>" 
                                   onclick="return confirm('Are you sure you want to delete this museum?');">
                                   Delete
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Bootstrap JS + Popper -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
</body>
</html>
