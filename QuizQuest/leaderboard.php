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
        GROUP BY c.id
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
            COUNT(sq.id) AS stat_count
        FROM student_classes sc
        JOIN classes c ON c.class_code = sc.class_code
        LEFT JOIN quizzes q ON q.class_code = c.class_code
        LEFT JOIN student_quizzes sq 
            ON sq.quiz_id = q.id AND sq.student_id = sc.student_id
        WHERE sc.student_id = ?
        GROUP BY c.id, c.title, c.section, c.class_code, c.created_at
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$classes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$mysqli->close();
?>