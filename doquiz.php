<?php
require_once "db.php"; // kết nối DB (tạo $conn)

$quizId = isset($_GET['quiz']) ? intval($_GET['quiz']) : 1;

$question = null;
$options = [];

// Lấy 1 câu hỏi ngẫu nhiên trong quiz
$sql = "SELECT * FROM question WHERE QuizID = $quizId ORDER BY RAND() LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $question = $result->fetch_assoc();
    $questionId = $question['QuestionID'];

    // Lấy các option
    $sqlOpt = "SELECT * FROM option WHERE QuestionID = $questionId";
    $resOpt = $conn->query($sqlOpt);
    if ($resOpt && $resOpt->num_rows > 0) {
        while ($row = $resOpt->fetch_assoc()) {
            $options[] = $row;
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
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
        .submit-btn, .back-btn {
            width: 100%;
            background: red;
            color: white;
            padding: 12px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
        }
        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .back-btn {
            background: gray;
        }
        .timer {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
            color: #333;
        }
        .result {
            margin-top: 15px;
            font-size: 18px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="quiz-box">
        <?php if (!empty($question)): ?>
            <div class="timer">⏳ Thời gian còn lại: <span id="countdown">15</span>s</div>
            <?php if (!empty($options)): ?>
                <form id="quizForm">
                    <div class="question"><?= htmlspecialchars($question['QuestionText']) ?></div>
                    <?php foreach ($options as $opt): ?>
                        <div class="option">
                            <label>
                                <input type="radio" name="option" value="<?= $opt['OptionID'] ?>" data-correct="<?= $opt['isCorrect'] ?>" required>
                                <?= htmlspecialchars($opt['TEXT']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="submit-btn" id="submitBtn">Nộp bài</button>
                </form>
            <?php else: ?>
                <p style="color:red;">❌ Câu hỏi này chưa có đáp án nào.</p>
            <?php endif; ?>
        <?php else: ?>
            <p style="color:red;">❌ Quiz chưa có câu hỏi.</p>
        <?php endif; ?>

        <div class="result"></div>

        <button class="back-btn" onclick="window.location.href='index.php'">⬅️ Trở về</button>
    </div>

    <script>
        let timeLeft = 15;
        let stopped = false;
        const countdown = document.getElementById("countdown");
        const resultDiv = document.querySelector(".result");

        function disableAll() {
            document.querySelectorAll("input[type=radio]").forEach(r => r.disabled = true);
            document.getElementById("submitBtn").disabled = true;
        }

        function showResult(isCorrect, msg) {
            stopped = true;
            disableAll();
            resultDiv.innerHTML = `<p style="color:${isCorrect ? 'green' : 'red'};">${msg}</p>`;
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

        document.getElementById("quizForm")?.addEventListener("submit", function(e) {
            e.preventDefault(); // ngăn reload page
            if (stopped) return;

            const selected = document.querySelector("input[name=option]:checked");
            if (!selected) return;

            const isCorrect = selected.dataset.correct == 1;
            const msg = isCorrect ? "🎉 Chính xác!" : "❌ Sai rồi!";
            clearInterval(timer);
            showResult(isCorrect, msg);
        });
    </script>
</body>
</html>
