<?php
session_start();
var_dump($_SESSION); // see what’s actually stored
exit;
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "quizmaker");
if ($mysqli->connect_error) die("Connection failed: " . $mysqli->connect_error);

$role = $_SESSION['role'];
$user_id = (int)$_SESSION['user_id'];

// Fetch classes based on role
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

    // Students see classes they are enrolled in (match via class_code)
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
<title>Leaderboard - QuizQuest</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Base teacher/student style -->
<link rel="stylesheet" href="assets/css/teacher.css">

<!-- Leaderboard-specific CSS -->
<link rel="stylesheet" href="leaderboard.css">
</head>

<body>

<canvas id="background-canvas"></canvas>

<!-- Sidebar -->
<div class="sidebar">
    <img src="assets/images/logo.png" class="logo-img" alt="QuizQuest">
    <div class="menu-wrapper">
        <div class="nav">
            <a class="nav-item" href="profile.php"><i data-lucide="user"></i> Profile</a>
            <a class="nav-item" href="classes.php"><i data-lucide="layout"></i> Classes</a>
            <a class="nav-item active" href="leaderboard.php"><i data-lucide="award"></i> Leaderboard</a>
        </div>
    </div>
    <a class="logout" href="logout.php"><i data-lucide="log-out"></i> Logout</a>
</div>

<!-- Main Content -->
<div class="content">
    <div class="avatar-container">
        <span class="greeting">Leaderboard</span>
        <img src="https://i.imgur.com/oQEsWSV.png" class="freiren-avatar" alt="avatar">
    </div>

    <h2 class="quizzes-title mb-4">Select a Class</h2>

    <div class="row g-4">
        <?php if (!empty($classes)): ?>
            <?php foreach ($classes as $class): ?>
            <div class="col-md-4 col-sm-6">
                <div class="card subject-card h-100 leaderboard-card"
                     onclick="openLeaderboard(<?= (int)$class['id'] ?>)">
                     
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($class['title']) ?></h5>
                            <span class="badge class-code-badge">
                                Code: <?= htmlspecialchars($class['class_code']) ?>
                            </span>
                        </div>

                        <p class="card-text small mb-1">Section: <?= htmlspecialchars($class['section']) ?></p>

                        <div class="mt-auto text-end">
                            <small class="text-muted">
                                Created: <?= date('M d, Y', strtotime($class['created_at'])) ?>
                            </small>
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
function openLeaderboard(classId) {
    window.location.href = `leaderboard_view.php?class_id=${classId}`;
}
lucide.replace();
</script>

<!-- Background animation JS -->
<script src="teacherscripts.js"></script>
</body>
</html>

<?php $mysqli->close(); ?>