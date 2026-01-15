<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
guard_student();

$student_id = (int)$_SESSION['student_id'];
$exam_id    = (int)($_POST['exam_id'] ?? 0);

/* -------------------------------------------------
   1. VERIFY ASSIGNMENT
------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT e.*
    FROM exam_assignments ea
    JOIN exams e ON e.id = ea.exam_id
    WHERE ea.exam_id = ? AND ea.student_id = ?
");
$stmt->bind_param("ii", $exam_id, $student_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();

if (!$exam) {
    die("<div class='card'><h2>You are not assigned to this exam.</h2></div>");
}

/* -------------------------------------------------
   2. PREVENT DOUBLE SUBMISSION
------------------------------------------------- */
$check = $conn->prepare("
    SELECT id FROM exam_submissions
    WHERE exam_id=? AND student_id=? AND status='submitted'
");
$check->bind_param('ii', $exam_id, $student_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    die("<div class='card'><h2>This exam has already been submitted.</h2></div>");
}

/* -------------------------------------------------
   3. PREPARE STATEMENTS
------------------------------------------------- */
$stmtAuto = $conn->prepare("
    INSERT INTO student_answers
    (exam_id, student_id, question_id, answer, is_correct, points_awarded)
    VALUES (?,?,?,?,?,?)
");

$stmtEssay = $conn->prepare("
    INSERT INTO student_answers
    (exam_id, student_id, question_id, answer, is_correct, points_awarded)
    VALUES (?,?,?,?,?,NULL)
");

$answers = $_POST['answer'] ?? [];
$total_awarded = 0.0;

/* -------------------------------------------------
   4. PROCESS ANSWERS
------------------------------------------------- */
foreach ($answers as $qid => $ans) {
    $qid = (int)$qid;
    $ans = trim($ans);

    $q = $conn->prepare("SELECT * FROM questions WHERE id=? AND exam_id=?");
    $q->bind_param('ii', $qid, $exam_id);
    $q->execute();
    $question = $q->get_result()->fetch_assoc();

    if (!$question) continue;

    if (in_array($question['type'], ['mcq', 'true_false'])) {
        $correct = trim($question['correct_answer'] ?? '');
        $is_correct = (strcasecmp($correct, $ans) === 0) ? 1 : 0;
        $points = $is_correct ? (float)$question['points'] : 0.0;

        $stmtAuto->bind_param(
            'iiisid',
            $exam_id,
            $student_id,
            $qid,
            $ans,
            $is_correct,
            $points
        );
        $stmtAuto->execute();

        $total_awarded += $points;

    } else {
        // Essay question (manual grading)
        $is_correct = 0;

        $stmtEssay->bind_param(
            'iiisi',
            $exam_id,
            $student_id,
            $qid,
            $ans,
            $is_correct
        );
        $stmtEssay->execute();
    }
}

/* -------------------------------------------------
   5. CALCULATE MAX POINTS
------------------------------------------------- */
$maxpStmt = $conn->prepare("
    SELECT COALESCE(SUM(points),0) AS maxp
    FROM questions
    WHERE exam_id=?
");
$maxpStmt->bind_param('i', $exam_id);
$maxpStmt->execute();
$maxp = (float)$maxpStmt->get_result()->fetch_assoc()['maxp'];

/* -------------------------------------------------
   6. UPDATE SUBMISSION
------------------------------------------------- */
$completed = date('Y-m-d H:i:s');

$stmt2 = $conn->prepare("
    UPDATE exam_submissions
    SET completed_at=?, status='submitted', total_points=?, max_points=?
    WHERE exam_id=? AND student_id=?
");
$stmt2->bind_param(
    'sddii',
    $completed,
    $total_awarded,
    $maxp,
    $exam_id,
    $student_id
);
$stmt2->execute();

/* -------------------------------------------------
   7. REDIRECT
------------------------------------------------- */
header("Location: results.php?exam_id=$exam_id");
exit;
