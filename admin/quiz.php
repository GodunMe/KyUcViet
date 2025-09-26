<?php
// admin/quiz.php
require_once "../db.php";

$errors = [];
$success = false;

/* ---------------------------
   AJAX: museum search (single-file endpoint)
   Usage: GET quiz.php?action=museum_search&q=...
   --------------------------- */
if (isset($_GET['action']) && $_GET['action'] === 'museum_search') {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    if ($q === '') {
        echo '<div class="list-group-item text-muted">(empty)</div>';
        $conn->close();
        exit;
    }
    $like = '%' . strtolower($q) . '%';
    // Use LOWER(...) LIKE ? to avoid collation mismatch
    $stmt = $conn->prepare("SELECT MuseumID, MuseumName FROM museum WHERE LOWER(MuseumName) LIKE ? ORDER BY MuseumName ASC LIMIT 10");
    if ($stmt) {
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            echo '<div class="list-group-item text-muted">(no matches)</div>';
        } else {
            while ($r = $res->fetch_assoc()) {
                $mid = (int)$r['MuseumID'];
                $mname = htmlspecialchars($r['MuseumName']);
                echo "<a href=\"javascript:void(0)\" class=\"list-group-item list-group-item-action museum-item\" data-id=\"{$mid}\">{$mname}</a>";
            }
        }
        $stmt->close();
    } else {
        echo '<div class="list-group-item text-danger">Query error</div>';
    }
    $conn->close();
    exit;
}

/* ---------------------------
   POST: Add full quiz (quiz + multiple questions + options)
   action = add_quiz_full
   --------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_quiz_full') {
    // Basic validation
    $museumID = isset($_POST['museum_id']) ? (int)$_POST['museum_id'] : 0;
    $explain = isset($_POST['explain']) ? trim($_POST['explain']) : '';
    $questions = isset($_POST['questions']) && is_array($_POST['questions']) ? $_POST['questions'] : [];

    if ($museumID <= 0) $errors[] = "Bạn phải chọn một bảo tàng (museum).";
    else {
        // verify museum exists
        $stmt = $conn->prepare("SELECT MuseumID FROM museum WHERE MuseumID = ? LIMIT 1");
        $stmt->bind_param("i", $museumID);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) $errors[] = "Museum không tồn tại.";
        $stmt->close();
    }

    if ($explain === '') $errors[] = "Explaination không được để trống.";

    if (count($questions) === 0) {
        $errors[] = "Quiz phải có ít nhất 1 câu hỏi.";
    } else {
        // validate each question
        foreach ($questions as $qi => $q) {
            $qtext = isset($q['text']) ? trim($q['text']) : '';
            if ($qtext === '') $errors[] = "Question #" . ((int)$qi+1) . " trống.";
            $opts = isset($q['options']) && is_array($q['options']) ? $q['options'] : [];
            if (count($opts) < 1) $errors[] = "Question #" . ((int)$qi+1) . " cần ít nhất 1 option.";
            else {
                $hasCorrect = false;
                foreach ($opts as $oi => $opt) {
                    if (!isset($opt['text']) || trim($opt['text']) === '') {
                        $errors[] = "Option #" . ((int)$oi+1) . " của question #" . ((int)$qi+1) . " trống.";
                    }
                    if (isset($opt['isCorrect']) && ($opt['isCorrect'] == '1' || $opt['isCorrect'] == 1)) {
                        $hasCorrect = true;
                    }
                }
                if (!$hasCorrect) $errors[] = "Question #" . ((int)$qi+1) . " cần ít nhất 1 đáp án đúng (Correct).";
            }
        }
    }

    if (empty($errors)) {
        // Insert within transaction
        try {
            $conn->begin_transaction();

            $stmtQz = $conn->prepare("INSERT INTO quiz (MuseumID, Explaination) VALUES (?, ?)");
            if (!$stmtQz) throw new Exception($conn->error);
            $stmtQz->bind_param("is", $museumID, $explain);
            $stmtQz->execute();
            $quizID = $stmtQz->insert_id;
            $stmtQz->close();

            $stmtQ = $conn->prepare("INSERT INTO question (QuizID, QuestionText) VALUES (?, ?)");
            if (!$stmtQ) throw new Exception($conn->error);

            $stmtOpt = $conn->prepare("INSERT INTO `option` (`QuestionID`, `TEXT`, `isCorrect`) VALUES (?, ?, ?)");
            if (!$stmtOpt) throw new Exception($conn->error);

            foreach ($questions as $q) {
                $qtext = trim($q['text']);
                $stmtQ->bind_param("is", $quizID, $qtext);
                $stmtQ->execute();
                $questionID = $stmtQ->insert_id;

                $opts = isset($q['options']) && is_array($q['options']) ? $q['options'] : [];
                foreach ($opts as $opt) {
                    $otext = trim($opt['text']);
                    $isc = (isset($opt['isCorrect']) && ($opt['isCorrect'] == '1' || $opt['isCorrect'] == 1)) ? 1 : 0;
                    $stmtOpt->bind_param("isi", $questionID, $otext, $isc);
                    $stmtOpt->execute();
                }
            }

            $stmtQ->close();
            $stmtOpt->close();

            $conn->commit();
            $conn->close();

            // Redirect to avoid resubmission
            header("Location: quiz.php?success=1");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Lỗi khi lưu dữ liệu: " . $e->getMessage();
        }
    }
}

/* ---------------------------
   Fetch nested structure for display
   --------------------------- */
$nested = [];
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
        if (!isset($nested[$mid])) $nested[$mid] = ['MuseumName' => $mname, 'quizzes' => []];

        $qid = $r['QuizID'];
        if ($qid) {
            if (!isset($nested[$mid]['quizzes'][$qid])) {
                $nested[$mid]['quizzes'][$qid] = ['Explaination' => $r['Explaination'], 'questions' => []];
            }
            $qqid = $r['QuestionID'];
            if ($qqid) {
                if (!isset($nested[$mid]['quizzes'][$qid]['questions'][$qqid])) {
                    $nested[$mid]['quizzes'][$qid]['questions'][$qqid] = ['QuestionText' => $r['QuestionText'], 'options' => []];
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
$conn->close(); // closed here since we opened earlier and used it

// If redirected with ?success=1
if (isset($_GET['success']) && $_GET['success'] == '1') $success = true;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Manage Quizzes (single-file)</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
#museum_suggestions{position:absolute;z-index:1200;width:100%;}
.question .options .option input[type="text"]{min-width:0;}
</style>
</head>
<body class="container my-4">
    <h3>Manage Quizzes</h3>
    <a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <?php if ($success): ?>
        <div class="alert alert-success">Đã lưu quiz thành công.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Add Quiz Full Form -->
    <form method="post" class="border p-3 mb-4 bg-light position-relative" id="quiz_form">
        <input type="hidden" name="action" value="add_quiz_full">
        <div class="row">
            <div class="col-md-6 mb-2 position-relative">
                <label>Museum</label>
                <input type="text" id="museum_search" class="form-control" placeholder="Gõ tên bảo tàng...">
                <input type="hidden" name="museum_id" id="museum_id">
                <div id="museum_suggestions" class="list-group"></div>
            </div>
            <div class="col-md-6 mb-2">
                <label>Explaination</label>
                <input type="text" name="explain" id="explain" class="form-control" placeholder="Tiêu đề / mô tả ngắn của quiz" required>
            </div>
        </div>

        <hr>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Questions</h5>
            <div>
                <button type="button" id="add_question_btn" class="btn btn-sm btn-primary">+ Thêm Question</button>
            </div>
        </div>

        <div id="questions">
            <!-- JS sẽ thêm question blocks ở đây -->
        </div>

        <div class="mt-3">
            <button class="btn btn-success">Add Quiz (Lưu quiz + questions + options)</button>
        </div>
    </form>

    <!-- Display nested quizzes -->
    <div>
        <h4>Existing Quizzes</h4>
        <?php if (empty($nested)): ?>
            <div class="text-muted">(No quizzes)</div>
        <?php else: ?>
            <div class="accordion" id="museumAccordion">
                <?php $mIndex=0; foreach ($nested as $mid=>$m): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="mh<?=$mIndex?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mc<?=$mIndex?>">
                                <?= htmlspecialchars($m['MuseumName']) ?>
                            </button>
                        </h2>
                        <div id="mc<?=$mIndex?>" class="accordion-collapse collapse" data-bs-parent="#museumAccordion">
                            <div class="accordion-body">
                                <?php if (empty($m['quizzes'])): ?>
                                    <div class="text-muted">(no quizzes)</div>
                                <?php else: foreach ($m['quizzes'] as $qid=>$q): ?>
                                    <div class="mb-3">
                                        <b>Quiz #<?= (int)$qid ?></b>: <?= htmlspecialchars($q['Explaination']) ?>
                                        <?php if (empty($q['questions'])): ?>
                                            <div class="text-muted ms-3">(no questions)</div>
                                        <?php else: ?>
                                            <ul class="ms-3">
                                                <?php foreach ($q['questions'] as $qqid=>$qq): ?>
                                                    <li>
                                                        Q<?= (int)$qqid ?>: <?= htmlspecialchars($qq['QuestionText']) ?>
                                                        <?php if (!empty($qq['options'])): ?>
                                                            <ul>
                                                                <?php foreach ($qq['options'] as $opt): ?>
                                                                    <li><?= htmlspecialchars($opt['OptionText']) ?> <?php if ($opt['isCorrect']): ?><span class="badge bg-success">Correct</span><?php endif; ?></li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                <?php $mIndex++; endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<script>
$(function(){
    /* -------------------------
       Autocomplete Museum (calls same file with action=museum_search)
       ------------------------- */
    let museumTimer = null;
    $("#museum_search").on("input", function(){
        let q = $(this).val().trim();
        $("#museum_id").val('');
        if (museumTimer) clearTimeout(museumTimer);
        if (q.length < 1) { $("#museum_suggestions").empty(); return; }
        museumTimer = setTimeout(function(){
            $.get("quiz.php", { action: "museum_search", q: q }, function(data){
                $("#museum_suggestions").html(data);
            });
        }, 180);
    });

    $(document).on("click", ".museum-item", function(){
        let id = $(this).data("id");
        let text = $(this).text();
        $("#museum_id").val(id);
        $("#museum_search").val(text);
        $("#museum_suggestions").empty();
    });

    // hide suggestions on click outside
    $(document).on("click", function(e){
        if (!$(e.target).closest("#museum_search, #museum_suggestions").length) {
            $("#museum_suggestions").empty();
        }
    });

    /* -------------------------
       Dynamic questions & options
       ------------------------- */
    function addQuestion(initialText) {
        let qIndex = $("#questions .question").length;
        let html = `
        <div class="question border p-3 mb-2" data-qindex="${qIndex}">
            <div class="d-flex justify-content-between">
                <label class="fw-bold">Question ${qIndex+1}</label>
                <div>
                    <button type="button" class="btn btn-sm btn-danger remove-question">Remove</button>
                </div>
            </div>
            <input type="text" name="questions[${qIndex}][text]" class="form-control mb-2 question-text" placeholder="Question text" value="${initialText ? htmlspecialchars(initialText) : ''}" required>
            <div class="options mb-2"></div>
            <div>
                <button type="button" class="btn btn-sm btn-secondary add-option">+ Add Option</button>
            </div>
        </div>`;
        $("#questions").append(html);
        // add two default options
        addOptionToQuestion(qIndex);
        addOptionToQuestion(qIndex);
        refreshQuestionLabels();
    }

    function addOptionToQuestion(qIndex, optText) {
        let $q = $(`[data-qindex="${qIndex}"]`);
        if ($q.length === 0) return;
        let optIndex = $q.find(".options .option").length;
        let safeText = optText ? htmlspecialchars(optText) : '';
        let html = `
            <div class="option d-flex mb-1 align-items-center" data-oi="${optIndex}">
                <input type="text" name="questions[${qIndex}][options][${optIndex}][text]" class="form-control me-2" placeholder="Option text" value="${safeText}" required>
                <div class="form-check me-2">
                    <input class="form-check-input" type="checkbox" name="questions[${qIndex}][options][${optIndex}][isCorrect]" value="1" id="q${qIndex}_o${optIndex}">
                    <label class="form-check-label" for="q${qIndex}_o${optIndex}">Correct</label>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger remove-option">x</button>
            </div>`;
        $q.find(".options").append(html);
    }

    // helper to escape inserted text
    function htmlspecialchars(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function refreshQuestionLabels() {
        $("#questions .question").each(function(i){
            $(this).attr("data-qindex", i);
            $(this).find(".question-text").attr("name", `questions[${i}][text]`);
            // refresh options names
            $(this).find(".options .option").each(function(j){
                $(this).attr("data-oi", j);
                $(this).find('input[type="text"]').attr("name", `questions[${i}][options][${j}][text]`);
                $(this).find('input[type="checkbox"]').attr("name", `questions[${i}][options][${j}][isCorrect]`)
                    .attr("id", `q${i}_o${j}`);
                $(this).find('label').attr("for", `q${i}_o${j}`);
            });
            $(this).find("label.fw-bold").text(`Question ${i+1}`);
        });
    }

    // add first question by default
    addQuestion();

    // add question click
    $("#add_question_btn").on("click", function(){
        addQuestion('');
    });

    // delegate add-option
    $(document).on("click", ".add-option", function(){
        let qIndex = $(this).closest(".question").data("qindex");
        addOptionToQuestion(qIndex, '');
        refreshQuestionLabels();
    });

    // delegate remove-option
    $(document).on("click", ".remove-option", function(){
        $(this).closest(".option").remove();
        refreshQuestionLabels();
    });

    // delegate remove-question
    $(document).on("click", ".remove-question", function(){
        $(this).closest(".question").remove();
        refreshQuestionLabels();
    });

    // ensure indexing before submit
    $("#quiz_form").on("submit", function(){
        refreshQuestionLabels();
        // minimal client validation: ensure museum selected
        if (!$("#museum_id").val()) {
            alert("Bạn phải chọn Museum từ list gợi ý.");
            return false;
        }
        // let HTML5 required handle other required fields
        return true;
    });
});
</script>

<!-- Bootstrap bundle (collapse etc) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
