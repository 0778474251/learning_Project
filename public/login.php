<?php
// public/login.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role     = $_POST['role'] ?? '';
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($role === 'student' || $role === 'admin') {
        // Prepare query by role
        $stmt = $conn->prepare("
            SELECT id, name, role, email, password 
            FROM users 
            WHERE role = ? AND email = ? 
            LIMIT 1
        ");
        $stmt->bind_param('ss', $role, $email);
        $stmt->execute();
        $res  = $stmt->get_result();
        $user = $res->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            // ✅ Store numeric id in session
            $_SESSION['user_id']    = (int)$user['id'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['student_id'] = ($user['role'] === 'student') ? (int)$user['id'] : null;

            if ($user['role'] === 'admin') {
                redirect('/exam_system/public/admin/dashboard.php');
            } else {
                redirect('/exam_system/public/student/dashboard.php');
            }
            exit;
        } else {
            $error = "Invalid credentials.";
        }
    } else {
        $error = "Please select a role.";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="/exam_system/assets/css/styles.css">
  <script>
    function toggleFields() {
      const role = document.getElementById('role').value;
      const emailField = document.getElementById('email-field');
      emailField.style.display = (role === 'student' || role === 'admin') ? 'block' : 'none';
    }
  </script>
</head>
<body onload="toggleFields()">
<div class="card">
  <h2>Login</h2>
  <?php if ($error): ?><div class="alert"><?= sanitize($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Role</label>
    <select name="role" id="role" onchange="toggleFields()" required>
      <option value="">Select role</option>
      <option value="student">Student</option>
      <option value="admin">Admin</option>
    </select>

    <div id="email-field" style="display:none;">
      <label>Email</label>
      <input type="email" name="email" required>
    </div>

    <label>Password</label>
    <input type="password" name="password" required>

    <button class="btn" type="submit">Login</button>
    <a class="link" href="/exam_system/public/index.php">Back</a>
  </form>
</div>
</body>
</html>
