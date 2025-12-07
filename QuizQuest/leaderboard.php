<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "quizmaker");
if ($mysqli->connect_error) die("Connection failed: " . $mysqli->connect_error);

$role = $_SESSION['role'];
$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Student';

if ($role === "teacher") {
    // Teachers see only the classes they created
    $stmt = $mysqli->prepare("
        SELECT id, title, section, class_code, created_at 
        FROM classes 
        WHERE teacher_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
} else if ($role === "student") {
    // Students see classes they are enrolled in
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Classes - QuizQuest</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/teacher.css">
</head>
<body>

<canvas id="background-canvas"></canvas>

<!-- Sidebar -->
<div class="sidebar">
    <img src="assets/images/logo.png" class="logo-img" alt="QuizQuest">
    <div class="menu-wrapper">
        <div class="nav">
            <a class="nav-item" href="profile.php"><i data-lucide="user"></i> Profile</a>
            <a class="nav-item active" href="classes.php"><i data-lucide="layout"></i> Classes</a>
            <a class="nav-item" href="leaderboard.php"><i data-lucide="award"></i> Leaderboard</a>
        </div>
    </div>
    <a class="logout" href="logout.php"><i data-lucide="log-out"></i> Logout</a>
</div>

<!-- Main content -->
<div class="content container mt-4">
    <div class="avatar-container d-flex align-items-center gap-3 mb-4">
        <span class="greeting h5 mb-0">Hello, <?= htmlspecialchars($username) ?>!</span>
        <img src="https://i.imgur.com/oQEsWSV.png" alt="Avatar" class="freiren-avatar rounded-circle" width="50" height="50">
    </div>

    <h2 class="mb-3">Your Classes</h2>
    <div class="row g-4">
        <?php if (!empty($classes)): ?>
            <?php foreach ($classes as $class): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="card subject-card h-100" onclick="openClass(<?= (int)$class['id'] ?>)">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($class['title']) ?></h5>
                                <span class="badge bg-primary">Code: <?= htmlspecialchars($class['class_code']) ?></span>
                            </div>
                            <p class="card-text small mb-1">Section: <?= htmlspecialchars($class['section']) ?></p>
                            <div class="mt-auto text-end">
                                <small class="text-muted">Created: <?= date('M d, Y', strtotime($class['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <p class="text-muted">You are not enrolled in any classes yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@0.259.0/dist/lucide.js"></script>
<script>
function openClass(classId) {
    window.location.href = `student_class.php?class_id=${classId}`;
}
lucide.replace();
</script>
<script src="teacherscripts.js"></script>
</body>
</html>

<?php $mysqli->close(); ?>
