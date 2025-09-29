<?php
require_once __DIR__ . '/../auth_check.php';
require_once "../db.php";

/*
 editArtifact.php
 - Chỉnh sửa artifact: đổi museum, tên, mô tả, mime
 - Thay image (nếu upload) -> xóa image cũ an toàn
 - Thay artifact_detail (.html/.htm) (nếu upload) -> xóa file cũ an toàn
*/

// helper paths
$uploadsBaseDir = realpath(__DIR__ . "/../uploads"); // may be false if not exists
$artifactImgDir = __DIR__ . "/../uploads/artifacts/";
$artifactDetailDir = __DIR__ . "/../artifact_detail/";

// ensure folders exist
if (!is_dir($artifactImgDir)) mkdir($artifactImgDir, 0755, true);
if (!is_dir($artifactDetailDir)) mkdir($artifactDetailDir, 0755, true);

// small helper to safely delete a file only if it's in uploads folder or artifact_detail
function safe_unlink($relativePath, $uploadsBaseDir) {
    if (empty($relativePath)) return;
    $full = realpath(__DIR__ . "/../" . ltrim($relativePath, "/"));
    if (!$full) return;

    $uploadsDir = realpath(__DIR__ . "/../uploads");
    $detailDir = realpath(__DIR__ . "/../artifact_detail");

    if ((strpos($full, $uploadsDir) === 0 || strpos($full, $detailDir) === 0) && file_exists($full)) {
        @unlink($full);
    }
}

// validate uploaded image, return array(file_path, mime) or false
function validate_and_move_image($fileField, $destDir, $maxBytes = 5 * 1024 * 1024) {
    if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) return false;
    $f = $_FILES[$fileField];

    if ($f['size'] > $maxBytes) return false;

    $origName = basename($f['name']);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowedExt = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowedExt)) return false;

    // check real mime
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);

    // basic image validation
    $imgInfo = @getimagesize($f['tmp_name']);
    if ($imgInfo === false) return false;
    if (strpos($imgInfo['mime'], 'image/') !== 0) return false;

    // giữ nguyên tên gốc, thêm uniqid để tránh ghi đè
    $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $origName);
    $dest = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($f['tmp_name'], $dest)) return false;
    @chmod($dest, 0644);
    $webPath = 'uploads/artifacts/' . $safeName;
    return ['file_path' => $webPath, 'mime' => $mime];
}

// validate artifact_detail (html) and move, return file_path or false
function validate_and_move_detail($fileField, $destDir, $maxBytes = 200 * 1024) {
    if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) return false;
    $f = $_FILES[$fileField];

    if ($f['size'] > $maxBytes) return false;

    $origName = basename($f['name']);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowed = ['html','htm'];
    if (!in_array($ext, $allowed)) return false;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);

    // read content and ensure no PHP tag
    $contents = file_get_contents($f['tmp_name']);
    if ($contents === false) return false;
    if (stripos($contents, '<?php') !== false || stripos($contents, '<?=') !== false) return false;

    // giữ nguyên tên file gốc, lọc ký tự lạ
    $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $origName);
    $dest = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($f['tmp_name'], $dest)) return false;
    @chmod($dest, 0644);
    $webPath = 'artifact_detail/' . $safeName;
    return ['file_path' => $webPath, 'mime' => $mime];
}

// get id
if (!isset($_GET['id']) && !isset($_POST['artifact_id'])) {
    die("Artifact ID is required.");
}
$artifactId = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_POST['artifact_id'];

// fetch artifact + current museum name
$stmt = $conn->prepare("SELECT a.*, m.MuseumName FROM artifact a LEFT JOIN museum m ON a.MuseumID = m.MuseumID WHERE a.ArtifactID = ?");
$stmt->bind_param("i", $artifactId);
$stmt->execute();
$artifact = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$artifact) {
    die("Artifact not found.");
}

// handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $museumID = isset($_POST['museum_id']) ? (int)$_POST['museum_id'] : (int)$artifact['MuseumID'];
    $name = trim($_POST['ArtifactName'] ?? '');
    $desc = trim($_POST['Description'] ?? '');
    $mime = trim($_POST['MimeType'] ?? '');

    // Start with existing values
    $newImagePath = $artifact['Image'];
    $newMimeType = $artifact['MimeType'];
    $newDetailPath = $artifact['artifact_detail'];

    // If new image uploaded -> validate & move, then delete old if present
    $imgRes = validate_and_move_image('Image', $artifactImgDir);
    if ($imgRes !== false) {
        if (!empty($artifact['Image'])) safe_unlink($artifact['Image'], $uploadsBaseDir);
        $newImagePath = $imgRes['file_path'];
        $newMimeType = $imgRes['mime'];
    }

    // If new artifact_detail uploaded -> validate & move, then delete old
    $detRes = validate_and_move_detail('ArtifactDetail', $artifactDetailDir);
    if ($detRes !== false) {
        if (!empty($artifact['artifact_detail'])) safe_unlink($artifact['artifact_detail'], $uploadsBaseDir);
        $newDetailPath = $detRes['file_path'];
    }

    // Update DB
    $stmt = $conn->prepare("UPDATE artifact SET MuseumID=?, ArtifactName=?, Description=?, Image=?, MimeType=?, artifact_detail=? WHERE ArtifactID=?");
    $stmt->bind_param("isssssi", $museumID, $name, $desc, $newImagePath, $newMimeType, $newDetailPath, $artifactId);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        header("Location: artifacts.php");
        exit;
    } else {
        $error = "DB update failed: " . $conn->error;
    }
}

// fetch current museum name in case museum_id exists (for prefill)
$currentMuseumName = '';
if (!empty($artifact['MuseumID'])) {
    $q = $conn->prepare("SELECT MuseumName FROM museum WHERE MuseumID = ?");
    $q->bind_param("i", $artifact['MuseumID']);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    $currentMuseumName = $r ? $r['MuseumName'] : '';
    $q->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Edit Artifact</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>.small-preview{max-width:120px;max-height:90px;object-fit:cover} #museum_suggestions_edit{position:absolute;z-index:1000;width:100%;}</style>
</head>
<body class="container my-4">
    <h3>Edit Artifact #<?= (int)$artifact['ArtifactID'] ?></h3>
    <a href="artifacts.php" class="btn btn-secondary mb-3">← Back</a>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="border p-3 mb-4 bg-light position-relative">
        <input type="hidden" name="artifact_id" value="<?= (int)$artifact['ArtifactID'] ?>">

        <div class="mb-2 position-relative">
            <label>Museum</label>
            <input type="text" id="museum_search_edit" class="form-control" placeholder="Type museum name..." value="<?= htmlspecialchars($currentMuseumName) ?>">
            <input type="hidden" name="museum_id" id="museum_id_edit" value="<?= (int)$artifact['MuseumID'] ?>">
            <div id="museum_suggestions_edit" class="list-group"></div>
        </div>

        <div class="mb-2">
            <label>Artifact Name</label>
            <input type="text" name="ArtifactName" class="form-control" required value="<?= htmlspecialchars($artifact['ArtifactName']) ?>">
        </div>

        <div class="mb-2">
            <label>Description</label>
            <textarea name="Description" class="form-control"><?= htmlspecialchars($artifact['Description']) ?></textarea>
        </div>

        <div class="mb-2">
            <label>Current Image</label><br>
            <?php if (!empty($artifact['Image'])): ?>
                <img src="../<?= htmlspecialchars($artifact['Image']) ?>" class="small-preview mb-2" alt="current image"><br>
            <?php else: ?>
                <div class="text-muted mb-2">(no image)</div>
            <?php endif; ?>
            <label class="form-label">Replace Image (optional)</label>
            <input type="file" name="Image" class="form-control" accept="image/*">
        </div>

        <div class="mb-2">
            <label>Current Detail HTML</label><br>
            <?php if (!empty($artifact['artifact_detail'])): ?>
                <a href="../<?= htmlspecialchars($artifact['artifact_detail']) ?>" target="_blank">Open current detail</a><br>
            <?php else: ?>
                <div class="text-muted mb-2">(no detail)</div>
            <?php endif; ?>
            <label class="form-label">Replace Detail HTML (optional, .html/.htm)</label>
            <input type="file" name="ArtifactDetail" class="form-control" accept=".html,.htm">
        </div>

        <div class="mb-2">
            <label>Mime Type (image mime)</label>
            <input type="text" name="MimeType" class="form-control" value="<?= htmlspecialchars($artifact['MimeType']) ?>">
        </div>

        <button class="btn btn-primary">Save Changes</button>
        <a href="artifacts.php" class="btn btn-outline-secondary">Cancel</a>
    </form>

<script>
// autocomplete for museum (reuse your existing museum_search.php)
function bindMuseumSearch(inputSel, hiddenSel, listSel) {
    $(inputSel).on('input', function(){
        let q = $(this).val();
        if (q.length < 1) { $(listSel).empty(); return; }
        $.get('museum_search.php', { q: q }, function(data){
            $(listSel).html(data);
        });
    });
    $(document).on('click', '.museum-item', function(){
        let txt = $(this).text();
        let id = $(this).data('id');
        $(inputSel).val(txt);
        $(hiddenSel).val(id);
        $(listSel).empty();
    });
}
bindMuseumSearch('#museum_search_edit','#museum_id_edit','#museum_suggestions_edit');
</script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
</body>
</html>
