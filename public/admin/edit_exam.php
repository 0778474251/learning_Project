<?php // public/admin/edit_exam.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
guard_admin();

$msg = '';
$exam_id = (int)($_GET['exam_id'] ?? 0);

// Fetch exam
$stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->bind_param('i', $exam_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();

if (!$exam) {
    die("Exam not found.");
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title']);
    $type     = $_POST['type'];
    $duration = (int)$_POST['duration_minutes'];
    $start    = $_POST['start_time'];
    $end      = $_POST['end_time'];

    $update = $conn->prepare("
        UPDATE exams 
        SET title = ?, type = ?, duration_minutes = ?, start_time = ?, end_time = ? 
        WHERE id = ?
    ");
    $update->bind_param('ssissi', $title, $type, $duration, $start, $end, $exam_id);

    if ($update->execute()) {
        $msg = "Exam updated successfully.";
        // Refresh exam data
        $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->bind_param('i', $exam_id);
        $stmt->execute();
        $exam = $stmt->get_result()->fetch_assoc();
    } else {
        $msg = "Error updating exam.";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit exam</title>
  <link rel="stylesheet" href="/exam_system/assets/css/styles.css">
</head>
<body>
<div class="nav"><a href="dashboard.php">Dashboard</a></div>
<div class="card">
  <h2>Edit Exam: <?= sanitize($exam['title']) ?></h2>
  <?php if ($msg): ?><div class="alert"><?= sanitize($msg) ?></div><?php endif; ?>
  <form method="post">
    <label>Title</label>
    <input name="title" value="<?= sanitize($exam['title']) ?>" required>

    <label>Type</label>
    <select name="type" required>
      <option value="mcq" <?= $exam['type']=='mcq'?'selected':'' ?>>MCQ</option>
      <option value="true_false" <?= $exam['type']=='true_false'?'selected':'' ?>>True/False</option>
      <option value="essay" <?= $exam['type']=='essay'?'selected':'' ?>>Essay</option>
      <option value="mixed" <?= $exam['type']=='mixed'?'selected':'' ?>>Mixed</option>
    </select>

    <label>Duration (minutes)</label>
    <input type="number" name="duration_minutes" min="1" 
           value="<?= (int)$exam['duration_minutes'] ?>" required>

    <label>Start time</label>
    <input type="datetime-local" name="start_time" 
           value="<?= date('Y-m-d\TH:i', strtotime($exam['start_time'])) ?>" required>

    <label>End time</label>
    <input type="datetime-local" name="end_time" 
           value="<?= date('Y-m-d\TH:i', strtotime($exam['end_time'])) ?>" required>

    <button class="btn" type="submit">Update</button>
  </form>
</div>
</body>
</html>
