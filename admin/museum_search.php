<?php
require_once __DIR__ . '/../auth_check.php';
// admin/museum_search.php
require_once "../db.php";
$q=isset($_GET['q'])?trim($_GET['q']):'';
if($q!==''){
    $stmt=$conn->prepare("SELECT MuseumID,MuseumName FROM museum WHERE MuseumName LIKE CONCAT('%',?,'%') LIMIT 10");
    $stmt->bind_param("s",$q);
    $stmt->execute();
    $res=$stmt->get_result();
    while($r=$res->fetch_assoc()){
        echo '<a href="javascript:void(0)" class="list-group-item list-group-item-action museum-item" data-id="'.$r['MuseumID'].'">'.htmlspecialchars($r['MuseumName']).'</a>';
    }
    $stmt->close();
}
$conn->close();
?>
