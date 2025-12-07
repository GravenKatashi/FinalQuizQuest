<?php
session_start();

// simple access guard: must be logged in (teacher or student can view)
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view the leaderboard.");
}

// validate class_id param
if (!isset($_GET['class_id'])) {
    die("Missing class_id parameter.");
}
$class_id = (int)$_GET['class_id'];

// --- DB connection (self-contained; change credentials if needed) ---
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "quizmaker";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 1) fetch class info (title, section, class_code)
$stmt = $conn->prepare("SELECT title, section, class_code FROM classes WHERE id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$classRes = $stmt->get_result();
if ($classRes->num_rows === 0) {
    $stmt->close();
    $conn->close();
    die("Class not found.");
}
$class = $classRes->fetch_assoc();
$stmt->close();

$class_code = $class['class_code'];

// 2) query leaderboard: students in class, their EXP (student_exp) and SUM of quiz scores (student_quizzes via quizzes of this class)
$sql = "
SELECT 
    u.id AS student_id,
    COALESCE(u.full_name, u.username) AS name,
    COALESCE(se.exp, 0) AS exp,
    COALESCE(se.title, '') AS title,
    COALESCE(SUM(sq.score), 0) AS total_score
FROM student_classes sc
JOIN users u ON sc.student_id = u.id
LEFT JOIN student_exp se ON se.student_id = u.id AND se.class_code = sc.class_code
LEFT JOIN quizzes q ON q.class_code = sc.class_code
LEFT JOIN student_quizzes sq ON sq.student_id = u.id AND sq.quiz_id = q.id
WHERE sc.class_code = ?
GROUP BY u.id, name, exp, title
ORDER BY exp DESC, total_score DESC, name ASC
";

$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("s", $class_code);
$stmt2->execute();
$res = $stmt2->get_result();

// build array
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
</head>
<body>
<canvas id="background-canvas"></canvas>
<div class="container my-5">
    <div class="card shadow-lg leaderboard-card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h2 class="mb-0"><?php echo htmlspecialchars($class['title']); ?></h2>
                    <small class="text-muted">Section: <?php echo htmlspecialchars($class['section']); ?> • Code: <?php echo htmlspecialchars($class_code); ?></small>
                </div>
                <div>
                    <a href="leaderboard.php" class="btn btn-outline-secondary">← Back to Leaderboards</a>
                </div>
            </div>

            <?php if (count($leaderboard) === 0): ?>
                <div class="alert alert-info mb-0">No students enrolled in this class yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-borderless align-middle leaderboard-table">
                        <thead>
                            <tr>
                                <th style="width:90px">Rank</th>
                                <th>Student</th>
                                <th class="text-end" style="width:120px">EXP</th>
                                <th class="text-end" style="width:140px">Total Score</th>
                                <th style="width:140px">Title</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rank = 1;
                            foreach ($leaderboard as $i => $row):
                                // medal icons for top 3
                                $medal = '';
                                if ($i === 0) $medal = '<span class="medal gold">🥇</span>';
                                elseif ($i === 1) $medal = '<span class="medal silver">🥈</span>';
                                elseif ($i === 2) $medal = '<span class="medal bronze">🥉</span>';
                            ?>
                            <tr class="<?php echo ($i % 2 === 0) ? 'table-row-even' : ''; ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rank-circle me-2"><?php echo $rank++; ?></div>
                                        <?php echo $medal; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                </td>
                                <td class="text-end"><strong><?php echo (int)$row['exp']; ?></strong></td>
                                <td class="text-end"><?php echo (int)$row['total_score']; ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

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
