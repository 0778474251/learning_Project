<?php
// public/admin/edit_question.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
guard_admin();

$question_id = (int)($_GET['id'] ?? 0);
$exam_id     = (int)($_GET['exam_id'] ?? 0);

if ($question_id <= 0 || $exam_id <= 0) {
    die("Invalid request.");
}

// Fetch question
$q = $conn->query("SELECT * FROM questions WHERE id=$question_id AND exam_id=$exam_id")->fetch_assoc();
if (!$q) {
    die("Question not found.");
}

// Handle form submission
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text    = $_POST['question_text'];
    $type    = $_POST['type'];
    $options = $_POST['options'];
    $answer  = $_POST['correct_answer'];

    $stmt = $conn->prepare("
        UPDATE questions 
        SET question_text=?, type=?, options=?, correct_answer=? 
        WHERE id=? AND exam_id=?
    ");
    $stmt->bind_param("ssssii", $text, $type, $options, $answer, $question_id, $exam_id);
    $msg = $stmt->execute() ? "Question updated successfully." : "Update failed.";
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Edit Question</title>
<link rel="stylesheet" href="/exam_system/assets/css/styles.css">
</head>
<body>
<div class="nav"><a href="view_questions.php?exam_id=<?= $exam_id ?>">⬅ Back to Questions</a></div>
<div class="card">
<h2>Edit Question</h2>
<?php if ($msg): ?><div class="alert"><?= sanitize($msg) ?></div><?php endif; ?>

<form method="post">
<label>Question Text</label>
<textarea name="question_text" required><?= sanitize($q['question_text']) ?></textarea>

<label>Type</label>
<input type="text" name="type" value="<?= sanitize($q['type']) ?>" required>

<label>Options</label>
<textarea name="options"><?= sanitize($q['options']) ?></textarea>

<label>Correct Answer</label>
<input type="text" name="correct_answer" value="<?= sanitize($q['correct_answer']) ?>" required>

<button class="btn">Update Question</button>
</form>
</div>
</body>
</html>
