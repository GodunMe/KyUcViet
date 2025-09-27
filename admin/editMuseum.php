<?php
require_once __DIR__ . '/../auth_check.php';
require_once "../db.php";

if (!isset($_GET['id'])) {
    die("Museum ID required");
}

$id = intval($_GET['id']);

// Lấy thông tin museum
$stmt = $conn->prepare("SELECT MuseumId, MuseumName, Address, Description, Latitude, Longitude 
                        FROM museum WHERE MuseumId=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$museum = $result->fetch_assoc();
$stmt->close();

if (!$museum) {
    die("Museum not found");
}

// Xử lý cập nhật museum
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["museum_name"])) {
    $name = $_POST["museum_name"];
    $address = $_POST["address"];
    $desc = $_POST["description"];
    $lat = $_POST["latitude"];
    $lng = $_POST["longitude"];

    $sql = "UPDATE museum SET MuseumName=?, Address=?, Description=?, Latitude=?, Longitude=? WHERE MuseumId=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssddi", $name, $address, $desc, $lat, $lng, $id);
    $stmt->execute();
    $stmt->close();

    // Upload file media mới nếu có
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
            $stmt2->bind_param("isss", $id, $safeName, $mime, $webPath);
            $stmt2->execute();
            $stmt2->close();
        }
    }

    header("Location: museums.php");
    exit;
}

// Nếu xóa media
if (isset($_GET['delete_media'])) {
    $mediaId = intval($_GET['delete_media']);

    // Lấy path file để xóa file vật lý
    $stmt = $conn->prepare("SELECT file_path FROM museum_media WHERE id=? AND MuseumId=?");
    $stmt->bind_param("ii", $mediaId, $id);
    $stmt->execute();
    $stmt->bind_result($filePath);
    if ($stmt->fetch()) {
        $fullPath = __DIR__ . "/../" . ltrim($filePath, "/");
        if (file_exists($fullPath)) {
            unlink($fullPath); // xóa file vật lý
        }
    }
    $stmt->close();

    // Xóa DB
    $stmt = $conn->prepare("DELETE FROM museum_media WHERE id=? AND MuseumId=?");
    $stmt->bind_param("ii", $mediaId, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: editMuseum.php?id=" . $id);
    exit;
}

// Lấy media của museum
$stmt = $conn->prepare("SELECT id, file_name, mime_type, file_path FROM museum_media WHERE MuseumId=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$mediaResult = $stmt->get_result();
$mediaList = $mediaResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Museum</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
    <h2>Edit Museum</h2>
    <a href="museums.php" class="btn btn-secondary mb-3">← Back</a>

    <!-- Form update museum -->
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="museum_name" class="form-control mb-2"
               value="<?= htmlspecialchars($museum['MuseumName']) ?>" required>
        <input type="text" name="address" class="form-control mb-2"
               value="<?= htmlspecialchars($museum['Address']) ?>" required>
        <textarea name="description" class="form-control mb-2"><?= htmlspecialchars($museum['Description']) ?></textarea>
        <div class="row mb-2">
            <div class="col"><input type="text" name="latitude" class="form-control"
                value="<?= htmlspecialchars($museum['Latitude']) ?>"></div>
            <div class="col"><input type="text" name="longitude" class="form-control"
                value="<?= htmlspecialchars($museum['Longitude']) ?>"></div>
        </div>
        <div class="mb-3">
            <label>Upload new media:</label>
            <input type="file" name="media" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>

    <hr>
    <h4>Current Media</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Preview</th>
                <th>File Name</th>
                <th>MIME</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($mediaList as $media): ?>
            <tr>
                <td>
                    <?php if (strpos($media["mime_type"], "image") === 0): ?>
                        <img src="<?= htmlspecialchars($media["file_path"]) ?>" width="100">
                    <?php elseif (strpos($media["mime_type"], "video") === 0): ?>
                        <video src="<?= htmlspecialchars($media["file_path"]) ?>" width="150" controls></video>
                    <?php else: ?>
                        <?= htmlspecialchars($media["mime_type"]) ?>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($media["file_name"]) ?></td>
                <td><?= htmlspecialchars($media["mime_type"]) ?></td>
                <td>
                    <a href="editMuseum.php?id=<?= $id ?>&delete_media=<?= $media['id'] ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Delete this media?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
