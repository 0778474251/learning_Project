<?php
// Use absolute paths to avoid "failed to open stream" errors
require_once 'C:/xampp/htdocs/exam_system/config/db.php';
require_once 'C:/xampp/htdocs/exam_system/includes/helpers.php';
require_once 'C:/xampp/htdocs/exam_system/includes/auth.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $msg = "Passwords do not match!";
    } else {
        // Hash password securely
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Insert into DB (student_id auto-increment, role = admin)
        $stmt = $conn->prepare("INSERT INTO users (name, role, email, password) VALUES (?, 'admin', ?, ?)");
        $stmt->bind_param('sss', $name, $email, $hashed);

        if ($stmt->execute()) {
            $msg = "Admin registration successful! You can now login.";
        } else {
            $msg = "Error: " . $conn->error;
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Registration</title>
  <link rel="stylesheet" href="/exam_system/assets/css/styles.css">
</head>
<body>
<div class="card">
  <h2>Admin Registration</h2>
  <?php if ($msg): ?><div class="alert"><?= sanitize($msg) ?></div><?php endif; ?>
  <form method="post">
    <label>Full Name</label>
    <input type="text" name="name" required>

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <label>Confirm Password</label>
    <input type="password" name="confirm_password" required>

    <button class="btn" type="submit">Register</button>
    <a class="link" href="/exam_system/public/admin/login.php">Already registered? Login</a>
  </form>
</div>
</body>
</html>
