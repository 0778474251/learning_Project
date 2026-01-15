<?php
// public/admin/add_question.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
guard_admin();

$exam_id = (int)($_GET['exam_id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
if (!$exam) die('Exam not found');

$exam_type = $exam['type'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qtext  = trim($_POST['question_text']);
    $points = (float)($_POST['points'] ?? 1);
    $qtype  = $exam_type === 'mixed' ? $_POST['type'] : $exam_type;

    $options = null;
    $correct = null;

    if ($qtype === 'mcq') {
        $opts = [
            'A' => trim($_POST['optA']),
            'B' => trim($_POST['optB']),
            'C' => trim($_POST['optC']),
            'D' => trim($_POST['optD']),
        ];

        $opts = array_filter($opts);
        if (count($opts) < 2) {
            $msg = 'MCQ must have at least 2 options.';
        } else {
            $options = json_encode($opts, JSON_UNESCAPED_UNICODE);
            $correct = $_POST['correct_answer'];
            if (!isset($opts[$correct])) {
                $msg = 'Correct answer must match an option.';
            }
        }
    }

    elseif ($qtype === 'true_false') {
        $options = json_encode(['true' => 'True', 'false' => 'False']);
        $correct = $_POST['correct_answer_tf'];
    }

    elseif ($qtype === 'essay') {
        $options = null;
        $correct = null;
    }

    if (!$msg) {
        $stmt = $conn->prepare("
            INSERT INTO questions 
            (exam_id, question_text, type, options, correct_answer, points)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'issssd',
            $exam_id,
            $qtext,
            $qtype,
            $options,
            $correct,
            $points
        );
        $msg = $stmt->execute() ? '✅ Question added.' : '❌ Error adding question.';
    }
}

$questions = $conn->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id DESC");
$questions->bind_param("i", $exam_id);
$questions->execute();
$questions = $questions->get_result();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Add questions</title>
<link rel="stylesheet" href="/exam_system/assets/css/styles.css">
</head>
<body>

<div class="nav">
  <a href="dashboard.php">Dashboard</a>
  <a href="assign_exam.php">Assign exam</a>
</div>

<div class="card">
<h2>Add questions to: <?= sanitize($exam['title']) ?></h2>
<p><strong>Exam type:</strong> <?= strtoupper($exam_type) ?></p>

<?php if ($msg): ?>
  <div class="alert"><?= sanitize($msg) ?></div>
<?php endif; ?>

<form method="post">

<label>Question text</label>
<textarea name="question_text" required></textarea>

<?php if ($exam_type === 'mixed'): ?>
<label>Question Type</label>
<select name="type" id="qtype" onchange="toggleFields(this.value)">
  <option value="mcq">MCQ</option>
  <option value="true_false">True / False</option>
  <option value="essay">Essay</option>
</select>
<?php else: ?>
<input type="hidden" name="type" value="<?= $exam_type ?>">
<script>document.addEventListener('DOMContentLoaded',()=>toggleFields('<?= $exam_type ?>'));</script>
<?php endif; ?>

<div id="mcq_fields">
  <label>Option A</label><input name="optA">
  <label>Option B</label><input name="optB">
  <label>Option C</label><input name="optC">
  <label>Option D</label><input name="optD">

  <label>Correct Answer</label>
  <select name="correct_answer">
    <option value="A">A</option>
    <option value="B">B</option>
    <option value="C">C</option>
    <option value="D">D</option>
  </select>
</div>

<div id="tf_fields" style="display:none">
  <label>Correct Answer</label>
  <select name="correct_answer_tf">
    <option value="true">True</option>
    <option value="false">False</option>
  </select>
</div>

<label>Points</label>
<input type="number" name="points" value="1" min="0" step="0.5">

<button class="btn">Add question</button>
</form>
</div>

<div class="card">
<h3>Existing questions</h3>
<ul>
<?php while ($q = $questions->fetch_assoc()): ?>
  <li>
    #<?= $q['id'] ?> —
    <?= strtoupper($q['type']) ?> —
    <?= sanitize($q['question_text']) ?>
    (<?= $q['points'] ?> pts)
  </li>
<?php endwhile; ?>
</ul>
</div>

<script>
function toggleFields(type){
  document.getElementById('mcq_fields').style.display =
      (type === 'mcq') ? 'block' : 'none';
  document.getElementById('tf_fields').style.display =
      (type === 'true_false') ? 'block' : 'none';
}
</script>

</body>
</html>
