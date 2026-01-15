<?php // public/admin/grade_essay.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
guard_admin();

// Fetch essay answers not graded
$rows = $conn->query("
SELECT sa.id as ans_id, sa.exam_id, sa.student_id, sa.question_id, sa.answer, q.points, e.title, q.question_text
FROM student_answers sa
JOIN questions q ON q.id=sa.question_id
JOIN exams e ON e.id=sa.exam_id
WHERE q.type='essay' AND sa.points_awarded IS NULL
ORDER BY sa.submitted_at ASC
");

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ans_id = (int)$_POST['ans_id'];
    $points = (float)$_POST['points_awarded'];
    $stmt = $conn->prepare("UPDATE student_answers SET points_awarded=?, is_correct=NULL WHERE id=?");
    $stmt->bind_param('di', $points, $ans_id);
    $stmt->execute();

    // Recompute submission totals
    $row = $conn->query("SELECT exam_id, student_id FROM student_answers WHERE id=$ans_id")->fetch_assoc();
    $exam_id = (int)$row['exam_id']; $student_id = $row['student_id'];
    $totals = $conn->query("
      SELECT COALESCE(SUM(points_awarded),0) AS total, 
             (SELECT SUM(points) FROM questions WHERE exam_id=$exam_id) AS maxp
      FROM student_answers WHERE exam_id=$exam_id AND student_id='$student_id'
    ")->fetch_assoc();
    $stmt2 = $conn->prepare("UPDATE exam_submissions SET total_points=?, max_points=?, status='graded' WHERE exam_id=? AND student_id=?");
    $stmt2->bind_param('ddis', $totals['total'], $totals['maxp'], $exam_id, $student_id);
    $stmt2->execute();

    $msg = 'Essay graded and totals updated.';
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Grade essays</title>
<link rel="stylesheet" href="/exam_system/assets/css/styles.css"></head>
<body>
<div class="nav"><a href="dashboard.php">Dashboard</a></div>
<div class="card">
  <h2>Essay grading</h2>
  <?php if ($msg): ?><div class="alert"><?= sanitize($msg) ?></div><?php endif; ?>
  <?php while ($r = $rows->fetch_assoc()): ?>
    <div class="essay">
      <h3><?= sanitize($r['title']) ?> — Q#<?= $r['question_id'] ?> (<?= $r['points'] ?> pts)</h3>
      <p><b>Student:</b> <?= sanitize($r['student_id']) ?></p>
      <p><b>Question:</b> <?= sanitize($r['question_text']) ?></p>
      <p><b>Answer:</b><br><?= nl2br(sanitize($r['answer'])) ?></p>
      <form method="post">
        <input type="hidden" name="ans_id" value="<?= $r['ans_id'] ?>">
        <label>Points awarded (0–<?= $r['points'] ?>)</label>
        <input type="number" name="points_awarded" min="0" max="<?= $r['points'] ?>" step="0.5" required>
        <button class="btn" type="submit">Save</button>
      </form>
      <hr>
    </div>
  <?php endwhile; ?>
</div>
</body>
</html>
