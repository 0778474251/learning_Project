<?php
// exam.php
session_start();
$conn = new mysqli("localhost", "root", "", "exam_system");

$student_id = $_SESSION['student_id']; // after login
$exam_id = $_GET['exam_id'];

// Check if exam is assigned
$check = $conn->prepare("SELECT * FROM exam_assignments WHERE exam_id=? AND student_id=?");
$check->bind_param("is", $exam_id, $student_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows == 0) {
    die("You are not assigned to this exam.");
}

// Fetch questions
$q = $conn->prepare("SELECT * FROM questions WHERE exam_id=?");
$q->bind_param("i", $exam_id);
$q->execute();
$questions = $q->get_result();
?>

<form method="POST" action="submit_exam.php">
<?php while ($row = $questions->fetch_assoc()): ?>
    <p><b><?php echo $row['question_text']; ?></b></p>
    <?php if ($row['type'] == 'mcq'): 
        $options = json_decode($row['options'], true);
        foreach ($options as $opt): ?>
            <input type="radio" name="answer[<?php echo $row['id']; ?>]" value="<?php echo $opt; ?>"> <?php echo $opt; ?><br>
        <?php endforeach; ?>
    <?php elseif ($row['type'] == 'true_false'): ?>
        <input type="radio" name="answer[<?php echo $row['id']; ?>]" value="True"> True
        <input type="radio" name="answer[<?php echo $row['id']; ?>]" value="False"> False
    <?php else: ?>
        <textarea name="answer[<?php echo $row['id']; ?>]"></textarea>
    <?php endif; ?>
<?php endwhile; ?>
    <button type="submit">Submit Exam</button>
</form>
