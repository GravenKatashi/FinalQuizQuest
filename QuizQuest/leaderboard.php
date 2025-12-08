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

$role = $_SESSION['role'];
$user_id = (int) $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

if ($role === "teacher") {
    $stmt = $mysqli->prepare("
        SELECT 
            c.id AS class_id,
            c.title AS class_title,
            c.section,
            c.class_code,
            c.created_at,
            COUNT(DISTINCT sq.student_id) AS stat_count
        FROM classes c
        LEFT JOIN quizzes q ON q.class_code = c.class_code
        LEFT JOIN student_quizzes sq ON sq.quiz_id = q.id
        WHERE c.teacher_id = ?
        GROUP BY c.id, c.title, c.section, c.class_code, c.created_at
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);

} else {
    $stmt = $mysqli->prepare("
        SELECT 
            c.id AS class_id,
            c.title AS class_title,
            c.section,
            c.class_code,
            c.created_at,
            COUNT(DISTINCT sq.id) AS stat_count
        FROM classes c
        LEFT JOIN student_classes sc ON UPPER(sc.class_code) = UPPER(c.class_code) AND sc.student_id = ?
        LEFT JOIN quizzes q ON q.class_code = c.class_code
        LEFT JOIN student_quizzes sq ON sq.quiz_id = q.id AND sq.student_id = ?
        WHERE sc.student_id IS NOT NULL OR sq.student_id IS NOT NULL
        GROUP BY c.id, c.title, c.section, c.class_code, c.created_at
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("ii", $user_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$classes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Leaderboard - QuizQuest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/teacher.css">
<link rel="stylesheet" href="leaderboard.css">
</head>
<body>

<canvas id="background-canvas"></canvas>

<!-- Sidebar -->
<div class="sidebar">
    <img src="assets/images/logo.png" class="logo-img" alt="QuizQuest">
    <?php
    $currentPage = basename($_SERVER['PHP_SELF']); // gets the current file name
    ?>
    <div class="menu-wrapper">
        <div class="nav">
            <a class="nav-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>" href="profile.php">
                <i data-lucide="user"></i> Profile (<?=htmlspecialchars($username)?>)
            </a>
            <a class="nav-item <?= $currentPage === 'student.php' ? 'active' : '' ?>" href="student.php">
                <i data-lucide="layout"></i> Classes
            </a>
            <a class="nav-item <?= $currentPage === 'leaderboard.php' ? 'active' : '' ?>" href="leaderboard.php">
                <i data-lucide="award"></i> Leaderboard
            </a>
        </div>
    </div>
    <a class="logout" href="logout.php">Logout</a>
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
                         onclick="openLeaderboard(<?= (int)$class['class_id'] ?>)">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($class['class_title']) ?></h5>
                                <span class="badge class-code-badge">Code: <?= htmlspecialchars($class['class_code']) ?></span>
                            </div>
                            <p class="card-text small mb-1">Section: <?= htmlspecialchars($class['section']) ?></p>
                            <p class="card-text small mb-2">
                                <?= $role === 'teacher' ? 'Students Participated' : 'Quizzes Taken' ?>: <?= (int)$class['stat_count'] ?>
                            </p>
                            <div class="mt-auto text-end">
                                <small class="text-muted">Created: <?= date('M d, Y', strtotime($class['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12"><p class="text-muted">No classes found.</p></div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openLeaderboard(classId) {
    window.location.href = `leaderboard_view.php?class_id=${classId}`;
}
</script>
<script src="teacherscripts.js"></script>
</body>
</html>