<?php
// public/admin/create_exam.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/helpers.php';

guard_admin();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title']);
    $type       = $_POST['type'];
    $created_by = (int)$_SESSION['user_id'];

    if ($title === '' || $type === '') {
        $msg = "❌ All fields are required.";
    } else {
        // Check if exam (subject) already exists
        $check = $conn->prepare("SELECT id FROM exams WHERE title = ?");
        $check->bind_param('s', $title);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();

        if ($existing) {
            $exam_id = (int)$existing['id'];
            $msg = "⚠️ Subject '{$title}' already exists. Redirecting to add questions.";
            redirect("add_question.php?exam_id=$exam_id");
        } else {
            $stmt = $conn->prepare("
                INSERT INTO exams (title, type, created_by)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param('ssi', $title, $type, $created_by);

            if ($stmt->execute()) {
                $exam_id = $stmt->insert_id;
                $msg = "✅ Subject '{$title}' created successfully. Add questions now.";
                redirect("add_question.php?exam_id=$exam_id");
            } else {
                $msg = "❌ Failed to create subject.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Create Exam Subject</title>
<link rel="stylesheet" href="/exam_system/assets/css/styles.css">
<style>
/* ===== GLOBAL STYLES ===== */
body {
    margin: 0;
    font-family: "Segoe UI", Tahoma, sans-serif;
    background: #f1f3f6;
    color: #2c3e50;
}

/* ===== NAVBAR ===== */
.navbar {
    background: linear-gradient(90deg,#1e3c72,#2a5298);
    padding: 16px 30px;
}
.navbar a {
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    margin-right: 20px;
}
.navbar a:hover {
    text-decoration: underline;
}

/* ===== CARD ===== */
.container {
    max-width: 600px;
    margin: 40px auto;
    padding: 20px;
}
.card {
    background: #fff;
    padding: 30px 35px;
    border-radius: 14px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.12);
}
.card h2 {
    margin-top: 0;
    color: #2c3e50;
    font-size: 26px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ===== FORM ===== */
form {
    margin-top: 20px;
}
label {
    display: block;
    font-weight: 600;
    margin-top: 18px;
    color: #34495e;
}
input[type="text"], select {
    width: 100%;
    padding: 12px 14px;
    margin-top: 6px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    transition: border 0.2s;
}
input[type="text"]:focus, select:focus {
    border-color: #3498db;
    outline: none;
}

/* ===== BUTTON ===== */
.btn {
    display: inline-block;
    width: 100%;
    text-align: center;
    margin-top: 25px;
    padding: 14px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    background: #3498db;
    color: #fff;
    cursor: pointer;
    transition: background 0.3s, transform 0.2s;
}
.btn:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

/* ===== ALERT ===== */
.alert {
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert.success { background: #e8f5e9; color: #2e7d32; }
.alert.error   { background: #fdecea; color: #c0392b; }
.alert.warning { background: #fff3cd; color: #856404; }

/* ===== RESPONSIVE ===== */
@media(max-width: 650px){
    .container { margin: 20px; padding: 15px; }
    .card { padding: 25px; }
}
</style>
</head>

<body>

<!-- NAVBAR -->
<div class="navbar">
  <a href="dashboard.php">🏠 Dashboard</a>
</div>

<div class="container">
<div class="card">
<h2>📘 Create New Exam Subject</h2>

<?php if ($msg): ?>
    <?php
        $class = str_contains($msg, '✅') ? 'success' : (str_contains($msg, '❌') ? 'error' : 'warning');
    ?>
    <div class="alert <?= $class ?>">
        <?= sanitize($msg) ?>
    </div>
<?php endif; ?>

<form method="post">
    <label for="title">Subject Title</label>
    <input type="text" id="title" name="title" required placeholder="e.g. Mathematics, Physics">

    <label for="type">Exam Type</label>
    <select id="type" name="type" required>
        <option value="">-- Select Type --</option>
        <option value="mcq">Multiple Choice (MCQ)</option>
        <option value="true_false">True / False</option>
        <option value="essay">Essay</option>
        <option value="mixed">Mixed</option>
    </select>

    <button class="btn" type="submit">➕ Create Subject</button>
</form>
</div>
</div>

</body>
</html>
