<?php
session_start();
require_once "db.php"; // file này bạn tạo kết nối mysqli $conn

// Giả sử user đã đăng nhập, có Username lưu trong session
if (!isset($_SESSION['username'])) {
    die("Bạn cần đăng nhập để làm quiz!");
}

$username = $_SESSION['username'];

// Lấy quiz id bất kỳ (ví dụ quiz đầu tiên)
$quizId = isset($_GET['quiz']) ? intval($_GET['quiz']) : 1;

// Lấy 1 câu hỏi trong quiz
$sql = "SELECT * FROM question WHERE QuizID = $quizId ORDER BY RAND() LIMIT 1";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    die("Quiz chưa có câu hỏi!");
}
$question = $result->fetch_assoc();
$questionId = $question['QuestionID'];

// Lấy các option
$options = [];
$sqlOpt = "SELECT * FROM `option` WHERE QuestionID = $questionId";
$resOpt = $conn->query($sqlOpt);
while ($row = $resOpt->fetch_assoc()) {
    $options[] = $row;
}

// Nếu người dùng submit
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $optionId = intval($_POST['option']);
    // Kiểm tra đúng sai
    $checkSql = "SELECT isCorrect FROM `option` WHERE OptionID = $optionId";
    $checkRes = $conn->query($checkSql);
    if ($checkRes && $row = $checkRes->fetch_assoc()) {
        if ($row['isCorrect'] == 1) {
            // đúng → cộng 10 điểm
            $conn->query("UPDATE users SET Score = Score + 10 WHERE Username = '" . $conn->real_escape_string($username) . "'");
            $message = "<p style='color:green;font-weight:bold;'>🎉 Chính xác! Bạn được +10 điểm</p>";
        } else {
            $message = "<p style='color:red;font-weight:bold;'>❌ Sai rồi!</p>";
        }
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
    body {
        font-family: Arial, sans-serif;
        padding: 10px;
        background: #f9f9f9;
    }
    .quiz-box {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        max-width: 500px;
        margin: auto;
    }
    .question {
        font-size: 18px;
        margin-bottom: 15px;
    }
    .option {
        margin: 8px 0;
    }
    .submit-btn {
        width: 100%;
        background: red;
        color: white;
        padding: 12px;
        font-size: 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }
    .submit-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    .timer {
        font-size: 18px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 15px;
        color: #333;
    }
</style>
</head>
<body>

<div class="quiz-box">
    <div class="timer">⏳ Thời gian còn lại: <span id="countdown">15</span>s</div>
    <form method="POST" id="quizForm">
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
    <div class="result"><?= $message ?></div>
</div>

<script>
let timeLeft = 15;
let timer = setInterval(() => {
    document.getElementById("countdown").innerText = timeLeft;
    if (timeLeft <= 0) {
        clearInterval(timer);
        document.getElementById("submitBtn").disabled = true;
        document.querySelector(".result").innerHTML = "<p style='color:red;font-weight:bold;'>⏰ Hết thời gian! Bạn trả lời sai.</p>";
    }
    timeLeft--;
}, 1000);
</script>

</body>
</html>
