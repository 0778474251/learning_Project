<?php
// public/admin/manage_students.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
guard_admin();

$action = $_GET['action'] ?? '';
$msg = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $student_id = trim($_POST['student_id']); // now generated via form
        $name       = trim($_POST['name']);
        $email      = trim($_POST['email']);
        $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (student_id, name, email, role, password) VALUES (?, ?, ?, 'student', ?)");
        $stmt->bind_param('ssss', $student_id, $name, $email, $password);
        if ($stmt->execute()) $msg = "Student added successfully! ID: $student_id";
        else $msg = "Error: " . $conn->error;
    }

    if ($action === 'edit') {
        $id    = $_POST['id'];
        $name  = trim($_POST['name']);
        $email = trim($_POST['email']);

        $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=? AND role='student'");
        $stmt->bind_param('ssi', $name, $email, $id);
        if ($stmt->execute()) $msg = "Student updated successfully!";
        else $msg = "Error: " . $conn->error;
    }

    if ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='student'");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) $msg = "Student deleted successfully!";
        else $msg = "Error: " . $conn->error;
    }
}

// Fetch all students
$students = $conn->query("SELECT * FROM users WHERE role='student' ORDER BY id DESC");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manage Students</title>
  <link rel="stylesheet" href="/exam_system/assets/css/styles.css">
  <style>
    .id-generator { display:flex; gap:10px; align-items:center; margin-top:10px; }
    .id-generator input { flex:1; }
    .btn-small { padding:6px 12px; background:#3498db; color:#fff; border:none; border-radius:4px; cursor:pointer; }
    .btn-small:hover { background:#2980b9; }
  </style>
</head>
<body>
<div class="nav">
  <a href="dashboard.php">Dashboard</a>
  <a href="manage_students.php?action=add">Add Student</a>
  <a href="manage_students.php">View Students</a>
  <a class="right" href="/exam_system/public/logout.php">Logout</a>
</div>

<div class="card">
  <h2>Manage Students</h2>
  <?php if ($msg): ?><div class="alert"><?= sanitize($msg) ?></div><?php endif; ?>

  <?php if ($action === 'add'): ?>
    <form method="post">
      <label>Prefix</label>
      <input type="text" id="prefix" placeholder="e.g. STU" required>

      <label>Generated Student ID</label>
      <div class="id-generator">
        <input type="text" id="student_id" name="student_id" readonly required>
        <button type="button" class="btn-small" onclick="generateID()">Generate</button>
      </div>

      <label>Name</label>
      <input type="text" name="name" required>
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <button class="btn" type="submit">Add Student</button>
    </form>

    <script>
      function generateID() {
        const prefix = document.getElementById('prefix').value.trim();
        if (!prefix) {
          alert("Please enter a prefix first.");
          return;
        }
        // Simulate next ID by timestamp or random number (better: fetch last ID from DB via AJAX)
        const randomNum = Math.floor(Math.random() * 9999) + 1;
        const padded = String(randomNum).padStart(4, '0');
        document.getElementById('student_id').value = prefix + padded;
      }
    </script>

  <?php elseif ($action === 'edit' && isset($_GET['id'])): 
      $id = (int)$_GET['id'];
      $student = $conn->query("SELECT * FROM users WHERE id=$id AND role='student'")->fetch_assoc();
  ?>
    <form method="post">
      <input type="hidden" name="id" value="<?= $student['id'] ?>">
      <label>Name</label>
      <input type="text" name="name" value="<?= sanitize($student['name']) ?>" required>
      <label>Email</label>
      <input type="email" name="email" value="<?= sanitize($student['email']) ?>" required>
      <button class="btn" type="submit">Update Student</button>
    </form>

  <?php else: ?>
    <table>
      <tr><th>ID</th><th>Student ID</th><th>Name</th><th>Email</th><th>Actions</th></tr>
      <?php while ($s = $students->fetch_assoc()): ?>
        <tr>
          <td><?= $s['id'] ?></td>
          <td><?= sanitize($s['student_id']) ?></td>
          <td><?= sanitize($s['name']) ?></td>
          <td><?= sanitize($s['email']) ?></td>
          <td>
            <a class="btn" href="manage_students.php?action=edit&id=<?= $s['id'] ?>">Edit</a>
            <form method="post" action="manage_students.php?action=delete" style="display:inline;">
              <input type="hidden" name="id" value="<?= $s['id'] ?>">
              <button class="btn danger" type="submit" onclick="return confirm('Delete this student?')">Delete</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  <?php endif; ?>
</div>
</body>
</html>
