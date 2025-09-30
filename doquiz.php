<?php
session_start();

// Kiểm tra nếu đã hoàn thành quiz lần trước, xóa để cho phép làm lại
if (isset($_SESSION['quiz_completed']) && $_SESSION['quiz_completed'] === true) {
    unset($_SESSION['quiz_completed']);
    unset($_SESSION['quiz_index']);
}

if (!isset($_SESSION['UserToken']) || empty($_SESSION['UserToken'])) {
    header("Location: nfc_required.html");
    exit;
}

require_once 'db.php';

$museumId = isset($_GET['museumId']) ? intval($_GET['museumId']) : 0;
$userToken = $_SESSION['UserToken'];

// Kiểm tra lần đầu làm quiz bảo tàng này
$checkDoneStmt = $conn->prepare("SELECT * FROM user_do_quiz WHERE UserToken = ? AND MuseumID = ?");
$checkDoneStmt->bind_param("si", $userToken, $museumId);
$checkDoneStmt->execute();
$checkDone = $checkDoneStmt->get_result()->num_rows > 0;

// Lấy 5 quiz đầu tiên của bảo tàng
$quizSql = "SELECT * FROM quiz WHERE MuseumID = ? ORDER BY RAND() LIMIT 5";
$quizStmt = $conn->prepare($quizSql);
$quizStmt->bind_param("i", $museumId);
$quizStmt->execute();
$quizResult = $quizStmt->get_result();
$quizzes = $quizResult->fetch_all(MYSQLI_ASSOC);

if (!$quizzes) {
    echo "Không có quiz cho bảo tàng này.";
    exit;
}

// Lấy vị trí quiz hiện tại (câu hỏi thứ mấy) từ session hoặc query param
$currentIndex = isset($_SESSION['quiz_index']) ? $_SESSION['quiz_index'] : 0;
if (isset($_GET['index'])) {
    $indexGet = intval($_GET['index']);
    if ($indexGet >= 0 && $indexGet < count($quizzes)) {
        $currentIndex = $indexGet;
        $_SESSION['quiz_index'] = $currentIndex;
    }
}

// Xử lý khi ấn nút trả về để reset quiz_index
if (isset($_GET['reset']) && $_GET['reset'] == 1) {
    unset($_SESSION['quiz_index']);
    header("Location: museum.html?id=$museumId");
    exit;
}

// Kiểm tra nếu hoàn thành quiz, chuyển sang page hoàn thành một lần và set flag completed
if ($currentIndex >= count($quizzes)) {
    unset($_SESSION['quiz_index']);
    unset($_SESSION['quiz_completed']);
    //$_SESSION['quiz_completed'] = true;
    header("Location: quiz_complete.html?museumId=$museumId");
    exit;
}

$currentQuiz = $quizzes[$currentIndex];

// Lấy câu hỏi hiện tại
$questionSql = "SELECT * FROM question WHERE QuizID = ?";
$questionStmt = $conn->prepare($questionSql);
$questionStmt->bind_param("i", $currentQuiz['QuizID']);
$questionStmt->execute();
$questionResult = $questionStmt->get_result();
$question = $questionResult->fetch_assoc();

// Lấy các lựa chọn
$optionsSql = "SELECT * FROM 'option' WHERE QuestionID = ?";
$optionsStmt = $conn->prepare($optionsSql);
$optionsStmt->bind_param("i", $question['QuestionID']);
$optionsStmt->execute();
$optionsResult = $optionsStmt->get_result();
$options = $optionsResult->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selectedOption = intval($_POST['option']);

    // Kiểm tra đáp án đúng
    $optionCheckSql = "SELECT isCorrect FROM 'option' WHERE OptionID = ?";
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

    if ($currentIndex == count($quizzes) - 1 && !$checkDone) {
        $insertUserDoQuiz = $conn->prepare("INSERT INTO user_do_quiz (UserToken, MuseumID) VALUES (?, ?)");
        $insertUserDoQuiz->bind_param("si", $userToken, $museumId);
        $insertUserDoQuiz->execute();
    }

    $_SESSION['quiz_index'] = $currentIndex + 1;

    // Trả kết quả JSON cho ajax
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'isCorrect' => (bool)$isCorrect,
        'next' => $currentIndex < count($quizzes) - 1,
        'isFirstTime' => $isFirstTime
    ]);
    exit;
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
.big-x {
    position: fixed;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%) scale(0.5);
    font-size: 120px; color: red; font-weight: bold;
    opacity: 0; z-index: 9999;
    transition: all 0.6s ease;
}
.big-x.show {
    transform: translate(-50%, -50%) scale(1.2);
    opacity: 1;
}
</style>
</head>
<body>
<div class="quiz-box">
    <div class="timer">⏳ Thời gian còn lại: <span id="countdown">15</span>s</div>
    <form id="quizForm">
        <div class="question"><?= htmlspecialchars($question['QuestionText']) ?></div>
        <?php foreach ($options as $opt): ?>
        <div class="option">
            <label>
                <input type="radio" name="option" value="<?= $opt['OptionID'] ?>">
                <?= htmlspecialchars($opt['TEXT']) ?>
            </label>
        </div>
        <?php endforeach; ?>
        <div class="result"></div>
        <button type="submit" id="submitBtn" class="submit-btn" disabled>Nộp bài</button>
    </form>
    <button class="back-btn" onclick="window.location.href='doquiz.php?museumId=<?= $museumId ?>&reset=1'">⬅️ Trở về</button>
</div>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script>
let timeLeft = 15;
let stopped = false;
const countdown = document.getElementById("countdown");
const resultDiv = document.querySelector(".result");
const submitBtn = document.getElementById("submitBtn");
let currentIndex = <?= $currentIndex ?>;

function disableAll() {
    document.querySelectorAll("input[type=radio]").forEach(r => r.disabled = true);
    submitBtn.disabled = true;
}

function fireConfetti() {
    const duration = 1000;
    const end = Date.now() + duration;
    (function frame() {
        confetti({
            particleCount: 5,
            spread: 70,
            origin: { y: 0 }
        });
        if (Date.now() < end) {
            requestAnimationFrame(frame);
        }
    })();
}

let timer = setInterval(() => {
    if (stopped) return;
    countdown.innerText = timeLeft;
    if (timeLeft <= 0) {
        clearInterval(timer);
        showResult(false, "⏰ Hết thời gian! Bạn trả lời sai.");
        setTimeout(() => {
            stopped = true;
            currentIndex++;
            if (currentIndex >= <?= count($quizzes) ?>) {
                window.location.href = "quiz_complete.html?museumId=<?= $museumId ?>";
            } else {
                window.location.href = `doquiz.php?museumId=<?= $museumId ?>&index=${currentIndex}`;
            }
        }, 2000);
    }
    timeLeft--;
}, 1000);

document.querySelectorAll("input[name='option']").forEach(radio => {
    radio.addEventListener("change", () => {
        submitBtn.disabled = false;
    });
});

document.getElementById("quizForm").addEventListener("submit", function(e) {
    e.preventDefault();
    if (stopped) return;

    const selected = document.querySelector("input[name=option]:checked");
    if (!selected) return;

    const option = selected.value;

    fetch("doquiz.php?museumId=<?= $museumId ?>", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "option=" + encodeURIComponent(option)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            let msg = "";
            if (data.isCorrect) {
                msg = data.isFirstTime ? "🎉 Chính xác! +10 điểm" : "🎉 Chính xác!";
            } else {
                msg = "❌ Sai rồi!";
            }

            clearInterval(timer);
            showResult(data.isCorrect, msg);

            if (data.next) {
                currentIndex++;
                setTimeout(() => {
                    window.location.href = `doquiz.php?museumId=<?= $museumId ?>&index=${currentIndex}`;
                }, 2000);
            } else {
                setTimeout(() => {
                    window.location.href = "quiz_complete.html?museumId=<?= $museumId ?>";
                }, 2000);
            }
        } else {
            alert("Có lỗi xảy ra. Vui lòng thử lại.");
        }
    })
    .catch(err => console.error(err));
});

function showBigX() {
    const xElem = document.createElement("div");
    xElem.className = "big-x";
    xElem.innerText = "X";
    document.body.appendChild(xElem);

    setTimeout(() => xElem.classList.add("show"), 50);

    setTimeout(() => {
        xElem.style.opacity = "0";
        setTimeout(() => xElem.remove(), 500);
    }, 1200);
}

function showResult(isCorrect, msg) {
    stopped = true;
    disableAll();
    resultDiv.innerHTML = `<p style="color:${isCorrect ? 'green' : 'red'};">${msg}</p>`;
    if (isCorrect) {
        fireConfetti();
    } else {
        showBigX();
    }
}
</script>
</body>
</html>
