<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
guard_student();

$student_id = (int)$_SESSION['student_id'];
$exam_id    = (int)($_GET['exam_id'] ?? 0);

// Submission
$stmt = $conn->prepare("
    SELECT es.*, e.title 
    FROM exam_submissions es
    JOIN exams e ON e.id = es.exam_id
    WHERE es.student_id=? AND es.exam_id=?
");
$stmt->bind_param('ii', $student_id, $exam_id);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    die("<div class='card'><h2>No submission found for this exam.</h2></div>");
}

// Answers
$answers = $conn->query("
    SELECT sa.answer AS selected_answer, sa.is_correct, sa.points_awarded,
           q.question_text, q.points, q.correct_answer
    FROM student_answers sa
    JOIN questions q ON q.id = sa.question_id
    WHERE sa.exam_id=$exam_id AND sa.student_id=$student_id
");

$passed = 0;
$failed = 0;
if ($answers) {
    while ($a = $answers->fetch_assoc()) {
        if ($a['is_correct'] == 1) $passed++;
        elseif ($a['is_correct'] == 0) $failed++;
    }
    $total = $passed + $failed;
    $answers->data_seek(0);
}

$score_percent = ($submission['max_points'] > 0)
    ? round(($submission['total_points'] / $submission['max_points']) * 100)
    : 0;

$isPassed = $score_percent >= 50;
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>My Results</title>
<link rel="stylesheet" href="/exam_system/assets/css/styles.css">

<style>
/* Base */
body { background:#f4f6f9; font-family:Segoe UI,Tahoma; overflow-x:hidden; }
.card { position:relative; }

/* Progress */
.progress-bar { background:#eee; border-radius:8px; overflow:hidden; }
.progress-fill {
    height:22px;
    background:linear-gradient(90deg,#4caf50,#8bc34a);
    width:<?= $score_percent ?>%;
    text-align:center;
    color:#fff;
    font-size:12px;
    line-height:22px;
}

/* Badges */
.badge { padding:8px 15px; border-radius:20px; color:#fff; font-weight:bold; display:inline-block; margin-top:10px; }
.excellent { background:#4caf50; }
.good { background:#2196f3; }
.needs { background:#ff9800; }
.poor { background:#f44336; }

/* Celebration bubbles */
.bubble {
    position: fixed;
    bottom: -50px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: radial-gradient(circle,#fff,#4caf50);
    animation: floatUp 6s linear infinite;
    opacity: 0.8;
    z-index: 999;
}
@keyframes floatUp {
    0% { transform: translateY(0) scale(0.5); opacity:1; }
    100% { transform: translateY(-110vh) scale(1.4); opacity:0; }
}

/* Failure calm effect */
.fail-box {
    background: #fff3e0;
    border-left: 6px solid #ff9800;
    padding: 15px;
    margin-top: 20px;
    border-radius: 6px;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { box-shadow:0 0 0 rgba(255,152,0,.3); }
    70% { box-shadow:0 0 15px rgba(255,152,0,.6); }
    100% { box-shadow:0 0 0 rgba(255,152,0,.3); }
}

/* Table */
table { width:100%; border-collapse:collapse; margin-top:15px; }
th,td { padding:10px; border:1px solid #ddd; }
th { background:#f5f5f5; }
.pass { color:#2e7d32; font-weight:bold; }
.fail { color:#c62828; font-weight:bold; }
</style>
</head>

<body>

<div class="nav">
  <a href="dashboard.php">Dashboard</a>
  <a href="exams.php">My Exams</a>
  <a class="right" href="/exam_system/public/logout.php">Logout</a>
</div>

<div class="card">
<h2>Exam Results: <?= sanitize($submission['title']) ?></h2>

<p><strong>Score:</strong> <?= (int)$submission['total_points'] ?> / <?= (int)$submission['max_points'] ?> (<?= $score_percent ?>%)</p>

<div class="progress-bar">
  <div class="progress-fill"><?= $score_percent ?>%</div>
</div>

<?php
if ($score_percent >= 85) echo "<span class='badge excellent'>🌟 Excellent</span>";
elseif ($score_percent >= 70) echo "<span class='badge good'>👍 Good</span>";
elseif ($score_percent >= 50) echo "<span class='badge needs'>⚠️ Needs Improvement</span>";
else echo "<span class='badge poor'>❌ Not Passed</span>";
?>

<p><span class="pass">Passed: <?= $passed ?></span> | <span class="fail">Failed: <?= $failed ?></span></p>

<?php if ($isPassed): ?>
<script>
for (let i = 0; i < 30; i++) {
    const b = document.createElement('div');
    b.className = 'bubble';
    b.style.left = Math.random() * 100 + 'vw';
    b.style.animationDuration = (3 + Math.random() * 3) + 's';
    document.body.appendChild(b);
    setTimeout(() => b.remove(), 6000);
}
</script>

<h3>🎉 Congratulations!</h3>
<p>You successfully completed this exam. Keep up the great work!</p>

<?php else: ?>

<div class="fail-box">
  <h3>💡 Don’t Give Up</h3>
  <p>This attempt didn’t go as planned — but learning is a journey.</p>
  <ul>
    <li>Review incorrect questions carefully</li>
    <li>Practice similar questions</li>
    <li>Ask your instructor for clarification</li>
  </ul>
</div>

<?php endif; ?>

<h3>Question Breakdown</h3>
<table>
<tr>
  <th>#</th><th>Question</th><th>Your Answer</th><th>Correct</th><th>Points</th><th>Result</th>
</tr>
<?php $i=1; while ($a = $answers->fetch_assoc()): ?>
<tr>
  <td><?= $i++ ?></td>
  <td><?= sanitize($a['question_text']) ?></td>
  <td><?= sanitize($a['selected_answer']) ?></td>
  <td><?= sanitize($a['correct_answer']) ?></td>
  <td><?= $a['points_awarded'] ?? 'Pending' ?></td>
  <td>
    <?= $a['is_correct'] == 1 ? '<span class="pass">Passed</span>' :
        ($a['is_correct'] === 0 ? '<span class="fail">Failed</span>' : 'Pending') ?>
  </td>
</tr>
<?php endwhile; ?>
</table>

</div>
</body>
</html>
