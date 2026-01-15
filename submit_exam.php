<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
guard_student();

$student_id = (int)$_SESSION['student_id'];
$exam_id    = (int)$_POST['exam_id'];
$answers    = $_POST['answer'] ?? [];

/* =======================
   PREVENT RESUBMIT
======================= */
$stmt = $conn->prepare("
    SELECT * FROM exam_submissions
    WHERE exam_id=? AND student_id=? AND status='submitted'
");
$stmt->bind_param("ii", $exam_id, $student_id);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    header("Location: results.php?exam_id=$exam_id");
    exit;
}

/* =======================
   SAVE ANSWERS + GRADE
======================= */
$total = 0;
$score = 0;

foreach ($answers as $qid => $ans) {
    $qid = (int)$qid;

    $stmt = $conn->prepare("SELECT * FROM questions WHERE id=?");
    $stmt->bind_param("i", $qid);
    $stmt->execute();
    $q = $stmt->get_result()->fetch_assoc();
    if (!$q) continue;

    $total += $q['points'];
    $is_correct = null;

    if ($q['type'] !== 'essay') {
        if ($ans === $q['correct_answer']) {
            $score += $q['points'];
            $is_correct = 1;
        } else {
            $is_correct = 0;
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO student_answers
        (student_id, exam_id, question_id, answer, is_correct)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "iiisi",
        $student_id,
        $exam_id,
        $qid,
        $ans,
        $is_correct
    );
    $stmt->execute();
}

/* =======================
   FINALIZE SUBMISSION
======================= */
$stmt = $conn->prepare("
    UPDATE exam_submissions
    SET submitted_at=NOW(),
        status='submitted',
        score=?,
        total=?
    WHERE exam_id=? AND student_id=?
");
$stmt->bind_param("ddii", $score, $total, $exam_id, $student_id);
$stmt->execute();

header("Location: results.php?exam_id=$exam_id");
exit;
