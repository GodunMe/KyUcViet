<?php
session_start();
require_once 'db.php';
error_log("quiz_completed: " . (isset($_SESSION['quiz_completed']) ? ($_SESSION['quiz_completed'] ? 'true' : 'false') : 'not set'));

// Hàm reset session quiz
function resetQuizSession() {
    unset($_SESSION['quiz_index']);
    unset($_SESSION['quiz_list']);
    unset($_SESSION['quiz_result']);
    $_SESSION['quiz_completed'] = false;  // Luôn reset trạng thái hoàn thành khi reset quiz
}

// Lấy biến bảo tàng và token người dùng
$museumId = isset($_GET['museumId']) ? intval($_GET['museumId']) : 0;
$userToken = isset($_SESSION['UserToken']) ? $_SESSION['UserToken'] : '';

// Kiểm tra đăng nhập NFC bắt buộc
if (!$userToken) {
    header("Location: nfc_required.html");
    exit;
}

// Reset quiz khi có lệnh reset hoặc restart
if ((isset($_GET['reset']) && $_GET['reset'] == 1) || isset($_GET['restart'])) {
    resetQuizSession();
    header("Location: doquiz.php?museumId=$museumId");
    exit;
}

// Kiểm tra quiz đã hoàn thành trước đó
if (isset($_SESSION['quiz_completed']) && $_SESSION['quiz_completed'] === true) {
    header("Location: quiz_complete.html?museumId=$museumId");
    exit;
}

// Kiểm tra người dùng đã làm quiz bảo tàng này chưa
$checkDoneStmt = $conn->prepare("SELECT * FROM user_do_quiz WHERE UserToken = ? AND MuseumID = ?");
$checkDoneStmt->bind_param("si", $userToken, $museumId);
$checkDoneStmt->execute();
$checkDone = $checkDoneStmt->get_result()->num_rows > 0;

// Khởi tạo quiz list nếu chưa có
if (!isset($_SESSION['quiz_list'])) {
    $quizSql = "SELECT * FROM quiz WHERE MuseumID = ? ORDER BY RAND() LIMIT 5";
    $quizStmt = $conn->prepare($quizSql);
    $quizStmt->bind_param("i", $museumId);
    $quizStmt->execute();
    $quizResult = $quizStmt->get_result();
    $quizzes = $quizResult->fetch_all(MYSQLI_ASSOC);

    if (!$quizzes || count($quizzes) < 5) {
        echo "Không đủ câu hỏi quiz cho bảo tàng này.";
        exit;
    }

    $_SESSION['quiz_list'] = $quizzes;
    $_SESSION['quiz_index'] = 0;
    $_SESSION['quiz_completed'] = false;
} else {
    $quizzes = $_SESSION['quiz_list'];
}

// Kiểm tra quiz tồn tại
if (!$quizzes) {
    echo "Không có quiz cho bảo tàng này.";
    exit;
}

// Xác định câu hỏi hiện tại ưu tiên từ quiz_result
if (isset($_SESSION['quiz_result'])) {
    $currentIndex = $_SESSION['quiz_result']['currentIndex'];
} else if (isset($_GET['index'])) {
    $currentIndex = intval($_GET['index']);
    $_SESSION['quiz_index'] = $currentIndex;
} else {
    $currentIndex = isset($_SESSION['quiz_index']) ? $_SESSION['quiz_index'] : 0;
}

// Kiểm tra hết quiz
if ($currentIndex >= count($quizzes)) {
    $_SESSION['quiz_completed'] = true;
    header("Location: quiz_complete.html?museumId=$museumId");
    exit;
}

$currentQuiz = $quizzes[$currentIndex];

// Lấy câu hỏi
$questionSql = "SELECT * FROM question WHERE QuizID = ?";
$questionStmt = $conn->prepare($questionSql);
$questionStmt->bind_param("i", $currentQuiz['QuizID']);
$questionStmt->execute();
$question = $questionStmt->get_result()->fetch_assoc();

// Lấy các đáp án
$optionsSql = "SELECT * FROM `option` WHERE QuestionID = ? ORDER BY RAND()";
$optionsStmt = $conn->prepare($optionsSql);
$optionsStmt->bind_param("i", $question['QuestionID']);
$optionsStmt->execute();
$options = $optionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Xử lý POST (trả JSON)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selectedOption = intval($_POST['option']);

    $optionCheckSql = "SELECT isCorrect FROM `option` WHERE OptionID = ?";
    $optionCheckStmt = $conn->prepare($optionCheckSql);
    $optionCheckStmt->bind_param("i", $selectedOption);
    $optionCheckStmt->execute();
    $optionRow = $optionCheckStmt->get_result()->fetch_assoc();
    $isCorrect = $optionRow ? $optionRow['isCorrect'] : 0;

    $isFirstTime = !$checkDone;

    if ($isFirstTime && $isCorrect) {
        $updateScoreStmt = $conn->prepare("UPDATE users SET Score = Score + 10 WHERE UserToken = ?");
        $updateScoreStmt->bind_param("s", $userToken);
        $updateScoreStmt->execute();
    }

    if ($currentIndex == count($quizzes) - 1 && $isFirstTime) {
        $insertUserDoQuiz = $conn->prepare("INSERT INTO user_do_quiz (UserToken, MuseumID) VALUES (?, ?)");
        $insertUserDoQuiz->bind_param("si", $userToken, $museumId);
        $insertUserDoQuiz->execute();
    }

    $_SESSION['quiz_result'] = [
        'isCorrect' => (bool)$isCorrect,
        'question' => $question['QuestionText'],
        'explaination' => isset($currentQuiz['Explaination']) ? $currentQuiz['Explaination'] : '',
        'currentIndex' => $currentIndex,
        'total' => count($quizzes),
        'isLast' => ($currentIndex == count($quizzes) - 1)
    ];

    $_SESSION['quiz_index'] = $currentIndex + 1;

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'showResultPage' => true]);
    exit;
}

function renderTextAndImage($str) {
    // Chỉ cho phép <img> và <br>
    return preg_replace('/<(?!img|br).*?>/', '', $str);
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Ký ức Việt</title>
<link rel="icon" type="image/png" href="logo.PNG" />
<style>
body { font-family: Arial, sans-serif; padding: 10px; background: #f9f9f9; }
.quiz-box { background: white; border-radius: 12px; padding: 20px; max-width: 500px; margin: auto; box-shadow: 0 2px 5px rgba(0,0,0,0.2);}
.question { font-size: 18px; margin-bottom: 15px; }
.option { margin: 8px 0; }
.submit-btn, .back-btn { width: 100%; background: red; color: white; padding: 12px; font-size: 16px; border: none; border-radius: 8px; cursor: pointer; margin-top: 10px;}
.submit-btn:disabled { background: #ccc; cursor: not-allowed;}
.back-btn { background: gray;}
.timer { font-size: 18px; font-weight: bold; text-align: center; margin-bottom: 15px; color: #333; }
.result { margin-top: 10px; font-size: 18px; font-weight: bold; text-align: center;}
</style>
</head>
<body>

<?php
if (isset($_SESSION['quiz_result'])) {
    $qr = $_SESSION['quiz_result'];
?>
<div class="quiz-box">
    <div class="result" style="font-size:22px;margin-bottom:18px;">
        <?php if ($qr['isCorrect']): ?>
            <span style="color:green;">🎉 Chính xác!</span>
        <?php else: ?>
            <span style="color:red;">❌ Sai rồi!</span>
        <?php endif; ?>
    </div>
    <div class="question" style="margin-bottom:10px;"><b>Câu hỏi:</b><br><?php echo renderTextAndImage($qr['question']); ?></div>
    <?php if (!empty($qr['explaination'])): ?>
    <div class="explaination" style="margin-bottom:18px;color:#555;font-size:15px;">
        <b>Giải thích:</b><br><?php echo renderTextAndImage($qr['explaination']); ?>
    </div>
    <?php endif; ?>
    <?php if ($qr['isLast']): ?>
        <button class="submit-btn" onclick="window.location.href='quiz_complete.html?museumId=<?= $museumId ?>'">Hoàn thành</button>
    <?php else: ?>
        <button class="submit-btn" onclick="window.location.href='doquiz.php?museumId=<?= $museumId ?>&index=<?= $qr['currentIndex']+1 ?>'">Câu tiếp theo</button>
    <?php endif; ?>
</div>

<?php
    unset($_SESSION['quiz_result']);
} else {
?>
<div class="quiz-box">
    <div class="timer">⏳ Thời gian còn lại: <span id="countdown">15</span>s</div>
    <form id="quizForm">
        <div class="question"><?php echo renderTextAndImage($question['QuestionText']); ?></div>
        <?php foreach ($options as $opt): ?>
        <div class="option">
            <label>
                <input type="radio" name="option" value="<?= $opt['OptionID'] ?>">
                <?php echo renderTextAndImage($opt['TEXT']); ?>
            </label>
        </div>
        <?php endforeach; ?>
        <div class="result"></div>
        <button type="submit" id="submitBtn" class="submit-btn" disabled>Nộp bài</button>
    </form>
    <button class="back-btn" onclick="window.location.href='museum.html?id=<?= $museumId ?>'">⬅️ Trở về</button>
</div>
<?php
}
?>

<script>
if (document.getElementById("quizForm")) {
    document.querySelectorAll("input[name='option']").forEach(radio => {
        radio.addEventListener("change", () => {
            document.getElementById("submitBtn").disabled = false;
        });
    });

    let timeLeft = 15;
    let stopped = false;
    const countdown = document.getElementById("countdown");
    let timer = setInterval(() => {
        if (stopped) return;
        countdown.innerText = timeLeft;
        if (timeLeft <= 0) {
            clearInterval(timer);
            stopped = true;
            fetch("doquiz.php?museumId=<?= $museumId ?>", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "option=0"
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.showResultPage) {
                    window.location.reload();
                } else {
                    alert("Có lỗi xảy ra. Vui lòng thử lại.");
                }
            });
        }
        timeLeft--;
    }, 1000);

    document.getElementById("quizForm").addEventListener("submit", function(e) {
        e.preventDefault();
        if (stopped) return;

        const selected = document.querySelector("input[name='option']:checked");
        if (!selected) return;

        const option = selected.value;

        fetch("doquiz.php?museumId=<?= $museumId ?>", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "option=" + encodeURIComponent(option)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.showResultPage) {
                clearInterval(timer);
                window.location.reload();
            } else {
                alert("Có lỗi xảy ra. Vui lòng thử lại.");
            }
        })
        .catch(err => console.error(err));
    });
}
</script>

</body>
</html>
