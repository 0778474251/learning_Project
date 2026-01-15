<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
guard_student();

$student_id = (int)$_SESSION['student_id'];
$exam_id    = (int)($_GET['exam_id'] ?? 0);

/* =======================
   CHECK ASSIGNMENT
======================= */
$stmt = $conn->prepare("
    SELECT e.*
    FROM exam_assignments ea
    JOIN exams e ON e.id = ea.exam_id
    WHERE ea.exam_id=? AND ea.student_id=?
");
$stmt->bind_param("ii", $exam_id, $student_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
if (!$exam) die("Not assigned");

/* =======================
   TIME WINDOW
======================= */
$now   = new DateTime();
$start = new DateTime($exam['start_time']);
$end   = new DateTime($exam['end_time']);

if ($now < $start) die("Exam not started");
if ($now > $end)   die("Exam ended");

$remainingSeconds = $end->getTimestamp() - $now->getTimestamp();

/* =======================
   SUBMISSION RECORD
======================= */
$stmt = $conn->prepare("
    SELECT * FROM exam_submissions
    WHERE exam_id=? AND student_id=?
");
$stmt->bind_param("ii", $exam_id, $student_id);
$stmt->execute();
$sub = $stmt->get_result()->fetch_assoc();

if ($sub && $sub['status'] === 'submitted') {
    header("Location: results.php?exam_id=$exam_id");
    exit;
}

if (!$sub) {
    $stmt = $conn->prepare("
        INSERT INTO exam_submissions (exam_id, student_id, started_at, status)
        VALUES (?, ?, NOW(), 'in_progress')
    ");
    $stmt->bind_param("ii", $exam_id, $student_id);
    $stmt->execute();
}

/* =======================
   QUESTIONS
======================= */
$stmt = $conn->prepare("
    SELECT * FROM questions
    WHERE exam_id=?
    ORDER BY id ASC
");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$totalQ = count($questions);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Take Exam</title>

<style>
body{font-family:Segoe UI;background:#f4f6f9;margin:0}
.nav{background:#2c3e50;padding:15px;color:#fff;display:flex;justify-content:space-between}
.card{background:#fff;margin:20px auto;padding:20px;max-width:900px;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,.1)}
.timer{background:#e74c3c;color:#fff;padding:10px 18px;border-radius:8px}
.question{display:none}
.question.active{display:block}
.nav-btn{background:#3498db;color:#fff;border:none;padding:8px 16px;border-radius:6px;margin:5px;cursor:pointer}
.nav-btn:disabled{background:#aaa}
.pagination{text-align:center;margin-top:15px}
.dot{display:inline-block;width:30px;height:30px;line-height:30px;border-radius:50%;background:#ddd;margin:3px}
.dot.active{background:#3498db;color:#fff;font-weight:bold}
</style>
</head>
<body>

<div class="nav">
  <a href="dashboard.php" style="color:white;text-decoration:none">⬅ Dashboard</a>
  <div class="timer">⏰ <span id="timer"></span></div>
</div>

<div class="card">
<h2><?= htmlspecialchars($exam['title']) ?></h2>
<p><b>Exam Window:</b> <?= $exam['start_time'] ?> → <?= $exam['end_time'] ?></p>
</div>

<div class="card">
<form id="examForm" method="post" action="submit_exam.php">
<input type="hidden" name="exam_id" value="<?= $exam_id ?>">

<?php foreach ($questions as $index => $q): ?>
<div class="question <?= $index === 0 ? 'active' : '' ?>">
<h3>Question <?= $index + 1 ?> of <?= $totalQ ?></h3>
<p><b><?= htmlspecialchars($q['question_text']) ?></b></p>

<?php if ($q['type'] === 'mcq'):
$options = json_decode($q['options'], true);
foreach ($options as $key => $opt): ?>
<label>
<input type="radio" name="answer[<?= $q['id'] ?>]" value="<?= $key ?>">
<?= htmlspecialchars($opt) ?>
</label><br>
<?php endforeach; ?>

<?php elseif ($q['type'] === 'true_false'): ?>
<label><input type="radio" name="answer[<?= $q['id'] ?>]" value="true"> True</label>
<label><input type="radio" name="answer[<?= $q['id'] ?>]" value="false"> False</label>

<?php else: ?>
<textarea name="answer[<?= $q['id'] ?>]" rows="4" style="width:100%"></textarea>
<?php endif; ?>
</div>
<?php endforeach; ?>

<div class="pagination">
<button type="button" class="nav-btn" id="prevBtn">⬅ Prev</button>
<button type="button" class="nav-btn" id="nextBtn">Next ➡</button>
<button type="submit" class="nav-btn"
onclick="this.disabled=true;this.innerText='Submitting…';return confirm('Submit exam?')">
Submit
</button>
</div>

<div class="pagination">
<?php for ($i=1; $i<=$totalQ; $i++): ?>
<span class="dot <?= $i===1?'active':'' ?>"><?= $i ?></span>
<?php endfor; ?>
</div>

</form>
</div>

<script>
let current = 0;
const questions = document.querySelectorAll('.question');
const dots = document.querySelectorAll('.dot');

function showQuestion(i){
    questions.forEach(q=>q.classList.remove('active'));
    dots.forEach(d=>d.classList.remove('active'));
    questions[i].classList.add('active');
    dots[i].classList.add('active');
}
document.getElementById('nextBtn').onclick = ()=>{ if(current < questions.length-1){ current++; showQuestion(current); }};
document.getElementById('prevBtn').onclick = ()=>{ if(current > 0){ current--; showQuestion(current); }};

/* TIMER */
let remaining = <?= $remainingSeconds ?>;
const timer = document.getElementById('timer');
const form = document.getElementById('examForm');

const interval = setInterval(()=>{
    if(remaining <= 0){
        clearInterval(interval);
        timer.textContent = "0m 0s";
        alert("Time up! Submitting exam.");
        form.submit();
        return;
    }
    let m = Math.floor(remaining/60);
    let s = remaining%60;
    timer.textContent = m+"m "+s+"s";
    remaining--;
},1000);
</script>

</body>
</html>
