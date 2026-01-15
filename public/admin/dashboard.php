<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
guard_admin();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

/* ==========================
   UNASSIGN EXAM LOGIC
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $examId = (int)$_POST['exam_id'];
        if (isset($_POST['student_id'])) {
            $studentId = (int)$_POST['student_id'];
            $stmt = $conn->prepare("DELETE FROM exam_assignments WHERE exam_id = ? AND student_id = ?");
            $stmt->bind_param("ii", $examId, $studentId);
            $stmt->execute();
            $successMessage = "Student successfully unassigned from the exam.";
        } else {
            $stmt = $conn->prepare("DELETE FROM exam_assignments WHERE exam_id = ?");
            $stmt->bind_param("i", $examId);
            $stmt->execute();
            $successMessage = "Exam successfully unassigned from all students.";
        }
    } catch (mysqli_sql_exception $e) {
        $errorMessage = "Failed to unassign.";
    }
}

/* ==========================
   STATS
========================== */
$exams_count = $conn->query("SELECT COUNT(*) c FROM exams")->fetch_assoc()['c'];
$students_count = $conn->query("SELECT COUNT(*) c FROM users WHERE role='student'")->fetch_assoc()['c'];
$assignments_count = $conn->query("SELECT COUNT(*) c FROM exam_assignments")->fetch_assoc()['c'];

$subject_breakdown = $conn->query("
    SELECT e.id, e.title, COUNT(ea.student_id) AS student_count
    FROM exams e
    LEFT JOIN exam_assignments ea ON ea.exam_id = e.id
    GROUP BY e.id
    ORDER BY e.title
");

$recent_exams = $conn->query("
    SELECT id, title, type, start_time, end_time
    FROM exams
    ORDER BY id DESC
    LIMIT 5
");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
/* Reset & Base */
* {margin:0;padding:0;box-sizing:border-box;}
body {font-family:'Inter',sans-serif; background:#f4f7fa; color:#2c3e50;}
a {text-decoration:none;color:inherit;}
button {cursor:pointer;}

/* Sidebar */
.sidebar {
    position: fixed;
    left: 0; top: 0; bottom: 0;
    width: 220px;
    background:#2c3e50;
    color:#fff;
    display:flex;
    flex-direction:column;
}
.sidebar h2 {text-align:center; padding:20px 0; font-size:20px;}
.sidebar nav {flex:1;}
.sidebar nav a {
    display:flex; align-items:center; gap:10px;
    padding:15px 20px; color:#fff; font-weight:500;
    transition:0.2s; border-left:3px solid transparent;
}
.sidebar nav a:hover {background:#1a2736; border-left:3px solid #3498db;}

/* Main Content */
.main {margin-left:220px; padding:25px;}
.header {display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;}
.header h1 {font-size:24px;}
.alert {padding:12px; border-radius:8px; margin-bottom:20px;}
.alert.success {background:#e8f5e9; color:#2e7d32;}
.alert.error {background:#ffebee; color:#c62828;}

/* KPI Cards */
.kpi {display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:20px; margin-bottom:30px;}
.kpi-card {background:#fff; padding:20px; border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,.08);}
.kpi-card h3 {font-size:14px; color:#7f8c8d; margin-bottom:5px;}
.kpi-card span {font-size:28px; font-weight:700;}

/* Exam Cards */
.exam-card {background:#fff; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 6px 18px rgba(0,0,0,.08);}
.exam-header {display:flex; justify-content:space-between; align-items:center; cursor:pointer;}
.exam-header h4 {font-size:16px;}
.exam-content {margin-top:15px; display:none;}
.exam-content.active {display:block;}
.progress {background:#e0e0e0; border-radius:5px; height:10px; margin:5px 0;}
.progress span {display:block; height:10px; border-radius:5px; background:linear-gradient(90deg,#4caf50,#8bc34a);}

/* Student List */
.student-list {max-height:150px; overflow-y:auto; padding-left:20px; margin-top:5px;}
.student-list li {margin-bottom:5px; display:flex; justify-content:space-between; align-items:center;}
.student-list button {padding:2px 6px; font-size:12px; background:#f39c12; color:#fff; border:none; border-radius:4px;}

/* Recent Exams Table */
table {width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,.08);}
th, td {padding:12px; text-align:left;}
th {background:#2a5298; color:#fff;}
tr:nth-child(even) td {background:#f9f9f9;}
td button {padding:5px 10px; border:none; border-radius:5px; margin-right:5px;}

/* Responsive */
@media(max-width:900px){
    .sidebar {position:relative;width:100%;height:auto;}
    .main {margin-left:0;}
    .kpi {grid-template-columns:1fr 1fr;}
}
@media(max-width:600px){
    .kpi {grid-template-columns:1fr;}
}
</style>
<script>
function toggleExam(id){
    const el = document.getElementById('exam-'+id);
    el.classList.toggle('active');
}
function confirmUnassignAll(){
    return confirm("Unassign this exam from ALL students?");
}
function confirmUnassignStudent(name){
    return confirm("Unassign " + name + " from this exam?");
}
</script>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <nav>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="create_exam.php">➕ Create Exam</a>
        <a href="assign_exam.php">📌 Assign Exam</a>
        <a href="view_results.php">📊 Results</a>
        <a href="/exam_system/public/logout.php">🚪 Logout</a>
    </nav>
</div>

<!-- Main Content -->
<div class="main">
<div class="header"><h1>Dashboard</h1></div>

<!-- Alerts -->
<?php if(!empty($successMessage)): ?>
<div class="alert success"><?= sanitize($successMessage) ?></div>
<?php endif; ?>
<?php if(!empty($errorMessage)): ?>
<div class="alert error"><?= sanitize($errorMessage) ?></div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="kpi">
    <div class="kpi-card">
        <h3>Total Exams</h3>
        <span><?= $exams_count ?></span>
    </div>
    <div class="kpi-card">
        <h3>Total Students</h3>
        <span><?= $students_count ?></span>
    </div>
    <div class="kpi-card">
        <h3>Total Assignments</h3>
        <span><?= $assignments_count ?></span>
    </div>
</div>

<!-- Exam Cards -->
<?php while($row = $subject_breakdown->fetch_assoc()):
    $percent = ($students_count>0)? round(($row['student_count']/$students_count)*100):0;
    $students = $conn->query("SELECT u.id,u.name FROM users u JOIN exam_assignments ea ON u.id=ea.student_id WHERE ea.exam_id=".(int)$row['id']);
?>
<div class="exam-card">
    <div class="exam-header" onclick="toggleExam(<?= $row['id'] ?>)">
        <h4><?= sanitize($row['title']) ?> (<?= $row['student_count'] ?> students)</h4>
        <span>▼</span>
    </div>
    <div class="exam-content" id="exam-<?= $row['id'] ?>">
        <div class="progress"><span style="width:<?= $percent ?>%"></span></div>
        <small><?= $percent ?>% of students assigned</small>

        <ul class="student-list">
        <?php while($student = $students->fetch_assoc()): ?>
            <li>
                <?= sanitize($student['name']) ?>
                <form method="POST" style="display:inline;" onsubmit="return confirmUnassignStudent('<?= sanitize($student['name']) ?>')">
                    <input type="hidden" name="exam_id" value="<?= (int)$row['id'] ?>">
                    <input type="hidden" name="student_id" value="<?= (int)$student['id'] ?>">
                    <button name="unassign_exam">Unassign</button>
                </form>
            </li>
        <?php endwhile; ?>
        </ul>

        <form method="POST" onsubmit="return confirmUnassignAll();">
            <input type="hidden" name="exam_id" value="<?= (int)$row['id'] ?>">
            <button style="margin-top:10px;background:#e74c3c;color:#fff;padding:6px 12px;border:none;border-radius:5px;">Unassign All</button>
        </form>
    </div>
</div>
<?php endwhile; ?>

<!-- Recent Exams -->
<div style="margin-top:30px;">
<h2>📘 Recent Exams</h2>
<table>
<tr><th>Title</th><th>Type</th><th>Actions</th></tr>
<?php while($exam=$recent_exams->fetch_assoc()): ?>
<tr>
    <td><?= sanitize($exam['title']) ?></td>
    <td><?= sanitize($exam['type']) ?></td>
    <td>
        <a href="edit_exam.php?exam_id=<?= $exam['id'] ?>"><button style="background:#f39c12;color:#fff;">Edit</button></a>
        <a href="view_questions.php?exam_id=<?= $exam['id'] ?>"><button style="background:#3498db;color:#fff;">Questions</button></a>
    </td>
</tr>
<?php endwhile; ?>
</table>
</div>

</div>
</body>
</html>
