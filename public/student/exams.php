<?php // public/student/exams.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
guard_student();

$student_id = $_SESSION['student_id'];
$subs = $conn->query("
SELECT es.*, e.title
FROM exam_submissions es
JOIN exams e ON e.id=es.exam_id
WHERE es.student_id='$student_id'
ORDER BY es.started_at DESC
");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Exams</title>
  <link rel="stylesheet" href="/exam_system/assets/css/styles.css">
  <style>
    body { font-family: "Segoe UI", Tahoma, sans-serif; background:#f4f6f9; margin:0; }
    .nav { background: linear-gradient(90deg,#2c3e50,#34495e); padding:15px; display:flex; justify-content:space-between; align-items:center; }
    .nav a { color:#fff; margin-right:20px; text-decoration:none; font-weight:bold; }
    .nav a:hover { text-decoration:underline; }
    .card { background:#fff; padding:25px; margin:30px auto; max-width:900px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
    h2 { margin-top:0; color:#2c3e50; font-size:24px; border-bottom:2px solid #3498db; padding-bottom:10px; }
    table { width:100%; border-collapse:collapse; margin-top:20px; }
    th, td { border:1px solid #ddd; padding:12px; text-align:center; }
    th { background:#3498db; color:#fff; font-size:16px; }
    tr:nth-child(even) { background:#f9f9f9; }
    tr:hover { background:#ecf0f1; }
    .status-badge { padding:6px 12px; border-radius:20px; font-weight:bold; font-size:14px; }
    .status-pending { background:#f39c12; color:#fff; }
    .status-submitted { background:#2ecc71; color:#fff; }
    .status-expired { background:#e74c3c; color:#fff; }
    .score { font-weight:bold; color:#2c3e50; }
  </style>
</head>
<body>
<div class="nav">
  <div>
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="exams.php">📝 My Exams</a>
    <a href="results.php">📊 Results</a>
  </div>
  <a class="right" href="/exam_system/public/logout.php">🚪 Logout</a>
</div>

<div class="card">
  <h2>📘 Exam History</h2>
  <table>
    <tr>
      <th>Exam</th>
      <th>Status</th>
      <th>Total / Max</th>
      <th>Started</th>
      <th>Completed</th>
    </tr>
    <?php if ($subs->num_rows === 0): ?>
      <tr><td colspan="5">No exam history available.</td></tr>
    <?php endif; ?>
    <?php while ($s = $subs->fetch_assoc()): ?>
      <?php
        // Style status badge
        $statusClass = '';
        if ($s['status'] === 'submitted') $statusClass = 'status-submitted';
        elseif ($s['status'] === 'expired') $statusClass = 'status-expired';
        else $statusClass = 'status-pending';
      ?>
      <tr>
        <td><?= sanitize($s['title']) ?></td>
        <td><span class="status-badge <?= $statusClass ?>"><?= ucfirst(sanitize($s['status'])) ?></span></td>
        <td class="score"><?= sanitize($s['total_points']) ?> / <?= sanitize($s['max_points']) ?></td>
        <td><?= sanitize($s['started_at']) ?></td>
        <td><?= sanitize($s['completed_at']) ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
</div>
</body>
</html>
