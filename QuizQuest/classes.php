<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "quizmaker");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$user_id = (int)($_SESSION['user_id']);
$role = $_SESSION['role'] ?? 'User';

// Fetch full name from database
$full_name = 'User';
$stmtName = $mysqli->prepare("SELECT full_name FROM users WHERE id = ?");
$stmtName->bind_param("i", $user_id);
$stmtName->execute();
$resultName = $stmtName->get_result();
if ($resultName && $rowName = $resultName->fetch_assoc()) {
    $full_name = $rowName['full_name'];
}
$stmtName->close();

// --------------------
// HANDLE CREATE CLASS
// --------------------
if ($role === 'teacher' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['section'])) {
    $title = trim($_POST['title']);
    $section = trim($_POST['section']);
    // Generate a random 7-character uppercase class code
    $class_code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 7));

    $stmt = $mysqli->prepare("INSERT INTO classes (teacher_id, title, section, class_code) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $section, $class_code);
    if ($stmt->execute()) {
        header("Location: classes.php"); // refresh page to show new class
        exit;
    } else {
        $error = "Failed to create class. Please try again.";
    }
    $stmt->close();
}

// Fetch classes based on role
if ($role === 'teacher') {
    $stmt = $mysqli->prepare("SELECT id, title, section, class_code, created_at FROM classes WHERE teacher_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
} else { // student
    $stmt = $mysqli->prepare("
        SELECT c.id, c.title, c.section, c.class_code, c.created_at
        FROM classes c
        INNER JOIN student_classes sc ON sc.class_code = c.class_code
        WHERE sc.student_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$res = $stmt->get_result();
$classes = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Classes - QuizQuest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/teacher.css">
</head>
<body>
<canvas id="background-canvas"></canvas>

<div class="sidebar">
    <img src="assets/images/logo.png" class="logo-img" alt="QuizQuest">
    <div class="menu-wrapper">
        <div class="nav">
            <a class="nav-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>" href="profile.php">
                <i data-lucide="user"></i> Profile (<?= htmlspecialchars($full_name) ?>)
            </a>
            <a class="nav-item active" href="classes.php"><i data-lucide="layout"></i> Classes</a>
            <a class="nav-item" href="leaderboard.php"><i data-lucide="award"></i> Leaderboard</a>
        </div>
    </div>
    <a class="logout" href="logout.php"><i data-lucide="log-out"></i> Logout</a>
</div>

<div class="content">
    <div class="avatar-container">
        <span class="greeting">Hello! <?=htmlspecialchars($username)?></span>
        <img src="https://i.imgur.com/oQEsWSV.png" alt="avatar" class="freiren-avatar">
    </div>

    <h2 class="quizzes-title mb-4">Your Classes</h2>

    <div class="row g-4">
        <?php if($role === 'teacher'): ?>
            <!-- Create class card for teacher -->
            <div class="col-md-4 col-sm-6">
                <div class="card subject-card h-100 d-flex align-items-center justify-content-center" style="cursor:pointer; background:linear-gradient(135deg,#2563EB,#3B82F6);" data-bs-toggle="modal" data-bs-target="#createClassModal">
                    <h3 class="card-title text-center">+ Create Class</h3>
                </div>
            </div>
        <?php endif; ?>

        <!-- Class cards -->
        <?php if(!empty($classes)): ?>
            <?php foreach($classes as $class): ?>
            <div class="col-md-4 col-sm-6">
                <div class="card subject-card h-100" style="background: linear-gradient(135deg,#0ea5e9,#0284c7);" onclick="enterClass(<?= (int)$class['id'] ?>)">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?=htmlspecialchars($class['title'])?></h5>
                            <span class="badge" style="background:rgba(255,255,255,0.15);color:#fff;">Code: <?=htmlspecialchars($class['class_code'])?></span>
                        </div>
                        <p class="card-text small mb-1">Section: <?=htmlspecialchars($class['section'])?></p>
                        <div class="mt-auto text-end">
                            <small class="text-muted">Created: <?=date('M d, Y', strtotime($class['created_at']))?></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <p class="text-muted">No classes found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Class Modal (only for teachers) -->
<?php if($role === 'teacher'): ?>
<div class="modal fade" id="createClassModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create New Class</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Class Title</label>
            <input type="text" name="title" class="form-control" required maxlength="255">
        </div>
        <div class="mb-3">
            <label class="form-label">Section</label>
            <input type="text" name="section" class="form-control" required maxlength="255">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Create Class</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@0.259.0/dist/lucide.js"></script>
<script>
function enterClass(classId){
    <?php if($role === 'teacher'): ?>
        window.location.href = `teacher.php?class_id=${classId}`;
    <?php else: ?>
        window.location.href = `student_class.php?class_id=${classId}`;
    <?php endif; ?>
}
lucide.replace();
</script>
<script src="teacherscripts.js"></script>
</body>
</html>

<?php $mysqli->close(); ?>