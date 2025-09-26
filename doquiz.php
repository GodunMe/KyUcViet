<?php
session_start();
require_once "db.php";

// --- Check login ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Get museum ID ---
$museumId = isset($_GET['museum']) ? intval($_GET['museum']) : 0;
if ($museumId <= 0) {
    die("Không có bảo tàng hợp lệ!");
}

// --- Fetch museum name ---
$stmt = $conn->prepare("SELECT MuseumName FROM Museums WHERE MuseumID=?");
$stmt->execute([$museumId]);
$museum = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$museum) {
    die("Không tìm thấy bảo tàng!");
}

// --- Fetch questions ---
$stmt = $conn->prepare("SELECT * FROM QuizQuestions WHERE MuseumID=?");
$stmt->execute([$museumId]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultMsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = 0;
    foreach ($questions as $q) {
        $qid = $q['QuestionID'];
        if (isset($_POST['q'.$qid]) && $_POST['q'.$qid] == $q['CorrectOption']) {
            $score++;
        }
    }
    $total = count($questions);
    $earned = $score * 10;

    // Update user points
    $stmt = $conn->prepare("UPDATE Users SET score = score + ? WHERE id=?");
    $stmt->execute([$earned, $user_id]);

    $resultMsg = "Bạn trả lời đúng $score / $total câu. +$earned điểm!";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz - <?php echo htmlspecialchars($museum['MuseumName']); ?></title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background: #f9f9f9;
    }
    .container {
      max-width: 480px;
      margin: auto;
      padding: 15px;
    }
    h2 {
      text-align: center;
      font-size: 20px;
      margin-bottom: 20px;
    }
    .quiz-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      padding: 15px;
      margin-bottom: 20px;
    }
    .quiz-card h3 {
      font-size: 16px;
      margin-bottom: 10px;
    }
    .option {
      display: block;
      background: #f1f1f1;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 10px;
      cursor: pointer;
      transition: background 0.3s;
    }
    .option:hover {
      background: #e6e6e6;
    }
    .quiz-submit {
      width: 100%;
      padding: 14px;
      font-size: 16px;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      background: linear-gradient(135deg, #ffe29f, #ffa99f);
      color: #000;
      font-weight: bold;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .quiz-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .result {
      text-align: center;
      font-size: 18px;
      font-weight: bold;
      margin: 15px 0;
      color: #d35400;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Quiz: <?php echo htmlspecialchars($museum['MuseumName']); ?></h2>
    
    <?php if ($resultMsg): ?>
      <div class="result"><?php echo $resultMsg; ?></div>
    <?php endif; ?>

    <form method="post">
      <?php foreach ($questions as $index => $q): ?>
        <div class="quiz-card">
          <h3>Câu <?php echo $index+1; ?>: <?php echo htmlspecialchars($q['QuestionText']); ?></h3>
          <?php for ($i=1; $i<=4; $i++): 
              $opt = $q['Option'.$i]; ?>
              <label class="option">
                <input type="radio" name="q<?php echo $q['QuestionID']; ?>" value="<?php echo $i; ?>" required>
                <?php echo htmlspecialchars($opt); ?>
              </label>
          <?php endfor; ?>
        </div>
      <?php endforeach; ?>
      <button type="submit" class="quiz-submit">📩 Nộp bài</button>
    </form>
  </div>
</body>
</html>
