<?php // public/index.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Exam System</title>
  <link rel="stylesheet" href="/exam_system/assets/css/styles.css">
  <style>
    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: #f4f6f9;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 600px;
      margin: 80px auto;
      background: #fff;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      text-align: center;
    }
    h1 {
      margin-bottom: 20px;
      color: #2c3e50;
    }
    p {
      font-size: 1.1em;
      margin-bottom: 30px;
      color: #555;
    }
    .btn {
      display: inline-block;
      margin: 10px;
      padding: 12px 25px;
      font-size: 1em;
      border-radius: 6px;
      text-decoration: none;
      transition: all 0.3s ease;
      background: #3498db;
      color: #fff;
    }
    .btn:hover {
      background: #2980b9;
    }
    .btn-secondary {
      background: #95a5a6;
    }
    .btn-secondary:hover {
      background: #7f8c8d;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Welcome to the Exam System</h1>
    <?php if ($user && isset($user['id'])): ?>
      <p>Hello, <strong><?= sanitize($user['name']) ?></strong> (<?= sanitize($user['role']) ?>)</p>
      <?php if (is_admin()): ?>
        <a class="btn" href="/exam_system/public/admin/dashboard.php">Go to Admin Dashboard</a>
      <?php else: ?>
        <a class="btn" href="/exam_system/public/student/dashboard.php">Go to Student Dashboard</a>
      <?php endif; ?>
      <a class="btn btn-secondary" href="/exam_system/public/logout.php">Logout</a>
    <?php else: ?>
      <p>Please log in to access your dashboard.</p>
      <a class="btn" href="/exam_system/public/login.php">Student Login</a>
    <?php endif; ?>
  </div>
</body>
</html>
