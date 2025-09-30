<?php
session_start();
require_once "db.php"; // kết nối DB (tạo $conn)

// Giả sử người dùng đã login và lưu Username trong session
if (!isset($_SESSION['Username'])) {
    die("❌ Vui lòng đăng nhập để làm quiz.");
}
$currentUser = $_SESSION['Username'];

// Lấy museumId từ URL
$museumId = isset($_GET['museumId']) ? intval($_GET['museumId']) : 0;
if ($museumId <= 0) {
    die("❌ Museum ID không hợp lệ.");
}

// Lấy quiz theo museumId từ bảng quiz
$sqlQuiz = "SELECT QuizID FROM quiz WHERE MuseumID = $museumId";
$resultQuiz = $conn->query($sqlQuiz);

if ($resultQuiz && $resultQuiz->num_rows > 0) {
    $quizRow = $resultQuiz->fetch_assoc();
    $quizId = $quizRow['QuizID'];
} else {
    die("❌ Bảo tàng này chưa có quiz nào.");
}

// Lấy 1 câu hỏi ngẫu nhiên trong quiz đã chọn
$sql = "SELECT * FROM question WHERE QuizID = $quizId ORDER BY RAND() LIMIT 1";
$result = $conn->query($sql);

$question = null;
$options = [];

if ($result && $result->num_rows > 0) {
    $question = $result->fetch_assoc();
    $questionId = $question['QuestionID'];

    // Lấy các option (wrap 'option' trong backticks vì là reserved keyword)
    $sqlOpt = "SELECT * FROM `option` WHERE QuestionID = $questionId";
    $resOpt = $conn->query($sqlOpt);
    if ($resOpt && $resOpt->num_rows > 0) {
        while ($row = $resOpt->fetch_assoc()) {
            $options[] = $row;
        }
    }
} else {
    die("❌ Quiz này chưa có câu hỏi nào.");
}

// Xử lý submit bằng AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['optionID'])) {
    $optionID = intval($_POST['optionID']);

    // Kiểm tra xem option có đúng không (wrap 'option' trong backticks)
    $resCheck = $conn->query("SELECT isCorrect FROM `option` WHERE OptionID = $optionID");
    if ($resCheck && $resCheck->num_rows > 0) {
        $row = $resCheck->fetch_assoc();
        $isCorrect = $row['isCorrect'] == 1;

        // Lưu vào bảng useranswer
        $stmt = $conn->prepare("INSERT INTO useranswer (QuizID, QuestionID, OptionID, userName) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $quizId, $questionId, $optionID, $currentUser);
        $stmt->execute();
        $stmt->close();

        // Nếu đúng, cộng 10 điểm vào users.Score
        if ($isCorrect) {
            $conn->query("UPDATE users SET Score = Score + 10 WHERE Username = '$currentUser'");
        }

        echo json_encode(['success' => true, 'isCorrect' => $isCorrect]);
        exit;
    } else {
        echo json_encode(['success' => false]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Làm Quiz</title>
<style>
body { font-family: Arial, sans-serif; padding: 10px; background: #f9f9f9; }
.quiz-box { background: white; border-radius: 12px; padding: 20px; max-width: 500px; margin: auto; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
.question { font-size: 18px; margin-bottom: 15px; }
.option { margin: 8px 0; }
.submit-btn, .back-btn { width: 100%; background: red; color: white; padding: 12px; font-size: 16px; border: none; border-radius: 8px; cursor: pointer; margin-top: 10px; }
.submit-btn:disabled { background: #ccc; cursor: not-allowed; }
.back-btn { background: gray; }
.timer { font-size: 18px; font-weight: bold; text-align: center; margin-bottom: 15px; color: #333; }
.result { margin-top: 15px; font-size: 18px; font-weight: bold; }
/* Hiệu ứng X khi sai */
.big-x {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.5);
    font-size: 120px;
    color: red;
    font-weight: bold;
    opacity: 0;
    z-index: 9999;
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
                <input type="radio" name="option" value="<?= $opt['OptionID'] ?>" required>
                <?= htmlspecialchars($opt['TEXT']) ?>
            </label>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="submit-btn" id="submitBtn">Nộp bài</button>
    </form>

    <div class="result"></div>
    <button class="back-btn" onclick="window.location.href='museum.html?id=<?= $museumId ?>'">⬅️ Trở về</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script>
let timeLeft = 15;
let stopped = false;
const countdown = document.getElementById("countdown");
const resultDiv = document.querySelector(".result");

function disableAll() {
    document.querySelectorAll("input[type=radio]").forEach(r => r.disabled = true);
    document.getElementById("submitBtn").disabled = true;
}

function fireConfetti() {
    // bắn confetti trong 1 giây
    const duration = 1 * 1000;
    const end = Date.now() + duration;

    (function frame() {
        confetti({
            particleCount: 5,
            spread: 70,
            origin: { y: 0 } // bắn từ trên xuống
        });
        if (Date.now() < end) {
            requestAnimationFrame(frame);
        }
    }());
}

function showResult(isCorrect, msg) {
    stopped = true;
    disableAll();
    resultDiv.innerHTML = `<p style="color:${isCorrect ? 'green' : 'red'};">${msg}</p>`;

    // Nếu đúng thì chạy hiệu ứng confetti
    if (isCorrect) {
        fireConfetti();
    }
}

let timer = setInterval(() => {
    if (stopped) return;
    countdown.innerText = timeLeft;
    if (timeLeft <= 0) {
        clearInterval(timer);
        showResult(false, "⏰ Hết thời gian! Bạn trả lời sai.");
    }
    timeLeft--;
}, 1000);

document.getElementById("quizForm").addEventListener("submit", function(e) {
    e.preventDefault();
    if (stopped) return;

    const selected = document.querySelector("input[name=option]:checked");
    if (!selected) return;

    const optionID = selected.value;

    fetch("", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "optionID=" + optionID
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const msg = data.isCorrect ? "🎉 Chính xác! +10 điểm" : "❌ Sai rồi!";
            clearInterval(timer);
            showResult(data.isCorrect, msg);
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

    // Trigger hiệu ứng
    setTimeout(() => xElem.classList.add("show"), 50);

    // Sau 1.2 giây thì ẩn đi
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
