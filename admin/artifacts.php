<?php
// admin/artifacts.php
require_once "../db.php";

// Handle add artifact
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='add') {
    $museumID = (int)$_POST['museum_id'];
    $name = trim($_POST['ArtifactName']);
    $desc = trim($_POST['Description']);
    $mime = trim($_POST['MimeType']);

    $imgPath = null;
    if (!empty($_FILES['Image']['name'])) {
        $targetDir = "../uploads/artifacts/";
        if (!is_dir($targetDir)) mkdir($targetDir,0777,true);
        $fname = time() . "_" . basename($_FILES["Image"]["name"]);
        $targetFile = $targetDir . $fname;
        if (move_uploaded_file($_FILES["Image"]["tmp_name"], $targetFile)) {
            $imgPath = "uploads/artifacts/" . $fname;
        }
    }

    $stmt = $conn->prepare("INSERT INTO artifact (MuseumID, ArtifactName, Description, Image, MimeType) VALUES (?,?,?,?,?)");
    $stmt->bind_param("issss", $museumID, $name, $desc, $imgPath, $mime);
    $stmt->execute();
    $stmt->close();
    header("Location: artifacts.php");
    exit;
}

// Fetch artifacts list
$artifacts=[];
$sql="SELECT a.ArtifactID,a.ArtifactName,a.Description,a.Image,a.MimeType,m.MuseumName
      FROM artifact a LEFT JOIN museum m ON a.MuseumID=m.MuseumID
      ORDER BY a.ArtifactID DESC";
if($res=$conn->query($sql)){
    $artifacts=$res->fetch_all(MYSQLI_ASSOC);
    $res->free();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Manage Artifacts</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
.small-preview{max-width:80px;max-height:60px;object-fit:cover}
#museum_suggestions{position:absolute;z-index:1000;width:100%;}
</style>
</head>
<body class="container my-4">
<h3>Manage Artifacts</h3>
<a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

<!-- Add form -->
<form method="post" enctype="multipart/form-data" class="border p-3 mb-4 bg-light position-relative">
    <input type="hidden" name="action" value="add">
    <div class="mb-2 position-relative">
        <label>Museum</label>
        <input type="text" id="museum_search" class="form-control" placeholder="Type museum name...">
        <input type="hidden" name="museum_id" id="museum_id">
        <div id="museum_suggestions" class="list-group"></div>
    </div>
    <div class="mb-2">
        <label>Artifact Name</label>
        <input type="text" name="ArtifactName" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Description</label>
        <textarea name="Description" class="form-control"></textarea>
    </div>
    <div class="mb-2">
        <label>Image</label>
        <input type="file" name="Image" class="form-control">
    </div>
    <div class="mb-2">
        <label>Mime Type</label>
        <input type="text" name="MimeType" class="form-control" placeholder="image/jpeg">
    </div>
    <button class="btn btn-primary">Add Artifact</button>
</form>

<!-- List -->
<table class="table table-bordered">
<thead><tr><th>ID</th><th>Name</th><th>Museum</th><th>Description</th><th>Image</th><th>Mime</th></tr></thead>
<tbody>
<?php foreach($artifacts as $a): ?>
<tr>
  <td><?= $a['ArtifactID'] ?></td>
  <td><?= htmlspecialchars($a['ArtifactName']) ?></td>
  <td><?= htmlspecialchars($a['MuseumName']) ?></td>
  <td><?= htmlspecialchars($a['Description']) ?></td>
  <td><?php if($a['Image']): ?><img src="../<?= $a['Image'] ?>" class="small-preview"><?php endif; ?></td>
  <td><?= htmlspecialchars($a['MimeType']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<script>
// Autocomplete museum
$("#museum_search").on("input", function(){
    let q=$(this).val();
    if(q.length<1){$("#museum_suggestions").empty();return;}
    $.get("museum_search.php",{q:q},function(data){
        $("#museum_suggestions").html(data);
    });
});
$(document).on("click",".museum-item",function(){
    $("#museum_search").val($(this).text());
    $("#museum_id").val($(this).data("id"));
    $("#museum_suggestions").empty();
});
</script>
</body>
</html>
