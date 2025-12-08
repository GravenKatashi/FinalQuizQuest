<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view the leaderboard.");
}

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

if (!isset($_GET['class_id'])) {
    die("Missing class_id parameter.");
}
$class_id = (int)$_GET['class_id'];

$conn = new mysqli("localhost", "root", "", "quizmaker");
if ($conn->connect_error) die("DB connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// Get class info
$stmt = $conn->prepare("SELECT title, section, class_code FROM classes WHERE id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$classRes = $stmt->get_result();

if (!$classRes || $classRes->num_rows === 0) {
    die("Class not found.");
}

$class = $classRes->fetch_assoc();
$class_code = $class['class_code'];
$stmt->close();

// Fetch leaderboard sorted by EXP descending
$sql = "
SELECT 
    u.id AS student_id,
    COALESCE(u.full_name, u.username) AS name,
    COALESCE(se.exp,0) AS exp,
    COALESCE(se.title,'') AS title,
    COALESCE(SUM(sq.score),0) AS score
FROM student_classes sc
JOIN users u ON u.id = sc.student_id
LEFT JOIN student_exp se ON se.student_id = u.id AND UPPER(se.class_code) = UPPER(sc.class_code)
LEFT JOIN student_quizzes sq ON sq.student_id = u.id
LEFT JOIN quizzes q ON q.id = sq.quiz_id
WHERE UPPER(sc.class_code) = UPPER(?)  -- keep only this filter
GROUP BY u.id
ORDER BY exp DESC
";

$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("s", $class_code);
$stmt2->execute();
$res = $stmt2->get_result();

$leaderboard = [];
while ($r = $res->fetch_assoc()) {
    $leaderboard[] = $r;
}

$stmt2->close();
$conn->close();
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo htmlspecialchars($class['title']); ?> — Leaderboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/leaderboard_view.css">
<link rel="stylesheet" href="assets/css/teacher.css">
</head>
<body>

<canvas id="background-canvas"></canvas>

<div class="sidebar">
    <img src="assets/images/logo.png" class="logo-img" alt="QuizQuest">
    <div class="menu-wrapper">
        <div class="nav">
            <a class="nav-item" href="profile.php">Profile</a>
            <a class="nav-item active" href="leaderboard.php">Leaderboard</a>
        </div>
    </div>
    <a class="logout" href="logout.php">Logout</a>
</div>

<div class="content">
    <div class="container-fluid my-5 px-4">
        <div class="card shadow-lg leaderboard-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="mb-0"><?php echo htmlspecialchars($class['title']); ?></h2>
                        <small class="text-muted">
                            Section: <?php echo htmlspecialchars($class['section']); ?> • 
                            Code: <?php echo htmlspecialchars($class_code); ?>
                        </small>
                    </div>
                    <div>
                        <a href="leaderboard.php" class="btn btn-outline-secondary">
                            ← Back to Leaderboards
                        </a>
                    </div>
                </div>

                <?php if (empty($leaderboard)): ?>
                    <div class="alert alert-info mb-0">
                        No quiz results yet.
                    </div>
                <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-borderless align-middle leaderboard-table">

                        <thead>
                            <tr>
                                <th style="width: 80px">#</th>
                                <th>Student</th>
                                <th class="text-end" style="width: 120px">Score</th>
                                <th class="text-end" style="width: 120px">EXP</th>
                                <th style="width: 160px">Title</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php $rank = 1; ?>
                            <?php foreach ($leaderboard as $i => $row): ?>
                                <?php
                                $medal = '';
                                if ($i === 0) $medal = '<span class="medal gold">🥇</span>';
                                elseif ($i === 1) $medal = '<span class="medal silver">🥈</span>';
                                elseif ($i === 2) $medal = '<span class="medal bronze">🥉</span>';
                                ?>
                                <tr class="<?php echo ($i % 2 === 0) ? 'table-row-even' : ''; ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rank-circle me-2">
                                                <?php echo $rank++; ?>
                                            </div>
                                            <?php echo $medal; ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td class="text-end"><?php echo (int)$row['score']; ?></td>
                                    <td class="text-end"><strong><?php echo (int)$row['exp']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['title'] ?: '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="teacherscripts.js"></script>

</body>
</html>
