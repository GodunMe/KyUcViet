<?php
// doquiz.php
session_start();
require_once "db.php"; // file này chứa kết nối $conn = new mysqli(...);

// Lấy câu hỏi từ DB
$sql = "SELECT id, question, option1, option2, option3, correct_answer, explanation FROM questions";
$result = $conn->query($sql);

$questions = [];
while($row = $result->fetch_assoc()){
    $questions[] = [
        "q" => $row["question"],
        "options" => [$row["option1"], $row["option2"], $row["option3"]],
        "answer" => (int)$row["correct_answer"],  // index: 0,1,2
        "explanation" => $row["explanation"]
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Làm Quiz</title>
  <style>
    body {font-family: Arial, sans-serif; background: #f5f5f5; margin:0; padding:0;
          display:flex; justify-content:center; align-items:center; min-height:100vh;}
    .quiz-container {background:#fff; border-radius:12px; box-shadow:0 4px 8px rgba(0,0,0,0.1);
          padding:20px; max-width:600px; width:100%; text-align:center;}
    .question {font-size:20px; margin-bottom:20px;}
    .options {display:flex; flex-direction:column; gap:10px;}
    .options button {padding:10px; border:none; border-radius:8px; cursor:pointer;
          background:#e0e0e0; transition:0.2s;}
    .options button:hover {background:#d0d0d0;}
    .hidden {display:none;}
    .submit-btn {margin-top:20px; background:#ff5722; color:white; padding:12px 20px;
          border:none; border-radius:10px; font-size:16px; cursor:pointer;}
    .submit-btn:hover {background:#e64a19;}
    .timer {font-size:18px; color:#ff5722; margin-bottom:15px;}
    .explanation {margin-top:10px; font-style:italic; color:#444;}
  </style>
</head>
<body>
  <div class="quiz-container">
    <div id="quiz">
      <div class="timer">⏳ Thời gian: <span id="time">15</span>s</div>
      <div class="question" id="question"></div>
      <div class="options" id="options"></div>
      <div class="explanation hidden" id="explanation"></div>
      <button class="submit-btn hidden" id="nextBtn">Câu tiếp theo</button>
    </div>
    <div id="result" class="hidden"></div>
  </div>

  <audio id="correctSound"><source src="correct.mp3" type="audio/mpeg"></audio>
  <audio id="wrongSound"><source src="wrong.mp3" type="audio/mpeg"></audio>
  <audio id="hurrySound"><source src="hurry.mp3" type="audio/mpeg"></audio>

  <script>
    // Lấy dữ liệu PHP -> JS
    const questions = <?php echo json_encode($questions, JSON_UNESCAPED_UNICODE); ?>;

    let current = 0;
    let score = 0;
    let timer;
    let timeLeft = 15;

    const questionEl = document.getElementById("question");
    const optionsEl = document.getElementById("options");
    const resultEl = document.getElementById("result");
    const quizEl = document.getElementById("quiz");
    const nextBtn = document.getElementById("nextBtn");
    const explanationEl = document.getElementById("explanation");
    const timeEl = document.getElementById("time");

    function loadQuestion() {
      clearInterval(timer);
      timeLeft = 15;
      timeEl.textContent = timeLeft;
      timer = setInterval(countdown, 1000);

      const q = questions[current];
      questionEl.textContent = q.q;
      optionsEl.innerHTML = "";
      explanationEl.classList.add("hidden");
      nextBtn.classList.add("hidden");

      q.options.forEach((opt, i) => {
        const btn = document.createElement("button");
        btn.textContent = opt;
        btn.onclick = () => selectAnswer(i);
        optionsEl.appendChild(btn);
      });
    }

    function countdown() {
      timeLeft--;
      timeEl.textContent = timeLeft;
      if (timeLeft === 5) {
        document.getElementById("hurrySound").play();
        alert("Nhanh lên! Sắp hết giờ!");
      }
      if (timeLeft <= 0) {
        clearInterval(timer);
        alert("Hết giờ! Đọc nhanh hơn nhé!");
        showExplanation(false);
        nextBtn.classList.remove("hidden");
      }
    }

    function selectAnswer(i) {
      clearInterval(timer);
      const q = questions[current];
      if (i === q.answer) {
        score++;
        document.getElementById("correctSound").play();
        showExplanation(true);
      } else {
        document.getElementById("wrongSound").play();
        alert("Gà quá!");
        showExplanation(false);
      }
      nextBtn.classList.remove("hidden");
    }

    function showExplanation(correct) {
      explanationEl.textContent = questions[current].explanation;
      explanationEl.classList.remove("hidden");
    }

    nextBtn.onclick = () => {
      current++;
      if (current < questions.length) {
        loadQuestion();
      } else {
        finishQuiz();
      }
    };

    function finishQuiz() {
      quizEl.classList.add("hidden");
      resultEl.classList.remove("hidden");
      resultEl.innerHTML = `
        <h2>Bạn đã hoàn thành Quiz!</h2>
        <p>Điểm số của bạn: ${score}/${questions.length}</p>
        <h3>Cảm ơn bạn đã tham gia!</h3>
      `;

      // gửi điểm lên server
      fetch("update_score.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "score="+score+"&total="+questions.length
      });
    }

    loadQuestion();
  </script>
</body>
</html>
