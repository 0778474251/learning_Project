<?php // public/admin/view_questions.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
guard_admin();

$exam_id = (int)($_GET['exam_id'] ?? 0);
if ($exam_id <= 0) {
    die("Invalid exam ID.");
}

// Fetch exam info
$exam = $conn->query("SELECT * FROM exams WHERE id=$exam_id")->fetch_assoc();
if (!$exam) {
    die("Exam not found.");
}

// Fetch questions for this exam
$questions = $conn->query("SELECT * FROM questions WHERE exam_id=$exam_id ORDER BY id ASC");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Questions for <?= sanitize($exam['title']) ?></title>
  <link rel="stylesheet" href="/exam_system/assets/css/styles.css">
  <style>
    body { font-family: "Segoe UI", Tahoma, sans-serif; background:#f4f6f9; margin:0; }
    .nav { background:#2c3e50; padding:12px; }
    .nav a { color:#fff; margin-right:15px; text-decoration:none; font-weight:bold; }
    .nav a:hover { text-decoration:underline; }
    .card { background:#fff; padding:25px; margin:20px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
    h2 { margin-top:0; color:#2c3e50; }
    table { width:100%; border-collapse:collapse; margin-top:15px; }
    th, td { border:1px solid #ddd; padding:10px; text-align:left; }
    th { background:#3498db; color:#fff; }
    tr:nth-child(even) { background:#f9f9f9; }
    tr:hover { background:#ecf0f1; }
    .btn { display:inline-block; margin:5px; padding:8px 15px; border-radius:6px; background:#3498db; color:#fff; text-decoration:none; font-weight:bold; }
    .btn:hover { background:#2980b9; }
    .btn-edit { background:#f39c12; }
    .btn-edit:hover { background:#e67e22; }
  </style>
</head>
<body>
<div class="nav">
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="create_exam.php">➕ Create Exam</a>
  <a href="assign_exam.php">📌 Assign Exam</a>
  <a href="view_results.php">📊 View Results</a>
</div>

<div class="card">
  <h2>📄 Questions for Exam: <?= sanitize($exam['title']) ?></h2>
  <p>Type: <?= sanitize($exam['type']) ?> | Duration: <?= (int)$exam['duration_minutes'] ?> minutes</p>

  <?php if ($questions && $questions->num_rows > 0): ?>
    <table>
      <tr>
        <th>ID</th>
        <th>Question Text</th>
        <th>Type</th>
        <th>Options</th>
        <th>Correct Answer</th>
        <th>Actions</th>
      </tr>
      <?php while ($q = $questions->fetch_assoc()): ?>
        <tr>
          <td><?= $q['id'] ?></td>
          <td><?= sanitize($q['question_text']) ?></td>
          <td><?= sanitize($q['type']) ?></td>
          <td><?= sanitize($q['options']) ?></td>
          <td><?= sanitize($q['correct_answer']) ?></td>
          <td>
            <a class="btn btn-edit" href="edit_question.php?id=<?= $q['id'] ?>&exam_id=<?= $exam['id'] ?>">✏️ Edit</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  <?php else: ?>
    <p>No questions found for this exam.</p>
  <?php endif; ?>

  <a class="btn" href="add_question.php?exam_id=<?= $exam['id'] ?>">➕ Add Question</a>
</div>
</body>
</html>
