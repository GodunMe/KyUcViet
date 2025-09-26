<?php
// admin/dashboard.php
// Dashboard hiển thị chi tiết: Users, Museums, Artifacts, và Quiz theo dạng
// Museum -> Quiz -> Question -> Options (museum ở phần quiz chỉ hiển thị tên).
// Kết nối DB (file db.php của bạn)
require_once "../db.php";

/* ------------------ Users ------------------ */
$users = [];
if ($res = $conn->query("SELECT UserToken, Username, Role, Score, STATUS FROM users ORDER BY Username ASC")) {
    $users = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
}

/* ------------------ Museums (dùng trong nhiều phần) ------------------ */
/* Lấy danh sách museum (chỉ cần tên ở phần quiz) và media paths để hiển thị ở phần Museum list */
$museums = [];
$sql = "SELECT m.MuseumID, m.MuseumName,
               GROUP_CONCAT(mm.file_path SEPARATOR '||') AS media_files
        FROM museum m
        LEFT JOIN museum_media mm ON m.MuseumID = mm.MuseumID
        GROUP BY m.MuseumID
        ORDER BY m.MuseumID ASC";
if ($res = $conn->query($sql)) {
    $museums = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
}

/* ------------------ Artifacts ------------------ */
/* Theo ERD, artifact lưu trực tiếp Image + MimeType */
$artifacts = [];
$sql = "SELECT a.ArtifactID, a.ArtifactName, a.Description, a.Image, a.MimeType, m.MuseumName
        FROM artifact a
        LEFT JOIN museum m ON a.MuseumID = m.MuseumID
        ORDER BY a.ArtifactID ASC";
if ($res = $conn->query($sql)) {
    $artifacts = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
}

/* ------------------ Quiz -> Question -> Option (nested) ------------------ */
/* Lấy tất cả dữ liệu cần cho phần hiển thị nested bằng 1 query duy nhất */
$nested = []; // cấu trúc: [MuseumID => ['MuseumName'=>..., 'quizzes'=> [QuizID=>['explaination'=>..., 'questions'=>[QuestionID=>['text'=>..., 'options'=>[]]]]]]]
$sql = "SELECT m.MuseumID AS MuseumID, m.MuseumName AS MuseumName,
               q.QuizID AS QuizID, q.Explaination AS Explaination,
               qu.QuestionID AS QuestionID, qu.QuestionText AS QuestionText,
               o.OptionID AS OptionID, o.`TEXT` AS OptionText, o.isCorrect AS isCorrect
        FROM museum m
        LEFT JOIN quiz q ON q.MuseumID = m.MuseumID
        LEFT JOIN question qu ON qu.QuizID = q.QuizID
        LEFT JOIN `option` o ON o.QuestionID = qu.QuestionID
        ORDER BY m.MuseumID ASC, q.QuizID ASC, qu.QuestionID ASC, o.OptionID ASC";
if ($res = $conn->query($sql)) {
    while ($r = $res->fetch_assoc()) {
        $mid = $r['MuseumID'];
        $mname = $r['MuseumName'];

        if (!isset($nested[$mid])) {
            $nested[$mid] = [
                'MuseumName' => $mname,
                'quizzes' => []
            ];
        }

        $qid = $r['QuizID'];
        if ($qid) {
            if (!isset($nested[$mid]['quizzes'][$qid])) {
                $nested[$mid]['quizzes'][$qid] = [
                    'Explaination' => $r['Explaination'],
                    'questions' => []
                ];
            }

            $qqid = $r['QuestionID'];
            if ($qqid) {
                if (!isset($nested[$mid]['quizzes'][$qid]['questions'][$qqid])) {
                    $nested[$mid]['quizzes'][$qid]['questions'][$qqid] = [
                        'QuestionText' => $r['QuestionText'],
                        'options' => []
                    ];
                }

                $optid = $r['OptionID'];
                if ($optid) {
                    $nested[$mid]['quizzes'][$qid]['questions'][$qqid]['options'][] = [
                        'OptionID' => $optid,
                        'OptionText' => $r['OptionText'],
                        'isCorrect' => (int)$r['isCorrect']
                    ];
                }
            }
        }
    }
    $res->free();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f7f7f9; }
        .section-header { display:flex; align-items:center; justify-content:space-between; }
        .museum-link { font-weight:600; }
        .small-preview { max-width:80px; max-height:60px; object-fit:cover; }
    </style>
</head>
<body class="container my-4">
    <h2>Admin Dashboard</h2>

    <!-- TOP manage buttons -->
    <div class="mb-3">
        <a href="users.php" class="btn btn-primary me-2">Manage Users</a>
        <a href="museums.php" class="btn btn-success me-2">Manage Museums</a>
        <a href="artifacts.php" class="btn btn-warning me-2">Manage Artifacts</a>
        <a href="quiz.php" class="btn btn-info">Manage Quizzes</a>
    </div>

    <!-- USERS -->
    <div class="mb-4">
        <div class="section-header mb-2">
            <h4>Users</h4>
            <a href="users.php" class="btn btn-sm btn-outline-primary">Open Users Manager</a>
        </div>
        <table class="table table-sm table-bordered">
            <thead><tr><th>Username</th><th>Role</th><th>Score</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (count($users)===0): ?>
                <tr><td colspan="4" class="text-center">(no users)</td></tr>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['Username']) ?></td>
                    <td><?= htmlspecialchars($u['Role']) ?></td>
                    <td><?= (int)$u['Score'] ?></td>
                    <td><?= htmlspecialchars($u['STATUS']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- MUSEUMS (list + media links) -->
    <div class="mb-4">
        <div class="section-header mb-2">
            <h4>Museums</h4>
            <a href="museums.php" class="btn btn-sm btn-outline-success">Open Museums Manager</a>
        </div>
        <table class="table table-sm table-bordered">
            <thead><tr><th>Museum Name</th><th>Media</th></tr></thead>
            <tbody>
            <?php if (count($museums)===0): ?>
                <tr><td colspan="2" class="text-center">(no museums)</td></tr>
            <?php else: ?>
                <?php foreach ($museums as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['MuseumName']) ?></td>
                    <td>
                        <?php if (!empty($m['media_files'])): 
                            $files = explode('||', $m['media_files']);
                            foreach ($files as $f): ?>
                                <a href="<?= htmlspecialchars($f) ?>" target="_blank"><?= htmlspecialchars(basename($f)) ?></a><br>
                            <?php endforeach;
                        else: ?>
                            <span class="text-muted">(no media)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ARTIFACTS -->
    <div class="mb-4">
        <div class="section-header mb-2">
            <h4>Artifacts</h4>
            <a href="artifacts.php" class="btn btn-sm btn-outline-warning">Open Artifacts Manager</a>
        </div>
        <table class="table table-sm table-bordered">
            <thead><tr><th>Name</th><th>Description</th><th>Museum</th><th>Image</th><th>Mime</th></tr></thead>
            <tbody>
            <?php if (count($artifacts)===0): ?>
                <tr><td colspan="5" class="text-center">(no artifacts)</td></tr>
            <?php else: ?>
                <?php foreach ($artifacts as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['ArtifactName']) ?></td>
                    <td><?= htmlspecialchars($a['Description']) ?></td>
                    <td><?= htmlspecialchars($a['MuseumName']) ?></td>
                    <td>
                        <?php if (!empty($a['Image'])): ?>
                            <img src="<?= htmlspecialchars($a['Image']) ?>" class="small-preview" alt="img">
                        <?php else: ?>
                            <span class="text-muted">(no image)</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($a['MimeType']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- QUIZ: Museum -> Quiz -> Question -> Options -->
    <div class="mb-4">
        <div class="section-header mb-2">
            <h4>Quizzes (Museum → Quiz → Question → Options)</h4>
            <a href="quiz.php" class="btn btn-sm btn-outline-info">Open Quiz Manager</a>
        </div>

        <?php if (empty($nested)): ?>
            <div class="text-muted">(no quiz data)</div>
        <?php else: ?>
            <div class="accordion" id="museumAccordion">
                <?php $mIndex = 0; foreach ($nested as $mid => $mdata): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="museumHeading<?= $mIndex ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#museumCollapse<?= $mIndex ?>">
                                <?= htmlspecialchars($mdata['MuseumName']) /* museum chỉ hiển thị tên */ ?>
                            </button>
                        </h2>
                        <div id="museumCollapse<?= $mIndex ?>" class="accordion-collapse collapse" data-bs-parent="#museumAccordion">
                            <div class="accordion-body">
                                <?php if (empty($mdata['quizzes'])): ?>
                                    <div class="text-muted">(no quizzes for this museum)</div>
                                <?php else: ?>
                                    <div class="accordion" id="quizAccordion<?= $mIndex ?>">
                                        <?php $qIndex = 0; foreach ($mdata['quizzes'] as $qid => $qdata): ?>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="quizHeading<?= $mIndex ?>_<?= $qIndex ?>">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#quizCollapse<?= $mIndex ?>_<?= $qIndex ?>">
                                                        Quiz #<?= htmlspecialchars($qid) ?> - <?= htmlspecialchars($qdata['Explaination']?: '(no title)') ?>
                                                    </button>
                                                </h2>
                                                <div id="quizCollapse<?= $mIndex ?>_<?= $qIndex ?>" class="accordion-collapse collapse" data-bs-parent="#quizAccordion<?= $mIndex ?>">
                                                    <div class="accordion-body">
                                                        <?php if (empty($qdata['questions'])): ?>
                                                            <div class="text-muted">(no questions)</div>
                                                        <?php else: ?>
                                                            <div class="accordion" id="questionAccordion<?= $mIndex ?>_<?= $qIndex ?>">
                                                                <?php $quesIndex = 0; foreach ($qdata['questions'] as $qqid => $qq): ?>
                                                                    <div class="accordion-item">
                                                                        <h2 class="accordion-header" id="quesHeading<?= $mIndex ?>_<?= $qIndex ?>_<?= $quesIndex ?>">
                                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#quesCollapse<?= $mIndex ?>_<?= $qIndex ?>_<?= $quesIndex ?>">
                                                                                Q<?= $qqid ?>: <?= htmlspecialchars($qq['QuestionText'] ?: '(no text)') ?>
                                                                            </button>
                                                                        </h2>
                                                                        <div id="quesCollapse<?= $mIndex ?>_<?= $qIndex ?>_<?= $quesIndex ?>" class="accordion-collapse collapse" data-bs-parent="#questionAccordion<?= $mIndex ?>_<?= $qIndex ?>">
                                                                            <div class="accordion-body">
                                                                                <?php if (empty($qq['options'])): ?>
                                                                                    <div class="text-muted">(no options)</div>
                                                                                <?php else: ?>
                                                                                    <ul class="list-group">
                                                                                        <?php foreach ($qq['options'] as $opt): ?>
                                                                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                                <?= htmlspecialchars($opt['OptionText']) ?>
                                                                                                <?php if ($opt['isCorrect']): ?>
                                                                                                    <span class="badge bg-success">Correct</span>
                                                                                                <?php endif; ?>
                                                                                            </li>
                                                                                        <?php endforeach; ?>
                                                                                    </ul>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php $quesIndex++; endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php $qIndex++; endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php $mIndex++; endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
