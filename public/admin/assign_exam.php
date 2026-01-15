<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';

guard_admin();
$msg = '';

/* ==========================
   AUTO CLEANUP
========================== */
$conn->query("
    DELETE ea FROM exam_assignments ea
    INNER JOIN exam_submissions es
        ON es.exam_id = ea.exam_id
        AND es.student_id = ea.student_id
    WHERE es.status = 'submitted'
");

/* ==========================
   HANDLE ACTIONS
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ADD */
    if ($_POST['action'] === 'add') {
        $exam_id = (int)$_POST['exam_id'];
        $student_id = $_POST['student_id'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        if ($student_id === 'ALL') {
            $students = $conn->query("SELECT id FROM users WHERE role='student'");
            $count = 0;

            while ($s = $students->fetch_assoc()) {
                $sid = (int)$s['id'];
                $check = $conn->prepare("SELECT id FROM exam_assignments WHERE exam_id=? AND student_id=?");
                $check->bind_param('ii', $exam_id, $sid);
                $check->execute();
                if (!$check->get_result()->fetch_assoc()) {
                    $stmt = $conn->prepare("
                        INSERT INTO exam_assignments (exam_id, student_id, start_time, end_time)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->bind_param('iiss', $exam_id, $sid, $start_time, $end_time);
                    if ($stmt->execute()) $count++;
                }
            }
            $msg = "Assigned exam to ALL students ($count new).";
        } else {
            $student_id = (int)$student_id;
            $stmt = $conn->prepare("
                INSERT INTO exam_assignments (exam_id, student_id, start_time, end_time)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param('iiss', $exam_id, $student_id, $start_time, $end_time);
            $msg = $stmt->execute() ? "Exam assigned successfully." : "Assignment failed.";
        }
    }

    /* UPDATE */
    if ($_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $exam_id = (int)$_POST['exam_id'];
        $student_id = (int)$_POST['student_id'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        $stmt = $conn->prepare("
            UPDATE exam_assignments
            SET exam_id=?, student_id=?, start_time=?, end_time=?
            WHERE id=?
        ");
        $stmt->bind_param('iissi', $exam_id, $student_id, $start_time, $end_time, $id);
        $msg = $stmt->execute() ? "Assignment updated." : "Update failed.";
    }

    /* DELETE */
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM exam_assignments WHERE id=?");
        $stmt->bind_param('i', $id);
        $msg = $stmt->execute() ? "Assignment deleted." : "Delete failed.";
    }
}

/* ==========================
   FETCH DATA
========================== */
$exams = $conn->query("SELECT id,title FROM exams ORDER BY id DESC");
$students = $conn->query("SELECT id,name FROM users WHERE role='student' ORDER BY name");
$assigned = $conn->query("
    SELECT ea.*, e.title, u.name AS student_name
    FROM exam_assignments ea
    JOIN exams e ON e.id = ea.exam_id
    JOIN users u ON u.id = ea.student_id
    ORDER BY ea.id DESC
");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Assign Exam</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
/* Base */
body { font-family:'Inter',sans-serif; margin:0; background:#f4f7fa; color:#2c3e50; }
a { text-decoration:none; color:inherit; }
button { cursor:pointer; border:none; border-radius:5px; }

/* Navbar */
.nav { background:#2c3e50; color:#fff; padding:15px 25px; display:flex; align-items:center; }
.nav a { color:#fff; font-weight:600; }

/* Card */
.card { background:#fff; border-radius:12px; padding:25px; margin:25px auto; max-width:1000px; box-shadow:0 6px 18px rgba(0,0,0,.08); }
.card h2,h3 { margin-bottom:15px; }

/* Alerts */
.alert { padding:12px 20px; border-radius:8px; margin-bottom:20px; background:#e8f5e9; color:#2e7d32; }

/* Form */
form { display:flex; flex-wrap:wrap; gap:15px; align-items:flex-end; }
form label { flex-basis:100%; font-weight:600; }
form select,input { padding:8px 12px; border-radius:6px; border:1px solid #ccc; }
form button { background:#3498db; color:#fff; padding:10px 16px; font-weight:600; }

/* Table */
table { width:100%; border-collapse:collapse; margin-top:20px; }
th { background:#2a5298; color:#fff; padding:12px; text-align:left; }
td { padding:12px; border-bottom:1px solid #ddd; vertical-align:middle; }
.actions { display:flex; gap:6px; }

/* Buttons */
.btn.edit { background:#f39c12; color:#fff; padding:6px 10px; }
.btn.update { background:#3498db; color:#fff; padding:6px 10px; }
.btn.delete { background:#e74c3c; color:#fff; padding:6px 10px; }

/* Badges */
.badge { padding:4px 10px; border-radius:12px; font-size:13px; font-weight:600; }
.exam { background:#e8f5e9; color:#2e7d32; }
.student { background:#e3f2fd; color:#1565c0; }

/* Edit Row */
.edit-row { display:none; background:#f9f9f9; }

/* Responsive */
@media(max-width:700px){
    form { flex-direction:column; }
    .actions { flex-direction:column; }
}
</style>
<script>
function editRow(id){
    document.getElementById('view-'+id).style.display='none';
    document.getElementById('edit-'+id).style.display='table-row';
}
function cancelEdit(id){
    document.getElementById('edit-'+id).style.display='none';
    document.getElementById('view-'+id).style.display='table-row';
}
function confirmDelete(){
    return confirm("Are you sure you want to delete this assignment?");
}
</script>
</head>
<body>

<div class="nav">
<a href="dashboard.php">⬅ Dashboard</a>
</div>

<div class="card">
<h2>Assign Exam</h2>
<?php if ($msg): ?><div class="alert"><?= sanitize($msg) ?></div><?php endif; ?>

<form method="post">
<input type="hidden" name="action" value="add">

<label>Exam</label>
<select name="exam_id" required>
<?php while ($e = $exams->fetch_assoc()): ?>
<option value="<?= $e['id'] ?>"><?= sanitize($e['title']) ?></option>
<?php endwhile; ?>
</select>

<label>Student</label>
<select name="student_id" required>
<option value="ALL">All Students</option>
<?php while ($s = $students->fetch_assoc()): ?>
<option value="<?= $s['id'] ?>"><?= sanitize($s['name']) ?></option>
<?php endwhile; ?>
</select>

<label>Start Time</label>
<input type="datetime-local" name="start_time" required>

<label>End Time</label>
<input type="datetime-local" name="end_time" required>

<button type="submit">Assign</button>
</form>
</div>

<div class="card">
<h3>📋 Existing Assignments</h3>

<table>
<tr>
<th>Exam</th>
<th>Student</th>
<th>Schedule</th>
<th>Actions</th>
</tr>

<?php while ($a = $assigned->fetch_assoc()): ?>
<tr id="view-<?= $a['id'] ?>">
<td><span class="badge exam"><?= sanitize($a['title']) ?></span></td>
<td><span class="badge student"><?= sanitize($a['student_name']) ?></span></td>
<td><?= $a['start_time'] ?> → <?= $a['end_time'] ?></td>
<td class="actions">
<button class="btn edit" onclick="editRow(<?= $a['id'] ?>)">Edit</button>
<form method="post" onsubmit="return confirmDelete();">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?= $a['id'] ?>">
<button class="btn delete">Delete</button>
</form>
</td>
</tr>

<tr id="edit-<?= $a['id'] ?>" class="edit-row">
<td colspan="4">
<form method="post" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
<input type="hidden" name="action" value="edit">
<input type="hidden" name="id" value="<?= $a['id'] ?>">

<select name="exam_id" required>
<?php
$ex2 = $conn->query("SELECT id,title FROM exams");
while ($e2 = $ex2->fetch_assoc()):
?>
<option value="<?= $e2['id'] ?>" <?= $e2['id']==$a['exam_id']?'selected':'' ?>>
<?= sanitize($e2['title']) ?>
</option>
<?php endwhile; ?>
</select>

<select name="student_id" required>
<?php
$st2 = $conn->query("SELECT id,name FROM users WHERE role='student'");
while ($s2 = $st2->fetch_assoc()):
?>
<option value="<?= $s2['id'] ?>" <?= $s2['id']==$a['student_id']?'selected':'' ?>>
<?= sanitize($s2['name']) ?>
</option>
<?php endwhile; ?>
</select>

<input type="datetime-local" name="start_time" value="<?= date('Y-m-d\TH:i', strtotime($a['start_time'])) ?>" required>
<input type="datetime-local" name="end_time" value="<?= date('Y-m-d\TH:i', strtotime($a['end_time'])) ?>" required>

<button class="btn update">Update</button>
<button type="button" class="btn" onclick="cancelEdit(<?= $a['id'] ?>)">Cancel</button>
</form>
</td>
</tr>
<?php endwhile; ?>

</table>
</div>

</body>
</html>
