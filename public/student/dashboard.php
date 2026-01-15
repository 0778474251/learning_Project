<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
guard_student();

$student_id = (int)$_SESSION['student_id'];

$stmt = $conn->prepare("
    SELECT e.id, e.title, e.type, e.start_time, e.end_time, ea.assigned_at
    FROM exam_assignments ea
    JOIN exams e ON e.id = ea.exam_id
    WHERE ea.student_id = ?
    ORDER BY e.start_time ASC
");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$assigned = $stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Student Dashboard</title>
<link rel="stylesheet" href="/exam_system/assets/css/styles.css">

<style>
body { font-family:"Segoe UI", Tahoma, sans-serif; background:#f4f6f9; margin:0; }
.nav { background:linear-gradient(90deg,#3498db,#2980b9); padding:15px 25px; display:flex; }
.nav a { color:#fff; font-weight:bold; margin-right:20px; }
.nav a.right { margin-left:auto; }

.card {
    background:#fff;
    padding:25px;
    max-width:1000px;
    margin:30px auto;
    border-radius:10px;
    box-shadow:0 6px 18px rgba(0,0,0,0.1);
}

table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { padding:12px; border-bottom:1px solid #ddd; text-align:center; }
th { background:#3498db; color:#fff; }

.btn {
    background:#2ecc71;
    color:#fff;
    padding:8px 16px;
    border-radius:6px;
    font-weight:bold;
    text-decoration:none;
}
.btn.disabled {
    background:#aaa;
    cursor:not-allowed;
    pointer-events:none;
}

.status-msg {
    padding:6px 12px;
    border-radius:6px;
    font-weight:bold;
    font-size:13px;
}
.status-completed { background:#2ecc71; color:#fff; }
.status-expired { background:#e74c3c; color:#fff; }
.status-upcoming { background:#f39c12; color:#fff; }
</style>
</head>
<body>

<div class="nav">
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="exams.php">📝 My Exams</a>
  <a class="right" href="/exam_system/public/logout.php">🚪 Logout</a>
</div>

<div class="card">
<h2>Assigned Exams</h2>

<table>
<tr>
    <th>Title</th>
    <th>Type</th>
    <th>Exam Window</th>
    <th>Assigned</th>
    <th>Action</th>
</tr>

<?php if ($assigned->num_rows === 0): ?>
<tr><td colspan="5">No exams assigned.</td></tr>
<?php endif; ?>

<?php while ($e = $assigned->fetch_assoc()): ?>
<?php
    $now        = new DateTime();
    $start_time = new DateTime($e['start_time']);
    $end_time   = new DateTime($e['end_time']);

    // ✅ Calculate REAL duration from window
    $durationMinutes = round(($end_time->getTimestamp() - $start_time->getTimestamp()) / 60);

    // Check submission
    $check = $conn->prepare("
        SELECT id FROM exam_submissions
        WHERE exam_id=? AND student_id=? AND status='submitted'
    ");
    $check->bind_param('ii', $e['id'], $student_id);
    $check->execute();
    $submitted = $check->get_result()->fetch_assoc();

    if ($submitted) {
        $status = '<span class="status-msg status-completed">Completed</span>';
        $action = '<span class="btn disabled">Completed</span>';
    } elseif ($now > $end_time) {
        $status = '<span class="status-msg status-expired">Expired</span>';
        $action = '<span class="btn disabled">Expired</span>';
    } elseif ($now < $start_time) {
        $status = '<span class="status-msg status-upcoming">Not Started</span>';
        $action = '<span class="btn disabled start-btn"
                    data-start="'.$start_time->format('Y-m-d H:i:s').'"
                    data-end="'.$end_time->format('Y-m-d H:i:s').'"
                    data-examid="'.$e['id'].'">Starts Soon</span>';
    } else {
        $status = '';
        $action = '<a class="btn" href="take_exam.php?exam_id='.$e['id'].'">Start Exam</a>';
    }
?>
<tr>
    <td><?= sanitize($e['title']) ?></td>
    <td><?= strtoupper(sanitize($e['type'])) ?></td>
    <td>
        <?= $start_time->format('Y-m-d H:i') ?> → <?= $end_time->format('Y-m-d H:i') ?>
        <br><small><strong>Duration:</strong> <?= $durationMinutes ?> minutes</small>
    </td>
    <td><?= sanitize($e['assigned_at']) ?></td>
    <td><?= $status ?><br><?= $action ?></td>
</tr>
<?php endwhile; ?>

</table>
</div>

<script>
function updateStartButtons() {
    const now = new Date();
    document.querySelectorAll('.start-btn').forEach(btn => {
        const start = new Date(btn.dataset.start);
        const end   = new Date(btn.dataset.end);
        const examId = btn.dataset.examid;

        if (now >= start && now <= end) {
            const link = document.createElement('a');
            link.href = 'take_exam.php?exam_id=' + examId;
            link.className = 'btn';
            link.textContent = 'Start Exam';
            btn.replaceWith(link);
        } else if (now > end) {
            btn.textContent = 'Expired';
            btn.classList.add('disabled');
        }
    });
}
setInterval(updateStartButtons, 5000);
updateStartButtons();
</script>

</body>
</html>
