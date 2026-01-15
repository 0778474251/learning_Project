<?php
// public/admin/view_results.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
guard_admin();

/* ===== ENABLE MYSQLI EXCEPTIONS ===== */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

/* ===== CLEAR SUBMITTED EXAMS (FIXED FOR YOUR SCHEMA) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_submitted'])) {

    $conn->begin_transaction();

    try {
        // 1. Delete student answers linked to submitted exams
        $conn->query("
            DELETE sa
            FROM student_answers sa
            INNER JOIN exam_submissions es
                ON sa.exam_id = es.exam_id
               AND sa.student_id = es.student_id
            WHERE es.status = 'submitted'
        ");

        // 2. Delete submitted exam submissions
        $conn->query("
            DELETE FROM exam_submissions
            WHERE status = 'submitted'
        ");

        $conn->commit();
        $successMessage = "All submitted exams and related answers were successfully deleted.";

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $errorMessage = "Database error: " . $e->getMessage();
    }
}

/* ===== FETCH RESULTS ===== */
$results = $conn->query("
    SELECT es.id, es.exam_id, es.student_id, es.status,
           es.total_points, es.max_points, es.completed_at,
           u.name AS student_name,
           e.title AS exam_title
    FROM exam_submissions es
    JOIN users u ON u.id = es.student_id
    JOIN exams e ON e.id = es.exam_id
    ORDER BY es.completed_at DESC
");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Exam Results</title>
<link rel="stylesheet" href="/exam_system/assets/css/styles.css">

<style>
body {
    margin: 0;
    font-family: "Segoe UI", Tahoma, sans-serif;
    background: #f4f6f9;
    color: #2c3e50;
}
a { text-decoration: none; color: inherit; }

.navbar {
    background: linear-gradient(90deg,#4caf50,#2e7d32);
    padding: 16px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.navbar a {
    color: #fff;
    font-weight: 600;
    margin-right: 20px;
}
.navbar a:hover { text-decoration: underline; }

.container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 20px;
}
.card {
    background: #fff;
    padding: 30px;
    border-radius: 14px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-bottom: 20px;
}
.actions button {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    color: #fff;
}
.print-btn { background: #2e7d32; }
.clear-btn { background: #d32f2f; }

table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 14px;
    text-align: center;
    border-bottom: 1px solid #e0e0e0;
}
th {
    background: #4caf50;
    color: #fff;
}
tr:nth-child(even) { background: #f9f9f9; }

.badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 13px;
    color: #fff;
}
.passed { background: #4caf50; }
.failed { background: #f44336; }
.inprogress { background: #ff9800; }

.score-bar {
    background: #ddd;
    border-radius: 6px;
    overflow: hidden;
    height: 10px;
    margin-top: 5px;
}
.score-fill {
    height: 10px;
    background: linear-gradient(90deg,#4caf50,#8bc34a);
}

@media print {
    .navbar, .actions { display: none; }
}
</style>

<script>
function printResults() {
    window.print();
}
function confirmClear() {
    return confirm(
        "⚠️ WARNING!\n\nThis will permanently delete ALL submitted exams and their answers.\n\nThis action CANNOT be undone.\n\nDo you want to continue?"
    );
}
</script>
</head>

<body>

<div class="navbar">
    <div>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="grade_essay.php">📝 Grade Essays</a>
    </div>
</div>

<div class="container">
<div class="card">

<?php if (!empty($successMessage)): ?>
<div style="background:#e8f5e9;color:#2e7d32;padding:12px;border-radius:8px;margin-bottom:15px;">
    <?= sanitize($successMessage) ?>
</div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
<div style="background:#ffebee;color:#c62828;padding:12px;border-radius:8px;margin-bottom:15px;">
    <?= sanitize($errorMessage) ?>
</div>
<?php endif; ?>

<div class="actions">
    <button class="print-btn" onclick="printResults()">🖨️ Print Results</button>

    <form method="POST" onsubmit="return confirmClear();">
        <button type="submit" name="clear_submitted" class="clear-btn">
            🧹 Clear Submitted Exams
        </button>
    </form>
</div>

<h2>Exam Results Overview</h2>

<table>
<thead>
<tr>
    <th>Exam</th>
    <th>Student</th>
    <th>Status</th>
    <th>Score</th>
    <th>Performance</th>
    <th>Completed</th>
</tr>
</thead>
<tbody>

<?php if ($results && $results->num_rows > 0): ?>
<?php while ($r = $results->fetch_assoc()):
    $percent = ($r['max_points'] > 0)
        ? round(($r['total_points'] / $r['max_points']) * 100)
        : 0;

    if ($r['status'] === 'submitted') {
        $badgeClass = $percent >= 50 ? 'passed' : 'failed';
        $badgeText = $percent >= 50 ? 'Passed' : 'Failed';
    } else {
        $badgeClass = 'inprogress';
        $badgeText = 'In Progress';
    }
?>
<tr>
    <td><?= sanitize($r['exam_title']) ?></td>
    <td><?= sanitize($r['student_name']) ?><br>
        <small>ID: <?= (int)$r['student_id'] ?></small>
    </td>
    <td><?= ucfirst(sanitize($r['status'])) ?></td>
    <td>
        <?= (int)$r['total_points'] ?> / <?= (int)$r['max_points'] ?> (<?= $percent ?>%)
        <div class="score-bar">
            <div class="score-fill" style="width:<?= $percent ?>%"></div>
        </div>
    </td>
    <td><span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span></td>
    <td><?= sanitize($r['completed_at']) ?></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6">No submissions found.</td></tr>
<?php endif; ?>

</tbody>
</table>

</div>
</div>

</body>
</html>
